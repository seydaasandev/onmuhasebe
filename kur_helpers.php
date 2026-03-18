<?php

function sundoviz_kurlarini_getir(string $url = 'https://online.sundoviz.com/services/api.php'): array
{
    $json = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $cevap = curl_exec($ch);
        if ($cevap !== false) {
            $json = (string)$cevap;
        }

        curl_close($ch);
    }

    if ($json === '' && ini_get('allow_url_fopen')) {
        $icerik = @file_get_contents($url);
        if ($icerik !== false) {
            $json = (string)$icerik;
        }
    }

    if ($json === '') {
        return [
            'ok' => false,
            'message' => 'Kur servisine baglanilamadi.'
        ];
    }

    $liste = json_decode($json, true);
    if (!is_array($liste)) {
        return [
            'ok' => false,
            'message' => 'Kur servisi gecersiz veri dondurdu.'
        ];
    }

    $hedef = ['EUR', 'USD', 'GBP'];
    $bulunan = [];

    foreach ($liste as $satir) {
        $pb = strtoupper(trim((string)($satir['dovizcins'] ?? '')));
        if (!in_array($pb, $hedef, true)) {
            continue;
        }

        $alis = (float)($satir['alisKur'] ?? 0);
        $satis = (float)($satir['satisKur'] ?? 0);
        $degisimHam = (string)($satir['sonDegisiklik'] ?? '');
        $degisimZamani = strtotime($degisimHam);
        $degisim = $degisimZamani ? date('Y-m-d H:i:s', $degisimZamani) : date('Y-m-d H:i:s');

        if ($alis > 0 && $satis > 0) {
            $bulunan[$pb] = [
                'alis' => $alis,
                'satis' => $satis,
                'kur' => $satis,
                'son' => $degisim,
            ];
        }
    }

    if (empty($bulunan)) {
        return [
            'ok' => false,
            'message' => 'SunDoviz icinde EUR/USD/GBP kuru bulunamadi.'
        ];
    }

    return [
        'ok' => true,
        'rates' => $bulunan,
    ];
}

function kur_otomatik_guncelle(PDO $db, string $kaynak = 'sundoviz'): array
{
    $fetchResult = sundoviz_kurlarini_getir();
    if (empty($fetchResult['ok'])) {
        return $fetchResult;
    }

    $bulunan = $fetchResult['rates'];

    try {
        $db->beginTransaction();

        $upsert = $db->prepare(" 
            INSERT INTO doviz_kurlari (para_birimi, alis_kur, satis_kur, kur, kaynak, son_guncelleme)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                alis_kur = VALUES(alis_kur),
                satis_kur = VALUES(satis_kur),
                kur = VALUES(kur),
                kaynak = VALUES(kaynak),
                son_guncelleme = VALUES(son_guncelleme)
        ");

        $upsert->execute(['TRY', 1, 1, 1, $kaynak, date('Y-m-d H:i:s')]);

        foreach ($bulunan as $pb => $veri) {
            $upsert->execute([$pb, $veri['alis'], $veri['satis'], $veri['kur'], $kaynak, $veri['son']]);
        }

        if (!empty($bulunan['EUR']['kur'])) {
            $eur = (float)$bulunan['EUR']['kur'];
            $id = $db->query("SELECT id FROM kur LIMIT 1")->fetchColumn();
            if ($id) {
                $db->prepare("UPDATE kur SET kur=? WHERE id=?")->execute([$eur, $id]);
            } else {
                $db->prepare("INSERT INTO kur (kur) VALUES (?)")->execute([$eur]);
            }
        }

        $db->commit();

        return [
            'ok' => true,
            'rates' => $bulunan,
            'message' => 'Toplam ' . count($bulunan) . ' para birimi guncellendi.'
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        return [
            'ok' => false,
            'message' => 'Kur bilgileri veritabanina kaydedilemedi: ' . $e->getMessage()
        ];
    }
}