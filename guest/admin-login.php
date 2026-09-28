<?php
/**
 * No-JS fallback for Admin/html/admin-login.html and Admin/html/login.html.
 *
 * Normal operation is handled by assets/js/auth.js, which POSTs to
 * POST /api/auth/login with role=ADMIN and stores the returned token.
 * This handler exists so the form degrades gracefully instead of 404ing: it
 * validates the credentials, then bounces back to the login page with a
 * notice. The password is never placed in the query string.
 */

$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

require_once __DIR__ . '/../config/db.php';

$back = '../guest/admin_login.html';
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
        // Deliberately vague — do not reveal whether the account exists
        $notice = 'Invalid email or password.';
    } elseif ($user['role'] !== 'ADMIN') {
        $notice = 'This account is not an administrator.';
    } elseif ($user['status'] !== 'Active') {
        $notice = 'This administrator account is ' . strtolower($user['status']) . '.';
    } else {
        $notice = 'Credentials are valid. JavaScript is required to sign in — please enable it and retry.';
    }
}

header('Location: ' . $back . '?notice=' . rawurlencode($notice));
exit();
