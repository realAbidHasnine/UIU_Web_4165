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
    'in_progress' => [
        'label' => 'In Progress',
        'class' => '',
        'style' => 'color:#b45309;border-color:#fed7aa;background:#fffbeb',
    ],
    'awaiting_approval' => [
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
    'draft' => [
        'label' => 'Draft',
        'class' => 'badge-await',
        'style' => '',
    ],
    'cancelled' => [
        'label' => 'Cancelled',
        'class' => 'badge-await',
        'style' => '',
    ],
];

$where  = 'p.client_id = ?';
$params = [$me];

if ($filter === 'open') {
    $where .= " AND p.status = 'open'";
} elseif ($filter === 'in_progress') {
    $where .= " AND p.status IN ('in_progress','awaiting_approval','disputed')";
} elseif ($filter === 'completed') {
    $where .= " AND p.status = 'completed'";
}

// Proposal count and who got hired, both pulled in per row to avoid N+1.
$counts = '(SELECT COUNT(*) FROM proposals
             WHERE project_id = p.id)';

$hired = "(SELECT TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))
             FROM proposals
             JOIN client_profiles ON client_profiles.user_id = proposals.freelancer_id
            WHERE proposals.project_id = p.id AND proposals.status = 'accepted'
            LIMIT 1)";

$sql = "SELECT p.*, $counts AS proposal_count, $hired AS hired_name
          FROM projects p
         WHERE $where
         ORDER BY COALESCE(p.published_at, p.hired_at, '1970-01-01') DESC, p.id DESC";

$st = $conn->prepare($sql);
$st->execute($params);
$projects = $st->fetchAll();

$milestoneSql = 'SELECT title, amount, status, paid_at
                   FROM milestones
                  WHERE project_id = ?
                  ORDER BY position';

// Short date for the timeline, e.g. "Aug 1, 2026".
function when($date) {
    return $date ? date('M j, Y', strtotime($date)) : '';
}

// Where the primary button should point for each status.
function action_for($project, $count) {
    if ($project['status'] === 'draft') {
        return '<a class="btn btn-primary" href="post-project.php?project_id='
             . (int) $project['id'] . '">Resume</a>
';
    }

    if ($project['status'] === 'disputed') {
        return '<a class="btn btn-primary" href="dispute-status.php">View dispute</a>
';
    }

    if ($project['status'] === 'awaiting_approval') {
        return '<a class="btn btn-primary" href="work-approval.php">Review Work</a>
';
    }

    if ($count > 0) {
        return '<a class="btn btn-outline" href="review-proposal.php">View details</a>
';
    }

    return '<span class="btn btn-outline" aria-disabled="true">View details</span>
';
}
?>
<!DOCTYPE html>
<html lang="en">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width,initial-scale=1"> <title>My Projects - SkillMatch</title> <link rel="stylesheet" href="styles.css"> <link rel="stylesheet" href="../assets/css/global.css">
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

    $status = $project['status'];
    $look   = $looks[$status] ?? ['label' => ucfirst($status), 'class' => '', 'style' => ''];
    $count  = (int) $project['proposal_count'];
    $hired  = trim($project['hired_name']);

    if ($project['budget_type'] === 'hourly') {
        $budget = money($project['budget_min']) . ' - '
                . money($project['budget_max']) . ' / hr';
    } else {
        $budget = money($project['budget_max']) . ' Fixed';
    }

    $meta = [$budget, $count . ' proposal' . ($count === 1 ? '' : 's')];

    if ($hired !== '') {
        $meta[] = 'Freelancer: ' . $hired;
    }

    $st = $conn->prepare($milestoneSql);
    $st->execute([$project['id']]);
    $milestones = $st->fetchAll();
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
<span><?= e($item) ?></span><?php endforeach; ?></div>
</div>
    <?= action_for($project, $count) ?>
</div>
<details class="timeline">
<summary>View project timeline</summary>
<ol>
    <?php if ($project['published_at']): ?>
<li class="done">
      <strong>Posted project</strong> <span class="t-date"><?= when($project['published_at']) ?></span><br><?= e($budget) ?> budget.</li>
    <?php endif; ?>
    <?php if ($project['hired_at']): ?>
<li class="done">
      <strong>Hired <?= e($hired === '' ? 'a freelancer' : $hired) ?></strong> <span class="t-date"><?= when($project['hired_at']) ?></span>
</li>
    <?php endif; ?>
    <?php foreach ($milestones as $milestone): ?>
    <?php $settled = in_array($milestone['status'], ['paid', 'approved']) ?>
<li class="<?= $settled ? 'done' : 'pending' ?>">
      <strong><?= e($milestone['title']) ?></strong> <span class="t-date"><?= when($milestone['paid_at']) ?></span><br>
      <?= money($milestone['amount']) ?> &middot;
      <?= e(ucfirst(str_replace('_', ' ', $milestone['status']))) ?>
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
</body>
</html>