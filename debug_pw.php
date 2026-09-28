<?php
// Debug: check what's actually in the DB
require_once __DIR__ . '/api/config/db.php';

$stmt = $pdo->prepare("SELECT id, email, password, CHAR_LENGTH(password) as pwlen FROM users WHERE email = ? LIMIT 1");
$stmt->execute(['sarah.jenkins@example.com']);
$u = $stmt->fetch();

echo "ID: " . $u['id'] . "\n";
echo "Email: " . $u['email'] . "\n";
echo "PW length: " . $u['pwlen'] . "\n";
echo "PW prefix: " . substr($u['password'], 0, 20) . "\n";
echo "Verify 'password123': " . (password_verify('password123', $u['password']) ? 'MATCH' : 'NO MATCH') . "\n";

// Also generate fresh hash to compare
$fresh = password_hash('password123', PASSWORD_BCRYPT);
echo "Fresh hash: " . substr($fresh, 0, 30) . "...\n";
echo "Verify fresh: " . (password_verify('password123', $fresh) ? 'MATCH' : 'NO MATCH') . "\n";
