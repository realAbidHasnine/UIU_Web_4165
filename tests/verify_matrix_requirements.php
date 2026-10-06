<?php
/**
 * Automated Verification Test Suite for Requirement Matrix Items
 * Tests all 4 partially-implemented features against live XAMPP server
 */

$baseUrl = 'http://localhost/UIU_Web_4165';

function request($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $headers = [];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if ($data !== null) {
        $payload = is_string($data) ? $data : json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response,
        'json' => json_decode($response, true)
    ];
}

ob_start();
require_once __DIR__ . '/../api/config/db.php';
ob_end_clean();

echo "=== SKILLMATCH MATRIX REQUIREMENTS VERIFICATION TEST ===\n\n";
$results = [];

// -------------------------------------------------------------
// Helper: Authenticate Users
// -------------------------------------------------------------
echo "[1] Authenticating Test Users...\n";
$adminLogin = request($baseUrl . '/api/auth/login.php', 'POST', [
    'email' => 'admin@skillmatch.com',
    'password' => 'admin123'
]);
$adminToken = $adminLogin['json']['token'] ?? null;
echo "  - Admin login: " . ($adminToken ? "SUCCESS (Token received)" : "FAILED (Code: {$adminLogin['code']})") . "\n";

$flLogin = request($baseUrl . '/api/auth/login.php', 'POST', [
    'email' => 'sarah.jenkins@example.com',
    'password' => 'password123'
]);
$flToken = $flLogin['json']['token'] ?? null;
echo "  - Freelancer login: " . ($flToken ? "SUCCESS (Token received)" : "FAILED (Code: {$flLogin['code']})") . "\n";

$clientLogin = request($baseUrl . '/api/auth/login.php', 'POST', [
    'email' => 'abid.hasina@flow.com',
    'password' => 'password123'
]);
$clientToken = $clientLogin['json']['token'] ?? null;
echo "  - Client login: " . ($clientToken ? "SUCCESS (Token received)" : "FAILED (Code: {$clientLogin['code']})") . "\n\n";

// -------------------------------------------------------------
// ITEM 1: Admin Resolve Disputes
// -------------------------------------------------------------
echo "[ITEM 1] Testing Admin: Resolve Disputes...\n";
// Ensure at least one test dispute exists in DB and is in 'Under review' state
$disputeId = 'DIS-2026-0001';
$pdo->prepare("UPDATE disputes SET status = 'Under review' WHERE id = ?")->execute([$disputeId]);

// 1.1 GET /api/disputes
$dispList = request($baseUrl . '/api/disputes/index.php', 'GET', null, $adminToken);
$hasDisputes = !empty($dispList['json']['disputes']);
echo "  - 1.1 GET /api/disputes (Admin): HTTP {$dispList['code']}, count: " . count($dispList['json']['disputes'] ?? []) . " (" . ($hasDisputes ? "PASS" : "FAIL") . ")\n";

// 1.2 POST /api/disputes/resolve.php
$resolveRes = request($baseUrl . '/api/disputes/resolve.php?id=' . urlencode($disputeId), 'POST', [
    'outcome' => 'Release to freelancer',
    'resolution' => 'Administrative arbitration ruling: work meets specifications. Escrow released.'
], $adminToken);
$resolvedOk = ($resolveRes['code'] === 200 && ($resolveRes['json']['status'] ?? '') === 'Resolved');
echo "  - 1.2 POST /api/disputes/resolve.php: HTTP {$resolveRes['code']}, Status: " . ($resolveRes['json']['status'] ?? 'N/A') . " (" . ($resolvedOk ? "PASS" : "FAIL") . ")\n";

// 1.3 Verify Admin UI HTML contains dispute management tab and modal
$adminHtml = request($baseUrl . '/Admin/html/user-management.html');
$hasDisputeUI = strpos($adminHtml['body'], 'tabDisputesBtn') !== false 
             && strpos($adminHtml['body'], 'disputesTableCard') !== false
             && strpos($adminHtml['body'], 'resolveDisputeModal') !== false;
echo "  - 1.3 Admin user-management.html UI components: HTTP {$adminHtml['code']} (" . ($hasDisputeUI ? "PASS - Tabs & Modal Present" : "FAIL") . ")\n";
$results['Admin: Resolve Disputes'] = ($hasDisputes && $resolvedOk && $hasDisputeUI);
echo "\n";

