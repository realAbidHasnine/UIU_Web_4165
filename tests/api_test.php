<?php
/**
 * SkillMatch — end-to-end endpoint smoke test.
 * Run from CLI:  php tests/api_test.php
 * Requires Apache serving http://localhost/UIU_Web_4165
 */

$BASE = 'http://localhost/UIU_Web_4165/api';

$pass = 0;
$fail = 0;
$failures = [];

function req(string $method, string $path, array $body = null, string $token = null): array
{
    global $BASE;
    $ch = curl_init($BASE . $path);
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 25,
        // Collection endpoints are physical directories, so Apache answers the
        // slashless form with a 301 to the trailing-slash form. Browsers follow
        // this transparently; the harness does the same.
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'raw' => $raw, 'err' => $err, 'json' => json_decode($raw, true)];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($ok) {
        $pass++;
        printf("  [PASS] %s\n", $label);
    } else {
        $fail++;
        $failures[] = $label . ($detail ? " :: $detail" : '');
        printf("  [FAIL] %s  %s\n", $label, $detail);
    }
}

function section(string $name): void
{
    echo "\n=== $name ===\n";
}

// ---------------------------------------------------------------------------
// Snapshot / restore, so the suite can be run repeatedly without polluting the
// seeded demo data. Everything it creates is tagged with an "E2E" marker.
// ---------------------------------------------------------------------------
require_once __DIR__ . '/../api/config/db.php';

$RUN_START = date('Y-m-d H:i:s');

/** Capture the rows the suite overwrites so they can be put back afterwards. */
function takeSnapshot(): array
{
    global $pdo;

    $bio = $pdo->query("SELECT bio FROM users WHERE id = 'f-101'")->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, is_read FROM notifications WHERE user_id = 'c-201'");
    $stmt->execute();
    $readFlags = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    return ['bio' => $bio === false ? null : $bio, 'notifications' => $readFlags];
}

