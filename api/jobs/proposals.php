<?php
/**
 * Endpoint: POST /api/jobs/{id}/proposals
 * Role: Submits a new proposal for a job
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

// Verify job exists
$jobCheck = $pdo->prepare("SELECT id, title, company FROM jobs WHERE id = ? LIMIT 1");
$jobCheck->execute([$jobId]);
$job = $jobCheck->fetch();

if (!$job) {
    sendResponse(['message' => 'Job not found'], 404);
}

$body          = getRequestBody();
$freelancerId  = 'f-101'; // demo: extend with token auth
$proposedRate  = (float)($body['proposedRate'] ?? 0);
$estimatedDays = (int)($body['estimatedDays'] ?? 14);
$coverLetter   = trim($body['coverLetter'] ?? '');

if ($proposedRate <= 0) {
    sendResponse(['message' => 'A valid proposed rate is required'], 400);
}

if (empty($coverLetter)) {
    sendResponse(['message' => 'Cover letter is required'], 400);
}

$newId = 'prop-' . time();

$stmt = $pdo->prepare("
    INSERT INTO proposals (id, job_id, freelancer_id, proposed_rate, estimated_days, cover_letter, status)
    VALUES (?, ?, ?, ?, ?, ?, 'Submitted')
");
$stmt->execute([$newId, $jobId, $freelancerId, $proposedRate, $estimatedDays, $coverLetter]);

sendResponse([
    'id'            => $newId,
    'jobId'         => $jobId,
    'jobTitle'      => $job['title'],
    'clientName'    => $job['company'],
    'proposedRate'  => $proposedRate,
    'estimatedDays' => $estimatedDays,
    'status'        => 'Submitted',
    'submittedAt'   => date('c'),
    'message'       => 'Proposal submitted successfully'
], 201);
