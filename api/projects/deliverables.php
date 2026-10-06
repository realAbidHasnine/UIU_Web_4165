<?php
/**
 * Endpoint: GET  /api/projects/{projectId}/deliverables
 *           Lists the deliverables a freelancer has submitted for a project.
 * Endpoint: POST /api/projects/{projectId}/deliverables
 *           Submits completed work for client review.
 *
 * POST accepts EITHER:
 *   - multipart/form-data with uploaded_files[] (real bytes stored under
 *     /uploads/deliverables), or
 *   - application/json with file metadata + notes + repoUrl
 *
 * Fields: notes, repoUrl, milestoneId, files[{name,size,type}]
 */

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST'], true)) {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

// Extract project/job ID from URL: /api/projects/{id}/deliverables
$uri = $_SERVER['REQUEST_URI'];
preg_match('#/projects/([a-zA-Z0-9_-]+)/deliverables#', $uri, $matches);
$jobId = routeParam('id') ?: ($matches[1] ?? ($_GET['id'] ?? ($_GET['jobId'] ?? '')));

if ($jobId === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tempBody = getRequestBody();
    $jobId = $tempBody['projectId'] ?? '';
}

if ($jobId === '') {
    sendResponse(['message' => 'Project ID is required'], 400);
}

const MAX_UPLOAD_BYTES = 10485760; // 10 MB per file
const ALLOWED_EXT = ['txt', 'md', 'csv', 'json', 'xml', 'pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp',
                     'zip', 'py', 'js', 'ts', 'tsx', 'jsx', 'php', 'sql', 'html', 'css', 'docx', 'xlsx', 'pptx'];

