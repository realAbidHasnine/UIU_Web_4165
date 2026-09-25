<?php
/**
 * Endpoint: GET /api/clients/me/projects
 * Role: Returns projects posted by the logged-in client
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$clientId     = 'c-201'; // demo: extend with token auth
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

$sql    = "SELECT j.*, (SELECT COUNT(*) FROM proposals p WHERE p.job_id = j.id) AS proposals_count FROM jobs j WHERE j.client_id = ?";
$params = [$clientId];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND LOWER(j.status) = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$projects = [];
foreach ($rows as $r) {
    $projects[] = [
        'id'             => $r['id'],
        'title'          => $r['title'],
        'status'         => $r['status'],
        'budgetType'     => $r['budget_type'] === 'fixed' ? 'Fixed Price' : 'Hourly',
        'budget'         => $r['budget_display'],
        'proposalsCount' => (int)$r['proposals_count'],
        'freelancer'     => null, // would require proposals join for accepted freelancer
        'postedDate'     => date('M j, Y', strtotime($r['created_at'])),
        'dueDate'        => $r['status'] === 'Open' ? 'Reviewing candidates' : 'In progress'
    ];
}

sendResponse($projects);
