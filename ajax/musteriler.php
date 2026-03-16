<?php
ini_set('display_errors', 1);
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
$cacheDir  = __DIR__ . '/cachemusteriler/';
$cacheFile = $cacheDir . $cacheKey . '.json';

/* CACHE VARSA (100 sn) */
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 100) {
    echo file_get_contents($cacheFile);
    exit;
}

/* =====================================================
   WHERE
===================================================== */
$where  = "m.durum = 0";
$params = [];

/* SEARCH */
if ($search !== '') {
    $where .= " AND (
        m.musteri_Adi LIKE :search
        OR m.sorumlu LIKE :search
        OR m.yetkili LIKE :search
    )";
    $params[':search'] = "%$search%";
}



/* =====================================================
   TOTAL
===================================================== */
$total = $db->query("
    SELECT COUNT(*) 
    FROM musteriler 
    WHERE durum = 0
")->fetchColumn();

/* =====================================================
   FILTERED
===================================================== */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM musteriler m
    WHERE $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

/* =====================================================
   DATA
===================================================== */
$sql = "
SELECT 
    m.id,
    m.musteri_adi,
    m.yetkili,
    m.telefon,
    m.adres,
    m.sehir,
    u.namesurname AS sorumlu_adi
FROM musteriler m
LEFT JOIN users u ON u.id = m.sorumlu AND u.durum = 0
WHERE $where
ORDER BY m.musteri_adi ASC
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

    $musteriAdi = htmlspecialchars($r['musteri_adi'], ENT_QUOTES, 'UTF-8');

    $data[] = [
        $r['id'],
        '<a href="musteri-hesap.php?id='.$r['id'].'"
            class="link-success link-offset-2 text-decoration-underline
                   link-underline-opacity-25 link-underline-opacity-100-hover">'
            .$musteriAdi.
        '</a>',
        $r['yetkili'],
        $r['telefon'],
        $r['adres'],
        $r['sehir'],
        $r['sorumlu_adi'],
        '<a href="musteri-duzenle.php?id='.$r['id'].'" class="btn btn-sm btn-primary">Düzenle</a>
         <a href="javascript:void(0)" class="btn btn-sm btn-danger musteriSilBtn" data-id="'.$r['id'].'">Sil</a>'
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

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
file_put_contents($cacheFile, $response);

echo $response;
