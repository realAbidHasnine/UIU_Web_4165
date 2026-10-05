<?php

/*
 * Shared client header.
 *
 * Replaces the block that used to be copy pasted into every page. Renders the
 * nav, the notification panel and the profile menu from the database, and
 * works out which nav item is current so pages do not have to mark it.
 *
 * Pages include it with:
 *   <?php require __DIR__ . '/partials/header.php'; ?>
 */

require_once __DIR__ . '/../includes/auth.php';

$headerUser  = db_user();
$currentPage = basename($_SERVER['PHP_SELF']);

// Pages that render their own messages have already drained the queue, so
// this is a no-op there and picks everything up everywhere else.
$headerFlash = take_flashes();

$navItems = [
    'Dashboard'          => 'client-dashboard.php',
    'My Projects'        => 'my-projects.php',
    'Browse Freelancers' => 'browse-freelancers.php',
    'Messages'           => 'client-chat.php',
];

/*
 * The three icon styles the stylesheet supports, plus the shapes they draw.
 *   green  = good news, a tick
 *   blue   = something new, a chat bubble or a monitor
 *   brown  = the client needs to do something, a warning triangle
 */
$notifShapes = [
    'green' => [
        'paths' => '<path d="M20 6L9 17l-5-5"/>',
        'width' => 3,
    ],
    'blue' => [
        'paths' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 1 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'width' => 2,
    ],
    'monitor' => [
        'paths' => '<rect x="2" y="7" width="20" height="14" rx="2"/>'
                 . '<path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'width' => 2,
    ],
    'brown' => [
        'paths' => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>'
                 . '<path d="M12 9v4M12 17h.01"/>',
        'width' => 2,
    ],
];

$notifColours = [
    'proposal_accepted'   => 'green',
    'milestone_paid'      => 'green',
    'project_published'   => 'green',
    'new_message'         => 'blue',
    'proposal_received'   => 'monitor',
    'new_job_match'       => 'monitor',
    'milestone_submitted' => 'monitor',
    'dispute_updated'     => 'monitor',
    'review_received'     => 'monitor',
    'action_required'     => 'brown',
    'email_verification'  => 'brown',
    'dispute_filed'       => 'brown',
];

function notif_colour($type) {
    global $notifColours;

    return $notifColours[$type] ?? 'monitor';
}

function notif_icon($type) {
    global $notifShapes;

    $colour = notif_colour($type);
    $shape  = $notifShapes[$colour];

    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"'
         . ' stroke="#fff" stroke-width="' . $shape['width'] . '">'
         . $shape['paths'] . '</svg>';
}

/* "2m ago", "1h ago", "Yesterday", "Oct 24" */
function notif_time($when) {
    $then   = strtotime($when);
    $mins   = (time() - $then) / 60;

    if ($mins < 1) {
        return 'just now';
    }

    if ($mins < 60) {
        return round($mins) . 'm ago';
    }

    if ($mins < 1440 && date('Y-m-d') === date('Y-m-d', $then)) {
        return round($mins / 60) . 'h ago';
    }

    if ($mins < 2880 && date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', $then)) {
        return 'Yesterday';
    }

    if (date('Y') === date('Y', $then)) {
        return date('M j', $then);
    }

    return date('M j, Y', $then);
}

$st = db()->prepare(
    'SELECT id, type, title, body, is_read, created_at
       FROM notifications
      WHERE user_id = ?
      ORDER BY created_at DESC
      LIMIT 4'
);
$st->execute([CURRENT_USER_ID]);
$notifications = $st->fetchAll();
?>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="../index.html">SkillMatch</a>

    <nav class="nav">
      <?php foreach ($navItems as $label => $href): ?>
      <a class="<?= $currentPage === $href ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="top-actions">
      <details class="notif-wrap">
        <summary title="Notifications" aria-label="Notifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
          </svg>
        </summary>

        <div class="notif-panel" role="dialog" aria-label="Notifications">
          <div class="notif-head">
            <h2>Notifications</h2>
            <a class="notif-link" href="mark-notifications-read.php">Mark all as read</a>
          </div>

          <?php if (!$notifications): ?>
          <div class="notif-row">
            <span class="notif-dot placeholder" aria-hidden="true"></span>
            <div class="notif-text">
              <div class="notif-title">Nothing new</div>
              <div class="notif-desc">You are all caught up.</div>
            </div>
          </div>
          <?php endif; ?>

          <?php foreach ($notifications as $item): ?>
          <div class="notif-row<?= $item['is_read'] ? '' : ' unread' ?>">
            <span class="notif-dot<?= $item['is_read'] ? ' placeholder' : '' ?>" aria-hidden="true"></span>
            <span class="notif-icon <?= notif_colour($item['type']) ?>" aria-hidden="true">
              <?= notif_icon($item['type']) ?>
            </span>
            <div class="notif-text">
              <div class="notif-title"><?= e($item['title']) ?></div>
              <div class="notif-desc"><?= e($item['body']) ?></div>
            </div>
            <span class="notif-time"><?= notif_time($item['created_at']) ?></span>
          </div>
          <?php endforeach; ?>

          <div class="notif-foot">
            <span class="notif-link">View all activity</span>
          </div>
        </div>
      </details>

      <details class="profile-wrap">
        <summary>
          <img class="avatar" src="<?= e($headerUser['avatar_url']) ?>" alt="<?= e(display_name($headerUser)) ?>">
        </summary>

        <div class="profile-menu">
          <div class="profile-topbox">
            <img src="<?= e($headerUser['avatar_url']) ?>" alt="<?= e(display_name($headerUser)) ?>">
            <div>
              <div class="profile-name">
                <?= e(display_name($headerUser)) ?>
                <span class="profile-role">CLIENT</span>
              </div>
              <div class="profile-email"><?= e($headerUser['email']) ?></div>
            </div>
          </div>

          <div class="menu-div"></div>
          <nav class="menu-list">
            <a href="client-profile.php">My Profile</a>
            <a href="create-client-account.php">Account Settings</a>
            <a href="billing-payments.php">Billing &amp; Payments</a>
          </nav>

          <div class="menu-div"></div>
          <nav class="menu-list">
            <a class="logout" href="../guest/login.html">Log Out</a>
          </nav>
        </div>
      </details>
    </div>
</div>
  </header>
<?= $headerFlash ?>