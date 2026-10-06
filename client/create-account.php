<?php

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-client-account.php');
    exit;
}

$conn     = db();
$first    = trim($_POST['first_name'] ?? '');
$last     = trim($_POST['last_name'] ?? '');
$company  = trim($_POST['company_name'] ?? '');
$email    = strtolower(trim($_POST['work_email'] ?? ''));
$password = $_POST['password'] ?? '';
$hiring   = trim($_POST['hiring_needs'] ?? '');

if ($first === '') {
    flash('Enter your first name.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('Enter a valid work email address.');
}

if (strlen($password) < 8) {
    flash('Your password needs to be at least 8 characters.');
}

$exists = $conn->prepare('SELECT id FROM users WHERE email = ?');
$exists->execute([$email]);

if ($exists->fetch()) {
    flash('An account already exists for that email address.');
}

if (!empty($_SESSION['flash'])) {
    header('Location: create-client-account.php');
    exit;
}

try {
    $userId = 'c-' . rand(300, 999);
    $name   = trim($first . ' ' . $last);

    $stmt = $conn->prepare(
        "INSERT INTO users (id, email, password, role, status, name, company)
         VALUES (?, ?, ?, 'CLIENT', 'Active', ?, ?)"
    );
    $stmt->execute([
        $userId,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $name,
        $company
    ]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['user'] = [
        'id'    => $userId,
        'email' => $email,
        'name'  => $name,
        'role'  => 'CLIENT'
    ];

    flash('Account created successfully! Welcome to SkillMatch.', 'success');
    header('Location: client-dashboard.php');
    exit;
} catch (PDOException $e) {
    flash('Could not create your account: ' . $e->getMessage());
    header('Location: create-client-account.php');
    exit;
}