<?php
/**
 * Endpoint: POST /api/jobs/{id}/proposals
 * Submits a new proposal for a specific job (authenticated freelancer only)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract job ID from URL: /api/jobs/{id}/proposals
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/jobs\/([a-zA-Z0-9_-]+)\/proposals/', $uri, $matches);
$jobId = $matches[1] ?? '';

if (empty($jobId)) {
    sendResponse(['message' => 'Job ID is required'], 400);
}

try {
    // Resolve freelancer identity from the session token
    $freelancerId = requireAuth($pdo)['id'];

    // Verify job exists and is open
    $jobCheck = $pdo->prepare("SELECT id, title, company, status FROM jobs WHERE id = ? LIMIT 1");
    $jobCheck->execute([$jobId]);
    $job = $jobCheck->fetch();

    if (!$job) {
        sendResponse(['message' => 'Job not found'], 404);
    }

    if ($job['status'] !== 'Open') {
        sendResponse(['message' => 'This job is no longer accepting proposals'], 409);
    }

    // Prevent duplicate proposals
    $dupCheck = $pdo->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ? LIMIT 1");
    $dupCheck->execute([$jobId, $freelancerId]);
    if ($dupCheck->fetch()) {
        sendResponse(['message' => 'You have already submitted a proposal for this job'], 409);
    }

    $body          = getRequestBody();
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
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to submit proposal: ' . $e->getMessage()], 500);
}
