<?php
session_start();
require_once 'config.php';

function get_live_rates(PDO $db): array
{
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

function convert_from_eur(float $eur, array $rates): array
{
    $eurRate = (float)($rates['EUR'] ?? 0);
    $usdRate = (float)($rates['USD'] ?? 0);
    $gbpRate = (float)($rates['GBP'] ?? 0);
    $tlAmount = $eurRate > 0 ? $eur * $eurRate : 0;

    return [
        'EUR' => $eur,
        'USD' => $usdRate > 0 ? $tlAmount / $usdRate : 0,
        'GBP' => $gbpRate > 0 ? $tlAmount / $gbpRate : 0,
    ];
}

function format_money(float $amount, string $symbol): string
{
    return $symbol . ' ' . number_format($amount, 2, ',', '.');
}

function render_currency_stack(float $eur, array $rates, bool $strong = true): string
{
    $converted = convert_from_eur($eur, $rates);
    $primary = format_money($converted['EUR'], '€');

    if ($strong) {
        $primary = '<strong>' . $primary . '</strong>';
    }

    return $primary
        . '<div class="fx-stack">'
        . '<span>$ ' . number_format($converted['USD'], 2, ',', '.') . '</span>'
        . '<span>£ ' . number_format($converted['GBP'], 2, ',', '.') . '</span>'
        . '</div>';
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['islem_no']) || trim($_GET['islem_no']) === '') {
    die('Gecersiz islem');
}

$islem_no = trim($_GET['islem_no']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_printed') {
        $ok = $db->prepare("UPDATE satislar SET print = 1 WHERE islem_no = ? AND durum = 0")
            ->execute([$islem_no]);
        $_SESSION['fatura_mesaj'] = $ok ? 'Fatura yazdirildi olarak isaretlendi.' : 'Islem basarisiz oldu.';
    }

    if ($action === 'close_invoice') {
        $ok1 = $db->prepare("UPDATE muhasebe SET fatura_durum = 1 WHERE islem_no = ?")
            ->execute([$islem_no]);
        $ok2 = $db->prepare("UPDATE satislar SET fatura_durum = 1 WHERE islem_no = ? AND durum = 0")
            ->execute([$islem_no]);
        $_SESSION['fatura_mesaj'] = ($ok1 || $ok2) ? 'Fatura kapatildi.' : 'Fatura kapatilamadi.';
    }

    header('Location: fatura-goruntule.php?islem_no=' . urlencode($islem_no));
    exit;
}

