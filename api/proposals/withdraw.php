<?php
/**
 * Endpoint: DELETE /api/proposals/{id}
 * Role: Withdraw (delete) a proposal submitted by the logged-in freelancer
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract proposal ID from URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/proposals\/([a-zA-Z0-9_-]+)/', $uri, $matches);
$propId = $matches[1] ?? '';

if (empty($propId)) {
    sendResponse(['message' => 'Proposal ID is required'], 400);
}

$freelancerId = 'f-101'; // demo: extend with token auth

// Verify ownership
$check = $pdo->prepare("SELECT id FROM proposals WHERE id = ? AND freelancer_id = ? LIMIT 1");
$check->execute([$propId, $freelancerId]);
if (!$check->fetch()) {
    sendResponse(['message' => 'Proposal not found or unauthorized'], 404);
}

$stmt = $pdo->prepare("DELETE FROM proposals WHERE id = ? AND freelancer_id = ?");
$stmt->execute([$propId, $freelancerId]);

sendResponse(['success' => true, 'message' => 'Proposal withdrawn successfully']);
