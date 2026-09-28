<?php
/**
 * Endpoint: GET  /api/admin/reports                       — previously generated reports
 * Endpoint: POST /api/admin/reports                       — generate a report
 * Endpoint: GET  /api/admin/reports/{id}/download         — stream it as a PDF
 *
 * Powers Admin/html/reports-generator.html.
 *
 * Body (POST): { reportType: 'financial'|'user-growth'|'fraud-log', dateFrom?, dateTo? }
 * Dates arrive from the HTML date inputs as DD/MM/YYYY.
 *
 * A report row only stores its parameters; the PDF is rendered from the live
 * data on demand, so a stored report can never go stale.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/pdf.php';

const REPORT_TYPES = ['financial', 'user-growth', 'fraud-log'];

/**
 * Parse a DD/MM/YYYY (or YYYY-MM-DD) date into a MySQL date, or null when blank.
 * Sends 400 and exits when the value is present but unparseable.
 */
function reportDate($value, string $label): ?string {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $value, $m)) {
        [$d, $mo, $y] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m)) {
        [$y, $mo, $d] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } else {
        sendResponse(['message' => "Field '$label' must be a date in DD/MM/YYYY format"], 400);
        return null;
    }

    if (!checkdate($mo, $d, $y)) {
        sendResponse(['message' => "Field '$label' is not a real calendar date"], 400);
    }

    return sprintf('%04d-%02d-%02d', $y, $mo, $d);
}

/**
 * Human label for a report type.
 */
function reportTypeLabel(string $type): string {
    return [
        'financial'   => 'Financial Summary',
        'user-growth' => 'User Growth',
        'fraud-log'   => 'Fraud & Abuse Log',
    ][$type] ?? $type;
}

/**
 * Run the aggregate queries behind a report and return label => value pairs
 * per section. Keys are already formatted for the PDF.
 */
