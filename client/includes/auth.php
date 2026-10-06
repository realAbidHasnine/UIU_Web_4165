<?php

require_once __DIR__ . '/db.php';

// The logged-in client. Reads from active session/token or defaults to seeded client 'c-201' (Abida Hasan)
function current_client_id() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (!empty($_SESSION['user_id'])) {
        return (string) $_SESSION['user_id'];
    }
    if (!empty($_SESSION['user']['id'])) {
        return (string) $_SESSION['user']['id'];
    }
    if (!empty($_COOKIE['user_id'])) {
        return (string) $_COOKIE['user_id'];
    }

    // Check token from cookie or header against sessions table
    $token = $_COOKIE['token'] ?? $_SESSION['token'] ?? '';
    if ($token !== '') {
        try {
            $st = db()->prepare("SELECT user_id FROM sessions WHERE token = ? LIMIT 1");
            $st->execute([$token]);
            $uid = $st->fetchColumn();
            if ($uid) {
                return (string) $uid;
            }
        } catch (\Throwable $e) {}
    }

    return 'c-201'; // Default client Abida Hasan from database.sql
}

if (!defined('CURRENT_USER_ID')) {
    define('CURRENT_USER_ID', current_client_id());
}

function db_user() {
    static $user = false;

    if ($user === false) {
        $clientId = current_client_id();

        try {
            $sql = 'SELECT id, email, role, status, name, title, company, bio, location
                      FROM users
                     WHERE id = ?
                     LIMIT 1';

            $st = db()->prepare($sql);
            $st->execute([$clientId]);
            $row = $st->fetch();

            if ($row) {
                $parts = explode(' ', trim((string) $row['name']), 2);
                $row['first_name']     = $parts[0] ?? $row['name'];
                $row['last_name']      = $parts[1] ?? '';
                $row['company_name']   = $row['company'] ?? '';
                $row['avatar_url']     = '';
                $row['title']          = $row['title'] ?? 'Client & Hiring Manager';
                $row['bio']            = $row['bio'] ?? 'Independent client hiring pre-vetted, skill-tested freelancers. Focused on clear requirements, transparent milestones, and fair collaboration to ship high-quality products.';
                $row['location']       = $row['location'] ?? 'Dhaka, Bangladesh';
                $row['setup_complete'] = 1;
                $user = $row;
            } else {
                $user = null;
            }
        } catch (\Throwable $e) {
            $user = null;
        }
    }

    return $user ?: null;
}

function display_name($user) {
    if (!$user) {
        return 'Client';
    }
    if (!empty($user['name'])) {
        return $user['name'];
    }
    $first = $user['first_name'] ?? '';
    $last  = $user['last_name'] ?? '';
    $combined = trim($first . ' ' . $last);
    return $combined !== '' ? $combined : ($user['email'] ?? 'Client');
}

// Turns "1,500" or "$1,500" into 1500.00 so form input can be sloppy.
function parse_money($raw) {
    $digits = preg_replace('/[^0-9.]/', '', (string) $raw);
    return $digits === '' ? 0.0 : (float) $digits;
}

function money($amount) {
    return '$' . number_format((float) $amount, 2);
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Flash messages: the forms post then redirect, so errors need one extra hop.
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
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