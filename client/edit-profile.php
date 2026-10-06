<?php
require_once __DIR__ . '/includes/auth.php';

$clientId = current_client_id();
$conn     = db();
$user     = db_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim((string) ($_POST['first_name'] ?? ''));
    $last     = trim((string) ($_POST['last_name'] ?? ''));
    $company  = trim((string) ($_POST['company'] ?? ''));
    $title    = trim((string) ($_POST['title'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $bio      = trim((string) ($_POST['bio'] ?? ''));
    $name     = trim($first . ' ' . $last);

    if ($name === '') {
        flash('Please enter your name.', 'error');
        header('Location: edit-profile.php');
        exit;
    }

    try {
        $stmt = $conn->prepare(
            "UPDATE users 
                SET name = ?, company = ?, title = ?, location = ?, bio = ?
              WHERE id = ?"
        );
        $stmt->execute([
            $name,
            $company,
            $title,
            $location,
            $bio,
            $clientId
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!empty($_SESSION['user'])) {
            $_SESSION['user']['name'] = $name;
        }

        flash('Your profile has been updated successfully!', 'success');
        header('Location: client-profile.php');
        exit;
    } catch (\Throwable $e) {
        flash('Failed to update profile: ' . $e->getMessage(), 'error');
        header('Location: edit-profile.php');
        exit;
    }
}

$flashHtml = take_flashes();
$firstName = $user['first_name'] ?? '';
$lastName  = $user['last_name'] ?? '';
$company   = $user['company'] ?? ($user['company_name'] ?? '');
$title     = $user['title'] ?? 'Client & Hiring Manager';
$location  = $user['location'] ?? 'Dhaka, Bangladesh';
$bio       = $user['bio'] ?? '';
$email     = $user['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Profile - SkillMatch</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container" style="max-width:800px">
  <?= $flashHtml ?>
  
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 class="page-title" style="margin:0 0 4px">Edit Profile</h1>
      <p class="page-sub" style="margin:0">Update your public client profile, company details and hiring bio.</p>
    </div>
    <a class="btn btn-outline" href="client-profile.php">&larr; Back to Profile</a>
  </div>

  <form class="form-card" action="edit-profile.php" method="POST" style="max-width:none">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div class="field" style="margin:0">
        <label for="first_name">First Name</label>
        <input class="input" id="first_name" name="first_name" value="<?= e($firstName) ?>" placeholder="First name" required>
      </div>
      <div class="field" style="margin:0">
        <label for="last_name">Last Name</label>
        <input class="input" id="last_name" name="last_name" value="<?= e($lastName) ?>" placeholder="Last name">
      </div>
    </div>

    <div class="field">
      <label for="company">Company / Organization</label>
      <input class="input" id="company" name="company" value="<?= e($company) ?>" placeholder="e.g. Acme Studio, Flow Tech">
    </div>

    <div class="field">
      <label for="title">Professional Role / Title</label>
      <input class="input" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. Product Lead, Technical Founder">
    </div>

    <div class="field">
      <label for="location">Location</label>
      <input class="input" id="location" name="location" value="<?= e($location) ?>" placeholder="e.g. Dhaka, Bangladesh">
    </div>

    <div class="field">
      <label for="email_display">Email Address</label>
      <input class="input" id="email_display" value="<?= e($email) ?>" style="background:#f8fafc;cursor:not-allowed" readonly title="Email cannot be changed directly">
      <p style="color:var(--muted);font-size:12px;margin:4px 0 0">Associated with your SkillMatch client account.</p>
    </div>

    <div class="field">
      <label for="bio">About / Hiring Summary</label>
      <textarea class="textarea" id="bio" name="bio" rows="4" placeholder="Describe your team, hiring standards, and what you look for in candidates..."><?= e($bio) ?></textarea>
    </div>

    <hr class="hr">

    <div class="two-btns">
      <a class="btn btn-outline" href="client-profile.php" style="border-color:var(--border);color:#374151">Cancel</a>
      <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
  </form>
</main>
<script src="../assets/js/api.js"></script>
<script src="../assets/js/client-workflow.js"></script>
</body>
</html>
