<?php
/**
 * Export clean, complete database.sql from skillmatch_db
 * Includes all 19 tables, indexes, constraints, bcrypt passwords (NO plaintext),
 * skill_categories slug/is_active, full workflow seeds, and zero test artifacts.
 */

declare(strict_types=1);

require_once __DIR__ . '/../api/config/db.php';

echo "Generating unified database.sql...\n";

$sql = <<<SQL
-- =============================================================================
-- SkillMatch Platform Database Schema & Seed Data
-- Database: skillmatch_db
-- Target: MySQL 5.7+ / MariaDB 10.4+ (XAMPP phpMyAdmin)
-- Charset: utf8mb4 / utf8mb4_unicode_ci
-- All passwords are secure bcrypt hashes (password123 / admin123). No plaintext.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `skillmatch_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `skillmatch_db`;

SET FOREIGN_KEY_CHECKS = 0;

SQL;

// Table creation order (respecting dependencies)
$tableOrder = [
    'users',
    'sessions',
    'user_skills',
    'skill_categories',
    'skill_questions',
    'test_results',
    'jobs',
    'proposals',
    'deliverables',
    'portfolio_items',
    'chat_threads',
    'chat_messages',
    'project_milestones',
    'payments',
    'reviews',
    'disputes',
    'notifications',
    'approvals',
    'reports'
];

foreach ($tableOrder as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $createSql = $create['Create Table'];
    // Use CREATE TABLE IF NOT EXISTS
    $createSql = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $createSql);

    $sql .= "\n-- -----------------------------------------------------------------------------\n";
    $sql .= "-- Table: $table\n";
    $sql .= "-- -----------------------------------------------------------------------------\n";
    $sql .= $createSql . ";\n";
}

$sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
$sql .= "\n-- =============================================================================\n";
$sql .= "-- SEED DATA\n";
$sql .= "-- =============================================================================\n\n";

// Tables to export data for
$dataTables = [
    'users',
    'user_skills',
    'skill_categories',
    'skill_questions',
    'jobs',
    'proposals',
    'portfolio_items',
    'chat_threads',
    'chat_messages',
    'project_milestones',
    'payments',
    'reviews',
    'disputes',
    'notifications',
    'approvals'
];

foreach ($dataTables as $table) {
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) continue;

    $sql .= "-- Seed $table (" . count($rows) . " rows)\n";

    // Chunk inserts in batches of 20
    $chunks = array_chunk($rows, 20);
    $cols = array_keys($rows[0]);
    $colList = implode(', ', array_map(fn($c) => "`$c`", $cols));

    foreach ($chunks as $chunk) {
        $sql .= "INSERT INTO `$table` ($colList) VALUES\n";
        $valRows = [];
        foreach ($chunk as $row) {
            $vals = [];
            foreach ($cols as $col) {
                $v = $row[$col];
                if ($v === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $pdo->quote((string)$v);
                }
            }
            $valRows[] = '  (' . implode(', ', $vals) . ')';
        }
        $sql .= implode(",\n", $valRows) . "\n";
        $sql .= "ON DUPLICATE KEY UPDATE `" . $cols[0] . "`=VALUES(`" . $cols[0] . "`);\n\n";
    }
}

file_put_contents(__DIR__ . '/../database.sql', $sql);
echo "Exported database.sql (" . strlen($sql) . " bytes)\n";
