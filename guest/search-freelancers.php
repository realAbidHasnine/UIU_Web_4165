<?php
/**
 * Search freelancers by skill query — redirects to browse_freelancer.html
 * Handles: POST /guest/search-freelancers.php  (from hero search form on index.html)
 */

$skill = trim($_POST['skill_query'] ?? $_GET['skill_query'] ?? '');

// Sanitize and redirect to browse page with search parameter
$safeSkill = urlencode($skill);

if (!empty($safeSkill)) {
    header("Location: browse_freelancer.html?search=" . $safeSkill);
} else {
    header("Location: browse_freelancer.html");
}
exit();
