<?php
/**
 * Endpoint: POST /api/skills/tests/{category}/submit
 * Role: Grades the submitted skill test answers and saves the result
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract category from URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/skills\/tests\/([a-zA-Z0-9_-]+)\/submit/', $uri, $matches);
$category = $matches[1] ?? 'web';

$body = getRequestBody();
$answers = $body['answers'] ?? []; // { questionId => selectedIndex }

if (empty($answers)) {
    sendResponse(['message' => 'Answers are required'], 400);
}

// Load the correct answers for the submitted question IDs
$questionIds = array_keys($answers);
$placeholders = implode(',', array_fill(0, count($questionIds), '?'));
$stmt = $pdo->prepare("SELECT id, correct_index FROM skill_questions WHERE id IN ($placeholders)");
$stmt->execute($questionIds);
$correctMap = [];
foreach ($stmt->fetchAll() as $q) {
    $correctMap[$q['id']] = (int)$q['correct_index'];
}

$correct = 0;
$total = count($correctMap);
foreach ($correctMap as $qId => $correctIdx) {
    if (isset($answers[$qId]) && (int)$answers[$qId] === $correctIdx) {
        $correct++;
    }
}

$score = ($total > 0) ? (int)round(($correct / $total) * 100) : 0;
$passed = $score >= 70;
$badge = $passed ? 'Verified Pro' : null;

// Save result to test_results
$freelancerId = 'f-101'; // demo: extend with token auth
$resStmt = $pdo->prepare("
    INSERT INTO test_results (freelancer_id, category, score, passed, correct_count, total_count, verified_badge)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$resStmt->execute([$freelancerId, $category, $score, $passed ? 1 : 0, $correct, $total, $badge]);

// Update user score if improved
if ($passed) {
    $pdo->prepare("UPDATE users SET score = GREATEST(score, ?) WHERE id = ?")->execute([$score, $freelancerId]);
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
