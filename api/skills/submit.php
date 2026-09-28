<?php
/**
 * Endpoint: POST /api/skills/tests/{category}/submit
 * Grades submitted skill test answers and saves the result to test_results
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract category from URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/skills\/tests\/([a-zA-Z0-9_-]+)\/submit/', $uri, $matches);
$category = $matches[1] ?? 'web';

try {
    $freelancerId = requireAuth($pdo)['id'];

    $body    = getRequestBody();
    $answers = $body['answers'] ?? []; // { questionId => selectedIndex }

    if (empty($answers) || !is_array($answers)) {
        sendResponse(['message' => 'Answers are required (object mapping question IDs to answer indexes)'], 400);
    }

    // Validate question IDs are integers
    $questionIds = array_keys($answers);
    $validIds    = array_filter($questionIds, fn($id) => is_numeric($id) && (int)$id > 0);

    if (count($validIds) === 0) {
        sendResponse(['message' => 'Invalid question IDs provided'], 400);
    }

    // Load correct answers from DB
    $placeholders = implode(',', array_fill(0, count($validIds), '?'));
    $stmt = $pdo->prepare("SELECT id, correct_index FROM skill_questions WHERE id IN ($placeholders) AND category = ?");
    $stmt->execute(array_merge(array_map('intval', $validIds), [$category]));
    $correctMap = [];
    foreach ($stmt->fetchAll() as $q) {
        $correctMap[$q['id']] = (int)$q['correct_index'];
    }

    if (empty($correctMap)) {
        sendResponse(['message' => 'No matching questions found for this category'], 400);
    }

    // Grade answers
    $correct = 0;
    $total   = count($correctMap);
    foreach ($correctMap as $qId => $correctIdx) {
        if (isset($answers[$qId]) && (int)$answers[$qId] === $correctIdx) {
            $correct++;
        }
    }

    $score  = (int)round(($correct / $total) * 100);
    $passed = $score >= 70;
    $badge  = $passed ? 'Verified Pro' : null;

    // Save result
    $resStmt = $pdo->prepare("
        INSERT INTO test_results (freelancer_id, category, score, passed, correct_count, total_count, verified_badge)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $resStmt->execute([$freelancerId, $category, $score, $passed ? 1 : 0, $correct, $total, $badge]);

    // Update user score if this test improved it
    if ($passed) {
        $pdo->prepare("UPDATE users SET score = GREATEST(score, ?) WHERE id = ?")
            ->execute([$score, $freelancerId]);
    }

    sendResponse([
        'category'     => $category,
        'score'        => $score,
        'passed'       => $passed,
        'correctCount' => $correct,
        'totalCount'   => $total,
        'verifiedBadge'=> $badge,
        'submittedAt'  => date('c')
    ]);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to submit test: ' . $e->getMessage()], 500);
}
