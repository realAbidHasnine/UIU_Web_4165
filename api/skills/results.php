<?php
/**
 * Endpoint: GET /api/skills/tests/results/latest
 * Returns the most recent skill test result for the authenticated freelancer
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $freelancerId = requireAuth($pdo)['id'];

    $stmt = $pdo->prepare("
        SELECT * FROM test_results
        WHERE freelancer_id = ?
        ORDER BY submitted_at DESC
        LIMIT 1
    ");
    $stmt->execute([$freelancerId]);
    $row = $stmt->fetch();

    if (!$row) {
        // Return a 200 with null data rather than 404, so the frontend can handle gracefully
        sendResponse([
            'found'   => false,
            'message' => 'No test results found yet. Complete a skill assessment to see your results here.'
        ]);
    }

    sendResponse([
        'found'        => true,
        'category'     => $row['category'],
        'score'        => (int)$row['score'],
        'passed'       => (bool)$row['passed'],
        'correctCount' => (int)$row['correct_count'],
        'totalCount'   => (int)$row['total_count'],
        'verifiedBadge'=> $row['verified_badge'],
        'submittedAt'  => $row['submitted_at']
    ]);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to fetch test results: ' . $e->getMessage()], 500);
}
