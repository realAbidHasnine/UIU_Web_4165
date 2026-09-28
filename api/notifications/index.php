<?php
/**
 * Endpoint: GET  /api/notifications              — the caller's notifications
 * Endpoint: POST /api/notifications/read-all     — mark every one as read
 * Endpoint: POST /api/notifications/{id}/read     — mark one as read
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$markAll = routeName() === 'notifications/read-all';
$notificationId = routeParam('id');

try {
    $user   = requireAuth($pdo);
    $userId = $user['id'];

    if ($method === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }
        $onlyUnread = ($_GET['unread'] ?? '') === '1';

        $sql = "SELECT id, type, title, body, link, is_read, created_at
                FROM notifications WHERE user_id = ?";
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC, id DESC LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'      => (int)$r['id'],
                'type'    => $r['type'],
                'title'   => $r['title'],
                'body'    => $r['body'] ?? '',
                'link'    => $r['link'] ?? '',
                'isRead'  => (bool)$r['is_read'],
                'time'    => timeAgo($r['created_at'])
            ];
        }

        $unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $unread->execute([$userId]);

        sendResponse([
            'notifications' => $items,
            'unreadCount'   => (int)$unread->fetchColumn()
        ], 200);
    }

    if ($method === 'POST' && $markAll) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        sendResponse(['message' => 'All notifications marked as read', 'updated' => $stmt->rowCount()], 200);
    }

    if ($method === 'POST' && $notificationId !== '' && ctype_digit($notificationId)) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$notificationId, $userId]);
        if ($stmt->rowCount() === 0) {
            sendResponse(['message' => 'Notification not found'], 404);
        }
        sendResponse(['message' => 'Notification marked as read', 'id' => (int)$notificationId], 200);
    }

    sendResponse(['message' => 'Method Not Allowed'], 405);

} catch (PDOException $e) {
    sendResponse(['message' => 'Notification error: ' . $e->getMessage()], 500);
}

/**
 * Render a compact relative timestamp, e.g. "2m ago".
 */
function timeAgo(string $datetime): string {
    $ts   = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}
