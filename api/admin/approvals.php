<?php
/**
 * Endpoint: GET  /api/admin/approvals?type=FREELANCER|CLIENT&status=Pending|Approved|Rejected|all&search=
 * Endpoint: POST /api/admin/approvals/{id}/decide     body: { decision: 'Approved'|'Rejected', reason? }
 *
 * Powers Admin/html/freelancer-approvals.html and Admin/html/client-approvals.html.
 */

require_once __DIR__ . '/../config/db.php';

try {
    $admin  = requireRole($pdo, ['ADMIN']);
    $method = $_SERVER['REQUEST_METHOD'];

    // ------------------------------------------------------------------ list
    if ($method === 'GET') {
        $type   = requireOneOf((string)($_GET['type'] ?? 'FREELANCER'), ['FREELANCER', 'CLIENT'], 'type');
        $status = requireOneOf((string)($_GET['status'] ?? 'Pending'), ['Pending', 'Approved', 'Rejected', 'all'], 'status', 'raw');
        $search = trim((string)($_GET['search'] ?? ''));

        $sql = "SELECT a.id, a.type, a.status, a.portfolio_url, a.github_url, a.skills,
                       a.reject_reason, a.reviewed_by, a.reviewed_at, a.created_at,
                       u.id AS user_id, u.name, u.email, u.title, u.company, u.location,
                       u.bio, u.hourly_rate, u.rating, u.score, u.status AS user_status,
                       u.created_at AS user_created_at
                FROM approvals a
                INNER JOIN users u ON u.id = a.user_id
                WHERE a.type = ?";
        $params = [$type];

        if ($status !== 'all') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY FIELD(a.status, 'Pending', 'Approved', 'Rejected'), a.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll() as $r) {
            $skills = array_values(array_filter(array_map('trim', explode(',', (string)$r['skills']))));
            $items[] = [
                'id'           => $r['id'],
                'type'         => $r['type'],
                'status'       => $r['status'],
                'userId'       => $r['user_id'],
                'name'         => $r['name'],
                'email'        => $r['email'],
                'title'        => $r['title'],
                'company'      => $r['company'],
                'location'     => $r['location'],
                'bio'          => $r['bio'],
                'hourlyRate'   => (float)$r['hourly_rate'],
                'rating'       => (float)$r['rating'],
                'score'        => (int)$r['score'],
                'portfolioUrl' => $r['portfolio_url'],
                'githubUrl'    => $r['github_url'],
                'skills'       => $skills,
                'rejectReason' => $r['reject_reason'],
                'reviewedBy'   => $r['reviewed_by'],
                'reviewedAt'   => $r['reviewed_at'],
                'userStatus'   => $r['user_status'],
                'appliedAt'    => $r['created_at'],
            ];
        }

        $counts = $pdo->prepare("SELECT status, COUNT(*) AS n FROM approvals WHERE type = ? GROUP BY status");
        $counts->execute([$type]);
        $byStatus = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
        foreach ($counts->fetchAll() as $row) {
            $byStatus[$row['status']] = (int)$row['n'];
        }

        sendResponse([
            'approvals' => $items,
            'type'      => $type,
            'status'    => $status,
            'counts'    => $byStatus,
        ], 200);
    }

    // ---------------------------------------------------------------- decide
    if ($method === 'POST') {
        $approvalId = routeParam('id');
        if ($approvalId === '') {
            sendResponse(['message' => 'Approval request ID is required'], 400);
        }

        $body     = getRequestBody();
        $decision = requireOneOf((string)($body['decision'] ?? ''), ['Approved', 'Rejected'], 'decision', 'raw');
        $reason   = trim((string)($body['reason'] ?? ''));

        if ($decision === 'Rejected' && $reason === '') {
            sendResponse(['message' => 'A reason is required when rejecting an application'], 400);
        }
        if (mb_strlen($reason) > 255) {
            sendResponse(['message' => 'Reason must be 255 characters or fewer'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM approvals WHERE id = ? LIMIT 1");
        $stmt->execute([$approvalId]);
        $approval = $stmt->fetch();
        if (!$approval) {
            sendResponse(['message' => 'Approval request not found'], 404);
        }
        if ($approval['status'] !== 'Pending') {
            sendResponse(['message' => 'This application was already ' . strtolower($approval['status'])], 409);
        }

        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare("UPDATE approvals
                                  SET status = ?, reject_reason = ?, reviewed_by = ?, reviewed_at = NOW()
                                  WHERE id = ?");
            $upd->execute([
                $decision,
                $decision === 'Rejected' ? $reason : null,
                $admin['id'],
                $approvalId,
            ]);

            $usr = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $usr->execute([
                $decision === 'Approved' ? 'Active' : 'Suspended',
                $approval['user_id'],
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            sendResponse(['message' => 'Could not record the decision: ' . $e->getMessage()], 500);
        }

        $isFreelancer = $approval['type'] === 'FREELANCER';
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, body, link)
                                VALUES (?, ?, ?, ?, ?)");
        $notif->execute([
            $approval['user_id'],
            $decision === 'Approved' ? 'success' : 'warning',
            $decision === 'Approved' ? 'Application approved' : 'Application declined',
            $decision === 'Approved'
                ? 'Good news — your SkillMatch account is now active.'
                : 'Your application was not approved. Reason: ' . $reason,
            $isFreelancer ? 'freelancer-dashboard.html' : 'client-dashboard.html',
        ]);

        sendResponse([
            'message' => 'Application ' . strtolower($decision),
            'id'      => $approvalId,
            'status'  => $decision,
        ], 200);
    }

    sendResponse(['message' => 'Method Not Allowed'], 405);

} catch (PDOException $e) {
    sendResponse(['message' => 'Approval error: ' . $e->getMessage()], 500);
}
