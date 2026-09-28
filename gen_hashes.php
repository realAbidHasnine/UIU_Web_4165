<?php
$h123 = password_hash('password123', PASSWORD_BCRYPT);
$hadm = password_hash('admin123', PASSWORD_BCRYPT);
echo 'password123: ' . $h123 . PHP_EOL;
echo 'admin123: ' . $hadm . PHP_EOL;
echo 'Verify123: ' . (password_verify('password123', $h123) ? 'OK' : 'FAIL') . PHP_EOL;
echo 'VerifyAdm: ' . (password_verify('admin123', $hadm) ? 'OK' : 'FAIL') . PHP_EOL;
