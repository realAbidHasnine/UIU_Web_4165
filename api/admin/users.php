<?php
/**
 * Endpoint: GET /api/admin/users
 * Role: Returns the user list with search/filter for admin panel
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$search = trim($_GET['search'] ?? '');
$role   = strtoupper(trim($_GET['role'] ?? ''));
$status = trim($_GET['status'] ?? '');

$sql    = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role) && in_array($role, ['FREELANCER', 'CLIENT', 'ADMIN'])) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if (!empty($status) && in_array($status, ['Active', 'Suspended', 'Flagged'])) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$users = [];
foreach ($rows as $r) {
    $users[] = [
        'id'           => $r['id'],
        'name'         => $r['name'],
        'email'        => $r['email'],
        'role'         => $r['role'],
        'status'       => $r['status'],
        'score'        => (int)($r['score'] ?? 90),
        'rating'       => (float)($r['rating'] ?? 5.0),
        'completedJobs'=> (int)($r['completed_jobs'] ?? 0),
        'earnings'     => (float)($r['earnings'] ?? 0),
        'joinedAt'     => $r['created_at']
    ];
}

sendResponse($users);
