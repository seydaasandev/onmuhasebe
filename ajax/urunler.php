<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require "../config.php";
header('Content-Type: application/json; charset=utf-8');

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
$cacheKey = md5(json_encode([$search, $startDate, $endDate, $start, $length]));
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
    u.marka,
    u.cins,
    u.kategori,
    u.kdv,
    u.satis_fiyat,
    u.web,
    u.raf_bolumu
FROM urunler u
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

while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $resimHtml = '';
    if (!empty($r['resim'])) {
        $resimUrl = htmlspecialchars($r['resim'], ENT_QUOTES, 'UTF-8');
        $resimHtml = '<img src="' . $resimUrl . '" alt="Urun resmi" style="width:65px;height:65px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">';
    }

    $data[] = [
        $r['id'],
        $resimHtml,
        $r['urun_adi'],
        $r['barkod'],
        $r['stok'],
        $r['marka'],
        $r['cins'],
        $r['kategori'] ?? '',
        $r['raf_bolumu'] ?? '',
        $r['kdv'],
        number_format($r['satis_fiyat'], 2, ',', '.'),
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