/** Delete everything this run created and undo the rows it edited. */
function cleanup(array $snapshot): void
{
    global $pdo, $RUN_START;
    global $pass, $fail;

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $deletes = [
        'portfolio_items'    => "DELETE FROM portfolio_items WHERE title LIKE 'E2E%'",
        'chat_messages'      => "DELETE FROM chat_messages WHERE text LIKE 'E2E%'",
        'proposals'          => "DELETE FROM proposals WHERE cover_letter LIKE 'E2E%'",
        'skill_questions'    => "DELETE FROM skill_questions WHERE question LIKE 'E2E%'",
        'skill_categories'   => "DELETE FROM skill_categories WHERE name LIKE 'E2E%'",
        'reports'            => "DELETE FROM reports WHERE created_at >= '$RUN_START'",
        'test_results'       => "DELETE FROM test_results WHERE freelancer_id = 'f-101' AND submitted_at >= '$RUN_START'",
        'sessions'           => "DELETE FROM sessions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'e2e.tester.%@example.com')",
        'users'              => "DELETE FROM users WHERE email LIKE 'e2e.tester.%@example.com'",
    ];

    foreach ($deletes as $table => $sql) {
        $pdo->exec($sql);
    }

    // Orphan questions whose category the suite just removed.
    $pdo->exec('DELETE FROM skill_questions WHERE category NOT IN (SELECT id FROM skill_categories)');
    $pdo->exec('UPDATE skill_categories c SET question_count = (SELECT COUNT(*) FROM skill_questions q WHERE q.category = c.id)');

    // Sync chat threads last message
    $pdo->exec('UPDATE chat_threads t SET last_message = COALESCE((SELECT text FROM chat_messages m WHERE m.thread_id = t.id ORDER BY m.created_at DESC LIMIT 1), "")');

    // Put back what the suite overwrote.
    $pdo->prepare('UPDATE users SET bio = ? WHERE id = \'f-101\'')->execute([$snapshot['bio']]);

    $unread = $pdo->prepare('UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = \'c-201\'');
    foreach ($snapshot['notifications'] as $id => $isRead) {
        $unread->execute([(int)$isRead, $id]);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    check('cleanup removed all E2E artifacts', true);
}

// ---------------------------------------------------------------- auth
section('AUTH');
$fl = req('POST', '/auth/login', ['email' => 'sarah.jenkins@example.com', 'password' => 'password123', 'role' => 'FREELANCER']);
check('freelancer login 200', $fl['code'] === 200, "code={$fl['code']} {$fl['err']}");
$FL = $fl['json']['token'] ?? null;
check('login returns token', !empty($FL));
check('login returns skills array', isset($fl['json']['user']['skills']) && is_array($fl['json']['user']['skills']));
check('password never echoed', !isset($fl['json']['user']['password']) && strpos((string)$fl['raw'], 'password') === false);

$cl = req('POST', '/auth/login', ['email' => 'abid.hasina@flow.com', 'password' => 'password123', 'role' => 'CLIENT']);
check('client login 200', $cl['code'] === 200, "code={$cl['code']}");
$CL = $cl['json']['token'] ?? null;
check('client token issued', !empty($CL));

$ad = req('POST', '/auth/login', ['email' => 'admin@skillmatch.com', 'password' => 'admin123', 'role' => 'ADMIN']);
check('admin login 200', $ad['code'] === 200, "code={$ad['code']} raw=" . substr((string)$ad['raw'], 0, 200));
$AD = $ad['json']['token'] ?? null;
check('admin token issued', !empty($AD));

$bad = req('POST', '/auth/login', ['email' => 'sarah.jenkins@example.com', 'password' => 'wrongpass', 'role' => 'FREELANCER']);
check('wrong password 401', $bad['code'] === 401, "code={$bad['code']}");

$wrongRole = req('POST', '/auth/login', ['email' => 'abid.hasina@flow.com', 'password' => 'password123', 'role' => 'FREELANCER']);
check('role mismatch rejected 401', $wrongRole['code'] === 401, "code={$wrongRole['code']}");

$noTok = req('GET', '/freelancers/me');
check('unauthenticated /freelancers/me = 401 (no demo fallback)', $noTok['code'] === 401, "code={$noTok['code']}");

$badTok = req('GET', '/freelancers/me', null, 'deadbeef' . str_repeat('0', 56));
check('bogus token = 401', $badTok['code'] === 401, "code={$badTok['code']}");

// Capture the rows the suite is about to overwrite (f-101's bio, and which of
// c-201's notifications are unread) so cleanup() can restore them.
$SNAPSHOT = takeSnapshot();

// ------------------------------------------------------------- register
section('REGISTER');
$email = 'e2e.tester.' . time() . '@example.com';
$reg = req('POST', '/auth/register', ['name' => 'E2E Tester', 'email' => $email, 'password' => 'testpass123', 'role' => 'CLIENT', 'company' => 'E2E Co']);
check('register 201', $reg['code'] === 201, "code={$reg['code']} " . substr((string)$reg['raw'], 0, 200));
$NEW = $reg['json']['token'] ?? null;
check('register issues token', !empty($NEW));
$newId = $reg['json']['user']['id'] ?? null;

$dupe = req('POST', '/auth/register', ['name' => 'Dup', 'email' => $email, 'password' => 'testpass123', 'role' => 'CLIENT']);
check('duplicate email 409', $dupe['code'] === 409, "code={$dupe['code']}");

$shortPw = req('POST', '/auth/register', ['name' => 'X', 'email' => 'x' . time() . '@e.com', 'password' => '123', 'role' => 'FREELANCER']);
check('short password 400', $shortPw['code'] === 400, "code={$shortPw['code']}");

$badEmail = req('POST', '/auth/register', ['name' => 'Y', 'email' => 'not-an-email', 'password' => 'password123', 'role' => 'FREELANCER']);
check('invalid email 400', $badEmail['code'] === 400, "code={$badEmail['code']}");

$badRole = req('POST', '/auth/register', ['name' => 'Z', 'email' => 'z' . time() . '@e.com', 'password' => 'password123', 'role' => 'SUPERUSER']);
check('invalid role 400', $badRole['code'] === 400, "code={$badRole['code']}");

// ------------------------------------------------------------- public reads
section('PUBLIC READS');
$fls = req('GET', '/freelancers');
check('GET /freelancers 200', $fls['code'] === 200, "code={$fls['code']}");
check('freelancer list non-empty', is_array($fls['json']) && count($fls['json']) > 0, 'count=' . (is_array($fls['json']) ? count($fls['json']) : 'n/a'));
$firstFl = $fls['json'][0]['id'] ?? null;

$prof = req('GET', '/freelancers/' . $firstFl);
check('GET /freelancers/{id} 200', $prof['code'] === 200, "code={$prof['code']}");
$missing = req('GET', '/freelancers/does-not-exist-xyz');
check('unknown freelancer 404', $missing['code'] === 404, "code={$missing['code']}");

$jobs = req('GET', '/jobs');
check('GET /jobs 200', $jobs['code'] === 200, "code={$jobs['code']}");
check('jobs non-empty', is_array($jobs['json']) && count($jobs['json']) > 0);

$jobsCat = req('GET', '/jobs?category=web');
check('GET /jobs?category=web 200', $jobsCat['code'] === 200, "code={$jobsCat['code']}");

// filtering
$exp = req('GET', '/freelancers?experience=expert');
check('filter experience=expert 200', $exp['code'] === 200, "code={$exp['code']}");
$allOk = true;
foreach (($exp['json'] ?? []) as $f) { if ((int)$f['score'] < 95) { $allOk = false; } }
check('expert filter only returns score>=95', $allOk, 'n=' . count($exp['json'] ?? []));

$cat = req('GET', '/freelancers?categories=ui_ux_design');
check('filter categories=ui_ux_design 200', $cat['code'] === 200, "code={$cat['code']}");

$sr = req('GET', '/freelancers?search=react');
check('search=react 200', $sr['code'] === 200, "code={$sr['code']}");

$srt = req('GET', '/freelancers?sort=price_asc');
$rates = array_column($srt['json'] ?? [], 'hourlyRate');
$sorted = $rates; sort($sorted);
check('sort=price_asc is ascending', $rates === $sorted, json_encode(array_slice($rates, 0, 5)));

// ------------------------------------------------------- freelancer scope
section('FREELANCER SCOPED');
$me = req('GET', '/freelancers/me', null, $FL);
check('GET /freelancers/me 200', $me['code'] === 200, "code={$me['code']}");
check('me returns own id', ($me['json']['id'] ?? '') === 'f-101', 'got=' . ($me['json']['id'] ?? 'null'));
check('me has skills', !empty($me['json']['skills']));

$mePut = req('PUT', '/freelancers/me', ['fullName' => 'Sarah Jenkins', 'bio' => 'Updated bio from e2e test.', 'skills' => ['React', 'TypeScript']], $FL);
check('PUT /freelancers/me 200', $mePut['code'] === 200, "code={$mePut['code']} " . substr((string)$mePut['raw'], 0, 200));

$port = req('GET', '/freelancers/me/portfolio', null, $FL);
check('GET portfolio 200', $port['code'] === 200, "code={$port['code']}");
$portAdd = req('POST', '/freelancers/me/portfolio', ['title' => 'E2E Test Project', 'category' => 'Web Application', 'url' => 'https://example.com/e2e', 'desc' => 'Added by the e2e suite.'], $FL);
check('POST portfolio 201', $portAdd['code'] === 201, "code={$portAdd['code']} " . substr((string)$portAdd['raw'], 0, 200));

$props = req('GET', '/freelancers/me/proposals', null, $FL);
check('GET my proposals 200', $props['code'] === 200, "code={$props['code']}");

$tests = req('GET', '/skills/tests/web', null, $FL);
check('GET /skills/tests/web 200', $tests['code'] === 200, "code={$tests['code']}");
$qCount = is_array($tests['json']) ? count($tests['json']) : 0;
check('test returns questions', $qCount > 0, "n=$qCount");

// skill test submit
$answers = [];
foreach (($tests['json'] ?? []) as $i => $q) { $answers[$i] = 0; }
$sub = req('POST', '/skills/tests/web/submit', ['answers' => $answers], $FL);
check('POST skill test submit 200', $sub['code'] === 200, "code={$sub['code']} " . substr((string)$sub['raw'], 0, 200));

$res = req('GET', '/skills/tests/results/latest', null, $FL);
check('GET latest results 200', $res['code'] === 200, "code={$res['code']}");
check('results have a score', isset($res['json']['score']), json_encode($res['json']));

// --------------------------------------------------------------- client
section('CLIENT SCOPED');
$dash = req('GET', '/clients/me/dashboard', null, $CL);
check('GET dashboard 200', $dash['code'] === 200, "code={$dash['code']}");
$projs = req('GET', '/clients/me/projects', null, $CL);
check('GET my projects 200', $projs['code'] === 200, "code={$projs['code']}");

$dashNoAuth = req('GET', '/clients/me/dashboard');
check('dashboard without token 401', $dashNoAuth['code'] === 401, "code={$dashNoAuth['code']}");

$dashWrongRole = req('GET', '/clients/me/dashboard', null, $FL);
check('dashboard as freelancer 403', $dashWrongRole['code'] === 403, "code={$dashWrongRole['code']}");

$ms = req('GET', '/clients/me/milestones', null, $CL);
check('GET milestones 200', $ms['code'] === 200, "code={$ms['code']} " . substr((string)$ms['raw'], 0, 200));
$milestones = is_array($ms['json']) ? $ms['json'] : [];
check('milestones seeded', count($milestones) > 0, 'n=' . count($milestones));

$bill = req('GET', '/clients/me/billing', null, $CL);
check('GET billing 200', $bill['code'] === 200, "code={$bill['code']} " . substr((string)$bill['raw'], 0, 300));
check('billing has summary', isset($bill['json']['summary']['totalSpent']), json_encode($bill['json']['summary'] ?? null));
check('billing has month groups', !empty($bill['json']['groups']));

$billNoAuth = req('GET', '/clients/me/billing');
check('billing without token 401', $billNoAuth['code'] === 401, "code={$billNoAuth['code']}");

// CSV export
$ch = curl_init($BASE . '/clients/me/billing/export');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $CL],
    CURLOPT_TIMEOUT => 20,
]);
$csv = curl_exec($ch);
$csvCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$csvType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
check('CSV export 200', $csvCode === 200, "code=$csvCode");
check('CSV content-type', is_string($csvType) && strpos($csvType, 'text/csv') === 0, (string)$csvType);
check('CSV has header row', is_string($csv) && strpos($csv, 'Receipt') !== false, substr((string)$csv, 0, 120));

