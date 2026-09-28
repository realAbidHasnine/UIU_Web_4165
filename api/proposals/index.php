<?php
/**
 * Endpoint: GET /api/freelancers/me/proposals
 * Returns proposals submitted by the authenticated freelancer, with optional status filter
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $freelancerId = requireAuth($pdo)['id'];

    $statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

    $sql = "
        SELECT p.*, j.title AS job_title, j.company AS client_name, j.status AS job_status
        FROM proposals p
        LEFT JOIN jobs j ON j.id = p.job_id
        WHERE p.freelancer_id = ?
    ";
    $params = [$freelancerId];

    if ($statusFilter !== 'all' && !empty($statusFilter)) {
        $sql .= " AND LOWER(p.status) = ?";
        $params[] = strtolower($statusFilter);
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
            'jobTitle'     => $r['job_title'] ?? 'Untitled Project',
            'clientName'   => $r['client_name'] ?? 'Client',
            'proposedRate' => (float)$r['proposed_rate'],
            'estimatedDays'=> (int)$r['estimated_days'],
            'coverLetter'  => $r['cover_letter'] ?? '',
            'status'       => $r['status'],
            'jobStatus'    => $r['job_status'] ?? 'Open',
            'submittedAt'  => $r['submitted_at']
        ];
    }

    sendResponse($proposals);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to fetch proposals: ' . $e->getMessage()], 500);
}
