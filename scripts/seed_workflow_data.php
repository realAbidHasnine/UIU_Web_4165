<?php
/**
 * SkillMatch — workflow seed data.
 *
 * Populates the tables added in schema_milestones.sql with a coherent story so
 * every screen has something real to show:
 *
 *   job-18 "Real-Time Chat System" (c-201 + f-101)
 *     ms-18-1  Paid       escrow released, review left by the client
 *     ms-18-2  Submitted  waiting for the client to approve or request changes
 *     ms-18-3  Posted     escrow funded, work not started
 *
 *   job-15 "UX Research & Accessibility Audit" (c-203 + fl-1)
 *     ms-15-1  Disputed   escrow frozen by an open dispute awaiting a response
 *     ms-15-2  Posted     escrow funded
 *
 *   Admin approval queue: 2 pending freelancers, 1 pending client,
 *   plus one approved and one rejected application for history.
 *
 * Escrow fee is 3% of the milestone amount, matching the platform fee shown
 * elsewhere in the UI.
 *
 * Usage:  php scripts/seed_workflow_data.php
 * Re-running is safe: it stops as soon as milestones already exist.
 */

declare(strict_types=1);

require_once __DIR__ . '/../api/config/db.php';

const ESCROW_RATE = 0.03;
const SEED_PASSWORD = 'password123';

function escrowFee(float $amount): float
{
    return round($amount * ESCROW_RATE, 2);
}

/**
 * Insert a notification, ignoring a null recipient.
 */
function notify(PDO $pdo, ?string $userId, string $type, string $title, string $body, string $link = '', bool $isRead = false): void
{
    if ($userId === null || $userId === '') {
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link, is_read)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $body, $link, $isRead ? 1 : 0]);
}

$existing = (int)$pdo->query("SELECT COUNT(*) FROM project_milestones")->fetchColumn();
if ($existing > 0) {
    echo "project_milestones already has {$existing} row(s) — nothing to do.\n";
    echo "Delete the seeded rows first if you really want to re-run this.\n";
    exit(0);
}

echo "Seeding SkillMatch workflow data...\n";

$pdo->beginTransaction();

