<?php
/**
 * Endpoint: GET /api/clients/me/dashboard
 * Role: Returns client KPI metrics from the database
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$clientId = 'c-201'; // demo: extend with token auth

// Get client user record
$user = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'CLIENT' LIMIT 1");
$user->execute([$clientId]);
$client = $user->fetch();

if (!$client) {
    sendResponse(['message' => 'Client not found'], 404);
}

// Active projects (open or in progress)
$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status IN ('Open', 'In Progress')");
$activeStmt->execute([$clientId]);
$activeProjects = (int)$activeStmt->fetchColumn();

// Total proposals received across client's jobs
$propStmt = $pdo->prepare("
    SELECT COUNT(*) FROM proposals p
    INNER JOIN jobs j ON j.id = p.job_id
    WHERE j.client_id = ?
");
$propStmt->execute([$clientId]);
$proposalsReceived = (int)$propStmt->fetchColumn();

// Unique freelancers with accepted proposals
$hiredStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.freelancer_id) FROM proposals p
    INNER JOIN jobs j ON j.id = p.job_id
    WHERE j.client_id = ? AND p.status IN ('Accepted', 'Active')
");
$hiredStmt->execute([$clientId]);
$freelancersHired = (int)$hiredStmt->fetchColumn();

// Total spent = sum of completed job budgets (approximate)
$spentStmt = $pdo->prepare("
    SELECT SUM(CAST(j.budget AS DECIMAL(12,2))) FROM jobs j
    WHERE j.client_id = ? AND j.status = 'Completed'
");
$spentStmt->execute([$clientId]);
$totalSpentRaw = (float)($spentStmt->fetchColumn() ?? 0);
$totalSpent = '$' . number_format($totalSpentRaw, 0);

sendResponse([
    'clientName'        => $client['name'],
    'activeProjects'    => $activeProjects,
    'proposalsReceived' => $proposalsReceived,
    'freelancersHired'  => $freelancersHired,
    'totalSpent'        => $totalSpent
]);
