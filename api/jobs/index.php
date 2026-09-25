<?php
/**
 * Endpoint: /api/jobs
 * - GET: List active jobs with optional filtering (category, budget, duration, search)
 * - POST: Create a new project job posting
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? '';
    $budget = $_GET['budget'] ?? '';
    $duration = $_GET['duration'] ?? '';
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT * FROM jobs WHERE 1=1";
    $params = [];

    if (!empty($category) && $category !== 'all') {
        $cats = explode(',', $category);
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $sql .= " AND category IN ($placeholders)";
        $params = array_merge($params, $cats);
    }

    if (!empty($budget) && $budget !== 'any') {
        $sql .= " AND budget = ?";
        $params[] = $budget;
    }

    if (!empty($duration) && $duration !== 'any') {
        $sql .= " AND duration = ?";
        $params[] = $duration;
    }

    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $jobs = [];
    foreach ($rows as $r) {
        $jobs[] = [
            'id' => $r['id'],
            'title' => $r['title'],
            'category' => $r['category'],
            'budgetType' => $r['budget_type'],
            'budget' => $r['budget'],
            'budgetDisplay' => $r['budget_display'],
            'duration' => $r['duration'],
            'durationDisplay' => $r['duration_display'],
            'level' => $r['level'],
            'desc' => $r['description'],
            'company' => $r['company'] ?? 'Apex Capital Partners',
            'location' => $r['location'] ?? 'Remote',
            'skills' => json_decode($r['skills'] ?? '[]', true) ?: ['React', 'TypeScript'],
            'responsibilities' => json_decode($r['responsibilities'] ?? '[]', true) ?: ['Deliver milestone code'],
            'posted' => $r['posted_time'] ?? 'Recently',
            'proposalsCount' => 4,
            'suggestedRate' => 3000
        ];
    }

    sendResponse($jobs, 200);

} elseif ($method === 'POST') {
    $body = getRequestBody();

    $newId = 'job-' . (time());
    $title = trim($body['title'] ?? 'Untitled Project');
    $category = $body['category'] ?? 'web';
    $budgetType = $body['budgetType'] ?? 'fixed';
    $budget = (string)($body['budget'] ?? '3000');
    $budgetDisplay = $body['budgetDisplay'] ?? ('$' . number_format((float)$budget) . ' Fixed');
    $duration = $body['duration'] ?? '1-3-months';
    $durationDisplay = $body['durationDisplay'] ?? '1-3 Months';
    $level = $body['level'] ?? 'Intermediate';
    $desc = $body['desc'] ?? '';
    $skills = json_encode($body['skills'] ?? ['React', 'TypeScript']);

    $stmt = $pdo->prepare("
        INSERT INTO jobs (id, client_id, title, category, budget_type, budget, budget_display, duration, duration_display, level, description, company, location, skills, status)
        VALUES (?, 'c-201', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Abida Hasan (Client)', 'Remote', ?, 'Open')
    ");

    $stmt->execute([
        $newId, $title, $category, $budgetType, $budget, $budgetDisplay, $duration, $durationDisplay, $level, $desc, $skills
    ]);

    sendResponse([
        'id' => $newId,
        'title' => $title,
        'status' => 'Open',
        'posted' => 'Just now'
    ], 201);

} else {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}