// ---------------------------------------------------------------- chat
section('CHAT');
$threads = req('GET', '/chat/threads', null, $CL);
check('GET chat threads (client) 200', $threads['code'] === 200, "code={$threads['code']}");
$cthreads = is_array($threads['json']) ? $threads['json'] : [];
check('client has threads', count($cthreads) > 0, 'n=' . count($cthreads));

if ($cthreads) {
    $tid = $cthreads[0]['id'];
    $msgs = req('GET', '/chat/threads/' . $tid . '/messages', null, $CL);
    check('GET thread messages 200', $msgs['code'] === 200, "code={$msgs['code']}");
    $send = req('POST', '/chat/threads/' . $tid . '/messages', ['text' => 'E2E probe message'], $CL);
    check('POST message 201', $send['code'] === 201, "code={$send['code']} " . substr((string)$send['raw'], 0, 200));
    $empty = req('POST', '/chat/threads/' . $tid . '/messages', ['text' => '   '], $CL);
    check('empty message 400', $empty['code'] === 400, "code={$empty['code']}");
}
$threadsNoAuth = req('GET', '/chat/threads');
check('chat threads without token 401', $threadsNoAuth['code'] === 401, "code={$threadsNoAuth['code']}");

// ------------------------------------------------------------ proposals
section('PROPOSALS');
$openJobs = array_values(array_filter($jobs['json'] ?? [], fn($j) => ($j['status'] ?? '') === 'Open'));
if ($openJobs) {
    $jid = $openJobs[0]['id'];
    $prop = req('POST', '/jobs/' . $jid . '/proposals', [
        'coverLetter' => 'E2E automated proposal covering the full scope.',
        'proposedRate' => 55,
        'estimatedDuration' => 10
    ], $FL);
    check('POST proposal 201', $prop['code'] === 201, "code={$prop['code']} " . substr((string)$prop['raw'], 0, 250));
    $newPropId = $prop['json']['id'] ?? null;
    check('proposal id returned', !empty($newPropId));

    if ($newPropId) {
        $wd = req('DELETE', '/proposals/' . $newPropId, null, $FL);
        check('DELETE proposal 200', $wd['code'] === 200, "code={$wd['code']} " . substr((string)$wd['raw'], 0, 200));
        $wdAgain = req('DELETE', '/proposals/' . $newPropId, null, $FL);
        check('DELETE twice 404/409', in_array($wdAgain['code'], [404, 409], true), "code={$wdAgain['code']}");
    }
} else {
    echo "  [SKIP] no Open jobs to propose against\n";
}

