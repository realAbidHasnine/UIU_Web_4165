<?php
/**
 * Endpoint: GET /api/clients/me/dashboard
 * Returns real KPI metrics for the authenticated client
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);
    $clientId = $client['id'];

    // Active projects count
    $activeStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status IN ('Open', 'In Progress')");
    $activeStmt->execute([$clientId]);
    $activeProjects = (int)$activeStmt->fetchColumn();

    // Total proposals received
    $propStmt = $pdo->prepare("
        SELECT COUNT(*) FROM proposals p
        INNER JOIN jobs j ON j.id = p.job_id
        WHERE j.client_id = ?
    ");
    $propStmt->execute([$clientId]);
    $proposalsReceived = (int)$propStmt->fetchColumn();

    // Freelancers hired
    $hiredStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.freelancer_id) FROM proposals p
        INNER JOIN jobs j ON j.id = p.job_id
        WHERE j.client_id = ? AND p.status IN ('Accepted', 'Active')
    ");
    $hiredStmt->execute([$clientId]);
    $freelancersHired = (int)$hiredStmt->fetchColumn();

    // Total spent on completed jobs
    $spentStmt = $pdo->prepare("
        SELECT COALESCE(SUM(CAST(j.budget AS DECIMAL(12,2))), 0)
        FROM jobs j WHERE j.client_id = ? AND j.status = 'Completed'
    ");
    $spentStmt->execute([$clientId]);
    $totalSpentRaw = (float)$spentStmt->fetchColumn();
    $totalSpent    = '$' . number_format($totalSpentRaw, 0);

    // Recent proposals (last 3)
    $recentProps = $pdo->prepare("
        SELECT p.*, j.title AS job_title, u.name AS freelancer_name, u.score AS freelancer_score
        FROM proposals p
        INNER JOIN jobs j ON j.id = p.job_id
        INNER JOIN users u ON u.id = p.freelancer_id
        WHERE j.client_id = ?
        ORDER BY p.submitted_at DESC
        LIMIT 3
    ");
    $recentProps->execute([$clientId]);
    $recentProposals = $recentProps->fetchAll();

    $recentPropList = [];
    foreach ($recentProposals as $rp) {
        $recentPropList[] = [
            'id'            => $rp['id'],
            'jobTitle'      => $rp['job_title'],
            'freelancerName'=> $rp['freelancer_name'],
            'score'         => (int)$rp['freelancer_score'],
            'proposedRate'  => (float)$rp['proposed_rate'],
            'status'        => $rp['status']
        ];
    }

    sendResponse([
        'clientName'        => $client['name'],
        'company'           => $client['company'] ?? '',
        'activeProjects'    => $activeProjects,
        'proposalsReceived' => $proposalsReceived,
        'freelancersHired'  => $freelancersHired,
        'totalSpent'        => $totalSpent,
        'recentProposals'   => $recentPropList
    ]);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to load dashboard: ' . $e->getMessage()], 500);
}
