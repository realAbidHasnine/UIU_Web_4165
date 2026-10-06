<?php
require_once __DIR__ . '/includes/auth.php';

$flashHtml = take_flashes();
$conn      = db();
$clientId  = current_client_id();
$user      = db_user();

$name     = $user ? ($user['name'] ?? 'Client') : 'Client';
$company  = $user['company'] ?? ($user['company_name'] ?? '');
$location = $user['location'] ?? 'Dhaka, Bangladesh';
$email    = $user['email'] ?? 'client@example.com';
$bio      = $user['bio'] ?? 'Independent client hiring pre-vetted, skill-tested freelancers. Focused on clear requirements, transparent milestones, and fair collaboration to ship high-quality products.';
$title    = $user['title'] ?? 'Client';

$activeProjectsCount = 0;
$proposalsCount      = 0;
$hiresCount          = 0;
$totalSpent          = 0;
$recentJobs          = [];

try {
    $st = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status IN ('Open', 'In Progress')");
    $st->execute([$clientId]);
    $activeProjectsCount = (int) $st->fetchColumn();

    $st = $conn->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON j.id = p.job_id WHERE j.client_id = ?");
    $st->execute([$clientId]);
    $proposalsCount = (int) $st->fetchColumn();

    $st = $conn->prepare("SELECT COUNT(DISTINCT p.freelancer_id) FROM proposals p JOIN jobs j ON j.id = p.job_id WHERE j.client_id = ? AND p.status = 'Accepted'");
    $st->execute([$clientId]);
    $hiresCount = (int) $st->fetchColumn();

    $st = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE client_id = ? AND status = 'Released'");
    $st->execute([$clientId]);
    $totalSpent = (float) $st->fetchColumn();

    $st = $conn->prepare("SELECT j.*, 
                                 (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count
                            FROM jobs j 
                           WHERE j.client_id = ? 
                           ORDER BY j.created_at DESC 
                           LIMIT 4");
    $st->execute([$clientId]);
    $recentJobs = $st->fetchAll();
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($name) ?> - SkillMatch</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container" style="max-width:1100px">
  <?= $flashHtml ?>
  <div class="card">
    <div class="hero"></div>
    <div class="profile-top">
      <img class="profile-photo" src="https://i.pravatar.cc/160?img=47" alt="<?= e($name) ?>">
      <div style="flex:1">
        <h2 style="margin:0"><?= e($name) ?></h2>
        <div style="color:var(--muted);font-size:14px">
          <?= e($company !== '' ? ($company . ' · ') : '') ?><?= e($title) ?> &nbsp;&bull;&nbsp; <?= e($location) ?> &nbsp;&bull;&nbsp; <strong style="color:var(--text)"><?= e($email) ?></strong>
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn btn-outline" href="edit-profile.php">Edit Profile</a>
        <a class="btn btn-primary" href="post-project-details.php">+ Post a Project</a>
      </div>
    </div>
    <div style="height:20px"></div>
  </div>

  <div class="two-col">
    <div style="display:flex;flex-direction:column;gap:20px">
      <section class="card" style="padding:20px">
        <h3 style="margin:0 0 10px">About</h3>
        <p style="color:var(--muted);font-size:14px;line-height:1.6;margin:0"><?= nl2br(e($bio)) ?></p>
      </section>

      <section class="card" style="padding:20px">
        <h3 style="margin:0 0 10px">Account Overview</h3>
        <div class="skillbar">
          <span>Active Projects</span>
          <span class="badge badge-match"><?= $activeProjectsCount ?></span>
        </div>
        <div class="skillbar">
          <span>Proposals Received</span>
          <span class="badge badge-match"><?= $proposalsCount ?></span>
        </div>
        <div class="skillbar">
          <span>Freelancers Hired</span>
          <span class="badge badge-match"><?= $hiresCount ?></span>
        </div>
        <div class="skillbar" style="border:0">
          <span>Total Spent</span>
          <span class="badge badge-match"><?= money($totalSpent) ?></span>
        </div>
      </section>
    </div>

    <div>
      <h3 style="margin:4px 0 12px">Recent Projects</h3>
      <?php if (empty($recentJobs)): ?>
      <div class="card" style="padding:24px;text-align:center;color:var(--muted)">
        No projects posted yet. <a href="post-project-details.php" style="font-weight:600">Post a project</a>
      </div>
      <?php else: ?>
      <?php foreach ($recentJobs as $job): 
          $stLower = strtolower($job['status']);
          $badgeCls = $stLower === 'open' ? 'badge-open' : ($stLower === 'in progress' ? 'badge-progress' : 'badge-done');
          $propCnt = (int) ($job['proposal_count'] ?? 0);
          $budgetText = is_numeric($job['budget']) ? ('$' . number_format((float)$job['budget'], 0)) : ('$' . $job['budget']);
      ?>
      <div class="card" style="padding:14px 18px;margin-bottom:12px">
        <div style="display:flex;gap:10px;justify-content:space-between;align-items:center">
          <div>
            <strong style="font-size:14px"><?= e($job['title']) ?></strong>
            <div style="color:var(--muted);font-size:12.5px">Budget <?= e($budgetText) ?> &nbsp;&bull;&nbsp; <?= $propCnt ?> proposal<?= $propCnt === 1 ? '' : 's' ?></div>
          </div>
          <span class="badge <?= $badgeCls ?>"><?= e($job['status']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      <div style="text-align:right;margin-top:6px">
        <a href="my-projects.php" style="font-size:13.5px;font-weight:600">View All Projects &rarr;</a>
      </div>
    </div>
  </div>
</main>
<script src="../assets/js/api.js"></script>
<script src="../assets/js/client-workflow.js"></script>
</body>
</html>