$propNoAuth = req('POST', '/jobs/job-1/proposals', ['coverLetter' => 'x', 'proposedRate' => 1, 'estimatedDuration' => 1]);
check('proposal without token 401', $propNoAuth['code'] === 401, "code={$propNoAuth['code']}");

// ---------------------------------------------------------------- admin
section('ADMIN');
$metrics = req('GET', '/admin/metrics', null, $AD);
check('GET metrics 200 (admin)', $metrics['code'] === 200, "code={$metrics['code']} " . substr((string)$metrics['raw'], 0, 200));
$metricsNoAuth = req('GET', '/admin/metrics');
check('metrics without token 401', $metricsNoAuth['code'] === 401, "code={$metricsNoAuth['code']}");
$metricsFl = req('GET', '/admin/metrics', null, $FL);
check('metrics as freelancer 403', $metricsFl['code'] === 403, "code={$metricsFl['code']}");

$users = req('GET', '/admin/users', null, $AD);
check('GET admin users 200', $users['code'] === 200, "code={$users['code']}");

// approvals
$aprF = req('GET', '/admin/approvals?type=FREELANCER&status=Pending', null, $AD);
check('GET freelancer approvals 200', $aprF['code'] === 200, "code={$aprF['code']} " . substr((string)$aprF['raw'], 0, 250));
$aprC = req('GET', '/admin/approvals?type=CLIENT&status=Pending', null, $AD);
check('GET client approvals 200', $aprC['code'] === 200, "code={$aprC['code']}");

