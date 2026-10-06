<?php
/**
 * Endpoint: POST /api/disputes/{id}/respond
 * Role: FREELANCER — the freelancer named on the dispute.
 *
 * Body: { response: string, evidenceUrl?: string }
 *
 * A response escalates the dispute to an administrator, because only an admin
 * can move the escrowed money (see disputes/resolve.php).
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $freelancer = requireRole($pdo, ['FREELANCER']);

    $disputeId = routeParam('id');
    if ($disputeId === '') {
        sendResponse(['message' => 'Dispute ID is required'], 400);
    }

    $body     = getRequestBody();
    $response = requireField($body, 'response', 2000, 'response');

    if (mb_strlen($response) < 10) {
        sendResponse(['message' => 'Please give at least 10 characters of explanation'], 400);
    }

    $evidence = trim((string)($body['evidenceUrl'] ?? ''));
    if ($evidence !== '') {
        if (mb_strlen($evidence) > 255) {
            sendResponse(['message' => 'Field evidenceUrl must be 255 characters or fewer'], 400);
        }
        if (!filter_var($evidence, FILTER_VALIDATE_URL)) {
            sendResponse(['message' => 'Field evidenceUrl must be a valid URL'], 400);
        }
        $response .= "\n\nEvidence: " . $evidence;
    }

    $stmt = $pdo->prepare("SELECT d.*, m.label AS milestone_label, m.amount AS milestone_amount
                            FROM disputes d
                            INNER JOIN project_milestones m ON m.id = d.milestone_id
                            WHERE d.id = ? LIMIT 1");
    $stmt->execute([$disputeId]);
    $dispute = $stmt->fetch();

    if (!$dispute) {
        sendResponse(['message' => 'Dispute not found'], 404);
    }
    if ($dispute['freelancer_id'] === null || $dispute['freelancer_id'] !== $freelancer['id']) {
        sendResponse(['message' => 'This dispute was not raised against you'], 403);
    }
    if ($dispute['status'] !== 'Under review') {
        sendResponse(['message' => 'This dispute is ' . strtolower($dispute['status']) . ' and no longer accepts a response'], 409);
    }
    if ($dispute['freelancer_response'] !== null) {
        sendResponse(['message' => 'You have already responded to this dispute'], 409);
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare("UPDATE disputes
                              SET freelancer_response = ?, responded_at = NOW(), status = 'Escalated'
                              WHERE id = ? AND status = 'Under review' AND freelancer_response IS NULL");
        $upd->execute([$response, $disputeId]);

        if ($upd->rowCount() === 0) {
            $pdo->rollBack();
            sendResponse(['message' => 'This dispute was answered by someone else first'], 409);
        }

        // Tell the client, and every admin so it can be picked up.
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link)
                                VALUES (?, ?, ?, ?, ?)");
        $notif->execute([
            $dispute['client_id'],
            'dispute',
            'Freelancer responded to dispute ' . $disputeId,
            "{$freelancer['name']} submitted a response. An administrator will now review the case.",
            '../client/raise-dispute.php',
        ]);

        $admins = $pdo->query("SELECT id FROM users WHERE role = 'ADMIN'")->fetchAll();
        foreach ($admins as $a) {
            $notif->execute([
                $a['id'],
                'dispute',
                'Dispute ' . $disputeId . ' needs review',
                "{$freelancer['name']} responded to a dispute on \"{$dispute['milestone_label']}\".",
                '../Admin/html/reports-generator.html',
            ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse(['message' => 'Could not record your response: ' . $e->getMessage()], 500);
    }

    sendResponse([
        'message'      => 'Your response has been recorded and the dispute escalated for review.',
        'disputeId'    => $disputeId,
        'status'       => 'Escalated',
        'respondedAt'  => date('Y-m-d H:i:s'),
        'milestoneId'  => $dispute['milestone_id'],
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Dispute error: ' . $e->getMessage()], 500);
}
