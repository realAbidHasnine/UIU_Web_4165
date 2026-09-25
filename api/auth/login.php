<?php
/**
 * Endpoint: POST /api/auth/login
 * Role: Authenticates User against MySQL and returns Session Token & Profile
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$body = getRequestBody();
$email = trim($body['email'] ?? '');
$password = trim($body['password'] ?? '');
$role = strtoupper(trim($body['role'] ?? 'FREELANCER'));

if (empty($email) || empty($password)) {
    sendResponse(['message' => 'Email and password are required'], 400);
}

// Query user by email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // If not found, create or return demo response
    sendResponse(['message' => 'Invalid email or password'], 401);
}

// In plain text / development mode check password
if ($user['password'] !== $password && !password_verify($password, $user['password'])) {
    sendResponse(['message' => 'Invalid credentials'], 401);
}

// Format response matching frontend contract
$token = 'sess_' . bin2hex(random_bytes(16));
$profile = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'title' => $user['title'] ?? '',
    'company' => $user['company'] ?? '',
    'hourlyRate' => (float)($user['hourly_rate'] ?? 0),
    'score' => (int)($user['score'] ?? 90),
    'verifiedScore' => (int)($user['score'] ?? 90),
    'rating' => (float)($user['rating'] ?? 5.0),
    'earnings' => (float)($user['earnings'] ?? 0),
    'completedJobs' => (int)($user['completed_jobs'] ?? 0),
    'token' => $token
];

sendResponse([
    'token' => $token,
    'user' => $profile
], 200);
