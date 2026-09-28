<?php
/**
 * Endpoint: GET /api/chat/threads
 * Returns chat threads for the authenticated user (freelancer or client)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $userId   = requireAuth($pdo)['id'];

    $stmt = $pdo->prepare("
        SELECT ct.*
        FROM chat_threads ct
        WHERE ct.freelancer_id = ? OR ct.client_id = ?
        ORDER BY ct.updated_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $rows = $stmt->fetchAll();

    $threads = [];
    foreach ($rows as $r) {
        // Get the latest message for preview
        $msgStmt = $pdo->prepare("
            SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at DESC LIMIT 1
        ");
        $msgStmt->execute([$r['id']]);
        $lastMsg = $msgStmt->fetch();

        // Determine the "other party's" name
        if ($userId === $r['freelancer_id']) {
            // User is freelancer, show client name
            $displayName = $r['client_name'];
            $role        = 'Client';
        } else {
            // User is client, show freelancer name
            $flStmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
            $flStmt->execute([$r['freelancer_id']]);
            $fl          = $flStmt->fetch();
            $displayName = $fl ? $fl['name'] : 'Freelancer';
            $role        = 'Freelancer';
        }

        // Count unread messages (those not from current user)
        $unreadStmt = $pdo->prepare("
            SELECT COUNT(*) FROM chat_messages
            WHERE thread_id = ? AND is_me = 0
        ");
        $unreadStmt->execute([$r['id']]);
        $unread = (int)$unreadStmt->fetchColumn();

        $lastMessage = $lastMsg ? $lastMsg['text'] : $r['last_message'];
        $lastTime    = $lastMsg ? date('g:i A', strtotime($lastMsg['created_at'])) : '';

        $threads[] = [
            'id'          => $r['id'],
            'name'        => $displayName,
            'company'     => 'SkillMatch',
            'role'        => $role,
            'status'      => $r['status'] ?? 'offline',
            'unread'      => $unread,
            'lastMessage' => $lastMessage,
            'time'        => $lastTime
        ];
    }

    sendResponse($threads);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to load threads: ' . $e->getMessage()], 500);
}