$aprBad = req('GET', '/admin/approvals?type=HACKER', null, $AD);
check('bad approval type 400', $aprBad['code'] === 400, "code={$aprBad['code']}");

$aprNoAuth = req('GET', '/admin/approvals?type=FREELANCER', null, $CL);
check('approvals as client 403', $aprNoAuth['code'] === 403, "code={$aprNoAuth['code']}");

// skill categories
$cats = req('GET', '/admin/skill-categories', null, $AD);
check('GET skill-categories 200', $cats['code'] === 200, "code={$cats['code']}");
$catsList = $cats['json']['categories'] ?? [];
check('categories seeded', count($catsList) > 0, 'n=' . count($catsList));
check('categories expose isActive', isset($catsList[0]['isActive']));

$catName = 'E2E Category ' . time();
$catNew = req('POST', '/admin/skill-categories', ['name' => $catName, 'subcategories' => ['Alpha', 'Beta'], 'icon' => 'flask'], $AD);
check('POST create category 201', $catNew['code'] === 201, "code={$catNew['code']} " . substr((string)$catNew['raw'], 0, 200));
$newCatId = $catNew['json']['id'] ?? null;
$catDupe = req('POST', '/admin/skill-categories', ['name' => $catName], $AD);
check('duplicate category 409', $catDupe['code'] === 409, "code={$catDupe['code']}");

if ($newCatId) {
    $catUpd = req('PUT', '/admin/skill-categories/' . $newCatId, ['isActive' => false, 'subcategories' => ['Gamma']], $AD);
    check('PUT toggle category 200', $catUpd['code'] === 200, "code={$catUpd['code']}");
    check('toggle reflects isActive=false', ($catUpd['json']['isActive'] ?? true) === false, json_encode($catUpd['json']));
    $catDel = req('DELETE', '/admin/skill-categories/' . $newCatId, null, $AD);
    check('DELETE empty category 200', $catDel['code'] === 200, "code={$catDel['code']} " . substr((string)$catDel['raw'], 0, 200));
}

// question bank
$qList = req('GET', '/admin/skills/web/questions', null, $AD);
check('GET questions 200', $qList['code'] === 200, "code={$qList['code']} " . substr((string)$qList['raw'], 0, 200));
$qNew = req('POST', '/admin/skills/web/questions', [
    'question' => 'E2E: What does the E2E marker return?',
    'qtype' => 'Multiple Choice',
    'difficulty' => 'Easy',
    'options' => ['Pass', 'Fail', 'Maybe'],
    'correctIndex' => 0
], $AD);
check('POST question 201', $qNew['code'] === 201, "code={$qNew['code']} " . substr((string)$qNew['raw'], 0, 200));
$qId = $qNew['json']['id'] ?? null;

