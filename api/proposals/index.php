<?php
/**
 * Endpoint: GET /api/freelancers/me/proposals
 * Role: Returns proposals submitted by the logged-in freelancer
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$freelancerId = 'f-101'; // demo account; extend with token-based auth
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

$sql = "
    SELECT p.*, j.title as job_title, j.company as client_name
    FROM proposals p
    LEFT JOIN jobs j ON j.id = p.job_id
    WHERE p.freelancer_id = ?
";
$params = [$freelancerId];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND LOWER(p.status) = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$proposals = [];
foreach ($rows as $r) {
    $proposals[] = [
        'id'           => $r['id'],
        'jobId'        => $r['job_id'],
        'jobTitle'     => $r['job_title'] ?? 'Project',
        'clientName'   => $r['client_name'] ?? 'Client',
        'proposedRate' => (float)$r['proposed_rate'],
        'estimatedDays'=> (int)$r['estimated_days'],
        'coverLetter'  => $r['cover_letter'] ?? '',
        'status'       => $r['status'],
        'submittedAt'  => $r['submitted_at']
    ];
}

sendResponse($proposals);
