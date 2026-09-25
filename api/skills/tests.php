<?php
/**
 * Endpoint: GET /api/skills/tests/{category}
 * Role: Returns randomized exam questions for the given skill category
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract category from URL: /api/skills/tests/{category}
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/skills\/tests\/([a-zA-Z0-9_-]+)$/', $uri, $matches);
$category = $matches[1] ?? 'web';

$stmt = $pdo->prepare("SELECT * FROM skill_questions WHERE category = ? ORDER BY RAND() LIMIT 5");
$stmt->execute([$category]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    // Fallback: try 'web' category
    $stmt = $pdo->prepare("SELECT * FROM skill_questions WHERE category = 'web' ORDER BY RAND() LIMIT 5");
    $stmt->execute();
    $rows = $stmt->fetchAll();
}

$questions = [];
foreach ($rows as $r) {
    $questions[] = [
        'id'       => (int)$r['id'],
        'question' => $r['question'],
        'options'  => json_decode($r['options'], true),
        'correct'  => (int)$r['correct_index']
    ];
}

sendResponse($questions);
