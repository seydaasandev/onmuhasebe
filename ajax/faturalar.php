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

$search    = trim($_POST['search']['value'] ?? '');
$startDate = $_POST['start_date'] ?? '';
$endDate   = $_POST['end_date'] ?? '';

/* =====================================================
   ORDER (SERVER SIDE)
===================================================== */
$columns = [
    0 => 'o.id',
    1 => 'm.musteri_adi',
    2 => 'o.fatura_tutari',
    3 => 'o.fatura_no',
    4 => 'o.indirim_toplami',
    5 => 'u.namesurname',
    6 => 'o.tarih',
    7 => 'o.id'
];

$orderColIndex = $_POST['order'][0]['column'] ?? 0;
$orderDir      = $_POST['order'][0]['dir'] ?? 'desc';
$orderColumn   = $columns[$orderColIndex] ?? 'o.id';

/* =====================================================
   CACHE
===================================================== */
$cacheKey = md5(json_encode([
    $search, $startDate, $endDate, $start, $length,
    $orderColumn, $orderDir
]));
$cacheDir  = __DIR__ . '/cachefaturalar/';
$cacheFile = $cacheDir . $cacheKey . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 10) {
    echo file_get_contents($cacheFile);
    exit;
}

/* =====================================================
   WHERE (ÖDEME KAYITLARI)
===================================================== */
$where  = "o.durum = 0 AND o.siparis = 0 AND (o.ISLEMSEBEP = 'F')";
$params = [];

/* SEARCH */
if ($search !== '') {
    $where .= " AND (
        m.musteri_adi LIKE :search
        OR u.namesurname LIKE :search
        OR o.fatura_no LIKE :search
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
    FROM muhasebe
    WHERE durum = 0
      AND (ISLEMSEBEP = 'F')
")->fetchColumn();

/* =====================================================
   FILTERED
===================================================== */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM muhasebe o
    LEFT JOIN musteriler m ON m.id = o.musteri_id
    LEFT JOIN users u ON u.id = o.islemi_yapan_id
    WHERE $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

/* =====================================================
   DATA
===================================================== */
$sql = "
SELECT 
    o.islem_no,
    o.id,
    o.fatura_tutari,
    o.fatura_no,
    o.tarih,
    o.indirim_toplami,
    COALESCE(sp.print, 0) AS print,
    m.musteri_adi,
    u.namesurname
FROM muhasebe o
LEFT JOIN musteriler m ON m.id = o.musteri_id
LEFT JOIN users u ON u.id = o.islemi_yapan_id
LEFT JOIN (
    SELECT islem_no, MAX(print) AS print
    FROM satislar
    GROUP BY islem_no
) sp ON sp.islem_no = o.islem_no
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

    // 🔹 Yazdırma durumu butonu
    if ((int)$r['print'] === 0) {
        $printBtn = '
            <button type="button"
                class="btn btn-info btn-sm printBtn"
                data-id="'.$r['islem_no'].'">
                <i class="ri-printer-line"></i> Yazdırılmadı
            </button>';
    } else {
        $printBtn = '
            <button type="button"
                class="btn btn-secondary btn-sm"
                disabled>
                <i class="ri-printer-line"></i> Fatura Yazdırıldı
            </button>';
    }

    // 🔹 Aksiyonlar
    $actions = '
        <a href="fatura-goruntule.php?islem_no='.urlencode($r['islem_no']).'"
           class="btn btn-success btn-sm btn-label waves-effect waves-light" target="_blank">
            <i class="ri-eye-line label-icon align-middle fs-16 me-2"></i>
            Görüntüle
        </a>
        '.$printBtn.'
        <a href="fatura-guncelle.php?id='.$r['id'].'" class="btn btn-sm btn-primary">Düzenle</a>
        <a href="javascript:void(0)" 
           class="btn btn-sm btn-danger faturaSilBtn" 
           data-id="'.$r['islem_no'].'">Sil</a>
    ';

    $data[] = [
        $r['id'],
        $r['musteri_adi'],
        number_format($r['fatura_tutari'], 2, ',', '.') . ' ₺',
        $r['fatura_no'],
        number_format($r['indirim_toplami'], 2, ',', '.') . ' ₺',
        $r['namesurname'],
        date('d.m.Y H:i', strtotime($r['tarih'])),
        $actions
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
