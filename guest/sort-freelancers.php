<?php
/**
 * Form handler: POST browse_freelancer.html#freelancerSortForm
 * Preserves the chosen sort order across the redirect back to the listing.
 */

$sort = trim((string)($_POST['sort_by'] ?? ''));
$allowed = ['skill_score', 'price_asc', 'price_desc', 'rating'];
if (!in_array($sort, $allowed, true)) {
    $sort = '';
}

$params = [];
if ($sort !== '') {
    $params['sort'] = $sort;
}

// Keep any active filters/search in place
foreach (['search' => 'search', 'categories' => 'categories', 'experience' => 'experience'] as $post => $get) {
    $val = trim((string)($_POST[$post] ?? $_GET[$get] ?? ''));
    if ($val !== '') {
        $params[$get] = $val;
    }
}

$qs = http_build_query($params);
header('Location: browse_freelancer.html' . ($qs !== '' ? '?' . $qs : ''));
exit();
