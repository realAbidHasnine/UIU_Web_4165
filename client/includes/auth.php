<?php

require_once __DIR__ . '/db.php';

// The logged-in client. Hardcoded for now because there is no login page yet,
// so every page acts as this user. Swap this one line for session lookups
// once auth is added.
define('CURRENT_USER_ID', 1);

function db_user() {
    static $user = false;

    if ($user === false) {
        $sql = 'SELECT u.id, u.email, u.avatar_url, u.role, u.status,
                       cp.first_name, cp.last_name, cp.company_name, cp.location,
                       cp.setup_complete
                  FROM users u
                  LEFT JOIN client_profiles cp ON cp.user_id = u.id
                 WHERE u.id = ?';

        $st = db()->prepare($sql);
        $st->execute([CURRENT_USER_ID]);
        $user = $st->fetch();
    }

    return $user ? $user : null;
}

// Id of the client making the request. Everything in this portal hangs off it.
function current_client_id() {
    $user = db_user();

    if (!$user) {
        throw new RuntimeException('No client found. Did you run db/seed.sql?');
    }

    return (int) $user['id'];
}

function display_name($user) {
    $name = trim($user['first_name'] . ' ' . $user['last_name']);

    return $name !== '' ? $name : $user['email'];
}

// Turns "1,500" or "$1,500" into 1500.00 so form input can be sloppy.
function parse_money($raw) {
    $digits = preg_replace('/[^0-9.]/', '', $raw);

    return $digits === '' ? 0.0 : (float) $digits;
}

function money($amount) {
    return '$' . number_format((float) $amount, 2);
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Flash messages: the forms post then redirect, so errors need one extra hop.
// Start the session before anything is printed, otherwise PHP refuses.
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function flash($message, $type = 'error') {
    start_session();
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

// Prints the queued messages and clears them so they show only once.
function take_flashes() {
    start_session();

    if (empty($_SESSION['flash'])) {
        return '';
    }

    $queued = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $html = '';

    foreach ($queued as $item) {
        if ($item['type'] === 'error') {
            $look = 'border-color:#f0c6c6;color:#b91c1c;background:#fef4f4';
        } else {
            $look = 'border-color:#a7f3d0;color:#065f46;background:#f2fbf7';
        }

        $html .= '<div class="notice" role="alert" style="' . $look
               . ';border:1px solid;border-radius:8px;padding:12px 14px;'
               . 'margin-bottom:18px;font-size:13.5px">'
               . e($item['message']) . '</div>';
    }

    return $html;
}