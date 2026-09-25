<?php
/**
 * Endpoint: GET|POST /api/chat/threads/{id}/messages
 * GET  - Returns all messages for a thread
 * POST - Sends a new message to a thread
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract thread ID from URL: /api/chat/threads/{id}/messages
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/chat\/threads\/([a-zA-Z0-9_-]+)\/messages/', $uri, $matches);
$threadId = $matches[1] ?? '';

if (empty($threadId)) {
    sendResponse(['message' => 'Thread ID is required'], 400);
}

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at ASC");
    $stmt->execute([$threadId]);
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $r) {
        $messages[] = [
            'sender' => $r['sender_name'],
            'text'   => $r['text'],
            'time'   => $r['sent_time'],
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

    $senderName = 'Sarah Jenkins'; // demo: extend with session
    $now = date('g:i A');

    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (thread_id, sender_name, sender_role, text, is_me, sent_time)
        VALUES (?, ?, 'Freelancer', ?, 1, ?)
    ");
    $stmt->execute([$threadId, $senderName, $text, $now]);

    // Update thread last message
    $updStmt = $pdo->prepare("UPDATE chat_threads SET last_message = ? WHERE id = ?");
    $updStmt->execute([$text, $threadId]);

    sendResponse([
        'sender' => $senderName,
        'text'   => $text,
        'time'   => $now,
        'isMe'   => true
    ], 201);

} else {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}
