<?php
/**
 * Endpoint: POST /api/projects/{milestoneId}/approve
 * Role: CLIENT — the client who owns the project.
 *
 * Body: { note?: string }
 *
 * Approving a submitted milestone releases its escrowed funds: the milestone
 * becomes Paid, its payment row flips to Released with a receipt number, the
 * freelancer's earnings go up, and the project moves to Completed once every
 * milestone is paid.
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);

    $milestoneId = routeParam('id');
    if ($milestoneId === '') {
        sendResponse(['message' => 'Milestone ID is required'], 400);
    }

    $body = getRequestBody();
    $note = trim((string)($body['note'] ?? ''));
    if (mb_strlen($note) > 500) {
        sendResponse(['message' => 'Field note must be 500 characters or fewer'], 400);
    }

    $stmt = $pdo->prepare("SELECT m.*, j.title AS job_title, j.status AS job_status
                            FROM project_milestones m
                            INNER JOIN jobs j ON j.id = m.job_id
                            WHERE m.id = ? AND m.client_id = ?
                            LIMIT 1");
    $stmt->execute([$milestoneId, $client['id']]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        sendResponse(['message' => 'Milestone not found for your account'], 404);
    }
    if ($milestone['status'] === 'Paid') {
        sendResponse(['message' => 'This milestone has already been paid'], 409);
    }
    if ($milestone['status'] === 'Disputed') {
        sendResponse(['message' => 'This milestone is locked by an open dispute'], 409);
    }
    if ($milestone['status'] !== 'Submitted') {
        sendResponse([
            'message' => 'Only a submitted milestone can be approved (this one is ' . strtolower($milestone['status']) . ')',
        ], 409);
    }
    if (empty($milestone['freelancer_id'])) {
        sendResponse(['message' => 'No freelancer is assigned to this milestone'], 409);
    }

    $amount    = (float)$milestone['amount'];
    $escrowFee = (float)$milestone['escrow_fee'];
    $freelancerId = $milestone['freelancer_id'];

    $pdo->beginTransaction();
    try {
        // 1. Flip the milestone to Paid.
        $upd = $pdo->prepare("UPDATE project_milestones
                              SET status = 'Paid', approved_at = NOW(), paid_at = NOW()
                              WHERE id = ? AND status = 'Submitted'");
        $upd->execute([$milestoneId]);
        if ($upd->rowCount() === 0) {
            $pdo->rollBack();
            sendResponse(['message' => 'This milestone was changed by someone else — reload and try again'], 409);
        }

        // 2. Release the matching escrow row.
        $receipt = 'RCP-' . strtoupper(bin2hex(random_bytes(4)));
        $pay = $pdo->prepare("SELECT id FROM payments
                              WHERE milestone_id = ? AND status = 'Held in escrow'
                              ORDER BY created_at DESC LIMIT 1");
        $pay->execute([$milestoneId]);
        $paymentId = $pay->fetchColumn();

        if ($paymentId) {
            $pdo->prepare("UPDATE payments
                           SET status = 'Released', receipt_id = ?, released_at = NOW()
                           WHERE id = ?")
                ->execute([$receipt, $paymentId]);
        } else {
            // No escrow row (e.g. seeded data) — record the payout so billing adds up.
            $newPaymentId = newId('pay');
            $pdo->prepare("INSERT INTO payments
                             (id, milestone_id, job_id, client_id, freelancer_id, amount, escrow_fee, total, status, receipt_id, released_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Released', ?, NOW())")
                ->execute([
                    $newPaymentId, $milestoneId, $milestone['job_id'], $client['id'], $freelancerId,
                    $amount, $escrowFee, round($amount + $escrowFee, 2), $receipt,
                ]);
        }

        // 3. Credit the freelancer.
        $pdo->prepare("UPDATE users SET earnings = earnings + ?, completed_jobs = completed_jobs + 1 WHERE id = ?")
            ->execute([$amount, $freelancerId]);

        // 4. Record the hire and settle the project when nothing is outstanding.
        $pdo->prepare("UPDATE jobs SET hired_freelancer_id = COALESCE(hired_freelancer_id, ?) WHERE id = ?")
            ->execute([$freelancerId, $milestone['job_id']]);

        $remaining = $pdo->prepare("SELECT COUNT(*) FROM project_milestones
                                    WHERE job_id = ? AND status <> 'Paid'");
        $remaining->execute([$milestone['job_id']]);
        $openCount = (int)$remaining->fetchColumn();
        $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?")
            ->execute([$openCount === 0 ? 'Completed' : 'In Progress', $milestone['job_id']]);

        // 5. Tell the freelancer.
        $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link)
                       VALUES (?, 'payment', 'Milestone approved and paid', ?, ?)")
            ->execute([
                $freelancerId,
                $client['name'] . ' approved "' . $milestone['label'] . '" on "' . $milestone['job_title'] . '". '
                . '$' . number_format($amount, 2) . ' has been released to you.'
                . ($note !== '' ? ' Note: ' . $note : ''),
                '../freelancer/freelancer_projects.html',
            ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse(['message' => 'Could not release the payment: ' . $e->getMessage()], 500);
    }

    sendResponse([
        'message'     => 'Milestone approved — funds released.',
        'milestoneId' => $milestoneId,
        'status'      => 'Paid',
        'amount'      => $amount,
        'escrowFee'   => $escrowFee,
        'total'       => round($amount + $escrowFee, 2),
        'receiptId'   => $receipt,
        'freelancerId' => $freelancerId,
        'projectStatus' => $openCount === 0 ? 'Completed' : 'In Progress',
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Approval error: ' . $e->getMessage()], 500);
}
