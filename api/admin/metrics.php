<?php
/**
 * Endpoint: GET /api/admin/metrics
 * Role: Returns KPI analytics for the Admin Dashboard
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Aggregate metrics from the database
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$freelancers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'FREELANCER'")->fetchColumn();
$clients      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'CLIENT'")->fetchColumn();
$activeJobs   = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Open' OR status = 'In Progress'")->fetchColumn();
$completedJobs= $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Completed'")->fetchColumn();
$totalProposals = $pdo->query("SELECT COUNT(*) FROM proposals")->fetchColumn();
$suspended    = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Suspended'")->fetchColumn();
$verifiedCount= $pdo->query("SELECT COUNT(*) FROM test_results WHERE passed = 1")->fetchColumn();
$totalEarnings= $pdo->query("SELECT SUM(earnings) FROM users WHERE role = 'FREELANCER'")->fetchColumn();

sendResponse([
    'totalUsers'      => (int)$totalUsers,
    'freelancers'     => (int)$freelancers,
    'clients'         => (int)$clients,
    'activeJobs'      => (int)$activeJobs,
    'completedJobs'   => (int)$completedJobs,
    'totalProposals'  => (int)$totalProposals,
    'suspendedUsers'  => (int)$suspended,
    'verifiedFreelancers' => (int)$verifiedCount,
    'totalEarnings'   => (float)($totalEarnings ?? 0),
    'platformRevenue' => round((float)($totalEarnings ?? 0) * 0.10, 2),
    'successRate'     => $activeJobs > 0 ? round(($completedJobs / ($activeJobs + $completedJobs)) * 100) : 0
]);