$ust = $db->prepare("\n    SELECT 
        o.*,
        m.musteri_adi AS m_musteri_adi,
        m.yetkili AS m_yetkili,
        m.telefon AS m_telefon,
        m.adres AS m_adres,
        m.sehir AS m_sehir,
        COALESCE(sp.print_durum, 0) AS print_durum
    FROM muhasebe o
        LEFT JOIN (
            SELECT islem_no, MAX(musteri_id) AS satir_musteri_id
            FROM satislar
            WHERE durum = 0
            GROUP BY islem_no
        ) sm ON sm.islem_no = o.islem_no
        LEFT JOIN musteriler m ON m.id = COALESCE(o.musteri_id, sm.satir_musteri_id)
    LEFT JOIN (
        SELECT islem_no, MAX(print) AS print_durum
        FROM satislar
        WHERE durum = 0
        GROUP BY islem_no
    ) sp ON sp.islem_no = o.islem_no
    WHERE o.islem_no = ?
    LIMIT 1
");
$ust->execute([$islem_no]);
$fatura = $ust->fetch(PDO::FETCH_ASSOC);

if (!$fatura) {
    die('Fatura bulunamadi');
}

$kalemler = $db->prepare(
    "SELECT s.*, u.barkod, u.urun_adi
     FROM satislar s
     LEFT JOIN urunler u ON u.id = s.urun_id
     WHERE s.islem_no = ? AND s.durum = 0
     ORDER BY s.id ASC"
);
$kalemler->execute([$islem_no]);
$urunler = $kalemler->fetchAll(PDO::FETCH_ASSOC);

$rates = get_live_rates($db);

$ara_toplam = 0.0;
$kdv_toplam = 0.0;
$indirim_toplam = 0.0;
$genel_toplam = 0.0;

foreach ($urunler as $u) {
    $ara_toplam += (float)($u['tutar'] ?? 0);
    $kdv_toplam += (float)($u['kdv_toplami'] ?? 0);
    $indirim_toplam += (float)($u['indirim_toplami'] ?? ($u['indirim'] ?? 0));
    $genel_toplam += (float)($u['genel_tutar'] ?? 0);
}

if ((float)($fatura['fatura_tutari'] ?? 0) > 0) {
    $genel_toplam = (float)$fatura['fatura_tutari'];
}

$flash = $_SESSION['fatura_mesaj'] ?? '';
unset($_SESSION['fatura_mesaj']);

$fatura_durum = (int)($fatura['fatura_durum'] ?? 0);
$print_durum = (int)($fatura['print_durum'] ?? 0);
$duzenlenme_tarihi = !empty($fatura['tarih']) ? date('d.m.Y H:i', strtotime($fatura['tarih'])) : '-';

$summaryCards = [
    'Ara Toplam' => $ara_toplam,
    'Indirim' => $indirim_toplam,
    'KDV' => $kdv_toplam,
    'Genel Toplam' => $genel_toplam,
];

$eurToUsd = ((float)($rates['EUR'] ?? 0) > 0 && (float)($rates['USD'] ?? 0) > 0)
    ? (float)$rates['EUR'] / (float)$rates['USD']
    : 0;
$eurToGbp = ((float)($rates['EUR'] ?? 0) > 0 && (float)($rates['GBP'] ?? 0) > 0)
    ? (float)$rates['EUR'] / (float)$rates['GBP']
    : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Fatura | Nextario Muhasebe Programi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta content="Musteri takip, stok, odeme takip muhasebe programi" name="description" />
    <meta content="Nextario" name="author" />
    <link rel="shortcut icon" href="assets/images/favicon.ico" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --invoice-ink: #10233a;
            --invoice-muted: #6a7788;
            --invoice-accent-dark: #0b5f54;
            --invoice-card: #fffdf8;
            --invoice-line: rgba(16, 35, 58, 0.12);
            --invoice-shadow: 0 24px 60px rgba(16, 35, 58, 0.14);
        }
        body {
            font-family: 'Manrope', system-ui, sans-serif;
            color: var(--invoice-ink);
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(214, 179, 109, 0.18), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 123, 108, 0.18), transparent 26%),
                linear-gradient(135deg, #f7f2e8 0%, #f2f6f8 48%, #eef2f3 100%);
        }
        .invoice-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 18px 14px 24px;
        }
        .invoice-frame {
            border: 1px solid rgba(255,255,255,0.55);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--invoice-shadow);
            background: rgba(255,255,255,0.42);
            backdrop-filter: blur(10px);
        }
        .invoice-head {
            position: relative;
            padding: 26px 28px;
            color: #f5f1e8;
            background:
                linear-gradient(160deg, rgba(9, 27, 48, 0.94) 0%, rgba(16, 48, 78, 0.96) 48%, rgba(12, 80, 73, 0.94) 100%),
                url('assets/images/sidebar/img-4.jpg') center/cover;
        }
        .invoice-head::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(214, 179, 109, 0.28), transparent 24%),
                linear-gradient(180deg, transparent 0%, rgba(9, 27, 48, 0.18) 100%);
            pointer-events: none;
        }
        .invoice-head-content {
            position: relative;
            z-index: 1;
        }
        .hero-kicker,
        .panel-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .hero-kicker {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .panel-kicker {
            background: rgba(15, 123, 108, 0.1);
            color: var(--invoice-accent-dark);
        }
        .invoice-title,
        .panel-title,
        .summary-primary {
            font-family: 'Fraunces', serif;
        }
        .invoice-title {
            font-size: clamp(1.65rem, 3vw, 2.2rem);
            line-height: 1.04;
            letter-spacing: -0.03em;
            margin: 12px 0 8px;
        }
        .invoice-subtitle {
            max-width: 580px;
            color: rgba(245, 241, 232, 0.78);
            font-size: 13px;
            line-height: 1.55;
        }
        .hero-badges,
        .summary-secondary,
        .fx-stack,
        .fx-mini,
        .action-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .hero-badges {
            margin-top: 14px;
        }
        .hero-badge {
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.14);
            color: #f6f1e7;
            font-size: 11px;
            font-weight: 700;
        }
        .invoice-body {
            background: linear-gradient(180deg, rgba(255,255,255,0.82) 0%, rgba(255,255,255,0.98) 100%);
            padding: 20px;
        }
        .invoice-panel,
        .table-shell {
            border: 1px solid rgba(16, 35, 58, 0.08);
            border-radius: 18px;
            background: var(--invoice-card);
            box-shadow: 0 18px 44px rgba(16, 35, 58, 0.09);
            overflow: hidden;
            height: 100%;
        }
        .table-shell {
            box-shadow: 0 14px 30px rgba(16, 35, 58, 0.06);
        }
        .panel-body {
            padding: 16px;
        }
        .panel-title {
            font-size: 1.15rem;
            margin: 10px 0 4px;
        }
        .invoice-meta {
            color: var(--invoice-muted);
            font-size: 12px;
            line-height: 1.5;
        }
        .info-card,
        .summary-card,
        .note-card {
            border-radius: 18px;
        }
        .info-card {
            border: 1px solid var(--invoice-line);
            background: linear-gradient(180deg, #ffffff 0%, #fbf8f1 100%);
            padding: 12px;
            height: 100%;
        }
        .info-label,
        .summary-label {
            display: block;
            color: var(--invoice-muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }
        .info-value {
            color: var(--invoice-ink);
            font-size: 14px;
            font-weight: 700;
        }
        .info-grid {
            display: grid;
            gap: 10px;
        }
        .note-card {
            margin-top: 12px;
            padding: 12px;
            border: 1px dashed rgba(16, 35, 58, 0.16);
            background: rgba(214, 179, 109, 0.08);
            font-size: 12px;
            line-height: 1.45;
        }
        .identity-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .summary-card {
            padding: 12px;
            border: 1px solid var(--invoice-line);
            background: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, rgba(244,247,248,0.9) 100%);
            min-height: 92px;
        }
        .identity-grid .info-card,
        .summary-grid .summary-card {
            min-width: 0;
        }
        .summary-primary {
            font-size: 1.2rem;
            color: var(--invoice-ink);
            line-height: 1.05;
            margin-bottom: 4px;
        }
        .summary-secondary,
        .fx-stack,
        .fx-mini {
            color: var(--invoice-muted);
            font-size: 11px;
            font-weight: 700;
        }
        .table thead th {
            background: #f5efe3;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid rgba(16, 35, 58, 0.08);
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .table tbody td {
            padding-top: 10px;
            padding-bottom: 10px;
            vertical-align: middle;
            border-color: rgba(16, 35, 58, 0.06);
            font-size: 12px;
        }
        .product-name {
            font-weight: 800;
            color: var(--invoice-ink);
            margin-bottom: 2px;
            font-size: 13px;
        }
        .product-code {
            color: var(--invoice-muted);
            font-size: 10px;
        }
        @media (max-width: 991.98px) {
            .invoice-head,
            .invoice-body,
            .panel-body {
                padding: 16px;
            }
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .identity-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 767.98px) {
            .invoice-shell {
                padding: 10px 10px 16px;
            }
            .invoice-title {
                font-size: 1.5rem;
            }
            .identity-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .invoice-shell {
                padding: 0;
                max-width: none;
            }
            .invoice-frame,
            .invoice-panel,
            .table-shell {
                border: none;
                box-shadow: none;
                border-radius: 0;
                backdrop-filter: none;
            }
            .invoice-body {
                background: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-shell">
        <?php if ($flash !== ''): ?>
            <div class="alert alert-info alert-border-left alert-dismissible fade show no-print" role="alert">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <div class="invoice-frame">
            <div class="invoice-head">
                <div class="invoice-head-content">
                    <div class="row align-items-center gy-4">
                        <div class="col-lg-8">
                            <span class="hero-kicker"><i class="ri-bill-line"></i> Finansal Dokuman</span>
                            <h1 class="invoice-title">Kurumsal Fatura Ozeti</h1>
                            <p class="invoice-subtitle mb-0">
                                Bu sayfa ana para birimi olarak Euro kullanan, diger doviz tutarlarini anlik kurdan hesaplayan ve yazdirma icin temiz bir sunum veren profesyonel fatura gorunumudur.
                            </p>
                            <div class="hero-badges">
                                <span class="hero-badge">Fatura No: <?= htmlspecialchars($fatura['fatura_no'] ?? '-') ?></span>
                                <span class="hero-badge">Islem No: <?= htmlspecialchars($fatura['islem_no'] ?? '-') ?></span>
                                <span class="hero-badge">Tarih: <?= htmlspecialchars($duzenlenme_tarihi) ?></span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="hero-badges justify-content-lg-end mt-lg-0">
                                <span class="hero-badge"><?= $fatura_durum === 1 ? 'Kapali Fatura' : 'Acik Fatura' ?></span>
                                <span class="hero-badge"><?= $print_durum === 1 ? 'Yazdirildi' : 'Yazdirilmadi' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="invoice-body">
                <div class="row gy-4 mb-4">
                    <div class="col-12">
                        <div class="invoice-panel">
                            <div class="panel-body">
                                <span class="panel-kicker"><i class="ri-building-line"></i> Musteri ve Fatura Bilgileri</span>
                                <h2 class="panel-title"><?= htmlspecialchars($fatura['m_musteri_adi'] ?? ($fatura['musteri_adi'] ?? '-')) ?></h2>
                                <p class="invoice-meta mb-4">Kurumsal raporlama ve profesyonel cikti icin musterinin temel iletisim ve operasyon bilgileri tek blokta toplandi.</p>
                                <div class="identity-grid">
                                    <div class="info-card">
                                        <span class="info-label">Musteri</span>
                                        <div class="info-value"><?= htmlspecialchars($fatura['m_musteri_adi'] ?? ($fatura['musteri_adi'] ?? '-')) ?></div>
                                    </div>
                                    <div class="info-card">
                                        <span class="info-label">Adres</span>
                                        <div class="info-value"><?= htmlspecialchars($fatura['m_adres'] ?? '-') ?></div>
                                    </div>
                                    <div class="info-card">
                                        <div class="info-card">
                                            <span class="info-label">Yetkili</span>
                                            <div class="info-value"><?= htmlspecialchars($fatura['m_yetkili'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="info-card">
                                            <span class="info-label">Telefon</span>
                                            <div class="info-value"><?= htmlspecialchars($fatura['m_telefon'] ?? '-') ?></div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <span class="info-label">Sehir</span>
                                        <div class="info-value"><?= htmlspecialchars($fatura['m_sehir'] ?? '-') ?></div>
                                    </div>
                                    <div class="info-card">
                                        <span class="info-label">Tarih</span>
                                        <div class="info-value"><?= htmlspecialchars($duzenlenme_tarihi) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="summary-grid mb-4">
                    <?php foreach ($summaryCards as $label => $value): $converted = convert_from_eur((float)$value, $rates); ?>
                        <div class="summary-card">
                            <div class="summary-label"><?= htmlspecialchars($label) ?></div>
                            <div class="summary-primary"><?= format_money($converted['EUR'], '€') ?></div>
                            <div class="summary-secondary">
                                <span>$ <?= number_format($converted['USD'], 2, ',', '.') ?></span>
                                <span>£ <?= number_format($converted['GBP'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-shell mb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">Barkod</th>
                                    <th>Urun</th>
                                    <th style="width: 90px;" class="text-end">Adet</th>
                                    <th style="width: 180px;" class="text-end">Birim Fiyat</th>
                                    <th style="width: 180px;" class="text-end">KDV</th>
                                    <th style="width: 180px;" class="text-end">Indirim</th>
                                    <th style="width: 180px;" class="text-end">Fatura Tutari</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($urunler) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">Bu faturaya ait satir bulunamadi.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($urunler as $u): ?>
                                    <tr>
                                        <td><div class="product-code"><?= htmlspecialchars($u['barkod'] ?? '-') ?></div></td>
                                        <td>
                                            <div class="product-name"><?= htmlspecialchars($u['urun_adi'] ?? '-') ?></div>
                                            <div class="product-code">EUR bazli fatura satiri</div>
                                        </td>
                                        <td class="text-end fw-semibold"><?= number_format((float)($u['adet'] ?? 0), 2, ',', '.') ?></td>
                                        <td class="text-end"><?= render_currency_stack((float)($u['birim_fiyat'] ?? 0), $rates, false) ?></td>
                                        <td class="text-end"><?= render_currency_stack((float)($u['kdv_toplami'] ?? 0), $rates, false) ?></td>
                                        <td class="text-end"><?= render_currency_stack((float)($u['indirim_toplami'] ?? ($u['indirim'] ?? 0)), $rates, false) ?></td>
                                        <td class="text-end"><?= render_currency_stack((float)($u['genel_tutar'] ?? 0), $rates) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row gy-4 align-items-stretch">
                    <div class="col-lg-7">
                        <div class="invoice-panel">
                            <div class="panel-body">
                                <span class="panel-kicker"><i class="ri-file-chart-line"></i> Finansal Aciklama</span>
                                <h3 class="panel-title">Donusum Mantigi</h3>
                                <p class="invoice-meta mb-3">
                                    Sistemde ana para birimi Euro oldugu icin satir ve toplam tutarlar once EUR olarak gosterilir. Ardindan ayni tutar mevcut doviz kurlarina gore USD ve GBP karsiliklariyla raporlanir.
                                </p>
                                <div class="info-card">
                                    <span class="info-label">Genel Toplam Donusumu</span>
                                    <div class="info-value mb-2"><?= format_money($genel_toplam, '€') ?></div>
                                    <?php $genelConverted = convert_from_eur($genel_toplam, $rates); ?>
                                    <div class="fx-mini">
                                        <span>$ <?= number_format($genelConverted['USD'], 2, ',', '.') ?></span>
                                        <span>£ <?= number_format($genelConverted['GBP'], 2, ',', '.') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="invoice-panel">
                            <div class="panel-body">
                                <span class="panel-kicker"><i class="ri-bank-card-line"></i> Islem Durumu</span>
                                <h3 class="panel-title">Operasyon Kontrolu</h3>
                                <div class="info-grid">
                                    <div class="info-card">
                                        <span class="info-label">Fatura Durumu</span>
                                        <div class="info-value"><?= $fatura_durum === 1 ? 'Kapali' : 'Acik' ?></div>
                                    </div>
                                    <div class="info-card">
                                        <span class="info-label">Yazdirma Durumu</span>
                                        <div class="info-value"><?= $print_durum === 1 ? 'Yazdirildi' : 'Yazdirilmadi' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-4 mt-3 border-top no-print">
                    <a href="faturalar.php" class="btn btn-light btn-label">
                        <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Faturalara Don
                    </a>

                    <div class="action-wrap">
                        <button type="button" class="btn btn-primary" onclick="window.print();">
                            <i class="ri-printer-line align-middle me-1"></i>Yazdir
                        </button>

                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="mark_printed" />
                            <button type="submit" class="btn btn-info" <?= $print_durum === 1 ? 'disabled' : '' ?>>
                                <i class="ri-check-double-line align-middle me-1"></i>Fatura Yazdirildi
                            </button>
                        </form>

                        <form method="post" class="d-inline" onsubmit="return confirm('Faturayi kapatmak istediginize emin misiniz?');">
                            <input type="hidden" name="action" value="close_invoice" />
                            <button type="submit" class="btn btn-danger" <?= $fatura_durum === 1 ? 'disabled' : '' ?>>
                                <i class="ri-lock-line align-middle me-1"></i>Faturayi Kapat
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
