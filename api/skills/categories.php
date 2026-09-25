<?php
/**
 * Endpoint: GET /api/skills/categories
 * Role: Returns the list of available skill verification test categories
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$stmt = $pdo->query("SELECT * FROM skill_categories ORDER BY name ASC");
$rows = $stmt->fetchAll();

$categories = [];
foreach ($rows as $r) {
    $categories[] = [
        'id'            => $r['id'],
        'name'          => $r['name'],
        'icon'          => $r['icon'],
        'questionCount' => (int)$r['question_count'],
        'difficulty'    => $r['difficulty']
    ];
}

sendResponse($categories);
