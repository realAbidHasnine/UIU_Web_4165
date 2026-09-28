<?php
/**
 * Endpoint: GET|POST /api/freelancers/me/portfolio
 * GET  — Returns portfolio items for the authenticated freelancer
 * POST — Adds a new portfolio item
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $freelancerId = requireAuth($pdo)['id'];

    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE freelancer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$freelancerId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id'       => $r['id'],
                'title'    => $r['title'],
                'category' => $r['category'],
                'url'      => $r['url'],
                'image'    => $r['image'] ?? '../assets/images/portfolio-1.png',
                'desc'     => $r['description']
            ];
        }
        sendResponse($items);

    } elseif ($method === 'POST') {
        $body  = getRequestBody();
        $title = trim($body['title'] ?? '');
        $cat   = trim($body['category'] ?? 'Web Application');
        $url   = trim($body['url'] ?? '#');
        $image = trim($body['image'] ?? '../assets/images/portfolio-1.png');
        $desc  = trim($body['desc'] ?? '');

        if (empty($title)) {
            sendResponse(['message' => 'Project title is required'], 400);
        }

        // Sanitize URL
        if (!empty($url) && $url !== '#' && !filter_var($url, FILTER_VALIDATE_URL)) {
            sendResponse(['message' => 'Invalid URL format'], 400);
        }

        $newId = 'port-' . substr(md5(uniqid($freelancerId, true)), 0, 8);

        $stmt = $pdo->prepare("
            INSERT INTO portfolio_items (id, freelancer_id, title, category, url, image, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$newId, $freelancerId, $title, $cat, $url, $image, $desc]);

        sendResponse([
            'id'       => $newId,
            'title'    => $title,
            'category' => $cat,
            'url'      => $url,
            'image'    => $image,
            'desc'     => $desc,
            'message'  => 'Portfolio item added successfully'
        ], 201);

    } elseif ($method === 'DELETE') {
        // Extract portfolio item ID from URL
        $uri = $_SERVER['REQUEST_URI'];
        preg_match('/\/portfolio\/([a-zA-Z0-9_-]+)/', $uri, $matches);
        $portId = $matches[1] ?? '';

        if (empty($portId)) {
            sendResponse(['message' => 'Portfolio item ID is required'], 400);
        }

        // Verify ownership
        $check = $pdo->prepare("SELECT id FROM portfolio_items WHERE id = ? AND freelancer_id = ? LIMIT 1");
        $check->execute([$portId, $freelancerId]);
        if (!$check->fetch()) {
            sendResponse(['message' => 'Portfolio item not found or unauthorized'], 404);
        }

        $pdo->prepare("DELETE FROM portfolio_items WHERE id = ? AND freelancer_id = ?")->execute([$portId, $freelancerId]);
        sendResponse(['success' => true, 'message' => 'Portfolio item removed']);

    } else {
        sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Database error: ' . $e->getMessage()], 500);
}
