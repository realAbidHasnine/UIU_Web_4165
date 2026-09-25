<?php
/**
 * Endpoint: GET|PUT /api/freelancers/me
 * Role: Returns or updates the logged-in freelancer's profile
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Simple session-less auth: read Bearer token from Authorization header
// In production you would validate against a sessions table
// For this demo, we fetch the first active freelancer (f-101 = Sarah Jenkins)
$freelancerId = 'f-101'; // default demo account

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
// Future: resolve $freelancerId from token stored in a sessions table

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'FREELANCER' LIMIT 1");
    $stmt->execute([$freelancerId]);
    $r = $stmt->fetch();

    if (!$r) {
        sendResponse(['message' => 'Freelancer not found'], 404);
    }

    sendResponse([
        'id'            => $r['id'],
        'name'          => $r['name'],
        'email'         => $r['email'],
        'role'          => $r['role'],
        'title'         => $r['title'] ?? 'Verified Specialist',
        'bio'           => $r['bio'] ?? '',
        'location'      => $r['location'] ?? 'Remote',
        'hourlyRate'    => (float)($r['hourly_rate'] ?? 65),
        'rating'        => (float)($r['rating'] ?? 5.0),
        'score'         => (int)($r['score'] ?? 90),
        'verifiedScore' => (int)($r['score'] ?? 90),
        'earnings'      => (float)($r['earnings'] ?? 0),
        'completedJobs' => (int)($r['completed_jobs'] ?? 0),
        'skills'        => ['React', 'TypeScript', 'TailwindCSS', 'Spring Boot', 'REST APIs'],
        'verified'      => true
    ]);

} elseif ($method === 'PUT') {
    $body = getRequestBody();

    $fields = [];
    $params = [];

    if (isset($body['name'])) { $fields[] = 'name = ?'; $params[] = $body['name']; }
    if (isset($body['title'])) { $fields[] = 'title = ?'; $params[] = $body['title']; }
    if (isset($body['bio'])) { $fields[] = 'bio = ?'; $params[] = $body['bio']; }
    if (isset($body['hourlyRate'])) { $fields[] = 'hourly_rate = ?'; $params[] = (float)$body['hourlyRate']; }
    if (isset($body['location'])) { $fields[] = 'location = ?'; $params[] = $body['location']; }

    if (empty($fields)) {
        sendResponse(['message' => 'No fields to update'], 400);
    }

    $params[] = $freelancerId;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($params);

    // Return updated profile
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$freelancerId]);
    $r = $stmt->fetch();

    sendResponse([
        'id'            => $r['id'],
        'name'          => $r['name'],
        'email'         => $r['email'],
        'role'          => $r['role'],
        'title'         => $r['title'] ?? '',
        'bio'           => $r['bio'] ?? '',
        'hourlyRate'    => (float)($r['hourly_rate'] ?? 0),
        'verifiedScore' => (int)($r['score'] ?? 90),
        'message'       => 'Profile updated successfully'
    ]);

} else {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}
