<?php
/**
 * Endpoint: POST /api/auth/login
 * Role: Authenticates a user and creates a session token
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$body     = getRequestBody();
$email    = trim($body['email'] ?? '');
$password = trim($body['password'] ?? '');
$roleHint = strtoupper(trim($body['role'] ?? 'FREELANCER'));

if (empty($email) || empty($password)) {
    sendResponse(['message' => 'Email and password are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(['message' => 'Invalid email address format'], 400);
}

// Fetch user by email (optionally filtered by role)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    sendResponse(['message' => 'Invalid email or password'], 401);
}

// Passwords are stored exclusively as bcrypt hashes (password_hash()).
if (!password_verify($password, $user['password'])) {
    sendResponse(['message' => 'Invalid email or password'], 401);
}

// Optional: validate that the role matches if the frontend specified one
if ($roleHint !== 'GUEST' && $user['role'] !== $roleHint) {
    sendResponse([
        'message' => 'No account found with that role. Please select the correct role.'
    ], 401);
}

// Generate session token and store in sessions table
$token = bin2hex(random_bytes(32)); // 64 hex chars
$sesStmt = $pdo->prepare("INSERT INTO sessions (token, user_id) VALUES (?, ?)");
$sesStmt->execute([$token, $user['id']]);

// Fetch skills from user_skills table
$skills = getUserSkills($pdo, $user['id']);

// Return user profile + token
$profile = [
    'id'           => $user['id'],
    'name'         => $user['name'],
    'email'        => $user['email'],
    'role'         => $user['role'],
    'title'        => $user['title'] ?? '',
    'company'      => $user['company'] ?? '',
    'hourlyRate'   => (float)($user['hourly_rate'] ?? 0),
    'score'        => (int)($user['score'] ?? 90),
    'verifiedScore'=> (int)($user['score'] ?? 90),
    'rating'       => (float)($user['rating'] ?? 5.0),
    'earnings'     => (float)($user['earnings'] ?? 0),
    'completedJobs'=> (int)($user['completed_jobs'] ?? 0),
    'skills'       => $skills,
    'token'        => $token
];

sendResponse([
    'token' => $token,
    'user'  => $profile
], 200);
