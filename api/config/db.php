<?php
/**
 * SkillMatch PHP REST Backend — Database Connection & Global Utilities
 * Configured for XAMPP (Apache + MySQL / MariaDB)
 */

// Enable CORS for frontend flexibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request.
// ($_SERVER['REQUEST_METHOD'] is absent when this file is loaded from the CLI,
// e.g. scripts/seed_workflow_data.php, so default it.)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// XAMPP Default MySQL Credentials
$host     = '127.0.0.1';
$db       = 'skillmatch_db';
$user     = 'root';
$password = ''; // Default XAMPP root password is empty
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success'  => false,
        'message'  => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

/**
 * Send JSON Response helper
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Get Request Body helper for POST / PUT / PATCH
 */
function getRequestBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/**
 * Resolve the authenticated user from Bearer token.
 * Returns the user row from `users` or null if not authenticated.
 */
function getAuthUser(PDO $pdo): ?array {
    $token = bearerToken();
    if ($token === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT u.* FROM sessions s
        INNER JOIN users u ON u.id = s.user_id
        WHERE s.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }
    // A suspended/flagged account must not keep using an existing token.
    if (($user['status'] ?? 'Active') !== 'Active') {
        return null;
    }
    return $user;
}

/**
 * Extract the Bearer token from the request.
 * Tries several sources because Apache's handling of the Authorization header
 * varies by build (mod_rewrite E= flag, REDIRECT_ prefix, CGI passthrough).
 */
function bearerToken(): string {
    $candidates = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];

    // Some Apache/php-cgi combinations only expose it via the raw header list
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach (['Authorization', 'authorization', 'REDIRECT_HTTP_AUTHORIZATION'] as $h) {
            if (!empty($headers[$h])) {
                $candidates[] = $headers[$h];
            }
        }
    }

    foreach ($candidates as $header) {
        $header = (string)$header;
        if ($header !== '' && preg_match('/^\s*Bearer\s+(\S+)/i', $header, $m)) {
            return trim($m[1]);
        }
    }
    return '';
}

/**
 * Require authentication — returns the user or sends 401
 */
function requireAuth(PDO $pdo): array {
    $user = getAuthUser($pdo);
    if (!$user) {
        sendResponse(['message' => 'Unauthorized. Please log in.'], 401);
    }
    return $user;
}

/**
 * Get skills array for a user from user_skills table
 */
function getUserSkills(PDO $pdo, string $userId): array {
    $stmt = $pdo->prepare("SELECT skill_name FROM user_skills WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$userId]);
    return array_column($stmt->fetchAll(), 'skill_name');
}

/**
 * Require an authenticated user holding one of the given roles.
 * Sends 403 and exits when the role does not match.
 */
function requireRole(PDO $pdo, array $roles): array {
    $user = requireAuth($pdo);
    $roles = array_map('strtoupper', $roles);
    if (!in_array(strtoupper($user['role']), $roles, true)) {
        sendResponse([
            'message' => 'Forbidden. This action requires role: ' . implode(' or ', $roles) . '.'
        ], 403);
    }
    return $user;
}

/**
 * Read a required, length-capped string field from a decoded request body.
 * Sends 400 and exits when the value is missing/blank.
 */
function requireField(array $body, string $key, int $maxLen = 255, string $label = null): string {
    $label = $label ?? $key;
    $value = trim((string)($body[$key] ?? ''));
    if ($value === '') {
        sendResponse(['message' => "Field '$label' is required"], 400);
    }
    if (mb_strlen($value) > $maxLen) {
        sendResponse(['message' => "Field '$label' must be $maxLen characters or fewer"], 400);
    }
    return $value;
}

/**
 * Validate a value against a fixed set, returning it upper/lower-cased as given.
 * Sends 400 and exits on mismatch.
 */
function requireOneOf(string $value, array $allowed, string $label, string $case = 'upper'): string {
    $needle = $case === 'lower' ? strtolower($value) : strtoupper($value);
    $ok = false;
    foreach ($allowed as $a) {
        if (($case === 'lower' ? strtolower($a) : strtoupper($a)) === $needle) {
            $ok = true;
            break;
        }
    }
    if (!$ok) {
        sendResponse([
            'message' => "Invalid $label. Allowed values: " . implode(', ', $allowed)
        ], 400);
    }
    // Return the canonical spelling from $allowed
    foreach ($allowed as $a) {
        if (($case === 'lower' ? strtolower($a) : strtoupper($a)) === $needle) {
            return $a;
        }
    }
    return $value;
}

/**
 * Validate a numeric amount, returning a float. Sends 400 and exits when invalid.
 */
function requireAmount($value, string $label, float $min = 0.0, float $max = 99999999.0): float {
    if ($value === null || $value === '' || !is_numeric($value)) {
        sendResponse(['message' => "Field '$label' must be a number"], 400);
    }
    $n = (float)$value;
    if ($n < $min || $n > $max) {
        sendResponse(['message' => "Field '$label' must be between $min and $max"], 400);
    }
    return round($n, 2);
}

/**
 * Clamp an integer rating into the 1..5 range the review UI uses.
 */
function clampRating($value, string $label): int {
    if (!is_numeric($value)) {
        sendResponse(['message' => "Field '$label' must be a number between 1 and 5"], 400);
    }
    $n = (int)round((float)$value);
    if ($n < 1 || $n > 5) {
        sendResponse(['message' => "Field '$label' must be between 1 and 5"], 400);
    }
    return $n;
}

/**
 * Generate a prefixed, collision-resistant public id.
 */
function newId(string $prefix): string {
    return $prefix . '-' . substr(bin2hex(random_bytes(6)), 0, 10);
}

/**
 * Read a route parameter captured by the front controller (api/index.php).
 * e.g. routeParam('id') on "/api/disputes/dsp-1/respond" returns "dsp-1".
 * Returns '' when the parameter is not part of the matched route.
 */
function routeParam(string $name): string {
    $value = $_GET['__route_' . $name] ?? '';
    return is_string($value) ? trim($value) : '';
}

/**
 * The matched route path, e.g. "notifications/read-all".
 */
function routeName(): string {
    $value = $_GET['route'] ?? '';
    return is_string($value) ? $value : '';
}

/**
 * Extract a path segment from REQUEST_URI, e.g. pathSegment('disputes', 'id')
 * on "/api/disputes/dsp-1/respond" returns "dsp-1".
 *
 * $group selects which capture to return:
 *   1 (default) = the first segment after the literal
 *   2           = the second segment after the literal
 *
 * Returns '' when the segment is absent.
 */
function pathSegment(string $literal, string $param, int $group = 1): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    if (preg_match('#/' . preg_quote($literal, '#') . '/([a-zA-Z0-9_-]+)(?:/([a-zA-Z0-9_-]+))?#', $path, $m)) {
        return $group === 2 ? ($m[2] ?? '') : ($m[1] ?? '');
    }
    return '';
}

/**
 * Deliverable/milestone storage directory (real uploaded file bytes).
 */
function deliverablesDir(): string {
    $dir = __DIR__ . '/../../uploads/deliverables';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return realpath($dir) ?: $dir;
}

