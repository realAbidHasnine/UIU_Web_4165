<?php
/**
 * Endpoint: GET /api/freelancers
 * Returns verified active freelancers with their real skills from user_skills.
 *
 * Query params (all optional, all safely bound):
 *   search      free text over name / title / skills
 *   categories  comma list of skill slugs, e.g. web_development,ui_ux_design
 *   experience  entry | intermediate | expert   (mapped onto verification score)
 *   maxRate     hourly rate ceiling
 *   sort        skill_score | price_asc | price_desc | rating | newest
 */

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['message' => 'Method Not Allowed'], 405);
}

$search  = trim($_GET['search'] ?? '');
$sort    = trim($_GET['sort'] ?? 'skill_score');
$maxRate = isset($_GET['maxRate']) && $_GET['maxRate'] !== '' ? $_GET['maxRate'] : null;

// Map the browse-page filter checkboxes onto the skill names we store.
$CATEGORY_SKILL_MAP = [
    'web_development' => ['Web Development', 'React', 'Node.js', 'PHP', 'JavaScript', 'Fullstack'],
    'ui_ux_design'    => ['UI/UX Design', 'UI/UX', 'Figma', 'Design Systems', 'Prototyping'],
    'content_writing' => ['Content Writing', 'Copywriting', 'SEO', 'Technical Writing'],
];

$categories = [];
if (!empty($_GET['categories'])) {
    foreach (explode(',', (string)$_GET['categories']) as $c) {
        $c = trim($c);
        if ($c !== '' && isset($CATEGORY_SKILL_MAP[$c])) {
            $categories[] = $c;
        }
    }
}

$experience = trim($_GET['experience'] ?? '');
$experience = in_array($experience, ['entry', 'intermediate', 'expert'], true) ? $experience : '';

// Experience is derived from the verification score stored on the user row.
$EXPERIENCE_BOUNDS = [
    'entry'        => [0, 84],
    'intermediate' => [85, 94],
    'expert'       => [95, 100],
];

$where  = ["u.role = 'FREELANCER'", "u.status = 'Active'"];
$params = [];

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.title LIKE ? OR u.bio LIKE ?
                 OR EXISTS (SELECT 1 FROM user_skills s
                             WHERE s.user_id = u.id AND s.skill_name LIKE ?))";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

if ($categories) {
    $skillNames = [];
    foreach ($categories as $c) {
        $skillNames = array_merge($skillNames, $CATEGORY_SKILL_MAP[$c]);
    }
    if ($skillNames) {
        $holders = implode(',', array_fill(0, count($skillNames), '?'));
        $where[] = "EXISTS (SELECT 1 FROM user_skills s
                     WHERE s.user_id = u.id AND s.skill_name IN ($holders))";
        $params  = array_merge($params, $skillNames);
    }
}

if ($experience !== '' && isset($EXPERIENCE_BOUNDS[$experience])) {
    [$lo, $hi] = $EXPERIENCE_BOUNDS[$experience];
    $where[] = "u.score BETWEEN ? AND ?";
    $params[] = $lo;
    $params[] = $hi;
}

if ($maxRate !== null && is_numeric($maxRate)) {
    $where[] = "u.hourly_rate <= ?";
    $params[] = (float)$maxRate;
}

$orderBy = match ($sort) {
    'price_asc'  => 'u.hourly_rate ASC, u.score DESC',
    'price_desc' => 'u.hourly_rate DESC, u.score DESC',
    'rating'     => 'u.rating DESC, u.score DESC',
    'newest'     => 'u.created_at DESC',
    default      => 'u.score DESC, u.rating DESC'
};

$sql = "SELECT u.* FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $freelancers = [];
    foreach ($rows as $r) {
        $skills = getUserSkills($pdo, $r['id']);

        $freelancers[] = [
            'id'                => $r['id'],
            'name'              => $r['name'],
            'title'             => $r['title'] ?: 'Verified Specialist',
            'bio'               => $r['bio'] ?? '',
            'location'          => $r['location'] ?? 'Remote',
            'score'             => (int)$r['score'],
            'verifiedScore'     => (int)$r['score'],
            'skills'            => $skills,
            'rating'            => (float)$r['rating'],
            'completedProjects' => (int)$r['completed_jobs'],
            'completedJobs'     => (int)$r['completed_jobs'],
            'hourlyRate'        => (float)$r['hourly_rate'],
            'earnings'          => (float)$r['earnings'],
            'category'          => $categories[0] ?? 'web_development',
            'experience'        => (int)$r['score'] >= 95 ? 'expert'
                                  : ((int)$r['score'] >= 85 ? 'intermediate' : 'entry'),
            'verifiedBadge'     => (int)$r['score'] >= 90 ? 'Verified Pro' : null,
            'avatarUrl'         => 'https://i.pravatar.cc/160?u=' . rawurlencode($r['email'])
        ];
    }

    sendResponse($freelancers, 200);
} catch (PDOException $e) {
    sendResponse(['message' => 'Failed to fetch freelancers: ' . $e->getMessage()], 500);
}
