<?php

require_once __DIR__ . '/includes/auth.php';

// Chat composer: adds a message, refreshes the inbox preview, and pings the
// other person.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: client-chat.php');
    exit;
}

$conn   = db();
$sender = current_client_id();
$body   = trim($_POST['chat_message']);
$thread = (int) $_POST['conversation_id'];

if ($body === '') {
    header('Location: client-chat.php?c=' . $thread);
    exit;
}

if (mb_strlen($body) > 2000) {
    flash('That message is too long.');
    header('Location: client-chat.php?c=' . $thread);
    exit;
}

// Make sure this client is actually part of the thread.
$st = $conn->prepare(
    'SELECT client_id, freelancer_id
       FROM conversations
      WHERE id = ? AND (client_id = ? OR freelancer_id = ?)'
);
$st->execute([$thread, $sender, $sender]);
$people = $st->fetch();

if (!$people) {
    flash('That conversation is not available.');
    header('Location: client-chat.php');
    exit;
}

$other = (int) $people['client_id'] === $sender
    ? (int) $people['freelancer_id']
    : (int) $people['client_id'];

try {
    $conn->beginTransaction();

    $conn->prepare(
        'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)'
    )->execute([$thread, $sender, $body]);

    $messageId = (int) $conn->lastInsertId();

    // The inbox list shows this, so keep it on the thread row too.
    $conn->prepare(
        'UPDATE conversations
            SET last_message_at = NOW(), last_message_preview = ?
          WHERE id = ?'
    )->execute([mb_substr($body, 0, 255), $thread]);

    $conn->prepare(
        'INSERT INTO notifications (user_id, type, title, body, related_type, related_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$other, 'new_message', 'New Message', 'You have a new message.', 'message', $messageId]);

    $conn->commit();
} catch (PDOException $e) {
    flash('Your message could not be sent.');
}

header('Location: client-chat.php?c=' . $thread);
exit;