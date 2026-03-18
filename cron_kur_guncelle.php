<?php

require __DIR__ . '/config.php';
require __DIR__ . '/kur_helpers.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu script sadece CLI uzerinden calistirilabilir.');
}

$sonuc = kur_otomatik_guncelle($db);

if (empty($sonuc['ok'])) {
    fwrite(STDERR, '[HATA] ' . ($sonuc['message'] ?? 'Kur guncelleme basarisiz oldu.') . PHP_EOL);
    exit(1);
}

$guncelKurlar = [];
foreach (($sonuc['rates'] ?? []) as $pb => $veri) {
    $guncelKurlar[] = $pb . '=' . number_format((float)$veri['kur'], 4, '.', '');
}

echo '[OK] ' . ($sonuc['message'] ?? 'Kur guncelleme tamamlandi.') . PHP_EOL;
echo implode(', ', $guncelKurlar) . PHP_EOL;