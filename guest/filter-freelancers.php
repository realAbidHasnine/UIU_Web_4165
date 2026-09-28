<?php
/**
 * Form handler: POST browse_freelancer.html#freelancerFilterForm
 * Translates the filter checkboxes into query params and redirects back to the
 * listing page, so filtering still works when JavaScript is unavailable.
 * The JS path (browse-freelancers.js) sends the same params to GET /api/freelancers.
 */

$categories = $_POST['skill_categories'] ?? [];
if (!is_array($categories)) {
    $categories = [$categories];
}
$allowed = ['web_development', 'ui_ux_design', 'content_writing'];
$categories = array_values(array_intersect(
    array_map(fn($c) => trim((string)$c), $categories),
    $allowed
));

$experience = trim((string)($_POST['experience_level'] ?? ''));
if (!in_array($experience, ['entry', 'intermediate', 'expert'], true)) {
    $experience = '';
}

$params = [];
if ($categories) {
    $params['categories'] = implode(',', $categories);
}
if ($experience !== '') {
    $params['experience'] = $experience;
}

// Carry the active search term through so filtering does not clear it
$search = trim((string)($_POST['freelancer_query'] ?? $_GET['search'] ?? ''));
if ($search !== '') {
    $params['search'] = $search;
}

$qs = http_build_query($params);
header('Location: browse_freelancer.html' . ($qs !== '' ? '?' . $qs : ''));
exit();
