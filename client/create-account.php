<?php

require_once __DIR__ . '/includes/auth.php';

// Client registration: one row in users, one in client_profiles, and a nudge
// to verify the email.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-client-account.php');
    exit;
}

$conn     = db();
$first    = trim($_POST['first_name']);
$last     = trim($_POST['last_name']);
$company  = trim($_POST['company_name']);
$email    = strtolower(trim($_POST['work_email']));
$password = $_POST['password'];
$hiring   = trim($_POST['hiring_needs']);

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

// Anything queued above means the form needs another pass.
if (!empty($_SESSION['flash'])) {
    header('Location: create-client-account.php');
    exit;
}

$avatar = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = $_FILES['photo']['type'];

    if (!isset($types[$mime])) {
        flash('Profile photo must be a JPG, PNG or WEBP image.');
    } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        flash('Profile photo must be under 2 MB.');
    } else {
        $folder = dirname(__DIR__) . '/uploads/avatars';

        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $name   = 'avatar-' . bin2hex(random_bytes(8)) . '.' . $types[$mime];
        $target = $folder . '/' . $name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $avatar = 'uploads/avatars/' . $name;
        } else {
            flash('Could not save your profile photo.');
        }
    }
}

try {
    $conn->beginTransaction();

    $conn->prepare(
        "INSERT INTO users (email, password_hash, role, status, avatar_url, email_verified_at)
         VALUES (?, ?, 'client', 'active', ?, NOW())"
    )->execute([$email, password_hash($password, PASSWORD_DEFAULT), $avatar]);

    $userId = (int) $conn->lastInsertId();

    $conn->prepare(
        'INSERT INTO client_profiles
            (user_id, first_name, last_name, company_name, hiring_needs, setup_complete)
         VALUES (?, ?, ?, ?, ?, 1)'
    )->execute([$userId, $first, $last, $company, $hiring]);

    $conn->prepare(
        'INSERT INTO notifications (user_id, type, title, body)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $userId,
        'email_verification',
        'Action Required',
        'Please verify your email address to continue',
    ]);

    $conn->commit();

    flash('Account created for ' . $email . '.', 'success');

    // The current client is still hardcoded, so this does not sign them in.
    header('Location: client-dashboard.php');
    exit;
} catch (PDOException $e) {
    flash('Could not create your account. Please try again.');
    header('Location: create-client-account.php');
    exit;
}