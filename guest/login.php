<?php
/**
 * No-JS fallback for the client/freelancer login form.
 *
 * Normal operation is handled by assets/js/auth.js (POST /api/auth/login).
 * This handler keeps the form from 404ing and never echoes the password back
 * into the URL.
 */

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

require_once __DIR__ . '/../config/db.php';

$notice = '';

if ($email === '' || $password === '') {
    $notice = 'Enter both your email address and password.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $notice = 'That email address does not look valid.';
} else {
    $stmt = $pdo->prepare("SELECT id, role, status, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $notice = 'Invalid email or password.';
    } elseif ($user['status'] !== 'Active') {
        $notice = 'This account is ' . strtolower($user['status']) . '. Contact support.';
    } else {
        $notice = 'Credentials are valid. JavaScript is required to sign in — please enable it and retry.';
    }
}

header('Location: login.html?notice=' . rawurlencode($notice));
exit();
