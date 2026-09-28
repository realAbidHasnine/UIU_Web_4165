<?php
/**
 * Endpoint: POST /api/auth/register
 * Role: Creates a new user account (freelancer or client) and returns a session token
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$body = getRequestBody();

// --- Validate inputs ---
$name     = trim($body['name'] ?? '');
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$role     = strtoupper(trim($body['role'] ?? 'FREELANCER'));
$company  = trim($body['company'] ?? '');

if (empty($name) || strlen($name) < 2) {
    sendResponse(['message' => 'Full name is required (minimum 2 characters)'], 400);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(['message' => 'A valid email address is required'], 400);
}

if (empty($password) || strlen($password) < 6) {
    sendResponse(['message' => 'Password must be at least 6 characters'], 400);
}

if (!in_array($role, ['FREELANCER', 'CLIENT'], true)) {
    sendResponse(['message' => 'Role must be FREELANCER or CLIENT'], 400);
}

// Check email uniqueness
$checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkStmt->execute([$email]);
if ($checkStmt->fetch()) {
    sendResponse(['message' => 'An account with this email address already exists'], 409);
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Generate unique user ID
$newId = ($role === 'FREELANCER' ? 'fl-' : 'c-') . substr(md5(uniqid($email, true)), 0, 8);

// Insert user
$insertStmt = $pdo->prepare("
    INSERT INTO users (id, email, password, role, name, company, status, score, rating)
    VALUES (?, ?, ?, ?, ?, ?, 'Active', 85, 5.00)
");
$insertStmt->execute([$newId, $email, $hashedPassword, $role, $name, $company ?: null]);

// Create session
$token    = bin2hex(random_bytes(32));
$sesStmt  = $pdo->prepare("INSERT INTO sessions (token, user_id) VALUES (?, ?)");
$sesStmt->execute([$token, $newId]);

$profile = [
    'id'           => $newId,
    'name'         => $name,
    'email'        => $email,
    'role'         => $role,
    'title'        => '',
    'company'      => $company,
    'hourlyRate'   => 0,
    'score'        => 85,
    'verifiedScore'=> 85,
    'rating'       => 5.0,
    'earnings'     => 0,
    'completedJobs'=> 0,
    'skills'       => [],
    'token'        => $token
];

sendResponse([
    'token'   => $token,
    'user'    => $profile,
    'message' => 'Account created successfully'
], 201);
