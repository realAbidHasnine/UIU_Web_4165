<?php
/**
 * Endpoint: POST /api/reviews
 * Client rates a freelancer on a paid milestone (3 dimensions, 1-5 each).
 * Overall is the mean of the three and refreshes the freelancer's rating.
 *
 * Body: {
 *   milestoneId: string,
 *   communication: 1-5, quality: 1-5, timeliness: 1-5,
 *   comment?: string, satisfactionComment?: string
 * }
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);
    $body   = getRequestBody();

    $milestoneId = requireField($body, 'milestoneId', 50, 'milestoneId');
    $comm  = clampRating($body['communication'] ?? null, 'communication');
    $qual  = clampRating($body['quality'] ?? null, 'quality');
    $time  = clampRating($body['timeliness'] ?? null, 'timeliness');

    $comment    = trim((string)($body['comment'] ?? ''));
    $satisfaction = trim((string)($body['satisfactionComment'] ?? ''));
    if (mb_strlen($comment) > 2000 || mb_strlen($satisfaction) > 2000) {
        sendResponse(['message' => 'Review text must be 2000 characters or fewer'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT m.*, j.title AS job_title
        FROM project_milestones m
        INNER JOIN jobs j ON j.id = m.job_id
        WHERE m.id = ? AND m.client_id = ?
    ");
    $stmt->execute([$milestoneId, $client['id']]);
    $ms = $stmt->fetch();

    if (!$ms) {
        sendResponse(['message' => 'Milestone not found for your account'], 404);
    }
    if (empty($ms['freelancer_id'])) {
        sendResponse(['message' => 'This milestone has no assigned freelancer to review'], 409);
    }
    if ($ms['status'] !== 'Paid') {
        sendResponse(['message' => 'You can only review a milestone after it has been paid'], 409);
    }

    $overall = round(($comm + $qual + $time) / 3, 2);

    // One review per milestone — upsert so re-submitting edits rather than duplicating.
    $existing = $pdo->prepare("SELECT id FROM reviews WHERE milestone_id = ?");
    $existing->execute([$milestoneId]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $pdo->prepare("
            UPDATE reviews
            SET communication = ?, quality = ?, timeliness = ?, overall = ?,
                comment = COALESCE(?, comment),
                satisfaction_comment = COALESCE(?, satisfaction_comment)
            WHERE id = ?
        ")->execute([$comm, $qual, $time, $overall, $comment ?: null, $satisfaction ?: null, $existingId]);
        $reviewId = $existingId;
    } else {
        $reviewId = newId('rev');
        $pdo->prepare("
            INSERT INTO reviews
                (id, job_id, milestone_id, client_id, freelancer_id, communication, quality, timeliness, overall, comment, satisfaction_comment)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $reviewId, $ms['job_id'], $milestoneId, $ms['client_id'], $ms['freelancer_id'],
            $comm, $qual, $time, $overall, $comment ?: null, $satisfaction ?: null
        ]);
    }

    // Refresh the freelancer's aggregate rating from all their reviews
    $pdo->prepare("
        UPDATE users u
        SET u.rating = (SELECT ROUND(AVG(r.overall), 2) FROM reviews r WHERE r.freelancer_id = u.id)
        WHERE u.id = ?
    ")->execute([$ms['freelancer_id']]);

    $newRating = $pdo->prepare("SELECT rating FROM users WHERE id = ?");
    $newRating->execute([$ms['freelancer_id']]);

    $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, body, link)
        VALUES (?, 'review', 'New review received', ?, ?)
    ")->execute([
        $ms['freelancer_id'],
        $client['name'] . ' left you a ' . $overall . '/5 review on ' . $ms['job_title'] . '.',
        '../freelancer/index.html'
    ]);

    sendResponse([
        'message'        => 'Review submitted. Thank you!',
        'reviewId'       => $reviewId,
        'milestoneId'    => $milestoneId,
        'freelancerId'   => $ms['freelancer_id'],
        'communication'  => $comm,
        'quality'        => $qual,
        'timeliness'     => $time,
        'overall'        => $overall,
        'freelancerRating' => (float)$newRating->fetchColumn()
    ], $existingId ? 200 : 201);

} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to submit review: ' . $e->getMessage()], 500);
}
