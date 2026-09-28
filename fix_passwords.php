<?php
/**
 * One-time password migration: rehash all demo passwords correctly
 * Run via: http://localhost/UIU_Web_4165/fix_passwords.php
 */
require_once __DIR__ . '/api/config/db.php';

$hash123 = password_hash('password123', PASSWORD_BCRYPT);
$hashAdm = password_hash('admin123', PASSWORD_BCRYPT);

// Update all user accounts
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id != 'u-admin'");
$stmt->execute([$hash123]);
$rows123 = $stmt->rowCount();

$stmtAdm = $pdo->prepare("UPDATE users SET password = ? WHERE id = 'u-admin'");
$stmtAdm->execute([$hashAdm]);
$rowsAdm = $stmtAdm->rowCount();

// Verify
$check = $pdo->prepare("SELECT id, email, CHAR_LENGTH(password) as pwlen FROM users ORDER BY role");
$check->execute();
$users = $check->fetchAll();

echo "=== Password Migration Complete ===\n";
echo "Updated freelancers/clients: $rows123\n";
echo "Updated admin: $rowsAdm\n";
echo "\nHash lengths (should all be 60):\n";
foreach ($users as $u) {
    $ok = $u['pwlen'] == 60 ? 'OK' : 'BAD';
    echo "[{$ok}] {$u['email']} -> len={$u['pwlen']}\n";
}

// Spot verify
$sv = $pdo->prepare("SELECT password FROM users WHERE email='sarah.jenkins@example.com' LIMIT 1");
$sv->execute();
$pw = $sv->fetchColumn();
echo "\nSpot verify sarah.jenkins@example.com: " . (password_verify('password123', $pw) ? 'MATCH' : 'FAIL') . "\n";

$adm = $pdo->prepare("SELECT password FROM users WHERE id='u-admin' LIMIT 1");
$adm->execute();
$apw = $adm->fetchColumn();
echo "Spot verify admin@skillmatch.com: " . (password_verify('admin123', $apw) ? 'MATCH' : 'FAIL') . "\n";
