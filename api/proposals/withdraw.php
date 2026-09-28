<?php
/**
 * Endpoint: DELETE /api/proposals/{id}
 * Withdraws (deletes) a proposal — only by the owning freelancer
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

try {
    $freelancerId = requireAuth($pdo)['id'];

    // Verify ownership — freelancer can only delete their own proposals
    $check = $pdo->prepare("SELECT id, status FROM proposals WHERE id = ? AND freelancer_id = ? LIMIT 1");
    $check->execute([$propId, $freelancerId]);
    $existing = $check->fetch();

    if (!$existing) {
        sendResponse(['message' => 'Proposal not found or you are not authorized to withdraw it'], 404);
    }

    // Cannot withdraw an Accepted proposal
    if ($existing['status'] === 'Accepted') {
        sendResponse(['message' => 'Cannot withdraw an accepted proposal. Please contact support.'], 409);
    }

    $stmt = $pdo->prepare("DELETE FROM proposals WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$propId, $freelancerId]);

    sendResponse(['success' => true, 'message' => 'Proposal withdrawn successfully']);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to withdraw proposal: ' . $e->getMessage()], 500);
}
