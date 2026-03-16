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

$search    = trim($_POST['search']['value'] ?? '');
$startDate = $_POST['start_date'] ?? '';
$endDate   = $_POST['end_date'] ?? '';

/* =====================================================
   ORDER (SERVER SIDE)
===================================================== */
$columns = [
    0 => 'o.id',
    1 => 'm.musteri_adi',
    2 => 'o.tutar',
    3 => 'o.makbuz_no',
    4 => 'o.aciklama',
    5 => 'u.namesurname',
    6 => 'o.tarih'
];

$orderColIndex = $_POST['order'][0]['column'] ?? 0;
$orderDir      = $_POST['order'][0]['dir'] ?? 'desc';
$orderDir      = strtolower($orderDir);
$orderDir      = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';
$orderColumn   = $columns[$orderColIndex] ?? 'o.id';

/* =====================================================
   CACHE
===================================================== */
$cacheKey = md5(json_encode([
    $search, $startDate, $endDate, $start, $length,
    $orderColumn, $orderDir
]));
$cacheDir  = __DIR__ . '/cacheodemeler/';
$cacheFile = $cacheDir . $cacheKey . '.json';

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
   WHERE (ÖDEME KAYITLARI)
===================================================== */
$where  = "o.durum = 0";
$params = [];

/* SEARCH */
if ($search !== '') {
    $where .= " AND (
        m.musteri_adi LIKE :search
        OR u.namesurname LIKE :search
        OR o.makbuz_no LIKE :search
    )";
    $params[':search'] = "%$search%";
}

/* TARİH */
if ($startDate && $endDate) {
    $where .= " AND o.tarih BETWEEN :startDate AND :endDate";
    $params[':startDate'] = $startDate . " 00:00:00";
    $params[':endDate']   = $endDate   . " 23:59:59";
}

/* =====================================================
   TOTAL (FİLTRESİZ AMA SADECE ÖDEME)
===================================================== */
$total = $db->query("
    SELECT COUNT(*)
    FROM odemeler
    WHERE durum = 0
")->fetchColumn();

/* =====================================================
   FILTERED
===================================================== */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM odemeler o
    LEFT JOIN musteriler m ON m.id = o.musteri_id
    LEFT JOIN users u ON u.id = o.odemeyi_alan
    WHERE $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

/* =====================================================
   DATA
===================================================== */
$sql = "
SELECT 
    o.id,
    o.tutar,
    o.makbuz_no,
    o.tarih,
    o.aciklama,
    m.musteri_adi,
    u.namesurname
FROM odemeler o
LEFT JOIN musteriler m ON m.id = o.musteri_id
LEFT JOIN users u ON u.id = o.odemeyi_alan
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
        $r['musteri_adi'],
        number_format($r['tutar'], 2, ',', '.') . ' ₺',
        $r['makbuz_no'],
        $r['aciklama'],
        $r['namesurname'],
        date('d.m.Y H:i', strtotime($r['tarih'])),
        '<a href="odeme-duzenle.php?id='.$r['id'].'" class="btn btn-sm btn-primary">Düzenle</a>
         <a href="javascript:void(0)" class="btn btn-sm btn-danger odemeSilBtn" data-id="'.$r['id'].'">Sil</a>'
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
