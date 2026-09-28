<?php
/**
 * Endpoint: GET /api/clients/me/projects
 * Returns projects posted by the authenticated client with proposal counts
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $clientId     = requireAuth($pdo)['id'];
    $statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

    $sql = "
        SELECT j.*,
            (SELECT COUNT(*) FROM proposals p WHERE p.job_id = j.id) AS proposals_count,
            (SELECT u.name FROM proposals p INNER JOIN users u ON u.id = p.freelancer_id
             WHERE p.job_id = j.id AND p.status IN ('Accepted', 'Active') LIMIT 1) AS hired_freelancer
        FROM jobs j
        WHERE j.client_id = ?
    ";
    $params = [$clientId];

    if ($statusFilter !== 'all' && !empty($statusFilter)) {
        $sql    .= " AND LOWER(j.status) = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY j.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $projects = [];
    foreach ($rows as $r) {
        $postedDate = !empty($r['created_at'])
            ? date('M j, Y', strtotime($r['created_at']))
            : 'Recently';

        $dueDate = match($r['status']) {
            'Open'      => 'Reviewing candidates',
            'In Progress' => 'In progress',
            'Completed' => 'Completed',
            default     => 'Pending'
        };

        $projects[] = [
            'id'             => $r['id'],
            'title'          => $r['title'],
            'status'         => $r['status'],
            'category'       => $r['category'],
            'budgetType'     => $r['budget_type'] === 'fixed' ? 'Fixed Price' : 'Hourly',
            'budget'         => $r['budget_display'],
            'proposalsCount' => (int)$r['proposals_count'],
            'freelancer'     => $r['hired_freelancer'] ?? null,
            'postedDate'     => $postedDate,
            'dueDate'        => $dueDate,
            'skills'         => json_decode($r['skills'] ?? '[]', true) ?: []
        ];
    }

    sendResponse($projects);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to fetch projects: ' . $e->getMessage()], 500);
}
