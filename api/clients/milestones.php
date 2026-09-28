<?php
/**
 * Endpoint: GET /api/clients/me/milestones
 * Lists the logged-in client's project milestones with their deliverables,
 * amounts and escrow fees. Powers work-approval.html and my-projects.html.
 *
 * Query: ?status=Submitted|Paid|Disputed|all
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

try {
    $client = requireRole($pdo, ['CLIENT']);
    $clientId = $client['id'];

    $status = trim($_GET['status'] ?? 'all');
    $allowed = ['all', 'Posted', 'Submitted', 'Approved', 'Paid', 'Disputed'];
    $status = requireOneOf($status === '' ? 'all' : $status, $allowed, 'status', 'raw');

    $sql = "
        SELECT m.*,
               j.title AS job_title,
               j.category AS job_category,
               f.name  AS freelancer_name,
               f.title AS freelancer_title,
               f.rating AS freelancer_rating,
               d.id AS dispute_id,
               d.status AS dispute_status,
               r.id AS review_id,
               r.overall AS review_overall
        FROM project_milestones m
        INNER JOIN jobs j ON j.id = m.job_id
        LEFT  JOIN users f ON f.id = m.freelancer_id
        LEFT  JOIN disputes d ON d.milestone_id = m.id
        LEFT  JOIN reviews  r ON r.milestone_id = m.id
        WHERE m.client_id = ?
    ";
    $params = [$clientId];

    if ($status !== 'all') {
        $sql .= " AND m.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY FIELD(m.status,'Submitted','Disputed','Posted','Approved','Paid'), m.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Attach deliverables per milestone
    $delivStmt = $pdo->prepare("
        SELECT id, job_id, milestone_id, notes, file_paths, file_name, repo_url, status, submitted_at
        FROM deliverables WHERE milestone_id = ? ORDER BY submitted_at DESC
    ");

    $milestones = [];
    foreach ($rows as $r) {
        $delivStmt->execute([$r['id']]);
        $deliverables = [];
        foreach ($delivStmt->fetchAll() as $d) {
            $deliverables[] = [
                'id'        => $d['id'],
                'notes'     => $d['notes'] ?? '',
                'files'     => $d['file_paths'] ? json_decode($d['file_paths'], true) : [],
                'fileName'  => $d['file_name'] ?? '',
                'repoUrl'   => $d['repo_url'] ?? '',
                'status'    => $d['status'] ?? 'Submitted',
                'submittedAt' => $d['submitted_at'] ?? null
            ];
        }

        $milestones[] = [
            'id'              => $r['id'],
            'label'           => $r['label'],
            'description'     => $r['description'] ?? '',
            'jobId'           => $r['job_id'],
            'jobTitle'        => $r['job_title'],
            'jobCategory'     => $r['job_category'],
            'freelancerId'    => $r['freelancer_id'],
            'freelancerName'  => $r['freelancer_name'] ?? 'Unassigned',
            'freelancerTitle' => $r['freelancer_title'] ?? '',
            'freelancerRating'=> (float)($r['freelancer_rating'] ?? 0),
            'amount'          => (float)$r['amount'],
            'escrowFee'       => (float)$r['escrow_fee'],
            'total'           => (float)$r['amount'] + (float)$r['escrow_fee'],
            'status'          => $r['status'],
            'dueDate'         => $r['due_date'],
            'submittedAt'     => $r['submitted_at'],
            'approvedAt'      => $r['approved_at'],
            'paidAt'          => $r['paid_at'],
            'deliverables'    => $deliverables,
            'disputeId'       => $r['dispute_id'],
            'disputeStatus'   => $r['dispute_status'],
            'reviewed'        => $r['review_id'] !== null,
            'reviewOverall'   => $r['review_id'] !== null ? (float)$r['review_overall'] : null
        ];
    }

    sendResponse($milestones, 200);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to load milestones: ' . $e->getMessage()], 500);
}
