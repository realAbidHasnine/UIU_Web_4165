<?php
require_once __DIR__ . '/../api/config/db.php';

echo "Running reports test...\n";
// login as admin
$ch = curl_init('http://localhost/UIU_Web_4165/api/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['email' => 'admin@skillmatch.com', 'password' => 'admin123', 'role' => 'ADMIN']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);
$res = json_decode(curl_exec($ch), true);
curl_close($ch);
$token = $res['token'] ?? null;
echo "Admin token: " . ($token ? "OK" : "FAILED") . "\n";

foreach (['financial', 'user-growth', 'fraud-log'] as $t) {
    $ch = curl_init('http://localhost/UIU_Web_4165/api/admin/reports');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['reportType' => $t]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token]
    ]);
    $out = json_decode(curl_exec($ch), true);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Report $t -> HTTP $code (id: " . ($out['id'] ?? 'none') . ")\n";

    if (!empty($out['id'])) {
        $ch = curl_init('http://localhost/UIU_Web_4165/api/admin/reports/' . $out['id'] . '/download');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]
        ]);
        $pdf = curl_exec($ch);
        $dlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "Download $t -> HTTP $dlCode, PDF size: " . strlen($pdf) . " bytes\n";
    }
}

// Clean up generated reports from test
$pdo->exec("DELETE FROM reports WHERE filename LIKE 'skillmatch-%'");
echo "Done.\n";
