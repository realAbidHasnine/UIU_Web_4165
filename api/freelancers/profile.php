<?php
/**
 * Endpoint: GET /api/freelancers/{id}
 * Returns public profile of a single freelancer (by ID)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract {id} from URL path — handles /api/freelancers/{id}
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/freelancers\/([a-zA-Z0-9_-]+)(?:\/|$|\?)/', $uri, $matches);
$id = $matches[1] ?? '';

// Exclude 'me' — that's handled by me.php
if (empty($id) || $id === 'me') {
    sendResponse(['message' => 'Invalid freelancer ID'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'FREELANCER' LIMIT 1");
    $stmt->execute([$id]);
    $r = $stmt->fetch();

    if (!$r) {
        sendResponse(['message' => 'Freelancer not found'], 404);
    }

    // Fetch skills from user_skills table
    $skills = getUserSkills($pdo, $id);

    // Fetch portfolio items
    $pStmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE freelancer_id = ? ORDER BY created_at DESC");
    $pStmt->execute([$id]);
    $portfolio = $pStmt->fetchAll();

    $portfolioItems = [];
    foreach ($portfolio as $p) {
        $portfolioItems[] = [
            'id'       => $p['id'],
            'title'    => $p['title'],
            'category' => $p['category'],
            'url'      => $p['url'],
            'image'    => $p['image'] ?? '../assets/images/portfolio-1.png',
            'desc'     => $p['description']
        ];
    }

    // Fetch verified test badges
    $badgeStmt = $pdo->prepare("
        SELECT DISTINCT category, verified_badge FROM test_results
        WHERE freelancer_id = ? AND passed = 1
        ORDER BY submitted_at DESC
    ");
    $badgeStmt->execute([$id]);
    $badges = $badgeStmt->fetchAll();

    sendResponse([
        'id'                => $r['id'],
        'name'              => $r['name'],
        'title'             => $r['title'] ?? 'Verified Specialist',
        'bio'               => $r['bio'] ?? '',
        'location'          => $r['location'] ?? 'Remote',
        'hourlyRate'        => (float)($r['hourly_rate'] ?? 65),
        'rating'            => (float)($r['rating'] ?? 5.0),
        'score'             => (int)($r['score'] ?? 90),
        'verifiedScore'     => (int)($r['score'] ?? 90),
        'verifiedBadge'     => (int)($r['score'] ?? 90) >= 90 ? 'Verified Pro' : null,
        'completedProjects' => (int)($r['completed_jobs'] ?? 0),
        'completedJobs'     => (int)($r['completed_jobs'] ?? 0),
        'earnings'          => (float)($r['earnings'] ?? 0),
        'skills'            => $skills ?: ['Web Development', 'REST APIs'],
        'portfolio'         => $portfolioItems,
        'badges'            => $badges
    ]);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to fetch profile: ' . $e->getMessage()], 500);
}
