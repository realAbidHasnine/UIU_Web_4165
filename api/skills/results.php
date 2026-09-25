<?php
/**
 * Endpoint: GET /api/skills/tests/results/latest
 * Role: Returns the most recent skill test result for the logged-in freelancer
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$freelancerId = 'f-101'; // demo: extend with token auth

$stmt = $pdo->prepare("
    SELECT * FROM test_results
    WHERE freelancer_id = ?
    ORDER BY submitted_at DESC
    LIMIT 1
");
$stmt->execute([$freelancerId]);
$row = $stmt->fetch();

if (!$row) {
    sendResponse(['message' => 'No test results found for this freelancer'], 404);
}

sendResponse([
    'category'     => $row['category'],
    'score'        => (int)$row['score'],
    'passed'       => (bool)$row['passed'],
    'correctCount' => (int)$row['correct_count'],
    'totalCount'   => (int)$row['total_count'],
    'verifiedBadge'=> $row['verified_badge'],
    'submittedAt'  => $row['submitted_at']
]);
