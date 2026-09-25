<?php
/**
 * Endpoint: POST /api/admin/users/{id}/status
 * Role: Toggles a user's status between Active and Suspended
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract user ID from URL: /api/admin/users/{id}/status
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/admin\/users\/([a-zA-Z0-9_-]+)\/status/', $uri, $matches);
$userId = $matches[1] ?? '';

if (empty($userId)) {
    sendResponse(['message' => 'User ID is required'], 400);
}

$body      = getRequestBody();
$newStatus = trim($body['status'] ?? '');

if (!in_array($newStatus, ['Active', 'Suspended', 'Flagged'])) {
    // Toggle between Active and Suspended if no status provided
    $cur = $pdo->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $cur->execute([$userId]);
    $row = $cur->fetch();
    if (!$row) {
        sendResponse(['message' => 'User not found'], 404);
    }
    $newStatus = $row['status'] === 'Active' ? 'Suspended' : 'Active';
}

$stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $userId]);

if ($stmt->rowCount() === 0) {
    sendResponse(['message' => 'User not found'], 404);
}

sendResponse([
    'success' => true,
    'userId'  => $userId,
    'status'  => $newStatus,
    'message' => "User status updated to $newStatus"
]);
