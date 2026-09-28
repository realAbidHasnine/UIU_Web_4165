<?php
/**
 * Endpoint: GET /api/clients/me/billing
 * The client's escrow ledger: summary stats plus month-grouped payment rows.
 * Powers billing-payments.html.
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);
    $clientId = $client['id'];

    // Summary
    $stats = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN status = 'Released' THEN total ELSE 0 END), 0) AS released_total,
            COALESCE(SUM(CASE WHEN status = 'Held in escrow' THEN total ELSE 0 END), 0) AS held_total,
            COALESCE(SUM(CASE WHEN status = 'Refunded' THEN total ELSE 0 END), 0)  AS refunded_total,
            COALESCE(SUM(total), 0) AS lifetime_total
        FROM payments WHERE client_id = ?
    ");
    $stats->execute([$clientId]);
    $s = $stats->fetch();

    // Released this month
    $thisMonth = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) FROM payments
        WHERE client_id = ? AND status = 'Released'
          AND released_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
    ");
    $thisMonth->execute([$clientId]);
    $monthReleased = (float)$thisMonth->fetchColumn();

    // Ledger rows
    $rows = $pdo->prepare("
        SELECT p.*, m.label AS milestone_label, j.title AS job_title, f.name AS freelancer_name
        FROM payments p
        INNER JOIN project_milestones m ON m.id = p.milestone_id
        INNER JOIN jobs j ON j.id = p.job_id
        LEFT  JOIN users f ON f.id = p.freelancer_id
        WHERE p.client_id = ?
        ORDER BY COALESCE(p.released_at, p.created_at) DESC, p.created_at DESC
    ");
    $rows->execute([$clientId]);

    $entries = [];
    foreach ($rows->fetchAll() as $r) {
        $ts = $r['released_at'] ?: $r['created_at'];
        $entries[] = [
            'id'            => $r['id'],
            'milestoneId'   => $r['milestone_id'],
            'milestoneLabel'=> $r['milestone_label'],
            'jobTitle'      => $r['job_title'],
            'freelancerName'=> $r['freelancer_name'] ?? 'Unassigned',
            'amount'        => (float)$r['amount'],
            'escrowFee'     => (float)$r['escrow_fee'],
            'total'         => (float)$r['total'],
            'status'        => $r['status'],
            'receiptId'     => $r['receipt_id'],
            'note'          => $r['status'] === 'Held in escrow' ? 'Releases on approval' : null,
            'date'          => date('M j, Y', strtotime($ts)),
            'dateSort'      => date('Y-m-d', strtotime($ts)),
            'monthKey'      => date('Y-m', strtotime($ts)),
            'monthLabel'    => date('F Y', strtotime($ts))
        ];
    }

    // Group into months, preserving the newest-first ordering of the query
    $groups = [];
    foreach ($entries as $e) {
        if (!isset($groups[$e['monthKey']])) {
            $groups[$e['monthKey']] = [
                'monthKey'   => $e['monthKey'],
                'monthLabel' => $e['monthLabel'],
                'released'   => 0.0,
                'held'       => 0.0,
                'entries'    => []
            ];
        }
        if ($e['status'] === 'Released') {
            $groups[$e['monthKey']]['released'] += $e['total'];
        } elseif ($e['status'] === 'Held in escrow') {
            $groups[$e['monthKey']]['held'] += $e['total'];
        }
        $groups[$e['monthKey']]['entries'][] = $e;
    }
    foreach ($groups as &$g) {
        $g['released'] = round($g['released'], 2);
        $g['held']     = round($g['held'], 2);
    }
    unset($g);

    sendResponse([
        'summary' => [
            'totalSpent'      => round((float)$s['lifetime_total'], 2),
            'heldInEscrow'    => round((float)$s['held_total'], 2),
            'releasedTotal'   => round((float)$s['released_total'], 2),
            'refundedTotal'   => round((float)$s['refunded_total'], 2),
            'releasedThisMonth' => round($monthReleased, 2),
            'escrowFeeRate'   => '3%',
            'monthLabel'      => date('F Y')
        ],
        'groups'  => array_values($groups),
        'entries' => $entries
    ], 200);

} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to load billing data: ' . $e->getMessage()], 500);
}
