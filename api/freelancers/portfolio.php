<?php
/**
 * Endpoint: GET|POST /api/freelancers/me/portfolio
 * GET  - Returns portfolio items for the logged-in freelancer
 * POST - Adds a new portfolio item
 */

require_once __DIR__ . '/../config/db.php';

$method       = $_SERVER['REQUEST_METHOD'];
$freelancerId = 'f-101'; // demo: extend with token auth

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
    $newId = 'port-' . time();
    $title = trim($body['title'] ?? 'New Project');
    $cat   = $body['category'] ?? 'Web Application';
    $url   = $body['url'] ?? '#';
    $image = $body['image'] ?? '../assets/images/portfolio-1.png';
    $desc  = $body['desc'] ?? '';

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

} else {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}