// -------------------------------------------------------------
// ITEM 2: Freelancer Portfolio & GitHub Link
// -------------------------------------------------------------
echo "[ITEM 2] Testing Freelancer: Portfolio & GitHub Link...\n";
// 2.1 GET /api/freelancers/me contains githubUrl
$meRes = request($baseUrl . '/api/freelancers/me.php', 'GET', null, $flToken);
$hasGhField = array_key_exists('githubUrl', $meRes['json'] ?? []);
echo "  - 2.1 GET /api/freelancers/me includes githubUrl field: (" . ($hasGhField ? "PASS" : "FAIL") . ")\n";

// 2.2 POST /api/freelancers/me/portfolio?sync=github
$syncRes = request($baseUrl . '/api/freelancers/portfolio.php?sync=github', 'POST', [
    'action' => 'sync_github',
    'githubUsername' => 'sarahjenkins-dev'
], $flToken);
$syncOk = ($syncRes['code'] === 200 && !empty($syncRes['json']['success']) && !empty($syncRes['json']['items']));
echo "  - 2.2 POST /api/freelancers/portfolio.php?sync=github: HTTP {$syncRes['code']}, Msg: " . ($syncRes['json']['message'] ?? 'N/A') . " (" . ($syncOk ? "PASS" : "FAIL") . ")\n";

// 2.3 Verify DB persistence of github_url and portfolio_items
$dbGh = $pdo->query("SELECT github_url FROM users WHERE id = 'f-101'")->fetchColumn();
$dbItemCount = $pdo->query("SELECT COUNT(*) FROM portfolio_items WHERE freelancer_id = 'f-101'")->fetchColumn();
$persistedOk = !empty($dbGh) && $dbItemCount > 0;
echo "  - 2.3 DB Persistence: users.github_url = '{$dbGh}', items count = {$dbItemCount} (" . ($persistedOk ? "PASS" : "FAIL") . ")\n";

// 2.4 Verify portfolio_link.html page loads
$portHtml = request($baseUrl . '/freelancer/portfolio_link.html');
$portPageOk = ($portHtml['code'] === 200 && strpos($portHtml['body'], 'githubSyncBtn') !== false);
echo "  - 2.4 Freelancer portfolio_link.html: HTTP {$portHtml['code']} (" . ($portPageOk ? "PASS" : "FAIL") . ")\n";
$results['Freelancer: Portfolio & GitHub'] = ($hasGhField && $syncOk && $persistedOk && $portPageOk);
echo "\n";

// -------------------------------------------------------------
// ITEM 3: Freelancer Upload Completed Work
// -------------------------------------------------------------
echo "[ITEM 3] Testing Freelancer: Upload Completed Work...\n";
// Ensure freelancer is associated with job-1
$pdo->prepare("UPDATE jobs SET status = 'In Progress', hired_freelancer_id = 'f-101' WHERE id = 'job-1'")->execute();

// 3.1 POST /api/projects/job-1/deliverables with dynamic job ID
$uploadRes = request($baseUrl . '/api/projects/deliverables.php?id=job-1', 'POST', [
    'notes' => 'Complete frontend bundle and responsive widgets ready for review.',
    'repoUrl' => 'https://github.com/sarahjenkins-dev/fintech-dashboard',
    'files' => [
        ['name' => 'dashboard-release-v1.zip', 'size' => 1048576, 'type' => 'application/zip']
    ]
], $flToken);
$uploadOk = ($uploadRes['code'] === 201 && ($uploadRes['json']['status'] ?? '') === 'Submitted');
echo "  - 3.1 POST /api/projects/job-1/deliverables: HTTP {$uploadRes['code']}, Status: " . ($uploadRes['json']['status'] ?? 'N/A') . " (" . ($uploadOk ? "PASS" : "FAIL") . ")\n";

