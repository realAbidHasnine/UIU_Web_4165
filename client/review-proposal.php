<?php
require_once __DIR__ . '/includes/auth.php';

$clientId = current_client_id();
$conn     = db();

// Fetch all jobs belonging to this client for the job switcher
$clientJobs = [];
try {
    $st = $conn->prepare("SELECT id, title, status FROM jobs WHERE client_id = ? ORDER BY created_at DESC");
    $st->execute([$clientId]);
    $clientJobs = $st->fetchAll();
} catch (\Throwable $e) {}

// Determine active job ID
$jobId = trim((string)($_GET['job'] ?? ($_GET['jobId'] ?? '')));
if ($jobId === '') {
    // Pick the first job that has proposals, or the latest job
    try {
        $st = $conn->prepare("
            SELECT j.id FROM jobs j
            INNER JOIN proposals p ON p.job_id = j.id
            WHERE j.client_id = ?
            ORDER BY p.submitted_at DESC
            LIMIT 1
        ");
        $st->execute([$clientId]);
        $found = $st->fetchColumn();
        if ($found) {
            $jobId = $found;
        } elseif (!empty($clientJobs)) {
            $jobId = $clientJobs[0]['id'];
        } else {
            $jobId = 'job-1';
        }
    } catch (\Throwable $e) {
        $jobId = !empty($clientJobs) ? $clientJobs[0]['id'] : 'job-1';
    }
}

// Handle proposal acceptance POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept') {
    $proposalId = trim((string)($_POST['proposal_id'] ?? ''));
    if ($proposalId !== '') {
        try {
            $pStmt = $conn->prepare("
                SELECT p.*, u.name AS freelancer_name, j.title AS job_title
                FROM proposals p
                INNER JOIN users u ON u.id = p.freelancer_id
                INNER JOIN jobs j ON j.id = p.job_id
                WHERE p.id = ? AND p.job_id = ?
                LIMIT 1
            ");
            $pStmt->execute([$proposalId, $jobId]);
            $prop = $pStmt->fetch();

            if ($prop) {
                $conn->beginTransaction();

                // 1. Mark accepted
                $conn->prepare("UPDATE proposals SET status = 'Accepted' WHERE id = ?")->execute([$proposalId]);

                // 2. Archive competing proposals
                $conn->prepare("UPDATE proposals SET status = 'Archived' WHERE job_id = ? AND id <> ?")->execute([$jobId, $proposalId]);

                // 3. Set job in progress
                $conn->prepare("UPDATE jobs SET status = 'In Progress', hired_freelancer_id = ? WHERE id = ?")
                    ->execute([$prop['freelancer_id'], $jobId]);

                // 4. Milestone creation & escrow funding
                $msStmt = $conn->prepare("SELECT id FROM project_milestones WHERE job_id = ? LIMIT 1");
                $msStmt->execute([$jobId]);
                $msId = $msStmt->fetchColumn();

                if (!$msId) {
                    $msId = 'ms-' . substr(md5(uniqid($jobId, true)), 0, 8);
                    $fee  = round(((float)$prop['proposed_rate']) * 0.05, 2);
                    $conn->prepare("
                        INSERT INTO project_milestones (id, job_id, client_id, freelancer_id, label, description, amount, escrow_fee, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Posted')
                    ")->execute([
                        $msId,
                        $jobId,
                        $clientId,
                        $prop['freelancer_id'],
                        'Milestone 1: Project Deliverables',
                        'Initial milestone funded into escrow upon proposal acceptance.',
                        $prop['proposed_rate'],
                        $fee
                    ]);

                    $payId = 'pay-' . substr(md5(uniqid($msId, true)), 0, 8);
                    $conn->prepare("
                        INSERT INTO payments (id, milestone_id, job_id, client_id, amount, status)
                        VALUES (?, ?, ?, ?, ?, 'Held in escrow')
                    ")->execute([
                        $payId,
                        $msId,
                        $jobId,
                        $clientId,
                        $prop['proposed_rate']
                    ]);
                } else {
                    $conn->prepare("UPDATE project_milestones SET freelancer_id = ?, status = 'Posted' WHERE id = ?")
                        ->execute([$prop['freelancer_id'], $msId]);
                }

                // 5. Notification
                $conn->prepare("
                    INSERT INTO notifications (user_id, type, title, body, link)
                    VALUES (?, 'proposal', 'Proposal Accepted!', ?, ?)
                ")->execute([
                    $prop['freelancer_id'],
                    'Congratulations! Your proposal for "' . $prop['job_title'] . '" was accepted.',
                    '../freelancer/upload_comp_work.html?jobId=' . rawurlencode($jobId)
                ]);

                $conn->commit();
                flash('Proposal from ' . $prop['freelancer_name'] . ' accepted successfully! Contract is now In Progress and escrow is funded.', 'success');
                header('Location: work-approval.php?job=' . rawurlencode($jobId));
                exit;
            }
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            flash('Error accepting proposal: ' . $e->getMessage(), 'error');
        }
    }
}

