<?php
/**
 * No-JS fallback for client/post-project.html (the multi-step posting wizard).
 *
 * Normal operation is handled by assets/js/post-project.js, which POSTs the
 * assembled payload to POST /api/jobs. Step 1 of the wizard posts here when
 * JavaScript is unavailable; we carry the entered values forward in the query
 * string so nothing typed is lost, and send the user back to step 1.
 */

require_once __DIR__ . '/../config/db.php';

$title  = trim((string)($_POST['required_skills'] ?? ''));
$budgetMin = trim((string)($_POST['budget_min'] ?? ''));
$budgetMax = trim((string)($_POST['budget_max'] ?? ''));
$duration  = trim((string)($_POST['estimated_duration'] ?? ''));

$params = [];
foreach (['required_skills' => $title, 'budget_min' => $budgetMin,
          'budget_max' => $budgetMax, 'estimated_duration' => $duration] as $k => $v) {
    if ($v !== '') {
        $params[$k] = $v;
    }
}
$params['notice'] = 'JavaScript is required to publish a project. Your entries were kept.';

header('Location: post-project.html?' . http_build_query($params));
exit();