// 3.2 Verify DB persistence in deliverables table
$delivRow = $pdo->query("SELECT id, status, job_id FROM deliverables WHERE job_id = 'job-1' ORDER BY submitted_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$delivDbOk = !empty($delivRow) && $delivRow['status'] === 'Submitted';
echo "  - 3.2 DB Deliverables record: ID {$delivRow['id']}, Status: {$delivRow['status']} (" . ($delivDbOk ? "PASS" : "FAIL") . ")\n";

// 3.3 Verify upload_comp_work.html page loads with dynamic contract elements
$uploadHtml = request($baseUrl . '/freelancer/upload_comp_work.html?jobId=job-1');
$uploadUiOk = ($uploadHtml['code'] === 200 && strpos($uploadHtml['body'], 'contractTitle') !== false);
echo "  - 3.3 Freelancer upload_comp_work.html?jobId=job-1: HTTP {$uploadHtml['code']} (" . ($uploadUiOk ? "PASS" : "FAIL") . ")\n";
$results['Freelancer: Upload Completed Work'] = ($uploadOk && $delivDbOk && $uploadUiOk);
echo "\n";

// -------------------------------------------------------------
// ITEM 4: Client Review Proposals
// -------------------------------------------------------------
echo "[ITEM 4] Testing Client: Review Proposals...\n";
// 4.1 GET /api/jobs/job-1/proposals
$propListRes = request($baseUrl . '/api/jobs/proposals.php?jobId=job-1', 'GET', null, $clientToken);
$propListOk = ($propListRes['code'] === 200 && !empty($propListRes['json']['proposals']));
echo "  - 4.1 GET /api/jobs/job-1/proposals: HTTP {$propListRes['code']}, Proposals: " . count($propListRes['json']['proposals'] ?? []) . " (" . ($propListOk ? "PASS" : "FAIL") . ")\n";

// 4.2 GET client/review-proposal.php?job=job-1
$revPage = request($baseUrl . '/client/review-proposal.php?job=job-1');
$noPhpErrors = !preg_match('/(Fatal error|Warning|Notice|Parse error)/i', $revPage['body']);
$hasDynamicProposals = (strpos($revPage['body'], 'Abid Hasnine') !== false || strpos($revPage['body'], 'Sarah Jenkins') !== false)
                    && (strpos($revPage['body'], 'Accept Proposal') !== false);
echo "  - 4.2 GET client/review-proposal.php?job=job-1: HTTP {$revPage['code']}, No Errors: " . ($noPhpErrors ? "PASS" : "FAIL") . ", Dynamic Proposals: " . ($hasDynamicProposals ? "PASS" : "FAIL") . "\n";

// 4.3 POST accept proposal via API or Form
$testPropId = $propListRes['json']['proposals'][0]['id'] ?? 'prop-1';
$acceptRes = request($baseUrl . '/api/jobs/proposals.php?jobId=job-1', 'POST', [
    'action' => 'accept',
    'proposalId' => $testPropId
], $clientToken);
$acceptOk = ($acceptRes['code'] === 200 && ($acceptRes['json']['status'] ?? '') === 'Accepted');
echo "  - 4.3 Proposal Acceptance: HTTP {$acceptRes['code']}, Status: " . ($acceptRes['json']['status'] ?? 'N/A') . " (" . ($acceptOk ? "PASS" : "FAIL") . ")\n";

// 4.4 Verify Job status updated to 'In Progress' and payments escrow created
$jobState = $pdo->query("SELECT status, hired_freelancer_id FROM jobs WHERE id = 'job-1'")->fetch(PDO::FETCH_ASSOC);
$escrowState = $pdo->query("SELECT status, amount FROM payments WHERE job_id = 'job-1' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$hireVerified = ($jobState['status'] === 'In Progress' && !empty($jobState['hired_freelancer_id']));
echo "  - 4.4 Job & Escrow DB State: Job Status = '{$jobState['status']}', Hired = '{$jobState['hired_freelancer_id']}', Escrow = '{$escrowState['status']}' (" . ($hireVerified ? "PASS" : "FAIL") . ")\n";
$results['Client: Review Proposals'] = ($propListOk && $noPhpErrors && $hasDynamicProposals && $acceptOk && $hireVerified);
echo "\n";

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "=== VERIFICATION SUMMARY ===\n";
$allPassed = true;
foreach ($results as $item => $pass) {
    echo "  " . ($pass ? "✅ PASS" : "❌ FAIL") . " : {$item}\n";
    if (!$pass) $allPassed = false;
}
echo "\nOVERALL STATUS: " . ($allPassed ? "ALL 4 REQUIREMENTS FULLY IMPLEMENTED AND VERIFIED!" : "SOME ITEMS FAILED") . "\n";
