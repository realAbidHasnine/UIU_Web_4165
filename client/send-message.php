<?php

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: client-chat.php');
    exit;
}

$conn   = db();
$sender = current_client_id();
$body   = trim($_POST['chat_message'] ?? '');
$thread = trim((string) ($_POST['conversation_id'] ?? '1'));

if ($body === '') {
    header('Location: client-chat.php');
    exit;
}

try {
    $st = $conn->prepare("SELECT id FROM chat_threads WHERE id = ? OR client_id = ? LIMIT 1");
    $st->execute([$thread, $sender]);
    $threadId = $st->fetchColumn();

    if (!$threadId) {
        $threadId = 'thread-' . rand(100, 999);
        $conn->prepare("INSERT INTO chat_threads (id, client_id, freelancer_id) VALUES (?, ?, 'f-101')")
             ->execute([$threadId, $sender]);
    }

    $conn->prepare("INSERT INTO chat_messages (thread_id, sender_id, message) VALUES (?, ?, ?)")
         ->execute([$threadId, $sender, $body]);

    header('Location: client-chat.php');
    exit;
} catch (\Throwable $e) {
    header('Location: client-chat.php');
    exit;
}