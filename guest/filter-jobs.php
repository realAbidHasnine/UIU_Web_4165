<?php
/**
 * Form handler: POST public_job_listing.html#jobFilterForm
 * Maps the page's category checkboxes onto the category codes actually stored
 * in the jobs table, then redirects back to the listing with query params.
 *
 * NOTE: the page offers a "content_writing" filter but the jobs table has no
 * content category, so that value maps to nothing and yields an empty list.
 * Flagged in the final report as a content/data gap.
 */

$categories = $_POST['job_categories'] ?? [];
if (!is_array($categories)) {
    $categories = [$categories];
}

$MAP = [
    'web_development' => 'web',
    'ui_ux_design'    => 'uiux',
    'mobile_app_dev'  => 'mobile',
    'cloud_devops'    => 'cloud',
    'data_science'    => 'data',
];

$mapped = [];
foreach ($categories as $c) {
    $c = trim((string)$c);
    if (isset($MAP[$c])) {
        $mapped[] = $MAP[$c];
    }
}
$mapped = array_values(array_unique($mapped));

$budget = trim((string)($_POST['budget_range'] ?? ''));
$allowedBudget = ['', '1k-3k', '3k-5k', '5k+'];
if (!in_array($budget, $allowedBudget, true)) {
    $budget = '';
}

$params = [];
if ($mapped) {
    $params['categories'] = implode(',', $mapped);
}
if ($budget !== '') {
    $params['budget'] = $budget;
}
$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $params['search'] = $search;
}

$qs = http_build_query($params);
header('Location: public_job_listing.html' . ($qs !== '' ? '?' . $qs : ''));
exit();