$qBad = req('POST', '/admin/skills/web/questions', [
    'question' => 'Bad', 'qtype' => 'Multiple Choice', 'difficulty' => 'Easy',
    'options' => ['only-one'], 'correctIndex' => 5
], $AD);
check('question bad correctIndex 400', $qBad['code'] === 400, "code={$qBad['code']}");

$qBadDiff = req('POST', '/admin/skills/web/questions', [
    'question' => 'Bad', 'qtype' => 'Multiple Choice', 'difficulty' => 'Impossible', 'options' => ['a','b'], 'correctIndex' => 0
], $AD);
check('question bad difficulty 400', $qBadDiff['code'] === 400, "code={$qBadDiff['code']}");

if ($qId) {
    $qUpd = req('PUT', '/admin/skills/web/questions/' . $qId, [
        'question' => 'E2E updated question', 'qtype' => 'Coding', 'difficulty' => 'Hard'
    ], $AD);
    check('PUT question 200', $qUpd['code'] === 200, "code={$qUpd['code']} " . substr((string)$qUpd['raw'], 0, 200));
    $qDel = req('DELETE', '/admin/skills/web/questions/' . $qId, null, $AD);
    check('DELETE question 200', $qDel['code'] === 200, "code={$qDel['code']}");
}

$qBadCat = req('GET', '/admin/skills/nope/questions', null, $AD);
check('unknown category 404', $qBadCat['code'] === 404, "code={$qBadCat['code']}");

$qNoAuth = req('GET', '/admin/skills/web/questions', null, $CL);
check('question bank as client 403', $qNoAuth['code'] === 403, "code={$qNoAuth['code']}");

// reports
$repList = req('GET', '/admin/reports', null, $AD);
check('GET reports 200', $repList['code'] === 200, "code={$repList['code']}");
$repNew = req('POST', '/admin/reports', ['reportType' => 'financial', 'dateFrom' => '01/01/2026', 'dateTo' => '31/12/2026'], $AD);
check('POST report 201', $repNew['code'] === 201, "code={$repNew['code']} " . substr((string)$repNew['raw'], 0, 250));
$repId = $repNew['json']['id'] ?? null;
check('report filename returned', !empty($repNew['json']['filename']), json_encode($repNew['json']));

$repBadDate = req('POST', '/admin/reports', ['reportType' => 'financial', 'dateFrom' => 'not-a-date'], $AD);
check('bad report date 400', $repBadDate['code'] === 400, "code={$repBadDate['code']}");

$repBadType = req('POST', '/admin/reports', ['reportType' => 'nonsense'], $AD);
check('bad report type 400', $repBadType['code'] === 400, "code={$repBadType['code']}");

if ($repId) {
    $ch = curl_init($BASE . '/admin/reports/' . $repId . '/download');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $AD],
        CURLOPT_TIMEOUT => 20,
    ]);
    $pdf = curl_exec($ch);
    $pdfCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $pdfType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    check('report download 200', $pdfCode === 200, "code=$pdfCode");
    check('report is application/pdf', is_string($pdfType) && strpos($pdfType, 'application/pdf') === 0, (string)$pdfType);
    check('PDF magic header', is_string($pdf) && strncmp($pdf, '%PDF-', 5) === 0, substr((string)$pdf, 0, 20));
    check('PDF has EOF marker', is_string($pdf) && strpos((string)$pdf, '%%EOF') !== false);
}

// notifications
$notif = req('GET', '/notifications', null, $CL);
check('GET notifications 200', $notif['code'] === 200, "code={$notif['code']} " . substr((string)$notif['raw'], 0, 200));
check('notifications payload shape', isset($notif['json']['notifications']) && isset($notif['json']['unreadCount']));
$readAll = req('POST', '/notifications/read-all', [], $CL);
check('POST read-all 200', $readAll['code'] === 200, "code={$readAll['code']}");

// ---------------------------------------------------------------- summary
cleanup($SNAPSHOT);

echo "\n=========================================\n";
echo "PASSED: $pass   FAILED: $fail\n";
if ($failures) {
    echo "\nFAILURES:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
}
echo "=========================================\n";
exit($fail > 0 ? 1 : 0);
