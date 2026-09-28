<?php
/**
 * Endpoint: GET /api/clients/me/billing/export
 * Streams the client's escrow ledger as a downloadable CSV file.
 * (The "Export CSV" control on billing-payments.html.)
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);

    $stmt = $pdo->prepare("
        SELECT p.*, m.label AS milestone_label, j.title AS job_title, f.name AS freelancer_name
        FROM payments p
        INNER JOIN project_milestones m ON m.id = p.milestone_id
        INNER JOIN jobs j ON j.id = p.job_id
        LEFT  JOIN users f ON f.id = p.freelancer_id
        WHERE p.client_id = ?
        ORDER BY COALESCE(p.released_at, p.created_at) DESC
    ");
    $stmt->execute([$client['id']]);

    $filename = 'skillmatch-billing-' . $client['id'] . '-' . date('Ymd-His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Job', 'Milestone', 'Freelancer', 'Amount', 'Escrow Fee', 'Total', 'Status', 'Receipt']);

    foreach ($stmt->fetchAll() as $r) {
        fputcsv($out, [
            date('Y-m-d', strtotime($r['released_at'] ?: $r['created_at'])),
            $r['job_title'],
            $r['milestone_label'],
            $r['freelancer_name'] ?? 'Unassigned',
            number_format((float)$r['amount'], 2, '.', ''),
            number_format((float)$r['escrow_fee'], 2, '.', ''),
            number_format((float)$r['total'], 2, '.', ''),
            $r['status'],
            $r['receipt_id'] ?? ''
        ]);
    }
    fclose($out);
    exit();

} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to export billing data: ' . $e->getMessage()], 500);
}
