<?php

require_once __DIR__ . '/includes/auth.php';
$flashHtml = take_flashes();

$conn = db();
$me   = current_client_id();

$tabs = [
    ''            => 'All',
    'open'        => 'Open',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
];

$filter = $_GET['status'] ?? '';

if (!array_key_exists($filter, $tabs)) {
    $filter = '';
}

// How each status maps onto a badge, a colour and a word.
$looks = [
    'open' => [
        'label' => 'Open',
        'class' => 'badge-open',
        'style' => '',
    ],
    'in progress' => [
        'label' => 'In Progress',
        'class' => '',
        'style' => 'color:#b45309;border-color:#fed7aa;background:#fffbeb',
    ],
    'awaiting approval' => [
        'label' => 'Awaiting Approval',
        'class' => 'badge-await',
        'style' => '',
    ],
    'completed' => [
        'label' => 'Completed',
        'class' => 'badge-done',
        'style' => '',
    ],
    'disputed' => [
        'label' => 'Disputed',
        'class' => '',
        'style' => 'background:#fff;border:1px solid #e8b4b4;color:#b91c1c',
    ],
    'closed' => [
        'label' => 'Closed',
        'class' => 'badge-await',
        'style' => '',
    ],
];

$where  = 'j.client_id = ?';
$params = [$me];

if ($filter === 'open') {
    $where .= " AND j.status = 'Open'";
} elseif ($filter === 'in_progress') {
    $where .= " AND j.status IN ('In Progress', 'Disputed')";
} elseif ($filter === 'completed') {
    $where .= " AND j.status IN ('Completed', 'Closed')";
}

// Proposal count and who got hired
$counts = '(SELECT COUNT(*) FROM proposals WHERE job_id = j.id)';
$hired  = "(SELECT u.name FROM proposals p JOIN users u ON u.id = p.freelancer_id WHERE p.job_id = j.id AND p.status = 'Accepted' LIMIT 1)";

$sql = "SELECT j.*, $counts AS proposal_count, $hired AS hired_name
          FROM jobs j
         WHERE $where
         ORDER BY j.created_at DESC";

$projects = [];
try {
    $st = $conn->prepare($sql);
    $st->execute($params);
    $projects = $st->fetchAll();
} catch (\Throwable $e) {}

$milestoneSql = 'SELECT label AS title, amount, status, COALESCE(paid_at, approved_at, created_at) AS paid_at
                   FROM project_milestones
                  WHERE job_id = ?
                  ORDER BY created_at ASC';

// Short date for the timeline, e.g. "Aug 1, 2026".
function when($date) {
    return $date ? date('M j, Y', strtotime($date)) : '';
}

// Where the primary button should point for each status.
function action_for($project, $count) {
    $st = strtolower((string) ($project['status'] ?? ''));
    if ($st === 'disputed') {
        return '<a class="btn btn-primary" href="dispute-status.php">View dispute</a>';
    }

    if ($st === 'in progress') {
        return '<a class="btn btn-primary" href="work-approval.php?job=' . rawurlencode($project['id']) . '">Review Work</a>';
    }

    if ($count > 0) {
        return '<a class="btn btn-outline" href="review-proposal.php?job=' . rawurlencode($project['id']) . '">View Proposals (' . $count . ')</a>';
    }

    return '<span class="btn btn-outline" aria-disabled="true">View details</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Projects - SkillMatch</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="../assets/css/global.css">
</head>
<body>
<!-- header: partials/header.php --><?php require __DIR__ . '/partials/header.php'; ?><!-- end header -->
<main class="container">
<?= $flashHtml ?>
<div style="display:flex;justify-content:space-between;align-items:end;gap:16px;flex-wrap:wrap">
  <div>
    <h1 class="page-title">My Projects</h1>
    <p class="page-sub">Manage your posted jobs and active contracts.</p>
  </div>
  <input class="search" placeholder="Search projects...">
</div>
<div class="tabs"><?php foreach ($tabs as $key => $label): ?>
<a class="<?= $filter === $key ? 'active' : '' ?>"
   href="my-projects.php<?= $key === '' ? '' : '?status=' . $key ?>"><?= $label ?></a><?php endforeach; ?></div>
<?php if (!$projects): ?>
<div class="card" style="text-align:center;padding:36px 20px">
  <p style="color:var(--muted);margin:0 0 16px">No projects in this view yet.</p>
  <a class="btn btn-primary" href="post-project-details.php">Post a project</a>
</div>
<?php endif; ?>
<?php
foreach ($projects as $project):
    $stStr  = strtolower((string) ($project['status'] ?? 'open'));
    $look   = $looks[$stStr] ?? ['label' => ucfirst($stStr), 'class' => 'badge-open', 'style' => ''];
    $count  = (int) ($project['proposal_count'] ?? 0);
    $hired  = trim((string) ($project['hired_name'] ?? ''));
    $budget = is_numeric($project['budget'] ?? '') ? money($project['budget']) : ('$' . ($project['budget'] ?? '0'));

    $meta = [$budget, $count . ' proposal' . ($count === 1 ? '' : 's')];

    if ($hired !== '') {
        $meta[] = 'Freelancer: ' . $hired;
    }

    $milestones = [];
    try {
        $stM = $conn->prepare($milestoneSql);
        $stM->execute([$project['id']]);
        $milestones = $stM->fetchAll();
    } catch (\Throwable $e) {}
?>
<div class="card" style="margin-bottom:20px">
  <div class="list-row">
    <div>
      <p class="row-title">
        <?= e($project['title']) ?>
        <span class="badge <?= $look['class'] ?>"
              style="<?= $look['style'] ?>"><?= $look['label'] ?></span>
      </p>
      <div class="row-meta">
        <?php foreach ($meta as $item): ?>
        <span><?= e($item) ?></span><?php endforeach; ?>
      </div>
    </div>
    <?= action_for($project, $count) ?>
  </div>
  <details class="timeline">
    <summary>View project timeline</summary>
    <ol>
      <?php if (!empty($project['created_at'])): ?>
      <li class="done">
        <strong>Posted project</strong> <span class="t-date"><?= when($project['created_at']) ?></span><br><?= e($budget) ?> budget.
      </li>
      <?php endif; ?>
      <?php if ($hired !== ''): ?>
      <li class="done">
        <strong>Hired <?= e($hired) ?></strong>
      </li>
      <?php endif; ?>
      <?php foreach ($milestones as $milestone): ?>
      <?php $settled = in_array(strtolower((string) $milestone['status']), ['paid', 'approved', 'released']) ?>
      <li class="<?= $settled ? 'done' : 'pending' ?>">
        <strong><?= e($milestone['title']) ?></strong> <span class="t-date"><?= when($milestone['paid_at']) ?></span><br>
        <?= money($milestone['amount']) ?> &middot;
        <?= e(ucfirst($milestone['status'])) ?>
      </li>
      <?php endforeach; ?>
    </ol>
  </details>
</div>
<?php endforeach; ?>
<div style="display:flex;justify-content:space-between;align-items:center;color:var(--muted);font-size:14px;margin-top:26px">
  <span><?= count($projects) ?> project<?= count($projects) === 1 ? '' : 's' ?></span> <a href="post-project-details.php" style="color:var(--primary-ink);font-weight:600">Post another &rsaquo;</a>
</div>
</main>
<script src="../assets/js/api.js"></script>
<script src="../assets/js/client-workflow.js"></script>
</body>
</html>