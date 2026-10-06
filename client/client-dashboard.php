<?php
require_once __DIR__ . '/includes/auth.php';
$conn     = db();
$clientId = current_client_id();
$user     = db_user();

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
                                 (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) AS proposal_count,
                                 (SELECT u.name FROM proposals p JOIN users u ON u.id = p.freelancer_id WHERE p.job_id = j.id AND p.status = 'Accepted' LIMIT 1) AS hired_name
                            FROM jobs j 
                           WHERE j.client_id = ? 
                           ORDER BY j.created_at DESC 
                           LIMIT 5");
    $st->execute([$clientId]);
    $recentJobs = $st->fetchAll();
} catch (\Throwable $e) {}

$displayName = $user ? ($user['first_name'] ?? $user['name']) : 'Client';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - SkillMatch</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<section class="masthead" aria-label="Account overview">
  <svg class="masthead-mark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5L13 2z"/></svg>
  <div class="masthead-top">
    <div>
      <h1 class="page-title">Welcome back, <?= e($displayName) ?></h1>
      <p class="page-sub">Here is an overview of your current projects and freelancer activity.</p>
    </div>
    <a class="btn btn-inverse" href="post-project-details.php">+ Post a New Project</a>
  </div>
  <div class="perf-strip">
    <div class="perf-cell">
      <div class="perf-top">
        <span>Active Projects</span>
        <span class="ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        </span>
      </div>
      <div class="perf-num"><?= $activeProjectsCount ?></div>
    </div>
    <div class="perf-cell">
      <div class="perf-top">
        <span>Proposals Received</span>
        <span class="ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        </span>
      </div>
      <div class="perf-num"><?= $proposalsCount ?></div>
    </div>
    <div class="perf-cell">
      <div class="perf-top">
        <span>Freelancers Hired</span>
        <span class="ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
      </div>
      <div class="perf-num"><?= $hiresCount ?></div>
    </div>
    <div class="perf-cell">
      <div class="perf-top">
        <span>Total Spent</span>
        <span class="ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
        </span>
      </div>
      <div class="perf-num"><?= money($totalSpent) ?></div>
    </div>
  </div>
</section>

<div class="card" style="padding:20px 22px;margin-bottom:26px">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="font-size:18px;margin:0">Getting started</h2>
    <span class="badge badge-done">Setup complete</span>
  </div>
  <div class="start-grid">
    <a class="start-step" href="client-profile.php">
      <span class="start-check" aria-hidden="true">&#10003;</span>
      <span><strong>Complete your profile</strong><span>Photo, company and hiring needs are set.</span></span>
    </a>
    <a class="start-step" href="post-project-details.php">
      <span class="start-check" aria-hidden="true">&#10003;</span>
      <span><strong>Post your project</strong><span>Create and publish milestone-based jobs.</span></span>
    </a>
    <a class="start-step" href="browse-freelancers.php">
      <span class="start-check" aria-hidden="true">&#10003;</span>
      <span><strong>Hire talent</strong><span>Review verified scores and hire top talent.</span></span>
    </a>
  </div>
</div>

<div class="section-head">
  <h2>Recent Projects</h2>
  <a href="my-projects.php" style="font-size:13.5px;font-weight:600">View All Projects</a>
</div>
<div class="card">
  <?php if (empty($recentJobs)): ?>
  <div style="padding:24px;text-align:center;color:var(--muted)">No projects posted yet. <a href="post-project-details.php">Post your first project</a></div>
  <?php else: ?>
  <?php foreach ($recentJobs as $job): 
      $stLower = strtolower($job['status']);
      $badgeCls = $stLower === 'open' ? 'badge-open' : ($stLower === 'in progress' ? 'badge-progress' : 'badge-done');
      $propCnt = (int) ($job['proposal_count'] ?? 0);
      $budgetText = is_numeric($job['budget']) ? ('$' . number_format((float)$job['budget'], 0)) : ('$' . $job['budget']);
  ?>
  <div class="list-row">
    <div>
      <p class="row-title">
        <?= e($job['title']) ?>
        <span class="badge <?= $badgeCls ?>"><?= e($job['status']) ?></span>
      </p>
      <div class="row-meta">
        <span><?= date('M j, Y', strtotime($job['created_at'])) ?></span>
        <span><?= $propCnt ?> Proposal<?= $propCnt === 1 ? '' : 's' ?></span>
        <?php if (!empty($job['hired_name'])): ?>
        <span>Freelancer: <?= e($job['hired_name']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="price">
      <strong>Budget</strong><?= e($budgetText) ?> 
      <a class="btn btn-outline" style="margin-left:10px" href="<?= $propCnt > 0 ? ('review-proposal.php?job=' . rawurlencode($job['id'])) : ('my-projects.php') ?>">View</a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
</main>
</body>
</html>
