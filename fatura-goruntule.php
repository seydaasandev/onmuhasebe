<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['islem_no']) || trim($_GET['islem_no']) === '') {
    die('Gecersiz islem');
}

$islem_no = trim($_GET['islem_no']);

/* ======================
   BUTON ISLEMLERI
====================== */
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

/* ======================
   FATURA UST BILGILERI
====================== */
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

/* ======================
   FATURA KALEMLERI
====================== */
$kalemler = $db->prepare(
    "SELECT s.*, u.barkod, u.urun_adi
     FROM satislar s
     LEFT JOIN urunler u ON u.id = s.urun_id
     WHERE s.islem_no = ? AND s.durum = 0
     ORDER BY s.id ASC"
);
$kalemler->execute([$islem_no]);
$urunler = $kalemler->fetchAll(PDO::FETCH_ASSOC);

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
    <style>
        :root {
            --invoice-bg: #f3f5f9;
            --invoice-border: #e3e7ef;
            --invoice-heading: #1f2937;
            --invoice-muted: #6b7280;
            --invoice-primary: #0f766e;
        }
        body {
            background: radial-gradient(circle at top right, #dff6f2 0%, #f3f5f9 38%, #f6f7fb 100%);
            min-height: 100vh;
        }
        .invoice-shell {
            max-width: 1120px;
            margin: 28px auto;
            padding: 0 14px;
        }
        .invoice-card {
            border: 1px solid var(--invoice-border);
            border-radius: 16px;
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.09);
            overflow: hidden;
        }
        .invoice-head {
            background: linear-gradient(120deg, #0f766e, #0d9488);
            color: #fff;
            padding: 24px;
        }
        .invoice-meta {
            color: var(--invoice-muted);
            font-size: 13px;
        }
        .invoice-body {
            background: #fff;
            padding: 24px;
        }
        .table thead th {
            background: #f8fafc;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .summary-box {
            border: 1px solid var(--invoice-border);
            border-radius: 12px;
            background: #fbfdff;
            padding: 16px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: var(--invoice-heading);
            border-top: 1px dashed #d5dbe5;
            padding-top: 10px;
            margin-top: 10px;
        }
        .action-wrap {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .invoice-shell {
                margin: 0;
                max-width: none;
                padding: 0;
            }
            .invoice-card {
                border: none;
                box-shadow: none;
                border-radius: 0;
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

        <div class="invoice-card">
            <div class="invoice-head">
                <div class="row align-items-center gy-3">
                    <div class="col-md-7">
                        <h2 class="mb-1">Fatura</h2>
                        <div class="small opacity-75">Nextario Muhasebe</div>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="d-inline-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-dark">No: <?= htmlspecialchars($fatura['fatura_no'] ?? '-') ?></span>
                            <span class="badge <?= $fatura_durum === 1 ? 'bg-secondary' : 'bg-warning text-dark' ?>">
                                <?= $fatura_durum === 1 ? 'Kapali' : 'Acik' ?>
                            </span>
                            <span class="badge <?= $print_durum === 1 ? 'bg-success' : 'bg-info text-dark' ?>">
                                <?= $print_durum === 1 ? 'Yazdirildi' : 'Yazdirilmadi' ?>
                            </span>
                        </div>
                        <div class="small opacity-75"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($fatura['tarih'] ?? 'now'))) ?></div>
                    </div>
                </div>
            </div>

            <div class="invoice-body">
                <div class="row mb-4 gy-3">
                    <div class="col-lg-7">
                        <h6 class="text-uppercase text-muted mb-2">Musteri Bilgileri</h6>
                        <div class="fw-semibold fs-5 text-dark mb-1\"><?= htmlspecialchars($fatura['m_musteri_adi'] ?? ($fatura['musteri_adi'] ?? '-')) ?></div>
                        <div class="invoice-meta">Yetkili: <?= htmlspecialchars($fatura['m_yetkili'] ?? '-') ?></div>
                        <div class="invoice-meta">Telefon: <?= htmlspecialchars($fatura['m_telefon'] ?? '-') ?></div>
                    </div>
                    <div class="col-lg-5">
                        <h6 class="text-uppercase text-muted mb-2">Fatura Bilgileri</h6>
                        <div class="invoice-meta">Islem No: <?= htmlspecialchars($fatura['islem_no'] ?? '-') ?></div>
                        <div class="invoice-meta">Adres: <?= htmlspecialchars(($fatura['m_adres'] ?? '-') . ' / ' . ($fatura['m_sehir'] ?? '-')) ?></div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 110px;">Barkod</th>
                                <th>Urun</th>
                                <th style="width: 90px;" class="text-end">Adet</th>
                                <th style="width: 120px;" class="text-end">Birim Fiyat</th>
                                <th style="width: 110px;" class="text-end">KDV</th>
                                <th style="width: 110px;" class="text-end">Indirim</th>
                                <th style="width: 140px;" class="text-end">Genel Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($urunler) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Bu faturaya ait satir bulunamadi.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($urunler as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['barkod'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($u['urun_adi'] ?? '-') ?></td>
                                    <td class="text-end"><?= number_format((float)($u['adet'] ?? 0), 2, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format((float)($u['birim_fiyat'] ?? 0), 2, ',', '.') ?> TL</td>
                                    <td class="text-end"><?= number_format((float)($u['kdv_toplami'] ?? 0), 2, ',', '.') ?> TL</td>
                                    <td class="text-end"><?= number_format((float)($u['indirim_toplami'] ?? ($u['indirim'] ?? 0)), 2, ',', '.') ?> TL</td>
                                    <td class="text-end fw-semibold"><?= number_format((float)($u['genel_tutar'] ?? 0), 2, ',', '.') ?> TL</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row gy-3">
                    <div class="col-md-6 col-lg-7"></div>
                    <div class="col-md-6 col-lg-5">
                        <div class="summary-box">
                            <div class="summary-row"><span>Ara Toplam</span><span><?= number_format($ara_toplam, 2, ',', '.') ?> TL</span></div>
                            <div class="summary-row"><span>Indirim</span><span><?= number_format($indirim_toplam, 2, ',', '.') ?> TL</span></div>
                            <div class="summary-row"><span>KDV</span><span><?= number_format($kdv_toplam, 2, ',', '.') ?> TL</span></div>
                            <div class="summary-row total"><span>Genel Toplam</span><span><?= number_format($genel_toplam, 2, ',', '.') ?> TL</span></div>
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
