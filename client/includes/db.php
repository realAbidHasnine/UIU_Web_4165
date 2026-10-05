<?php

// XAMPP defaults: user "root", no password.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'skillmatch');
define('DB_USER', 'root');
define('DB_PASS', '');

function db() {
    static $conn = null;

    if ($conn === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        /*
         * Line MySQL's clock up with PHP's. On this machine XAMPP's MySQL
         * runs on a different offset to the PHP timezone, which makes every
         * stored time look hours in the future (and breaks the "2m ago"
         * labels in the header).
         */
        $offset = (new DateTime())->format('P');
        $conn->exec("SET time_zone = '$offset'");
    }

    return $conn;
}