try {
    $freelancer = requireRole($pdo, ['FREELANCER']);
    $freelancerId = $freelancer['id'];

    // Verify the project exists and this freelancer is actually hired on it
    $jobCheck = $pdo->prepare("SELECT id, title, status, client_id, hired_freelancer_id FROM jobs WHERE id = ? LIMIT 1");
    $jobCheck->execute([$jobId]);
    $job = $jobCheck->fetch();

    if (!$job) {
        sendResponse(['message' => 'Project not found'], 404);
    }

    // ---- GET: list this freelancer's submissions for the project ----
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT id, notes, file_paths, file_name, repo_url, status, milestone_id, submitted_at
            FROM deliverables
            WHERE job_id = ? AND freelancer_id = ?
            ORDER BY submitted_at DESC
        ");
        $stmt->execute([$jobId, $freelancerId]);

        $items = [];
        foreach ($stmt->fetchAll() as $d) {
            $items[] = [
                'id'           => $d['id'],
                'jobId'        => $d['job_id'] ?? $jobId,
                'milestoneId'  => $d['milestone_id'],
                'notes'        => $d['notes'] ?? '',
                'files'        => $d['file_paths'] ? json_decode($d['file_paths'], true) : [],
                'fileName'     => $d['file_name'] ?? '',
                'repoUrl'      => $d['repo_url'] ?? '',
                'status'       => $d['status'],
                'submittedAt'  => $d['submitted_at']
            ];
        }

        sendResponse(['projectId' => $jobId, 'projectTitle' => $job['title'], 'deliverables' => $items], 200);
    }

    // ---- POST: submit work ----
    $isMultipart = !empty($_FILES['uploaded_files']) || isset($_POST['notes']) || isset($_POST['repoUrl']);

    if ($isMultipart) {
        $notes       = trim((string)($_POST['notes'] ?? ''));
        $repoUrl     = trim((string)($_POST['repoUrl'] ?? ''));
        $milestoneId = trim((string)($_POST['milestoneId'] ?? '')) ?: null;
    } else {
        $body        = getRequestBody();
        $notes       = trim((string)($body['notes'] ?? ''));
        $repoUrl     = trim((string)($body['repoUrl'] ?? ''));
        $milestoneId = trim((string)($body['milestoneId'] ?? '')) ?: null;
    }

    $files = $_FILES['uploaded_files'] ?? [];

    if ($notes === '' && empty($files) && $repoUrl === '') {
        sendResponse(['message' => 'Please provide delivery notes, a repository URL, or file attachments'], 400);
    }
    if (mb_strlen($notes) > 5000) {
        sendResponse(['message' => 'Delivery notes must be 5000 characters or fewer'], 400);
    }
    if ($repoUrl !== '' && !filter_var($repoUrl, FILTER_VALIDATE_URL)) {
        sendResponse(['message' => 'Repository URL must be a valid http(s) URL'], 400);
    }
    if ($repoUrl !== '' && !preg_match('#^https?://#i', $repoUrl)) {
        sendResponse(['message' => 'Repository URL must start with http:// or https://'], 400);
    }

    // Validate the milestone belongs to this project (when supplied)
    if ($milestoneId !== null) {
        $msCheck = $pdo->prepare("SELECT id FROM project_milestones WHERE id = ? AND job_id = ?");
        $msCheck->execute([$milestoneId, $jobId]);
        if (!$msCheck->fetch()) {
            sendResponse(['message' => 'Milestone not found on this project'], 404);
        }
    }

    // ---- Store real uploaded bytes ----
    $filePaths = [];
    $primaryName = '';
    $uploadDir = deliverablesDir();

    if (!empty($files) && is_array($files['name'])) {
        $count = count($files['name']);
        if ($count > 10) {
            sendResponse(['message' => 'You can attach at most 10 files per submission'], 400);
        }

        for ($i = 0; $i < $count; $i++) {
            $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                sendResponse(['message' => "Upload failed for '{$files['name'][$i]}' (code $err)"], 400);
            }

            $size = (int)($files['size'][$i] ?? 0);
            if ($size <= 0) {
                sendResponse(['message' => "File '{$files['name'][$i]}' is empty"], 400);
            }
            if ($size > MAX_UPLOAD_BYTES) {
                sendResponse(['message' => "File '{$files['name'][$i]}' exceeds the 10 MB limit"], 400);
            }

            $original = basename((string)$files['name'][$i]);
            $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if ($ext !== '' && !in_array($ext, ALLOWED_EXT, true)) {
                sendResponse(['message' => "File type '.$ext' is not allowed"], 400);
            }

            $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
            $storedName = $safeBase . '-' . bin2hex(random_bytes(4)) . ($ext !== '' ? '.' . $ext : '');
            $target = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

            $tmp = (string)($files['tmp_name'][$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                sendResponse(['message' => "Upload for '{$original}' was not a valid HTTP upload"], 400);
            }
            if (!move_uploaded_file($tmp, $target)) {
                sendResponse(['message' => "Could not store '{$original}' on the server"], 500);
            }

            $filePaths[] = [
                'type'      => 'file',
                'name'      => $original,
                'size'      => $size,
                'sizeLabel' => formatBytes($size),
                'storedAs'  => $storedName,
                'mime'      => (string)($files['type'][$i] ?? 'application/octet-stream')
            ];
            if ($primaryName === '') {
                $primaryName = $original;
            }
        }
    } elseif (!$isMultipart) {
        // JSON mode: record metadata only (no bytes available over JSON)
        $meta = $body['files'] ?? [];
        if (is_array($meta)) {
            foreach ($meta as $f) {
                $name = basename(trim((string)($f['name'] ?? '')));
                if ($name === '') {
                    continue;
                }
                $size = (int)($f['size'] ?? 0);
                $filePaths[] = [
                    'type'      => 'reference',
                    'name'      => $name,
                    'size'      => $size,
                    'sizeLabel' => formatBytes($size)
                ];
                if ($primaryName === '') {
                    $primaryName = $name;
                }
            }
        }
    }

    if ($repoUrl !== '') {
        array_unshift($filePaths, ['type' => 'repo', 'url' => $repoUrl, 'name' => 'Repository Link']);
    }

    $newId = newId('deliv');
    $now   = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO deliverables
            (id, job_id, freelancer_id, milestone_id, notes, file_paths, file_name, repo_url, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)
    ");
    $stmt->execute([
        $newId, $jobId, $freelancerId, $milestoneId, $notes,
        json_encode($filePaths), $primaryName ?: null, $repoUrl ?: null, $now
    ]);

    // Advance the workflow: project -> awaiting approval, milestone -> submitted
    $pdo->prepare("UPDATE jobs SET status = 'Awaiting Approval' WHERE id = ? AND status IN ('Open','In Progress')")
        ->execute([$jobId]);

    if ($milestoneId !== null) {
        $pdo->prepare("
            UPDATE project_milestones SET status = 'Submitted', submitted_at = ?
            WHERE id = ? AND status = 'Posted'
        ")->execute([$now, $milestoneId]);
    }

    // Tell the client there is work to review
    $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, body, link)
        VALUES (?, 'deliverable', 'Work submitted for review', ?, ?)
    ")->execute([
        $job['client_id'],
        $freelancer['name'] . ' submitted deliverables for ' . $job['title'] . '.',
        '../client/work-approval.php?job=' . rawurlencode($jobId)
    ]);

    sendResponse([
        'id'          => $newId,
        'jobId'       => $jobId,
        'milestoneId' => $milestoneId,
        'status'      => 'Submitted',
        'fileCount'   => count($filePaths),
        'files'       => $filePaths,
        'message'     => 'Deliverable package submitted successfully for client review'
    ], 201);

} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to submit deliverable: ' . $e->getMessage()], 500);
}

/** 1536 -> "1.5 KB" */
function formatBytes(int $bytes, int $precision = 1): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow   = $bytes > 0 ? (int)floor(log($bytes, 1024)) : 0;
    $pow   = min($pow, count($units) - 1);
    $value = $bytes / (1024 ** $pow);
    return round($value, $precision) . ' ' . $units[$pow];
}
