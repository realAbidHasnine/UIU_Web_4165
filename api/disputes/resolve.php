<?php
/**
 * Endpoint: POST /api/disputes/{id}/resolve
 * Role: ADMIN — only an administrator can move escrowed funds.
 *
 * Body: {
 *   resolution: string,
 *   outcome: 'Release to freelancer'|'Refund client'|'Partial refund',
 *   partialRefundAmount?: number   // required when outcome = 'Partial refund'
 * }
 *
 * Releasing marks the milestone Paid and the payment Released; refunding sends
 * the money back to the client and reopens the milestone so the parties can
 * renegotiate.
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $admin = requireRole($pdo, ['ADMIN']);

    $body      = getRequestBody();
    $disputeId = routeParam('id');
    if ($disputeId === '') {
        $disputeId = (string)($_GET['id'] ?? ($body['id'] ?? ($body['disputeId'] ?? pathSegment('disputes', 1))));
    }
    if ($disputeId === '') {
        sendResponse(['message' => 'Dispute ID is required'], 400);
    }
    $outcome   = requireField($body, 'outcome', 60, 'outcome');
    $resolution = requireField($body, 'resolution', 2000, 'resolution');

    $outcome = requireOneOf(
        $outcome,
        ['Release to freelancer', 'Refund client', 'Partial refund'],
        'outcome',
        'raw'
    );

    if (mb_strlen($resolution) < 10) {
        sendResponse(['message' => 'Please record at least 10 characters of reasoning'], 400);
    }

    $partial = null;
    if ($outcome === 'Partial refund') {
        $partial = requireAmount($body['partialRefundAmount'] ?? null, 'partialRefundAmount', 0.01);
    }

    $stmt = $pdo->prepare("SELECT d.*, m.label AS milestone_label, m.amount AS milestone_amount,
                                  m.status AS milestone_status
                           FROM disputes d
                           INNER JOIN project_milestones m ON m.id = d.milestone_id
                           WHERE d.id = ? LIMIT 1");
    $stmt->execute([$disputeId]);
    $dispute = $stmt->fetch();

    if (!$dispute) {
        sendResponse(['message' => 'Dispute not found'], 404);
    }
    if ($dispute['status'] === 'Resolved' || $dispute['status'] === 'Withdrawn') {
        sendResponse(['message' => 'This dispute is already ' . strtolower($dispute['status'])], 409);
    }

    $milestoneAmount = (float)$dispute['milestone_amount'];
    if ($partial !== null && $partial > $milestoneAmount) {
        sendResponse([
            'message' => 'Partial refund cannot exceed the milestone amount of $' . number_format($milestoneAmount, 2),
        ], 400);
    }

    $releaseToFreelancer = $outcome === 'Release to freelancer';
    $pdo->beginTransaction();

    try {
        // 1. Close the dispute.
        $pdo->prepare("UPDATE disputes
                       SET status = 'Resolved', resolution = ?, resolved_at = NOW()
                       WHERE id = ?")
            ->execute([$resolution, $disputeId]);

        // 2. Move the money.
        if ($releaseToFreelancer) {
            $releasedAmount = $milestoneAmount;
        } elseif ($partial !== null) {
            $releasedAmount = round($milestoneAmount - $partial, 2);
        } else {
            $releasedAmount = 0.0;
        }

        $payment = $pdo->prepare("SELECT * FROM payments WHERE milestone_id = ? ORDER BY created_at DESC LIMIT 1");
        $payment->execute([$dispute['milestone_id']]);
        $pay = $payment->fetch();

        if ($pay) {
            if ($releasedAmount > 0) {
                $receipt = 'RCP-' . strtoupper(bin2hex(random_bytes(4)));
                $pdo->prepare("UPDATE payments
                               SET amount = ?, status = 'Released', receipt_id = ?, released_at = NOW()
                               WHERE id = ?")
                    ->execute([$releasedAmount, $receipt, $pay['id']]);

                if (!empty($dispute['freelancer_id'])) {
                    $pdo->prepare("UPDATE users SET earnings = earnings + ? WHERE id = ?")
                        ->execute([$releasedAmount, $dispute['freelancer_id']]);
                }
            } else {
                $pdo->prepare("UPDATE payments SET status = 'Refunded', receipt_id = NULL WHERE id = ?")
                    ->execute([$pay['id']]);
            }
        }

        // 3. Put the milestone into its new state.
        if ($releaseToFreelancer) {
            $pdo->prepare("UPDATE project_milestones
                           SET status = 'Paid', approved_at = NOW(), paid_at = NOW()
                           WHERE id = ?")
                ->execute([$dispute['milestone_id']]);
        } else {
            // Reopen so the freelancer can rework it.
            $pdo->prepare("UPDATE project_milestones
                           SET status = 'Posted', submitted_at = NULL, approved_at = NULL, paid_at = NULL
                           WHERE id = ?")
                ->execute([$dispute['milestone_id']]);
        }

        // 4. Re-evaluate the project status.
        $remaining = $pdo->prepare("SELECT COUNT(*) FROM project_milestones
                                    WHERE job_id = ? AND status <> 'Paid'");
        $remaining->execute([$dispute['job_id']]);
        $openCount = (int)$remaining->fetchColumn();
        $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?")
            ->execute([$openCount === 0 ? 'Completed' : 'In Progress', $dispute['job_id']]);

        // 5. Tell both parties.
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link)
                                VALUES (?, ?, ?, ?, ?)");
        $summary = "Dispute {$disputeId} resolved: {$outcome}.";
        $notif->execute([
            $dispute['client_id'],
            'dispute',
            'Dispute ' . $disputeId . ' resolved',
            $summary . ' ' . $resolution,
            '../client/raise-dispute.php',
        ]);
        if (!empty($dispute['freelancer_id'])) {
            $notif->execute([
                $dispute['freelancer_id'],
                'dispute',
                'Dispute ' . $disputeId . ' resolved',
                $summary . ($releasedAmount > 0 ? ' $' . number_format($releasedAmount, 2) . ' was released to you.' : ''),
                '../freelancer/freelancer_projects.html',
            ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse(['message' => 'Could not resolve the dispute: ' . $e->getMessage()], 500);
    }

    sendResponse([
        'message'        => "Dispute {$disputeId} resolved.",
        'disputeId'      => $disputeId,
        'status'         => 'Resolved',
        'outcome'        => $outcome,
        'releasedAmount' => $releasedAmount,
        'refundedAmount' => $partial !== null ? $partial : ($releaseToFreelancer ? 0.0 : $milestoneAmount),
        'milestoneStatus' => $releaseToFreelancer ? 'Paid' : 'Posted',
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Dispute error: ' . $e->getMessage()], 500);
}
