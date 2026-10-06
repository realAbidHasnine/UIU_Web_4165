<?php

require_once __DIR__ . '/includes/auth.php';

// Clears the notification badge. Linked from the header dropdown.

db()->prepare(
    'UPDATE notifications
        SET is_read = 1
      WHERE user_id = ? AND is_read = 0'
)->execute([current_client_id()]);

/*
 * Send the client back where they were. Only accept a plain local filename,
 * otherwise fall back to the dashboard, so this cannot be used to bounce
 * someone off to another site.
 */
$back = 'client-dashboard.php';

if (!empty($_SERVER['HTTP_REFERER'])) {
    $path = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '');

    if (substr($path, -4) === '.php' && !str_contains($path, '/')) {
        $back = $path;
    }
}

header('Location: ' . $back);
exit;