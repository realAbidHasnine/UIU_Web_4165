<?php
/**
 * Endpoint: GET|POST /api/chat/threads/{id}/messages
 * GET  - Returns all messages for a thread
 * POST - Sends a new message to a thread
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract thread ID from URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/chat\/threads\/([a-zA-Z0-9_-]+)\/messages/', $uri, $matches);
$threadId = $matches[1] ?? '';

if (empty($threadId)) {
    sendResponse(['message' => 'Thread ID is required'], 400);
}

try {
    $authUser   = requireAuth($pdo);
    $userId     = $authUser['id'];
    $senderName = $authUser['name'];
    $senderRole = ucfirst(strtolower($authUser['role']));

    // Verify thread exists and user is a participant
    $threadCheck = $pdo->prepare("
        SELECT * FROM chat_threads WHERE id = ? AND (freelancer_id = ? OR client_id = ?) LIMIT 1
    ");
    $threadCheck->execute([$threadId, $userId, $userId]);
    $thread = $threadCheck->fetch();

    if (!$thread) {
        sendResponse(['message' => 'Thread not found or you are not a participant'], 404);
    }

    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC
        ");
        $stmt->execute([$threadId]);
        $rows = $stmt->fetchAll();

        $messages = [];
        foreach ($rows as $r) {
            // Determine if this message was sent by the current user
            $isMine = ($r['is_me'] == 1 && $thread['freelancer_id'] === $userId)
                   || ($r['is_me'] == 0 && $thread['client_id'] === $userId);

            $messages[] = [
                'id'     => (int)$r['id'],
                'sender' => $r['sender_name'],
                'text'   => $r['text'],
                'time'   => $r['sent_time'] ?: date('g:i A', strtotime($r['created_at'])),
                'isMe'   => (bool)$r['is_me']
            ];
        }

        sendResponse($messages);

    } elseif ($method === 'POST') {
        $body = getRequestBody();
        $text = trim($body['text'] ?? '');

        if (empty($text)) {
            sendResponse(['message' => 'Message text is required'], 400);
        }
        if (strlen($text) > 2000) {
            sendResponse(['message' => 'Message is too long (max 2000 characters)'], 400);
        }

        $now = date('g:i A');

        // is_me = 1 when the sender is the freelancer (the "me" perspective)
        $isMe = ($thread['freelancer_id'] === $userId) ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (thread_id, sender_name, sender_role, text, is_me, sent_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$threadId, $senderName, $senderRole, $text, $isMe, $now]);

        $msgId = $pdo->lastInsertId();

        // Update thread last_message and updated_at
        $pdo->prepare("UPDATE chat_threads SET last_message = ? WHERE id = ?")
            ->execute([$text, $threadId]);

        sendResponse([
            'id'     => (int)$msgId,
            'sender' => $senderName,
            'text'   => $text,
            'time'   => $now,
            'isMe'   => (bool)$isMe
        ], 201);

    } else {
        sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Chat error: ' . $e->getMessage()], 500);
}
