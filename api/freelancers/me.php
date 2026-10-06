<?php
/**
 * Endpoint: GET|PUT /api/freelancers/me
 * GET  — Returns the authenticated freelancer's full profile
 * PUT  — Updates the authenticated freelancer's profile fields
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $freelancerId = requireAuth($pdo)['id'];

    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'FREELANCER' LIMIT 1");
        $stmt->execute([$freelancerId]);
        $r = $stmt->fetch();

        if (!$r) {
            sendResponse(['message' => 'Freelancer not found'], 404);
        }

        $skills = getUserSkills($pdo, $freelancerId);

        sendResponse([
            'id'            => $r['id'],
            'name'          => $r['name'],
            'email'         => $r['email'],
            'role'          => $r['role'],
            'title'         => $r['title'] ?? 'Verified Specialist',
            'bio'           => $r['bio'] ?? '',
            'location'      => $r['location'] ?? 'Remote',
            'hourlyRate'    => (float)($r['hourly_rate'] ?? 65),
            'rating'        => (float)($r['rating'] ?? 5.0),
            'score'         => (int)($r['score'] ?? 90),
            'verifiedScore' => (int)($r['score'] ?? 90),
            'earnings'      => (float)($r['earnings'] ?? 0),
            'completedJobs' => (int)($r['completed_jobs'] ?? 0),
            'githubUrl'     => $r['github_url'] ?? null,
            'skills'        => $skills,
            'verified'      => true
        ]);

    } elseif ($method === 'PUT') {
        $body = getRequestBody();

        $fields = [];
        $params = [];

        // Whitelist updatable fields
        if (isset($body['name']) && strlen(trim($body['name'])) >= 2) {
            $fields[] = 'name = ?';
            $params[] = trim($body['name']);
        }
        if (isset($body['title'])) {
            $fields[] = 'title = ?';
            $params[] = trim($body['title']);
        }
        if (isset($body['bio'])) {
            $fields[] = 'bio = ?';
            $params[] = trim($body['bio']);
        }
        if (isset($body['hourlyRate']) && is_numeric($body['hourlyRate']) && $body['hourlyRate'] >= 0) {
            $fields[] = 'hourly_rate = ?';
            $params[] = (float)$body['hourlyRate'];
        }
        if (isset($body['location'])) {
            $fields[] = 'location = ?';
            $params[] = trim($body['location']);
        }
        if (isset($body['githubUrl'])) {
            $fields[] = 'github_url = ?';
            $params[] = trim($body['githubUrl']);
        }

        if (empty($fields)) {
            sendResponse(['message' => 'No valid fields to update'], 400);
        }

        $params[] = $freelancerId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $pdo->prepare($sql)->execute($params);

        // Handle skills update
        if (isset($body['skills']) && is_array($body['skills'])) {
            $pdo->prepare("DELETE FROM user_skills WHERE user_id = ?")->execute([$freelancerId]);
            $skillStmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_name) VALUES (?, ?)");
            foreach ($body['skills'] as $skill) {
                $skillTrimmed = trim($skill);
                if (!empty($skillTrimmed)) {
                    $skillStmt->execute([$freelancerId, $skillTrimmed]);
                }
            }
        }

        // Return updated profile
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$freelancerId]);
        $r = $stmt->fetch();
        $skills = getUserSkills($pdo, $freelancerId);

        sendResponse([
            'id'            => $r['id'],
            'name'          => $r['name'],
            'email'         => $r['email'],
            'role'          => $r['role'],
            'title'         => $r['title'] ?? '',
            'bio'           => $r['bio'] ?? '',
            'location'      => $r['location'] ?? 'Remote',
            'hourlyRate'    => (float)($r['hourly_rate'] ?? 0),
            'verifiedScore' => (int)($r['score'] ?? 90),
            'skills'        => $skills,
            'message'       => 'Profile updated successfully'
        ]);

    } else {
        sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Database error: ' . $e->getMessage()], 500);
}
