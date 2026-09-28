<?php
/**
 * Endpoint: POST /api/disputes
 * Files a dispute against a milestone, freezing the escrowed funds.
 *
 * Body: {
 *   milestoneId: string,
 *   reason: 'Work quality'|'Missed deadline'|'Communication breakdown'|'Other',
 *   description: string,
 *   desiredOutcome: 'Request a revision'|'Partial refund'|'Full refund',
 *   partialRefundAmount?: number   // required when outcome = 'Partial refund'
 * }
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client   = requireRole($pdo, ['CLIENT']);
    $body     = getRequestBody();

    $milestoneId = requireField($body, 'milestoneId', 50, 'milestoneId');
    $reason      = requireField($body, 'reason', 60, 'reason');
    $description = requireField($body, 'description', 2000, 'description');
    $outcome     = requireField($body, 'desiredOutcome', 60, 'desiredOutcome');

    $reason  = requireOneOf($reason, ['Work quality', 'Missed deadline', 'Communication breakdown', 'Other'], 'reason', 'raw');
    $outcome = requireOneOf($outcome, ['Request a revision', 'Partial refund', 'Full refund'], 'desired outcome', 'raw');

    if (mb_strlen($description) < 10) {
        sendResponse(['message' => 'Please describe the issue in at least 10 characters'], 400);
    }

    $partial = null;
    if ($outcome === 'Partial refund') {
        $partial = requireAmount($body['partialRefundAmount'] ?? null, 'partialRefundAmount', 0.01);
    } elseif (isset($body['partialRefundAmount']) && $body['partialRefundAmount'] !== '') {
        $partial = requireAmount($body['partialRefundAmount'], 'partialRefundAmount', 0.01);
    }

    // Load and lock the milestone
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
    if ($ms['status'] === 'Paid') {
        sendResponse(['message' => 'This milestone has already been paid — raise a refund request instead'], 409);
    }
    if ($ms['status'] === 'Disputed') {
        sendResponse(['message' => 'A dispute is already open on this milestone'], 409);
    }

    if ($partial !== null && $partial > (float)$ms['amount']) {
        sendResponse(['message' => 'Partial refund cannot exceed the milestone amount of $' . number_format((float)$ms['amount'], 2)], 400);
    }

    $pdo->beginTransaction();

    // Sequence the DIS-YYYY-NNNN reference
    $year = date('Y');
    $seqStmt = $pdo->prepare("SELECT COUNT(*) FROM disputes WHERE id LIKE ?");
    $seqStmt->execute(["DIS-$year-%"]);
    $seq  = (int)$seqStmt->fetchColumn() + 1;
    $disputeId = sprintf('DIS-%s-%04d', $year, $seq);

    $now    = date('Y-m-d H:i:s');
    $dueAt  = date('Y-m-d H:i:s', strtotime('+3 days'));

    $pdo->prepare("
        INSERT INTO disputes
            (id, milestone_id, job_id, client_id, freelancer_id, reason, description,
             desired_outcome, partial_refund_amount, status, response_due_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Under review', ?)
    ")->execute([
        $disputeId, $milestoneId, $ms['job_id'], $ms['client_id'], $ms['freelancer_id'],
        $reason, $description, $outcome, $partial, $dueAt
    ]);

    // Freeze the milestone and its escrow
    $pdo->prepare("UPDATE project_milestones SET status = 'Disputed' WHERE id = ?")
        ->execute([$milestoneId]);
    $pdo->prepare("UPDATE payments SET status = 'Held in escrow', receipt_id = NULL WHERE milestone_id = ? AND status = 'Held in escrow'")
        ->execute([$milestoneId]);
    // jobs.status has no 'Disputed' member, so the project stays 'In Progress'
    // while the milestone itself carries the 'Disputed' state.
    $pdo->prepare("UPDATE jobs SET status = 'In Progress' WHERE id = ? AND status IN ('Open', 'In Progress')")
        ->execute([$ms['job_id']]);

    if (!empty($ms['freelancer_id'])) {
        $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, body, link)
            VALUES (?, 'dispute', 'Dispute filed', ?, ?)
        ")->execute([
            $ms['freelancer_id'],
            "{$client['name']} opened dispute {$disputeId} ({$reason}). You have 3 days to respond.",
            '../freelancer/freelancer_chat.html'
        ]);
    }

    $pdo->commit();

    sendResponse([
        'message'     => "Dispute {$disputeId} filed. Escrow is locked until it is resolved.",
        'disputeId'   => $disputeId,
        'milestoneId' => $milestoneId,
        'status'      => 'Under review',
        'reason'      => $reason,
        'desiredOutcome' => $outcome,
        'partialRefundAmount' => $partial,
        'responseDueAt' => $dueAt,
        'escrowHeld'  => round((float)$ms['amount'] + (float)$ms['escrow_fee'], 2)
    ], 201);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse(['message' => 'Failed to file dispute: ' . $e->getMessage()], 500);
}
