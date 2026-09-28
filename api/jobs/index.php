<?php
/**
 * Endpoint: /api/jobs
 * - GET:  List jobs with optional filtering
 * - POST: Create a new job posting (authenticated client)
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $category = trim((string)($_GET['category'] ?? ($_GET['categories'] ?? '')));
        $budget   = trim((string)($_GET['budget'] ?? ($_GET['budget_range'] ?? '')));
        $duration = trim((string)($_GET['duration'] ?? ''));
        $search   = trim((string)($_GET['search'] ?? ''));
        $status   = trim((string)($_GET['status'] ?? 'Open'));

        $sql    = "SELECT j.*, (SELECT COUNT(*) FROM proposals p WHERE p.job_id = j.id) AS proposals_count FROM jobs j WHERE 1=1";
        $params = [];

        // Status filter (default to Open for public listing)
        if (!empty($status) && $status !== 'all') {
            $sql .= " AND j.status = ?";
            $params[] = $status;
        }

        if (!empty($category) && $category !== 'all') {
            $catMap = [
                'web_development' => 'web',
                'web'             => 'web',
                'ui_ux_design'    => 'uiux',
                'uiux'            => 'uiux',
                'mobile_app_dev'  => 'mobile',
                'mobile'          => 'mobile',
                'cloud_devops'    => 'cloud',
                'cloud'           => 'cloud',
                'data_science'    => 'data',
                'data'            => 'data',
            ];
            $rawCats = array_filter(array_map('trim', explode(',', $category)));
            $cats = [];
            foreach ($rawCats as $rc) {
                $low = strtolower($rc);
                $cats[] = $catMap[$low] ?? $rc;
            }
            $cats = array_values(array_unique($cats));
            if (!empty($cats)) {
                $placeholders = implode(',', array_fill(0, count($cats), '?'));
                $sql         .= " AND j.category IN ($placeholders)";
                $params       = array_merge($params, $cats);
            }
        }

        if (!empty($budget) && $budget !== 'any') {
            $budgetMap = [
                'under-1000' => 'under-1000',
                'under-1k'   => 'under-1000',
                '<1k'        => 'under-1000',
                '1k-3k'      => '1000-3000',
                '1000-3000'  => '1000-3000',
                '3k-5k'      => '3000-5000',
                '3000-5000'  => '3000-5000',
                '5k+'        => '5000-plus',
                '5000+'      => '5000-plus',
                '5000-plus'  => '5000-plus',
            ];
            $budgetKey = strtolower($budget);
            $targetBudget = $budgetMap[$budgetKey] ?? $budget;
            $sql    .= " AND j.budget = ?";
            $params[] = $targetBudget;
        }

        if (!empty($duration) && $duration !== 'any') {
            $sql    .= " AND j.duration = ?";
            $params[] = $duration;
        }

        if (!empty($search)) {
            $sql    .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.company LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY j.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $jobs = [];
        foreach ($rows as $r) {
            $jobs[] = [
                'id'             => $r['id'],
                'title'          => $r['title'],
                'category'       => $r['category'],
                'budgetType'     => $r['budget_type'],
                'budget'         => $r['budget'],
                'budgetDisplay'  => $r['budget_display'],
                'duration'       => $r['duration'],
                'durationDisplay'=> $r['duration_display'],
                'level'          => $r['level'],
                'desc'           => $r['description'],
                'company'        => $r['company'] ?? 'SkillMatch Client',
                'location'       => $r['location'] ?? 'Remote',
                'skills'         => json_decode($r['skills'] ?? '[]', true) ?: [],
                'responsibilities'=> json_decode($r['responsibilities'] ?? '[]', true) ?: [],
                'posted'         => $r['posted_time'] ?? 'Recently',
                'status'         => $r['status'],
                'proposalsCount' => (int)($r['proposals_count'] ?? 0),
                'suggestedRate'  => 3000
            ];
        }

        sendResponse($jobs, 200);

    } elseif ($method === 'POST') {
        $clientId = requireAuth($pdo)['id'];

        $body = getRequestBody();

        $title         = trim($body['title'] ?? '');
        $category      = trim($body['category'] ?? 'web');
        $budgetType    = in_array($body['budgetType'] ?? 'fixed', ['fixed', 'hourly']) ? $body['budgetType'] : 'fixed';
        $budget        = trim((string)($body['budget'] ?? '3000'));
        $budgetDisplay = trim($body['budgetDisplay'] ?? ('$' . $budget . ' Fixed'));
        $duration      = trim($body['duration'] ?? '1-3-months');
        $durationDisplay = trim($body['durationDisplay'] ?? '1-3 Months');
        $level         = trim($body['level'] ?? 'Intermediate');
        $desc          = trim($body['desc'] ?? '');
        $skills        = json_encode(array_values($body['skills'] ?? []));
        $responsibilities = json_encode(array_values($body['responsibilities'] ?? []));

        if (empty($title) || strlen($title) < 5) {
            sendResponse(['message' => 'Job title must be at least 5 characters'], 400);
        }
        if (empty($desc) || strlen($desc) < 20) {
            sendResponse(['message' => 'Job description must be at least 20 characters'], 400);
        }

        // Get client's company name
        $clientRow = $pdo->prepare("SELECT name, company FROM users WHERE id = ? LIMIT 1");
        $clientRow->execute([$clientId]);
        $client  = $clientRow->fetch();
        $company = $client['company'] ?? ($client['name'] ?? 'SkillMatch Client');

        $newId = 'job-' . substr(md5(uniqid($clientId, true)), 0, 8);

        $stmt = $pdo->prepare("
            INSERT INTO jobs (id, client_id, title, category, budget_type, budget, budget_display,
                              duration, duration_display, level, description, company, location, skills, responsibilities, status, posted_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Remote', ?, ?, 'Open', 'Just now')
        ");
        $stmt->execute([
            $newId, $clientId, $title, $category, $budgetType, $budget, $budgetDisplay,
            $duration, $durationDisplay, $level, $desc, $company, $skills, $responsibilities
        ]);

        sendResponse([
            'id'     => $newId,
            'title'  => $title,
            'status' => 'Open',
            'posted' => 'Just now'
        ], 201);

    } else {
        sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Database error: ' . $e->getMessage()], 500);
}