function reportSections(PDO $pdo, string $type, ?string $from, ?string $to): array {
    $window = static function (string $column) use ($from, $to): array {
        $sql  = "SELECT COALESCE($column, created_at) AS d FROM users WHERE 1 = 1";
        $args = [];
        if ($from !== null) {
            $sql .= " AND COALESCE($column, created_at) >= ?";
            $args[] = $from;
        }
        if ($to !== null) {
            $sql .= " AND COALESCE($column, created_at) <= ?";
            $args[] = $to . ' 23:59:59';
        }
        return [$sql, $args];
    };

    if ($type === 'financial') {
        // Columns are aliased and read by name: the connection defaults to
        // PDO::FETCH_ASSOC, so positional destructuring would not work.
        $released = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS gross, COUNT(*) AS n
                                   FROM payments
                                   WHERE status = 'Released'
                                     AND (? IS NULL OR created_at >= ?)
                                     AND (? IS NULL OR created_at <= ?)");
        $released->execute([$from, $from, $to, $to]);
        $row = $released->fetch();
        $gross = (float)($row['gross'] ?? 0);
        $releaseCount = (int)($row['n'] ?? 0);

        $held = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS held, COUNT(*) AS n
                               FROM payments
                               WHERE status = 'Held in escrow'");
        $row = $held->fetch();
        $heldTotal = (float)($row['held'] ?? 0);
        $heldCount = (int)($row['n'] ?? 0);

        $refunds = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS refunded, COUNT(*) AS n
                                  FROM payments WHERE status = 'Refunded'");
        $row = $refunds->fetch();
        $refundTotal = (float)($row['refunded'] ?? 0);
        $refundCount = (int)($row['n'] ?? 0);

        $fees = $pdo->prepare("SELECT COALESCE(SUM(escrow_fee), 0) FROM payments");
        $feeTotal = (float)$fees->fetchColumn();

        $disputes = $pdo->prepare("SELECT COUNT(*) FROM disputes WHERE status <> 'Resolved'");
        $openDisputes = (int)$disputes->fetchColumn();

        return [
            'Escrow & Payouts' => [
                'Payments released'      => $releaseCount . ' (' . number_format((float)$gross, 2) . ')',
                'Currently in escrow'    => $heldCount . ' (' . number_format((float)$heldTotal, 2) . ')',
                'Refunded'               => $refundCount . ' (' . number_format((float)$refundTotal, 2) . ')',
                'Platform fees collected' => number_format($feeTotal, 2),
                'Unresolved disputes'    => (string)$openDisputes,
            ],
        ];
    }

    if ($type === 'user-growth') {
        [$sql, $args] = $window('created_at');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        $total = 0;
        $freelancers = 0;
        $clients = 0;
        $admins = 0;
        foreach ($stmt->fetchAll() as $row) {
            $total++;
            $roleStmt = $pdo->prepare("SELECT role FROM users WHERE created_at = ? LIMIT 1");
            $roleStmt->execute([$row['d']]);
            $role = (string)$roleStmt->fetchColumn();
            if ($role === 'FREELANCER') {
                $freelancers++;
            } elseif ($role === 'CLIENT') {
                $clients++;
            } elseif ($role === 'ADMIN') {
                $admins++;
            }
        }

        $suspended = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'Suspended'");
        $suspended->execute();

        $pending = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE status = 'Pending'");
        $pending->execute();

        return [
            'Accounts' => [
                'Total signups in range' => (string)$total,
                'Freelancers'           => (string)$freelancers,
                'Clients'               => (string)$clients,
                'Admins'                => (string)$admins,
                'Suspended accounts'    => (string)(int)$suspended->fetchColumn(),
                'Awaiting approval'     => (string)(int)$pending->fetchColumn(),
            ],
        ];
    }

    // fraud-log
    $flagged = $pdo->prepare("SELECT id, name, email, status FROM users
                              WHERE status IN ('Flagged', 'Suspended') ORDER BY name ASC");
    $flagged->execute();

    $rows = ['Flagged / suspended accounts' => []];
    foreach ($flagged->fetchAll() as $u) {
        $rows['Flagged / suspended accounts'][] = $u['name'] . ' <' . $u['email'] . '> - ' . $u['status'];
    }
    if (!$rows['Flagged / suspended accounts']) {
        $rows['Flagged / suspended accounts'][] = 'None recorded.';
    }

    $disputes = $pdo->prepare("SELECT id, reason, status, created_at FROM disputes ORDER BY created_at DESC");
    $disputes->execute();
    $rows['Disputes'] = [];
    foreach ($disputes->fetchAll() as $d) {
        $rows['Disputes'][] = $d['id'] . ' - ' . $d['reason'] . ' (' . $d['status'] . ') on ' . substr((string)$d['created_at'], 0, 10);
    }
    if (!$rows['Disputes']) {
        $rows['Disputes'][] = 'None recorded.';
    }

    return $rows;
}

/**
 * Render a report to PDF bytes.
 */
function buildReportPdf(PDO $pdo, array $report): string {
    $type = (string)$report['report_type'];
    $from = $report['date_from'];
    $to   = $report['date_to'];

    $pdf = new SimplePdf();
    $pdf->add('SkillMatch - ' . reportTypeLabel($type), 16, true);
    $pdf->add('Generated ' . date('d/m/Y H:i'), 9);
    $pdf->add('Report ID: ' . $report['id'], 9);
    $pdf->add('Period: ' . ($from ? date('d/m/Y', strtotime($from)) : 'All time')
        . ' to ' . ($to ? date('d/m/Y', strtotime($to)) : 'Present'), 9);
    $pdf->blank();
    $pdf->rule();

    foreach (reportSections($pdo, $type, $from, $to) as $section => $lines) {
        $pdf->add($section, 12, true);
        $pdf->blank();
        foreach ((array)$lines as $label => $value) {
            if (is_int($label)) {
                $pdf->add('  ' . $value, 10, false, 10);
            } else {
                $pdf->add($label . ': ' . $value, 10);
            }
        }
        $pdf->blank();
        $pdf->rule();
    }

    $pdf->add('End of report', 9);
    return $pdf->render();
}

try {
    $admin  = requireRole($pdo, ['ADMIN']);
    $method = $_SERVER['REQUEST_METHOD'];

    // -------------------------------------------------------------- download
    $reportId = routeParam('id');
    if ($method === 'GET' && $reportId !== '' && strpos(routeName(), '/download') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? LIMIT 1");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        if (!$report) {
            sendResponse(['message' => 'Report not found'], 404);
        }

        $pdf = buildReportPdf($pdo, $report);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename((string)$report['filename']) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }

    // ------------------------------------------------------------------ list
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT r.id, r.report_type, r.date_from, r.date_to, r.filename,
                                    r.generated_by, r.created_at, u.name AS generated_by_name
                             FROM reports r
                             LEFT JOIN users u ON u.id = r.generated_by
                             ORDER BY r.created_at DESC");
        $items = [];
        foreach ($stmt->fetchAll() as $r) {
            $items[] = [
                'id'           => $r['id'],
                'reportType'   => $r['report_type'],
                'label'        => reportTypeLabel($r['report_type']),
                'dateFrom'     => $r['date_from'],
                'dateTo'       => $r['date_to'],
                'filename'     => $r['filename'],
                'generatedBy'  => $r['generated_by'],
                'generatedByName' => $r['generated_by_name'],
                'createdAt'    => $r['created_at'],
                'downloadUrl'  => 'api/admin/reports/' . $r['id'] . '/download',
            ];
        }
        sendResponse(['reports' => $items, 'total' => count($items)], 200);
    }

    // --------------------------------------------------------------- generate
    if ($method === 'POST') {
        $body       = getRequestBody();
        $reportType = requireOneOf((string)($body['reportType'] ?? ''), REPORT_TYPES, 'report type', 'lower');

        $dateFrom = reportDate($body['dateFrom'] ?? '', 'dateFrom');
        $dateTo   = reportDate($body['dateTo'] ?? '', 'dateTo');

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            sendResponse(['message' => 'dateFrom must be on or before dateTo'], 400);
        }

        $id       = newId('rep');
        $filename = 'skillmatch-' . $reportType . '-' . $id . '.pdf';

        $ins = $pdo->prepare("INSERT INTO reports (id, report_type, date_from, date_to, filename, generated_by)
                              VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$id, $reportType, $dateFrom, $dateTo, $filename, $admin['id']]);

        sendResponse([
            'message'    => 'Report generated',
            'id'         => $id,
            'reportType' => $reportType,
            'label'      => reportTypeLabel($reportType),
            'filename'   => $filename,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'downloadUrl' => 'api/admin/reports/' . $id . '/download',
        ], 201);
    }

    sendResponse(['message' => 'Method Not Allowed'], 405);

} catch (PDOException $e) {
    sendResponse(['message' => 'Report error: ' . $e->getMessage()], 500);
}