try {
    // ---------------------------------------------------------------- people
    $passwordHash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT);
    $hashStmt = $pdo->prepare("INSERT INTO users (id, email, password, role, name, title, company, hourly_rate, bio, status, score, rating, location)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $people = [
        // id,        email,                             role,        name,             title,                    company,               rate,  status,      score, rating, location,      bio
        ['fl-20', 'nina.okafor@example.com',    'FREELANCER', 'Nina Okafor',    'Product Designer',       'Independent',              48.00, 'Suspended',  88, 4.70, 'Lagos, Nigeria',    'Product designer with 6 years across fintech and health products.'],
        ['fl-21', 'rahim.chowdhury@example.com', 'FREELANCER', 'Rahim Chowdhury', 'Backend Engineer',      'Independent',              62.00, 'Suspended',  91, 4.85, 'Dhaka, Bangladesh',  'Node and Laravel specialist; previously led a payments platform rebuild.'],
        ['c-210', 'ayesha.siddiqua@example.com', 'CLIENT',     'Ayesha Siddiqua', 'Head of Digital',        'Northwind Retail',          0,    'Suspended',  72, 0.00, 'Dubai, UAE',         'Leading digital products for a 40-store retail group.'],
        ['fl-22', 'marcus.bell@example.com',     'FREELANCER', 'Marcus Bell',    'React Engineer',         'Pixelcraft Studio',        74.00, 'Active',     97, 4.95, 'Austin, USA',        'React and TypeScript specialist, previously at a fintech scale-up.'],
        ['fl-23', 'lena.fischer@example.com',    'FREELANCER', 'Lena Fischer',   'Data Analyst',           'Independent',              39.00, 'Suspended',  54, 3.20, 'Berlin, Germany',    'Analyst focused on marketing attribution.'],
    ];

    foreach ($people as [$id, $email, $role, $name, $title, $company, $rate, $status, $score, $rating, $location, $bio]) {
        $hashStmt->execute([$id, $email, $passwordHash, $role, $name, $title, $company, $rate, $bio, $status, $score, $rating, $location]);
    }
    echo "  + 5 accounts (3 pending, 1 approved, 1 rejected)\n";

    $skillsStmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_name) VALUES (?, ?)");
    foreach ([
        ['fl-20', 'Figma'], ['fl-20', 'Design Systems'], ['fl-20', 'Prototyping'],
        ['fl-21', 'Node.js'], ['fl-21', 'Laravel'], ['fl-21', 'PostgreSQL'],
        ['c-210', 'Project Management'],
        ['fl-22', 'React'], ['fl-22', 'TypeScript'], ['fl-22', 'Next.js'],
        ['fl-23', 'SQL'], ['fl-23', 'Tableau'],
    ] as [$userId, $skill]) {
        $skillsStmt->execute([$userId, $skill]);
    }

    // ------------------------------------------------------- approval queue
    $approvalStmt = $pdo->prepare("INSERT INTO approvals (id, user_id, type, status, portfolio_url, github_url, skills, reject_reason, reviewed_by, reviewed_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $approvalStmt->execute(['apr-1', 'fl-20', 'FREELANCER', 'Pending', 'https://nina.design', 'https://github.com/ninaokafor', 'Figma, Design Systems, Prototyping', null, null, null]);
    $approvalStmt->execute(['apr-2', 'fl-21', 'FREELANCER', 'Pending', 'https://rahim.dev', 'https://github.com/rahimc', 'Node.js, Laravel, PostgreSQL', null, null, null]);
    $approvalStmt->execute(['apr-3', 'c-210', 'CLIENT', 'Pending', null, null, 'Project Management', null, null, null]);
    $approvalStmt->execute(['apr-4', 'fl-22', 'FREELANCER', 'Approved', 'https://marcusbell.dev', 'https://github.com/marcusbell', 'React, TypeScript, Next.js', null, 'u-admin', '2026-08-14 10:12:00']);
    $approvalStmt->execute(['apr-5', 'fl-23', 'FREELANCER', 'Rejected', 'https://lena.data', null, 'SQL, Tableau', 'Portfolio did not show relevant analytics work', 'u-admin', '2026-08-02 16:40:00']);
    echo "  + 5 approval requests (3 pending, 1 approved, 1 rejected)\n";

    // ------------------------------------------------- job-18: happy path
    $pdo->prepare("UPDATE jobs SET hired_freelancer_id = 'f-101', status = 'In Progress' WHERE id = 'job-18'")->execute();
    $pdo->prepare("INSERT INTO proposals (id, job_id, freelancer_id, proposed_rate, estimated_days, cover_letter, status, submitted_at)
                   VALUES ('prop-18', 'job-18', 'f-101', 2400.00, 21,
                           'I have built realtime messaging twice before and would use Socket.IO with a Redis pub/sub layer so it scales past a single node.',
                           'Accepted', '2026-08-20 09:30:00')")->execute();

    $msStmt = $pdo->prepare("INSERT INTO project_milestones
                             (id, job_id, client_id, freelancer_id, label, description, amount, escrow_fee, status, due_date, submitted_at, approved_at, paid_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $payStmt = $pdo->prepare("INSERT INTO payments
                              (id, milestone_id, job_id, client_id, freelancer_id, amount, escrow_fee, total, status, receipt_id, released_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // --- job-18 milestone 1: delivered and paid
    $msStmt->execute(['ms-18-1', 'job-18', 'c-201', 'f-101', 'Design the chat protocol and data model',
        'Message envelope schema, presence handling and the delivery/ack contract, agreed in writing before any UI work starts.',
        400.00, escrowFee(400.00), 'Paid', '2026-08-28', '2026-08-27 15:10:00', '2026-08-28 11:02:00', '2026-08-28 11:02:00']);
    $payStmt->execute(['pay-18-1', 'ms-18-1', 'job-18', 'c-201', 'f-101', 400.00, escrowFee(400.00), 412.00, 'Released', 'RCP-7A41C9', '2026-08-28 11:02:00']);

    // --- job-18 milestone 2: submitted, awaiting the client
    $msStmt->execute(['ms-18-2', 'job-18', 'c-201', 'f-101', 'Realtime message delivery over WebSockets',
        'Socket.IO gateway with Redis pub/sub, typing indicators, read receipts and automatic reconnection.',
        900.00, escrowFee(900.00), 'Submitted', '2026-09-20', '2026-09-18 17:45:00', null, null]);
    $payStmt->execute(['pay-18-2', 'ms-18-2', 'job-18', 'c-201', 'f-101', 900.00, escrowFee(900.00), 927.00, 'Held in escrow', null, null]);

    // --- job-18 milestone 3: funded, not started
    $msStmt->execute(['ms-18-3', 'job-18', 'c-201', 'f-101', 'Client integration, tests and handover',
        'Wire the existing React app to the gateway, add integration tests and document the deployment steps.',
        700.00, escrowFee(700.00), 'Posted', '2026-10-05', null, null, null]);
    $payStmt->execute(['pay-18-3', 'ms-18-3', 'job-18', 'c-201', 'f-101', 700.00, escrowFee(700.00), 721.00, 'Held in escrow', null, null]);

    // ------------------------------------------------- job-15: a live dispute
    $pdo->prepare("UPDATE jobs SET hired_freelancer_id = 'fl-1', status = 'In Progress' WHERE id = 'job-15'")->execute();
    $pdo->prepare("INSERT INTO proposals (id, job_id, freelancer_id, proposed_rate, estimated_days, cover_letter, status, submitted_at)
                   VALUES ('prop-15', 'job-15', 'fl-1', 550.00, 14,
                           'I run heuristic reviews and axe-core audits as part of every handover, with a written remediation backlog.',
                           'Accepted', '2026-08-25 13:05:00')")->execute();

    $msStmt->execute(['ms-15-1', 'job-15', 'c-203', 'fl-1', 'Heuristic evaluation of the five key flows',
        'Evaluate signup, checkout, search, support and settings against Nielsen heuristics, with severity-rated findings.',
        300.00, escrowFee(300.00), 'Disputed', '2026-09-12', '2026-09-10 10:20:00', null, null]);
    $payStmt->execute(['pay-15-1', 'ms-15-1', 'job-15', 'c-203', 'fl-1', 300.00, escrowFee(300.00), 309.00, 'Held in escrow', null, null]);

    $msStmt->execute(['ms-15-2', 'job-15', 'c-203', 'fl-1', 'Accessibility remediation plan and retest',
        'Prioritised fix list with effort estimates, then a retest to confirm each finding is resolved.',
        250.00, escrowFee(250.00), 'Posted', '2026-09-30', null, null, null]);
    $payStmt->execute(['pay-15-2', 'ms-15-2', 'job-15', 'c-203', 'fl-1', 250.00, escrowFee(250.00), 257.50, 'Held in escrow', null, null]);

    $pdo->prepare("INSERT INTO disputes
                     (id, milestone_id, job_id, client_id, freelancer_id, reason, description,
                      desired_outcome, partial_refund_amount, status, response_due_at)
                   VALUES ('DIS-2026-0001', 'ms-15-1', 'job-15', 'c-203', 'fl-1', 'Work quality',
                           'The report lists the checkout flow as low risk, but two of the five findings are screen-reader blockers that our audit flagged last quarter. We need this redone before we can share it with the board.',
                           'Request a revision', NULL, 'Under review', '2026-09-29 10:20:00')")->execute();

    echo "  + 5 milestones, 5 payments, 1 dispute\n";

    // -------------------------------------------------------------- review
    $pdo->prepare("INSERT INTO reviews
                     (id, job_id, milestone_id, client_id, freelancer_id, communication, quality, timeliness, overall, comment, satisfaction_comment)
                   VALUES ('rev-1', 'job-18', 'ms-18-1', 'c-201', 'f-101', 5, 5, 5, 5.00,
                           'Delivered ahead of the milestone date and the protocol document answered every question we raised in the review call.',
                           'Exactly the level of detail we needed before committing to the build phase.')")->execute();
    echo "  + 1 review\n";

    // ------------------------------------------------------- notifications
    notify($pdo, 'c-201', 'milestone', 'Milestone ready for review',
        'f-101 submitted "Realtime message delivery over WebSockets" for job-18. Approve it or request changes.',
        '../client/work-approval.html', false);
    notify($pdo, 'c-201', 'payment', 'Payment released',
        'You released $400.00 to Sarah Jenkins for "Design the chat protocol and data model".',
        '../client/billing-payments.html', true);
    notify($pdo, 'c-201', 'milestone', 'Escrow funded',
        'You funded $721.00 for "Client integration, tests and handover". It stays in escrow until you approve the work.',
        '../client/milestones.html', true);
    notify($pdo, 'f-101', 'payment', 'You were paid $400.00',
        'Abid Hasnine approved "Design the chat protocol and data model" on Real-Time Chat System.',
        '../freelancer/freelancer_projects.html', true);
    notify($pdo, 'f-101', 'milestone', 'Changes requested',
        'c-203 asked for a redo on the heuristic evaluation: two findings were under-rated. Respond within 3 days.',
        '../freelancer/freelancer_projects.html', false);
    notify($pdo, 'f-101', 'review', 'You received a 5.00 review',
        'Abid Hasnine left 5 stars for "Design the chat protocol and data model".',
        '../freelancer/freelancer_projects.html', true);
    notify($pdo, 'fl-1', 'dispute', 'Dispute filed against your milestone',
        'c-203 opened dispute DIS-2026-0001 (Work quality). The escrow of $300.00 is frozen until an administrator reviews it.',
        '../freelancer/freelancer_projects.html', false);
    notify($pdo, 'u-admin', 'dispute', 'New dispute awaiting review',
        'DIS-2026-0001 was filed on job-15 by c-203 against fl-1.',
        '../Admin/html/disputes.html', false);
    echo "  + 8 notifications\n";

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nDone. Current state:\n";
foreach ([
    'users', 'jobs', 'proposals', 'project_milestones', 'payments',
    'disputes', 'reviews', 'notifications', 'approvals', 'reports',
] as $table) {
    $n = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    printf("  %-20s %d\n", $table, $n);
}
echo "\nSign in with any of these (password: " . SEED_PASSWORD . ")\n";
foreach ($pdo->query("SELECT id, name, email, role FROM users WHERE id IN ('c-201','f-101','fl-1','u-admin','fl-20') ORDER BY id") as $u) {
    printf("  %-9s %-24s %s\n", $u['role'], $u['email'], $u['name']);
}
