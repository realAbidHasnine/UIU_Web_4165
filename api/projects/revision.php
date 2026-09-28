<?php
/**
 * Endpoint: POST /api/projects/{milestoneId}/revision
 * Role: CLIENT — the client who owns the project.
 *
 * Body: { note: string }
 *
 * Sends a submitted milestone back to the freelancer. The milestone returns to
 * 'Posted' so the deliverable can be replaced; escrow stays in place.
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);

    $milestoneId = routeParam('id');
    if ($milestoneId === '') {
        sendResponse(['message' => 'Milestone ID is required'], 400);
    }

    $body = getRequestBody();
    $note = requireField($body, 'note', 1000, 'note');
    if (mb_strlen($note) < 5) {
        sendResponse(['message' => 'Please describe the changes in at least 5 characters'], 400);
    }

    $stmt = $pdo->prepare("SELECT m.*, j.title AS job_title
                            FROM project_milestones m
                            INNER JOIN jobs j ON j.id = m.job_id
                            WHERE m.id = ? AND m.client_id = ?
                            LIMIT 1");
    $stmt->execute([$milestoneId, $client['id']]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        sendResponse(['message' => 'Milestone not found for your account'], 404);
    }
    if ($milestone['status'] === 'Disputed') {
        sendResponse(['message' => 'This milestone is locked by an open dispute'], 409);
    }
    if ($milestone['status'] === 'Paid') {
        sendResponse(['message' => 'This milestone has already been paid — open a dispute instead'], 409);
    }
    if ($milestone['status'] !== 'Submitted') {
        sendResponse([
            'message' => 'Only a submitted milestone can be sent back (this one is ' . strtolower($milestone['status']) . ')',
        ], 409);
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare("UPDATE project_milestones
                              SET status = 'Posted', submitted_at = NULL
                              WHERE id = ? AND status = 'Submitted'");
        $upd->execute([$milestoneId]);
        if ($upd->rowCount() === 0) {
            $pdo->rollBack();
            sendResponse(['message' => 'This milestone was changed by someone else — reload and try again'], 409);
        }

        // Previous submissions stay on file, marked as needing changes.
        $pdo->prepare("UPDATE deliverables
                       SET status = 'Revision Requested'
                       WHERE milestone_id = ? AND status = 'Submitted'")
            ->execute([$milestoneId]);

        if (!empty($milestone['freelancer_id'])) {
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link)
                           VALUES (?, 'revision', 'Changes requested', ?, ?)")
                ->execute([
                    $milestone['freelancer_id'],
                    $client['name'] . ' requested changes to "' . $milestone['label'] . '" on "'
                    . $milestone['job_title'] . '": ' . $note,
                    '../freelancer/freelancer_projects.html',
                ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse(['message' => 'Could not request a revision: ' . $e->getMessage()], 500);
    }

    sendResponse([
        'message'     => 'Revision requested — the milestone is open again.',
        'milestoneId' => $milestoneId,
        'status'      => 'Posted',
        'freelancerId' => $milestone['freelancer_id'],
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Revision error: ' . $e->getMessage()], 500);
}
