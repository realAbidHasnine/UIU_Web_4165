<?php
require_once __DIR__ . '/../api/config/db.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $tbl) {
    // get text/varchar columns
    $cols = $pdo->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
    $textCols = [];
    foreach ($cols as $c) {
        $type = strtolower($c['Type']);
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $textCols[] = "`{$c['Field']}`";
        }
    }
    if (empty($textCols)) continue;

    $where = [];
    foreach ($textCols as $col) {
        $where[] = "$col LIKE '%e2e%'";
    }
    $sql = "SELECT COUNT(*) FROM `$tbl` WHERE " . implode(" OR ", $where);
    $cnt = (int)$pdo->query($sql)->fetchColumn();
    if ($cnt > 0) {
        echo "Found $cnt row(s) in `$tbl` with '%e2e%'\n";
    }
}
echo "Check completed.\n";
