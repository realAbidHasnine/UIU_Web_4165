<?php
/**
 * Endpoint: GET /api/chat/threads
 * Role: Returns chat threads for the logged-in user (freelancer or client)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$userId = 'f-101'; // demo: extend with token auth

$stmt = $pdo->prepare("
    SELECT ct.*,
        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.thread_id = ct.id AND cm.is_me = 0) AS unread
    FROM chat_threads ct
    WHERE ct.freelancer_id = ? OR ct.client_id = ?
    ORDER BY ct.updated_at DESC
");
$stmt->execute([$userId, $userId]);
$rows = $stmt->fetchAll();

$threads = [];
foreach ($rows as $r) {
    // Get latest message for preview
    $msgStmt = $pdo->prepare("SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY created_at DESC LIMIT 1");
    $msgStmt->execute([$r['id']]);
    $lastMsg = $msgStmt->fetch();

    $threads[] = [
        'id'          => $r['id'],
        'name'        => $r['client_name'],
        'company'     => 'SkillMatch Client',
        'role'        => 'Client',
        'status'      => $r['status'] ?? 'offline',
        'unread'      => (int)($r['unread'] ?? 0),
        'lastMessage' => $lastMsg ? $lastMsg['text'] : $r['last_message']
    ];
}

sendResponse($threads);
