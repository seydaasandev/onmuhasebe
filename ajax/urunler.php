<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require "../config.php";
header('Content-Type: application/json; charset=utf-8');

function get_live_rates(PDO $db): array {
    $rates = ['TRY' => 1.0, 'EUR' => 1.0, 'USD' => 1.0, 'GBP' => 1.0];
    $q = $db->query("SELECT para_birimi, kur FROM doviz_kurlari WHERE para_birimi IN ('TRY','EUR','USD','GBP')");
    foreach ($q as $r) {
        $pb = strtoupper((string)$r['para_birimi']);
        $kur = (float)$r['kur'];
        if ($kur > 0 && isset($rates[$pb])) {
            $rates[$pb] = $kur;
        }
    }
    $rates['TRY'] = 1.0;
    return $rates;
}

function format_multi_currency_cell(float $eur, array $rates): string {
    $eur = (float)$eur;
    $tl = $rates['EUR'] > 0 ? $eur * $rates['EUR'] : 0;
    $usd = $rates['USD'] > 0 ? $tl / $rates['USD'] : 0;
    $gbp = $rates['GBP'] > 0 ? $tl / $rates['GBP'] : 0;

    return '<div><strong>€ ' . number_format($eur, 2, ',', '.') . '</strong></div>'
        . '<div class="text-muted" style="font-size:11px;line-height:1.3;">'
        . '₺ ' . number_format($tl, 2, ',', '.') . ' | '
        . '$ ' . number_format($usd, 2, ',', '.') . ' | '
        . '£ ' . number_format($gbp, 2, ',', '.')
        . '</div>';
}

/* =====================================================
   REQUEST
===================================================== */
$draw   = (int)($_POST['draw'] ?? 0);
$start  = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 50);

$search     = trim($_POST['search']['value'] ?? '');
$startDate  = $_POST['start_date'] ?? '';
$endDate    = $_POST['end_date'] ?? '';

/* =====================================================
   CACHE
===================================================== */
$cacheVersion = 'urunler_v4_multi_currency_eur';
$cacheKey = md5(json_encode([$cacheVersion, $search, $startDate, $endDate, $start, $length]));
$cacheDir  = __DIR__ . '/cacheurunler/';
$cacheFile = $cacheDir . $cacheKey . '.json';

/* CACHE VARSA (100 sn) */
if (is_file($cacheFile) && is_readable($cacheFile) && (time() - filemtime($cacheFile)) < 50) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false) {
        $cachedData = json_decode($cached, true);
        if (is_array($cachedData)) {
            $cachedData['draw'] = $draw;
            echo json_encode($cachedData, JSON_UNESCAPED_UNICODE);
        } else {
            echo $cached;
        }
    }
    exit;
}

/* =====================================================
   WHERE
===================================================== */
$where  = "u.durum = 0";
$params = [];

/* SEARCH */
if ($search !== '') {
    $where .= " AND (
        u.urun_adi LIKE :search
        OR u.barkod LIKE :search
        OR u.marka LIKE :search
        OR ak.ad LIKE :search
        OR mo.ad LIKE :search
        OR u.raf_bolumu LIKE :search
    )";
    $params[':search'] = "%$search%";
}



/* =====================================================
   TOTAL
===================================================== */
$total = $db->query("
    SELECT COUNT(*) 
    FROM urunler 
    WHERE durum = 0
")->fetchColumn();

/* =====================================================
   FILTERED
===================================================== */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM urunler u
    LEFT JOIN ana_kategoriler ak ON ak.id = u.ana_kategori_id
    LEFT JOIN modeller mo ON mo.id = u.model_id
    WHERE $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

/* =====================================================
   DATA
===================================================== */
$sql = "
SELECT 
    u.id,
    u.resim,
    u.urun_adi,
    u.barkod,
    u.stok,
    COALESCE(ak.ad, '') AS ana_kategori,
    u.marka,
    COALESCE(mo.ad, '') AS model,
    u.cins,
    u.kategori,
    u.kdv,
    u.satis_fiyat,
    u.satis_euro,
    u.web,
    u.raf_bolumu
FROM urunler u
LEFT JOIN ana_kategoriler ak ON ak.id = u.ana_kategori_id
LEFT JOIN modeller mo ON mo.id = u.model_id
WHERE $where
ORDER BY u.id DESC
LIMIT :start, :length
";

$stmt = $db->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':start',  $start,  PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();

$data = [];
$rates = get_live_rates($db);

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $resimHtml = '';
    if (!empty($r['resim'])) {
        $resimUrl = htmlspecialchars($r['resim'], ENT_QUOTES, 'UTF-8');
        $resimHtml = '<img src="' . $resimUrl . '" alt="Urun resmi" style="width:65px;height:65px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">';
    }

    $eurPrice = (float)($r['satis_euro'] ?? 0);
    if ($eurPrice <= 0 && $rates['EUR'] > 0) {
        $eurPrice = (float)($r['satis_fiyat'] ?? 0) / $rates['EUR'];
    }

    $data[] = [
        $r['id'],
        $resimHtml,
        $r['urun_adi'],
        $r['barkod'],
        $r['stok'],
        $r['ana_kategori'] ?? '',
        $r['marka'],
        $r['model'] ?? '',
        $r['cins'],
        $r['kategori'] ?? '',
        $r['raf_bolumu'] ?? '',
        $r['kdv'],
        format_multi_currency_cell($eurPrice, $rates),
        $r['web'] ? 'Evet' : 'Hayır',
        '<a href="urun-duzenle.php?id='.$r['id'].'" class="btn btn-sm btn-primary">Düzenle</a>
         <a href="javascript:void(0)" class="btn btn-sm btn-danger urunSilBtn" data-id="'.$r['id'].'">Sil</a>'
    ];
}

/* =====================================================
   OUTPUT
===================================================== */
$response = json_encode([
    "draw"            => $draw,
    "recordsTotal"    => (int)$total,
    "recordsFiltered" => (int)$filtered,
    "data"            => $data
], JSON_UNESCAPED_UNICODE);

if (is_dir($cacheDir) || @mkdir($cacheDir, 0775, true)) {
    @file_put_contents($cacheFile, $response);
}

echo $response;
