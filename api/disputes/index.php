<?php
/**
 * Endpoint: GET /api/disputes
 *
 * Lists the disputes the caller is party to — clients see the ones they filed,
 * freelancers the ones raised against their milestones. Powers
 * client/raise-dispute.html and the freelancer project view.
 *
 * Optional query: ?status=Under review|Resolved|Withdrawn|Escalated|all
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $user = requireRole($pdo, ['CLIENT', 'FREELANCER']);

    $statusFilter = (string)($_GET['status'] ?? 'all');
    if ($statusFilter !== 'all') {
        $statusFilter = requireOneOf(
            $statusFilter,
            ['Under review', 'Resolved', 'Withdrawn', 'Escalated'],
            'status',
            'raw'
        );
    }

    // Party to the dispute = the client who filed it, or the hired freelancer.
    $sql = "SELECT d.*, m.label AS milestone_label, m.amount AS milestone_amount,
                   m.status AS milestone_status, j.title AS job_title
            FROM disputes d
            INNER JOIN project_milestones m ON m.id = d.milestone_id
            INNER JOIN jobs j ON j.id = d.job_id
            WHERE (d.client_id = ? OR d.freelancer_id = ?)";
    $params = [$user['id'], $user['id']];

    if ($statusFilter !== 'all') {
        $sql .= " AND d.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY d.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $items = [];
    foreach ($stmt->fetchAll() as $r) {
        $isClient = $r['client_id'] === $user['id'];
        $items[] = [
            'id'                => $r['id'],
            'milestoneId'       => $r['milestone_id'],
            'milestoneLabel'    => $r['milestone_label'],
            'milestoneAmount'   => (float)$r['milestone_amount'],
            'milestoneStatus'   => $r['milestone_status'],
            'jobId'             => $r['job_id'],
            'jobTitle'          => $r['job_title'],
            'role'              => $isClient ? 'CLIENT' : 'FREELANCER',
            'counterpartyId'    => $isClient ? $r['freelancer_id'] : $r['client_id'],
            'reason'            => $r['reason'],
            'description'       => $r['description'],
            'desiredOutcome'    => $r['desired_outcome'],
            'partialRefundAmount' => $r['partial_refund_amount'] !== null ? (float)$r['partial_refund_amount'] : null,
            'status'            => $r['status'],
            'freelancerResponse' => $r['freelancer_response'],
            'respondedAt'       => $r['responded_at'],
            'resolution'        => $r['resolution'],
            'responseDueAt'     => $r['response_due_at'],
            'createdAt'         => $r['created_at'],
            'resolvedAt'        => $r['resolved_at'],
            'canRespond'        => !$isClient
                && $r['status'] === 'Under review'
                && $r['freelancer_response'] === null,
        ];
    }

    sendResponse([
        'disputes' => $items,
        'total'    => count($items),
        'openCount' => count(array_filter($items, fn($d) => $d['status'] === 'Under review' || $d['status'] === 'Escalated')),
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Dispute error: ' . $e->getMessage()], 500);
}
