<?php
ini_set('display_errors', 0);
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
   ORDER (SERVER SIDE)
===================================================== */
$columns = [
    0  => 's.id',
    1  => 's.islem_no',
    2  => 'm.musteri_adi',
    3  => 'p.urun_adi',
    4  => 's.adet',
    5  => 'ROUND(s.tutar / NULLIF(s.adet, 0), 2)',
    6  => 's.kdv_toplami',
    7  => 's.indirim_toplami',
    8  => 's.tutar',
    9  => 'u.namesurname',
    10 => 's.tarih'
];

$orderColIndex = (int)($_POST['order'][0]['column'] ?? 0);
$orderDir = strtolower($_POST['order'][0]['dir'] ?? 'desc');
$orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';
$orderColumn = $columns[$orderColIndex] ?? 's.id';

/* =====================================================
   CACHE KEY (ARAMA + TARİH + SAYFA)
===================================================== */
$cacheKey = md5(json_encode([
    $search, $startDate, $endDate, $start, $length,
    $orderColumn, $orderDir
]));

$cacheDir  = __DIR__ . '/cache/';
$cacheFile = $cacheDir . $cacheKey . '.json';

/* CACHE VARSA 10 DK */
if (is_file($cacheFile) && is_readable($cacheFile) && (time() - filemtime($cacheFile)) < 10) {
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
$where  = "s.durum=0 AND s.siparis=0";
$params = [];

/* SEARCH */
if ($search !== '') {
    $where .= " AND (
        s.islem_no LIKE :search
        OR m.musteri_adi LIKE :search
        OR p.urun_adi LIKE :search
        OR u.namesurname LIKE :search
    )";
    $params[':search'] = "%$search%";
}

/* TARİH FİLTRESİ */
if ($startDate && $endDate) {
    $where .= " AND s.tarih BETWEEN :startDate AND :endDate";
    $params[':startDate'] = $startDate . " 00:00:00";
    $params[':endDate']   = $endDate   . " 23:59:59";
}

/* =====================================================
   TOTAL
===================================================== */
$total = $db->query("
    SELECT COUNT(*) FROM satislar 
    WHERE durum=0 AND siparis=0
")->fetchColumn();

/* =====================================================
   FILTERED
===================================================== */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM satislar s
    LEFT JOIN musteriler m ON m.id=s.musteri_id
    LEFT JOIN urunler p    ON p.id=s.urun_id
    LEFT JOIN users u     ON u.id=s.satisi_yapan_id    WHERE $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

/* =====================================================
   DATA
===================================================== */
$sql = "
SELECT 
    s.id, s.islem_no, s.adet, s.tutar, 
    ROUND(s.tutar / s.adet, 2) as birim_fiyat,
    s.kdv_toplami, s.indirim_toplami, s.tarih, s.tutar as genel_tutar,
    m.musteri_adi,
    p.urun_adi,
    u.namesurname
FROM satislar s
LEFT JOIN musteriler m ON m.id=s.musteri_id
LEFT JOIN urunler p    ON p.id=s.urun_id
LEFT JOIN users u     ON u.id=s.satisi_yapan_id
WHERE $where
ORDER BY $orderColumn $orderDir
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
    $data[] = [
        $r['id'],
        $r['islem_no'],
        $r['musteri_adi'],
        $r['urun_adi'],
        $r['adet'],
        $r['birim_fiyat'],
        number_format($r['kdv_toplami'],2,',','.'),
        number_format($r['indirim_toplami'],2,',','.'),
        number_format($r['genel_tutar'],2,',','.').' ₺',
        $r['namesurname'],
        date('d.m.Y H:i', strtotime($r['tarih'])),
        '<a href="satis-duzenle.php?id='.$r['id'].'" class="btn btn-sm btn-primary">Düzenle</a>
         <a href="javascript:void(0)" class="btn btn-sm btn-danger satisSilBtn" data-id="'.$r['id'].'">Sil</a>'
    ];
}

/* =====================================================
   OUTPUT + CACHE WRITE
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
