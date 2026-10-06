<?php
/**
 * Endpoint: GET|POST /api/jobs/{id}/proposals
 * GET  — Returns proposals submitted for a job with full freelancer profile metrics
 * POST — Submits a proposal (freelancer) OR accepts/rejects a proposal (client)
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract job ID from URL: /api/jobs/{id}/proposals
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/jobs\/([a-zA-Z0-9_-]+)\/proposals/', $uri, $matches);
$jobId = routeParam('id') ?: ($matches[1] ?? ($_GET['jobId'] ?? ($_GET['job'] ?? ($_GET['id'] ?? ''))));

if (empty($jobId)) {
    sendResponse(['message' => 'Job ID is required'], 400);
}

try {
    $caller = requireAuth($pdo);

    // Verify job exists
    $jobCheck = $pdo->prepare("SELECT * FROM jobs WHERE id = ? LIMIT 1");
    $jobCheck->execute([$jobId]);
    $job = $jobCheck->fetch();

    if (!$job) {
        sendResponse(['message' => 'Job not found'], 404);
    }

    if ($method === 'GET') {
        $sort = strtolower(trim($_GET['sort'] ?? 'score'));

        $orderBy = "u.score DESC, p.submitted_at DESC";
        if ($sort === 'rating') {
            $orderBy = "u.rating DESC, u.score DESC";
        } elseif ($sort === 'rate_asc') {
            $orderBy = "p.proposed_rate ASC";
        } elseif ($sort === 'rate_desc') {
            $orderBy = "p.proposed_rate DESC";
        } elseif ($sort === 'newest') {
            $orderBy = "p.submitted_at DESC";
        }

        $stmt = $pdo->prepare("
            SELECT p.*,
                   u.name AS freelancer_name,
                   u.title AS freelancer_title,
                   u.hourly_rate AS freelancer_hourly_rate,
                   u.rating AS freelancer_rating,
                   u.score AS freelancer_score,
                   u.bio AS freelancer_bio,
                   u.location AS freelancer_location,
                   u.completed_jobs AS freelancer_completed_jobs
            FROM proposals p
            INNER JOIN users u ON u.id = p.freelancer_id
            WHERE p.job_id = ?
            ORDER BY {$orderBy}
        ");
        $stmt->execute([$jobId]);
        $rows = $stmt->fetchAll();

        $proposals = [];
        foreach ($rows as $r) {
            $proposals[] = [
                'id'            => $r['id'],
                'jobId'         => $r['job_id'],
                'freelancerId'  => $r['freelancer_id'],
                'freelancerName'=> $r['freelancer_name'],
                'freelancerTitle'=> $r['freelancer_title'] ?? 'Verified Specialist',
                'freelancerRating'=> (float)($r['freelancer_rating'] ?? 5.0),
                'freelancerScore' => (int)($r['freelancer_score'] ?? 90),
                'freelancerHourlyRate' => (float)($r['freelancer_hourly_rate'] ?? 0),
                'freelancerBio' => $r['freelancer_bio'] ?? '',
                'freelancerLocation' => $r['freelancer_location'] ?? 'Remote',
                'proposedRate'  => (float)$r['proposed_rate'],
                'estimatedDays' => (int)$r['estimated_days'],
                'coverLetter'   => $r['cover_letter'] ?? '',
                'status'        => $r['status'],
                'submittedAt'   => $r['submitted_at']
            ];
        }

        sendResponse([
            'job' => [
                'id'          => $job['id'],
                'title'       => $job['title'],
                'category'    => $job['category'],
                'status'      => $job['status'],
                'budget'      => $job['budget_label'] ?? $job['budget'] ?? '$' . $job['budget_min'],
                'description' => $job['description'] ?? '',
                'company'     => $job['company'] ?? '',
                'clientId'    => $job['client_id']
            ],
            'count'     => count($proposals),
            'proposals' => $proposals
        ], 200);

    } elseif ($method === 'POST') {
        $body = getRequestBody();

        // Check if this is an "accept" action from client
        if (($body['action'] ?? '') === 'accept' || isset($_GET['accept'])) {
            $proposalId = trim((string)($body['proposalId'] ?? ($body['proposal_id'] ?? '')));
            if ($proposalId === '') {
                sendResponse(['message' => 'Proposal ID is required to accept'], 400);
            }

            // Find proposal
            $pStmt = $pdo->prepare("SELECT p.*, u.name AS freelancer_name FROM proposals p JOIN users u ON u.id = p.freelancer_id WHERE p.id = ? AND p.job_id = ? LIMIT 1");
            $pStmt->execute([$proposalId, $jobId]);
            $prop = $pStmt->fetch();

            if (!$prop) {
                sendResponse(['message' => 'Proposal not found for this project'], 404);
            }

            $pdo->beginTransaction();
            try {
                // 1. Mark accepted proposal
                $pdo->prepare("UPDATE proposals SET status = 'Accepted' WHERE id = ?")->execute([$proposalId]);

                // 2. Archive other proposals for this job
                $pdo->prepare("UPDATE proposals SET status = 'Archived' WHERE job_id = ? AND id <> ?")->execute([$jobId, $proposalId]);

                // 3. Mark job In Progress with hired freelancer
                $pdo->prepare("UPDATE jobs SET status = 'In Progress', hired_freelancer_id = ? WHERE id = ?")
                    ->execute([$prop['freelancer_id'], $jobId]);

                // 4. Ensure milestone exists and is funded in escrow
                $msStmt = $pdo->prepare("SELECT id FROM project_milestones WHERE job_id = ? LIMIT 1");
                $msStmt->execute([$jobId]);
                $msId = $msStmt->fetchColumn();

                if (!$msId) {
                    $msId = 'ms-' . substr(md5(uniqid($jobId, true)), 0, 8);
                    $fee = round(((float)$prop['proposed_rate']) * 0.05, 2);
                    $pdo->prepare("
                        INSERT INTO project_milestones (id, job_id, client_id, freelancer_id, label, description, amount, escrow_fee, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Posted')
                    ")->execute([
                        $msId,
                        $jobId,
                        $job['client_id'],
                        $prop['freelancer_id'],
                        'Milestone 1: Project Deliverables',
                        'Initial milestone funded into escrow upon proposal acceptance.',
                        $prop['proposed_rate'],
                        $fee
                    ]);

                    // Record escrow payment
                    $payId = 'pay-' . substr(md5(uniqid($msId, true)), 0, 8);
                    $pdo->prepare("
                        INSERT INTO payments (id, milestone_id, job_id, client_id, amount, status)
                        VALUES (?, ?, ?, ?, ?, 'Held in escrow')
                    ")->execute([
                        $payId,
                        $msId,
                        $jobId,
                        $job['client_id'],
                        $prop['proposed_rate']
                    ]);
                } else {
                    $pdo->prepare("UPDATE project_milestones SET freelancer_id = ?, status = 'Posted' WHERE id = ?")
                        ->execute([$prop['freelancer_id'], $msId]);
                }

                // 5. Notify freelancer
                $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, body, link)
                    VALUES (?, 'proposal', 'Proposal Accepted!', ?, ?)
                ")->execute([
                    $prop['freelancer_id'],
                    'Congratulations! Your proposal for "' . $job['title'] . '" was accepted.',
                    '../freelancer/upload_comp_work.html?jobId=' . rawurlencode($jobId)
                ]);

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }

            sendResponse([
                'success'     => true,
                'message'     => "Proposal accepted successfully! Contract is now In Progress and escrow is funded.",
                'proposalId'  => $proposalId,
                'jobId'       => $jobId,
                'freelancer'  => $prop['freelancer_name'],
                'status'      => 'Accepted',
                'workApprovalUrl' => 'work-approval.php?job=' . rawurlencode($jobId)
            ], 200);
        }

        // Freelancer submitting new proposal
        $freelancerId = $caller['id'];
        if ($job['status'] !== 'Open') {
            sendResponse(['message' => 'This job is no longer accepting proposals'], 409);
        }

        $dupCheck = $pdo->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ? LIMIT 1");
        $dupCheck->execute([$jobId, $freelancerId]);
        if ($dupCheck->fetch()) {
            sendResponse(['message' => 'You have already submitted a proposal for this job'], 409);
        }

        $proposedRate  = (float)($body['proposedRate'] ?? 0);
        $estimatedDays = (int)($body['estimatedDays'] ?? 14);
        $coverLetter   = trim($body['coverLetter'] ?? '');

        if ($proposedRate <= 0) {
            sendResponse(['message' => 'A valid proposed rate is required'], 400);
        }
        if ($estimatedDays <= 0 || $estimatedDays > 365) {
            sendResponse(['message' => 'Estimated days must be between 1 and 365'], 400);
        }
        if (strlen($coverLetter) < 20) {
            sendResponse(['message' => 'Cover letter must be at least 20 characters'], 400);
        }

        $newId = 'prop-' . substr(md5(uniqid($jobId . $freelancerId, true)), 0, 8);

        $stmt = $pdo->prepare("
            INSERT INTO proposals (id, job_id, freelancer_id, proposed_rate, estimated_days, cover_letter, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Submitted')
        ");
        $stmt->execute([$newId, $jobId, $freelancerId, $proposedRate, $estimatedDays, $coverLetter]);

        sendResponse([
            'id'           => $newId,
            'jobId'        => $jobId,
            'jobTitle'     => $job['title'],
            'clientName'   => $job['company'],
            'proposedRate' => $proposedRate,
            'estimatedDays'=> $estimatedDays,
            'status'       => 'Submitted',
            'submittedAt'  => date('c'),
            'message'      => 'Proposal submitted successfully'
        ], 201);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Proposal error: ' . $e->getMessage()], 500);
}