// Fetch active job details
$job = null;
try {
    $st = $conn->prepare("SELECT * FROM jobs WHERE id = ? LIMIT 1");
    $st->execute([$jobId]);
    $job = $st->fetch();
} catch (\Throwable $e) {}

// Determine sort order
$sort = strtolower(trim((string)($_GET['sort'] ?? 'score')));
$orderBy = "u.score DESC, p.submitted_at DESC";
if ($sort === 'rating') {
    $orderBy = "u.rating DESC, u.score DESC";
} elseif ($sort === 'rate_asc') {
    $orderBy = "p.proposed_rate ASC";
} elseif ($sort === 'rate_desc') {
    $orderBy = "p.proposed_rate DESC";
} elseif ($sort === 'newest') {
    $orderBy = "p.submitted_at DESC";
}

// Fetch real proposals for this job
$proposals = [];
try {
    $st = $conn->prepare("
        SELECT p.*,
               u.name AS freelancer_name,
               u.title AS freelancer_title,
               u.hourly_rate AS freelancer_hourly_rate,
               u.rating AS freelancer_rating,
               u.score AS freelancer_score,
               u.bio AS freelancer_bio,
               u.location AS freelancer_location
        FROM proposals p
        INNER JOIN users u ON u.id = p.freelancer_id
        WHERE p.job_id = ?
        ORDER BY {$orderBy}
    ");
    $st->execute([$jobId]);
    $proposals = $st->fetchAll();
} catch (\Throwable $e) {}

$flashHtml = take_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Review Proposals - SkillMatch</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->

<main class="container">
  <?= $flashHtml ?>

  <?php if (!empty($clientJobs) && count($clientJobs) > 1): ?>
  <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div style="display:flex;align-items:center;gap:10px;">
      <label for="jobPicker" style="font-size:13.5px;font-weight:600;color:var(--muted)">Switch Project:</label>
      <select id="jobPicker" class="select" style="width:auto;padding:6px 12px;font-size:13.5px" onchange="window.location.href='review-proposal.php?job=' + encodeURIComponent(this.value)">
        <?php foreach ($clientJobs as $cj): ?>
          <option value="<?= e($cj['id']) ?>" <?= $cj['id'] === $jobId ? 'selected' : '' ?>>
            <?= e($cj['title']) ?> (<?= e($cj['status']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <a class="btn btn-outline" href="my-projects.php">&larr; Back to My Projects</a>
  </div>
  <?php endif; ?>

  <!-- Project Overview Card -->
  <?php if ($job): ?>
  <div class="card" style="padding:24px;display:flex;gap:20px;justify-content:space-between;flex-wrap:wrap;margin-bottom:24px">
    <div style="flex:1;min-width:280px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <h2 style="margin:0"><?= e($job['title']) ?></h2>
        <span class="badge <?= $job['status'] === 'Open' ? 'badge-match' : ($job['status'] === 'In Progress' ? 'badge-progress' : '') ?>">
          <?= e($job['status']) ?>
        </span>
      </div>
      <p style="color:var(--muted);font-size:14px;line-height:1.5;margin:0">
        <?= nl2br(e($job['description'] ?? 'No project description provided.')) ?>
      </p>
    </div>
    <div style="text-align:right">
      <div style="font-size:11px;letter-spacing:.6px;color:var(--muted)">BUDGET / CONTRACT</div>
      <div style="font-size:20px;font-weight:800;color:var(--primary, #2563eb)">
        <?= e($job['budget_label'] ?? $job['budget'] ?? '$' . ($job['budget_min'] ?? '0')) ?>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:4px">
        Category: <strong><?= ucfirst(e($job['category'] ?? 'General')) ?></strong>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="card" style="padding:20px;margin-bottom:24px">
    <p style="margin:0;color:var(--muted)">Project not found or no projects currently active.</p>
  </div>
  <?php endif; ?>

  <!-- Section Header & Sorting -->
  <div class="section-head" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px">
    <h2 style="margin:0">Proposals Received (<?= count($proposals) ?>)</h2>
    <div style="font-size:13.5px;display:flex;align-items:center;gap:8px">
      <label for="sortSelect" style="color:var(--muted)">Sort by:</label>
      <select id="sortSelect" class="select" style="width:auto;display:inline-block;padding:6px 12px" onchange="window.location.href='review-proposal.php?job=<?= rawurlencode($jobId) ?>&sort=' + encodeURIComponent(this.value)">
        <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Skill Verification Score</option>
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Client Rating</option>
        <option value="rate_asc" <?= $sort === 'rate_asc' ? 'selected' : '' ?>>Proposed Rate (Low to High)</option>
        <option value="rate_desc" <?= $sort === 'rate_desc' ? 'selected' : '' ?>>Proposed Rate (High to Low)</option>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
      </select>
    </div>
  </div>

  <!-- Proposal Cards List -->
  <?php if (empty($proposals)): ?>
  <div class="card" style="padding:48px;text-align:center;background:#fff;border-radius:10px">
    <div style="font-size:36px;margin-bottom:12px">📂</div>
    <h3 style="margin:0 0 8px;font-size:17px">No Proposals Received Yet</h3>
    <p style="color:var(--muted);font-size:14px;max-width:440px;margin:0 auto 18px">
      Freelancers haven't submitted bids for this project yet. You can also proactively browse verified specialists.
    </p>
    <a class="btn btn-primary" href="browse-freelancers.php">Browse &amp; Invite Freelancers</a>
  </div>
  <?php else: ?>
    <?php foreach ($proposals as $p): ?>
      <?php
        $flName = $p['freelancer_name'] ?? 'Specialist';
        $words  = explode(' ', trim($flName));
        $initials = strtoupper(substr($words[0] ?? 'S', 0, 1) . substr($words[1] ?? '', 0, 1));
        $rating = number_format((float)($p['freelancer_rating'] ?? 5.0), 1);
        $score  = (int)($p['freelancer_score'] ?? 90);
        $isAccepted = $p['status'] === 'Accepted';
      ?>
      <div class="card" style="margin-bottom:16px;border:1px solid <?= $isAccepted ? '#86efac' : '#e2e8f0' ?>;background:<?= $isAccepted ? '#f0fdf4' : '#fff' ?>">
        <div class="proposal">
          <!-- Freelancer Profile Cell -->
          <div>
            <div style="display:flex;gap:12px;align-items:center">
              <span class="avatar-lg" style="background:#e0e7ff;color:#2563eb;font-weight:700;display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:50%">
                <?= e($initials) ?>
              </span>
              <div>
                <strong style="font-size:16px"><?= e($flName) ?></strong>
                <div style="font-size:12px;color:var(--muted)"><?= e($p['freelancer_title'] ?? 'Verified Specialist') ?></div>
                <div class="rating" style="font-size:13px;color:#f59e0b;font-weight:600;margin-top:2px">
                  &#9733; <?= $rating ?> &bull; <?= e($p['freelancer_location'] ?? 'Remote') ?>
                </div>
              </div>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
              <span class="badge badge-match" title="Skill verification exam score">
                <?= $score ?>% Match (Score: <?= $score ?>)
              </span>
              <?php if ($isAccepted): ?>
                <span class="badge" style="background:#15803d;color:#fff;font-weight:700">Accepted Hire</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Proposal Terms & Cover Letter -->
          <div class="vdiv" style="flex:1;padding:0 20px">
            <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:6px">
              <h3 style="margin:0;font-size:18px;color:#1e293b">
                $<?= number_format((float)$p['proposed_rate'], 2) ?>
              </h3>
              <span style="font-size:12.5px;color:var(--muted)">
                Est. Turnaround: <strong><?= (int)$p['estimated_days'] ?> days</strong>
              </span>
            </div>
            <p style="color:#475569;font-size:13.5px;line-height:1.6;margin:0">
              <?= nl2br(e($p['cover_letter'])) ?>
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="p-actions" style="display:flex;flex-direction:column;gap:8px;justify-content:center;min-width:150px">
            <a class="btn btn-outline" href="freelancer-profile.php?id=<?= rawurlencode($p['freelancer_id']) ?>" style="text-align:center">View Profile</a>
            <a class="btn btn-outline" href="client-chat.php?freelancer=<?= rawurlencode($p['freelancer_id']) ?>" style="text-align:center">Message</a>

            <?php if ($isAccepted): ?>
              <a class="btn btn-primary" href="work-approval.php?job=<?= rawurlencode($jobId) ?>" style="text-align:center;background:#15803d;border-color:#15803d">
                View Project &rarr;
              </a>
            <?php else: ?>
              <form method="POST" action="review-proposal.php?job=<?= rawurlencode($jobId) ?>" style="margin:0" onsubmit="return confirm('Accept this proposal? This will fund the project milestone into escrow and hire <?= e(addslashes($flName)) ?>.');">
                <input type="hidden" name="action" value="accept">
                <input type="hidden" name="proposal_id" value="<?= e($p['id']) ?>">
                <button class="btn btn-primary" type="submit" style="width:100%">Accept Proposal</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<script src="../assets/js/api.js"></script>
<script src="../assets/js/client-workflow.js"></script>
</body>
</html>
