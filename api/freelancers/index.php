<?php
/**
 * Endpoint: GET /api/freelancers
 * Role: Returns verified freelancers from MySQL for browse and landing pages
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'FREELANCER' AND status = 'Active' ORDER BY score DESC");
$stmt->execute();
$rows = $stmt->fetchAll();

$freelancers = [];
foreach ($rows as $r) {
    $freelancers[] = [
        'id' => $r['id'],
        'name' => $r['name'],
        'title' => $r['title'] ?? 'Verified Specialist',
        'score' => (int)($r['score'] ?? 90),
        'verifiedScore' => (int)($r['score'] ?? 90),
        'skills' => ['React', 'TypeScript', 'TailwindCSS', 'Spring Boot', 'REST APIs'],
        'rating' => (float)($r['rating'] ?? 5.0),
        'completedProjects' => (int)($r['completed_jobs'] ?? 30),
        'completedJobs' => (int)($r['completed_jobs'] ?? 30),
        'hourlyRate' => (float)($r['hourly_rate'] ?? 65),
        'category' => 'web-development',
        'verifiedBadge' => 'Verified Pro'
    ];
}

sendResponse($freelancers, 200);
