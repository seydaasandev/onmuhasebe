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
$orderDir      = strtolower($orderDir);
$orderDir      = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';
$orderColumn   = $columns[$orderColIndex] ?? 'o.id';

/* =====================================================
   CACHE
===================================================== */
$cacheVersion = 'faturalar_multi_view_eur_v2';
$cacheKey = md5(json_encode([
    $cacheVersion,
    $search, $startDate, $endDate, $start, $length,
    $orderColumn, $orderDir
]));
$cacheDir  = __DIR__ . '/cachefaturalar/';
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
$rates = get_live_rates($db);

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
        format_multi_currency_cell((float)$r['fatura_tutari'], $rates),
        $r['fatura_no'],
        format_multi_currency_cell((float)$r['indirim_toplami'], $rates),
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

if (is_dir($cacheDir) || @mkdir($cacheDir, 0775, true)) {
    @file_put_contents($cacheFile, $response);
}

echo $response;
