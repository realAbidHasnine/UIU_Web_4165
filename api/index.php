<?php
/**
 * SkillMatch REST API — front controller.
 *
 * All /api requests are routed here by api/.htaccess and dispatched by the
 * table below. Routing lives in PHP rather than in ~50 mod_rewrite rules
 * because per-directory rewrite rules interact badly with the physical
 * sub-directories under api/ (mod_dir re-enters a sub-directory's config and
 * the ruleset re-applies, which produced infinite redirect loops / HTTP 500).
 *
 * Pattern segments prefixed with ':' match any single path segment.
 * Order matters: the first match wins, so specific routes are listed first.
 */

declare(strict_types=1);

const SKILLMATCH_ROUTES = [
    // --- Auth -------------------------------------------------------------
    ['auth/login',    'auth/login.php'],
    ['auth/register', 'auth/register.php'],

    // --- Jobs & proposals -------------------------------------------------
    ['jobs',                    'jobs/index.php'],
    ['jobs/:id/proposals',      'jobs/proposals.php'],
    ['freelancers/me/proposals', 'proposals/index.php'],
    ['proposals/:id',           'proposals/withdraw.php'],

    // --- Freelancers ------------------------------------------------------
    ['freelancers',                     'freelancers/index.php'],
    ['freelancers/me/portfolio/:id',    'freelancers/portfolio.php'],
    ['freelancers/me/portfolio',        'freelancers/portfolio.php'],
    ['freelancers/me',                  'freelancers/me.php'],
    ['freelancers/:id',                 'freelancers/profile.php'],

    // --- Chat -------------------------------------------------------------
    ['chat/threads',              'chat/threads.php'],
    ['chat/threads/:id/messages', 'chat/messages.php'],

    // --- Skills -----------------------------------------------------------
    ['skills/categories',           'skills/categories.php'],
    ['skills/tests/results/latest', 'skills/results.php'],
    ['skills/tests/:category/submit', 'skills/submit.php'],
    ['skills/tests/:category',      'skills/tests.php'],

    // --- Client workspace -------------------------------------------------
    ['clients/me/dashboard',      'clients/dashboard.php'],
    ['clients/me/projects',       'clients/projects.php'],
    ['clients/me/milestones',     'clients/milestones.php'],
    ['clients/me/billing/export', 'clients/billing_export.php'],
    ['clients/me/billing',        'clients/billing.php'],

    // --- Projects (milestones, deliverables, escrow) ----------------------
    ['projects/:id/deliverables', 'projects/deliverables.php'],
    ['projects/:id/revision',     'projects/revision.php'],
    ['projects/:id/approve',      'projects/approve.php'],

    // --- Disputes ---------------------------------------------------------
    ['disputes',                 'disputes/index.php'],
    ['disputes/create',          'disputes/create.php'],
    ['disputes/:id/respond',     'disputes/respond.php'],
    ['disputes/:id/resolve',     'disputes/resolve.php'],

    // --- Reviews ----------------------------------------------------------
    ['reviews', 'reviews/index.php'],

    // --- Notifications ----------------------------------------------------
    ['notifications/read-all', 'notifications/index.php'],
    ['notifications/:id/read', 'notifications/index.php'],
    ['notifications',          'notifications/index.php'],

    // --- Admin ------------------------------------------------------------
    ['admin/metrics',                              'admin/metrics.php'],
    ['admin/users',                                'admin/users.php'],
    ['admin/users/:id/status',                     'admin/status.php'],
    ['admin/approvals/:id/decide',                 'admin/approvals.php'],
    ['admin/approvals',                            'admin/approvals.php'],
    ['admin/skill-categories/:id',                 'admin/categories.php'],
    ['admin/skill-categories',                     'admin/categories.php'],
    ['admin/skills/:category/questions/:questionId', 'admin/questions.php'],
    ['admin/skills/:category/questions',           'admin/questions.php'],
    ['admin/reports/:id/download',                 'admin/reports.php'],
    ['admin/reports',                              'admin/reports.php'],
];

/**
 * Emit a JSON error and stop. Used before config/db.php is loaded.
 */
function frontControllerError(string $message, int $status): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit();
}

// --- Preflight -------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Length: 0');
    exit();
}

// --- Work out the requested route -----------------------------------------
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// SCRIPT_NAME is /<project>/api/index.php, so its directory is the API root.
$apiRoot = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'));
$apiDir  = __DIR__;

if (stripos($requestPath, $apiRoot . '/') === 0) {
    $route = substr($requestPath, strlen($apiRoot) + 1);
} else {
    // Fallback: everything after the last "/api/" segment.
    $pos = strripos($requestPath, '/api/');
    $route = $pos === false ? '' : substr($requestPath, $pos + 5);
}

$route = trim($route, '/');
if ($route === '') {
    frontControllerError('API route not specified', 404);
}

// --- Match the route -------------------------------------------------------
$segments  = explode('/', $route);
$target    = null;
$routeParams = [];

foreach (SKILLMATCH_ROUTES as [$pattern, $file]) {
    $patternSegments = explode('/', $pattern);

    if (count($patternSegments) !== count($segments)) {
        continue;
    }

    $matched = true;
    $params  = [];
    foreach ($patternSegments as $i => $patternSegment) {
        if ($patternSegment !== '' && $patternSegment[0] === ':') {
            if ($segments[$i] === '') {
                $matched = false;
                break;
            }
            $params[substr($patternSegment, 1)] = $segments[$i];
            continue;
        }
        if (strcasecmp($patternSegment, $segments[$i]) !== 0) {
            $matched = false;
            break;
        }
    }

    if ($matched) {
        $target      = $file;
        $routeParams = $params;
        break;
    }
}

if ($target === null) {
    frontControllerError('Unknown API endpoint: /' . $route, 404);
}

// --- Validate the target stays inside the API directory --------------------
$targetPath = realpath($apiDir . '/' . $target);
if ($targetPath === false || !is_file($targetPath)) {
    frontControllerError('Endpoint script missing: ' . $target, 500);
}
$apiDirReal = realpath($apiDir);
if ($apiDirReal === false || strpos($targetPath, $apiDirReal . DIRECTORY_SEPARATOR) !== 0) {
    frontControllerError('Invalid endpoint target', 500);
}

// --- Expose routing context to the endpoint --------------------------------
$_GET['route']  = $route;
$_POST['route'] = $route;
foreach ($routeParams as $name => $value) {
    $_GET['__route_' . $name] = $value;
}

// Endpoints parse $_SERVER['REQUEST_URI'] (pathSegment(), /download detection),
// so give them the clean logical route rather than index.php's own URL.
$_SERVER['REQUEST_URI'] = $apiRoot . '/' . $route
    . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '');

// --- Dispatch --------------------------------------------------------------
// Buffered so endpoints may use Content-Type: text/csv, application/pdf and
// Content-Disposition headers; anything they print is flushed on exit().
ob_start();
require $targetPath;
ob_end_flush();
