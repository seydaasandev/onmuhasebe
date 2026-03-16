<?php
require "config.php";
require "auth.php";
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT role, namesurname, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (strtolower($user['role']) === 'user') {
    header("Location: 403.php");
    exit;
}


// TOPLAM SATIŞ (satislar.tutar)
$stmt = $db->query("SELECT COALESCE(SUM(tutar),0) FROM satislar WHERE durum = 0");
$toplam_satis = $stmt->fetchColumn();

// TOPLAM ÜRÜN SAYISI
$stmt = $db->query("SELECT COUNT(*) FROM urunler WHERE durum = 0");
$toplam_urun = $stmt->fetchColumn();

// TOPLAM MÜŞTERİ SAYISI
$stmt = $db->query("SELECT COUNT(*) FROM musteriler WHERE durum = 0");
$toplam_musteri = $stmt->fetchColumn();

// TOPLAM ÖDEMELER (odemeler.tutar)
$stmt = $db->query("SELECT COALESCE(SUM(tutar),0) FROM odemeler WHERE durum = 0");
$toplam_odeme = $stmt->fetchColumn();


?>

<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">
<head>

    <meta charset="utf-8" />
    <title>Panel | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- jsvectormap css -->
    <link href="assets/libs/jsvectormap/jsvectormap.min.css" rel="stylesheet" type="text/css" />

    <!--Swiper slider css-->
    <link href="assets/libs/swiper/swiper-bundle.min.css" rel="stylesheet" type="text/css" />

    <!-- Layout config Js -->
    <script src="assets/js/layout.js"></script>
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <style>
        .stats-premium-card {
            position: relative;
            border: 1px solid #e6ecf4;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.08);
            transition: transform .2s ease, box-shadow .2s ease;
            background: linear-gradient(155deg, #ffffff 0%, #f7fafc 100%);
        }
        .stats-premium-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(17, 24, 39, 0.12);
        }
        .stats-premium-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
            opacity: .95;
        }
        .stats-premium-card.card-sales::before { background: #0f766e; }
        .stats-premium-card.card-products::before { background: #0369a1; }
        .stats-premium-card.card-customers::before { background: #b45309; }
        .stats-premium-card.card-payments::before { background: #1d4ed8; }
        .stats-premium-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stats-premium-value {
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: .2px;
            color: #0f172a;
            margin-bottom: .45rem;
            line-height: 1.2;
        }
        .stats-premium-link {
            font-weight: 600;
            font-size: .88rem;
            text-decoration: none;
            border-bottom: 1px dashed currentColor;
            padding-bottom: 2px;
        }
        .stats-premium-title {
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .6px;
            font-size: .76rem;
            font-weight: 700;
            margin-bottom: .6rem;
        }
        .stats-premium-chip {
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            padding: .26rem .55rem;
            background: #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }
    </style>

</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">
    <?php require "head.php"; ?>
        
        <!-- ========== App Menu ========== -->
        <?php require "sidebar.php"; ?>
       
        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row mb-3 pb-1">
                                    <div class="col-12">
                                        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                                            <div class="flex-grow-1">
                                                <h4 class="fs-16 mb-1">Selam, <?= htmlspecialchars($user['namesurname']) ?></h4>
                                                <p class="text-muted mb-0">Bu sayfada sana mağazan hakkında verileri sunuyoruz.</p>
                                            </div>
                                            <div class="mt-6 mt-lg-0">
                                                <form action="javascript:void(0);">
                                                    <div class="row g-6 mb-0 align-items-center">
                                                        <div class="col-auto">
                                                            <div class="input-group">
    <input 
        type="text" 
        class="form-control border-0 minimal-border dash-filter-picker shadow"
        data-provider="flatpickr"
        data-date-format="d M, Y"
        value="<?php
            $gunler = [
                'Sunday' => 'Pazar',
                'Monday' => 'Pazartesi',
                'Tuesday' => 'Salı',
                'Wednesday' => 'Çarşamba',
                'Thursday' => 'Perşembe',
                'Friday' => 'Cuma',
                'Saturday' => 'Cumartesi'
            ];
            $aylar = [
                'January' => 'Ocak',
                'February' => 'Şubat',
                'March' => 'Mart',
                'April' => 'Nisan',
                'May' => 'Mayıs',
                'June' => 'Haziran',
                'July' => 'Temmuz',
                'August' => 'Ağustos',
                'September' => 'Eylül',
                'October' => 'Ekim',
                'November' => 'Kasım',
                'December' => 'Aralık'
            ];
            echo  date('d') . ' ' . $aylar[date('F')] . ' ' . $gunler[date('l')] . ' ' . date('Y');
        ?>"
        readonly
    >
    <div class="input-group-text bg-primary border-primary text-white">
        <i class="ri-calendar-2-line"></i>
    </div>
</div>

                                                        </div>
                                                        <!--end col-->
                                                        <div class="col-auto">
    <a href="yeni-satis.php" class="btn btn-soft-success material-shadow-none">
        <i class="ri-add-circle-line align-middle me-1"></i> Yeni Satış Yap
    </a>
</div>
                                                        <!--end col-->
                                                        
                                                        <!--end col-->
                                                    </div>
                                                    <!--end row-->
                                                </form>
                                            </div>
                                        </div><!-- end card header -->
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->

                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card stats-premium-card card-sales card-animate h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <div class="d-flex align-items-start justify-content-between mb-3">
                                                    <div>
                                                        <p class="stats-premium-title">Toplam Satış</p>
                                                        <div class="stats-premium-value">TRY <span class="counter-value" data-target="<?= $toplam_satis ?>">0</span></div>
                                                    </div>
                                                    <span class="stats-premium-icon bg-success-subtle text-success">
                                                        <i class="bx bx-dollar-circle"></i>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <a href="tum-satislar.php" class="stats-premium-link text-success">Satışlar</a>
                                                    <span class="stats-premium-chip">Gelir</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- end col -->

                                    <div class="col-xl-3 col-md-6">
                                        <div class="card stats-premium-card card-products card-animate h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <div class="d-flex align-items-start justify-content-between mb-3">
                                                    <div>
                                                        <p class="stats-premium-title">Toplam Ürün</p>
                                                        <div class="stats-premium-value"><span class="counter-value" data-target="<?= $toplam_urun ?>">0</span></div>
                                                    </div>
                                                    <span class="stats-premium-icon bg-info-subtle text-info">
                                                        <i class="bx bx-shopping-bag"></i>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <a href="urunler.php" class="stats-premium-link text-info">Ürünler</a>
                                                    <span class="stats-premium-chip">Stok</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- end col -->

                                    <div class="col-xl-3 col-md-6">
                                        <div class="card stats-premium-card card-customers card-animate h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <div class="d-flex align-items-start justify-content-between mb-3">
                                                    <div>
                                                        <p class="stats-premium-title">Müşteri</p>
                                                        <div class="stats-premium-value"><span class="counter-value" data-target="<?= $toplam_musteri ?>">0</span></div>
                                                    </div>
                                                    <span class="stats-premium-icon bg-warning-subtle text-warning">
                                                        <i class="bx bx-user-circle"></i>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <a href="tum-musteriler.php" class="stats-premium-link text-warning">Müşteriler</a>
                                                    <span class="stats-premium-chip">CRM</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- end col -->

                                    <div class="col-xl-3 col-md-6">
                                        <div class="card stats-premium-card card-payments card-animate h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <div class="d-flex align-items-start justify-content-between mb-3">
                                                    <div>
                                                        <p class="stats-premium-title">Toplam Ödeme</p>
                                                        <div class="stats-premium-value">TRY <span class="counter-value" data-target="<?= $toplam_odeme ?>">0</span></div>
                                                    </div>
                                                    <span class="stats-premium-icon bg-primary-subtle text-primary">
                                                        <i class="bx bx-wallet"></i>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <a href="tum-odemeler.php" class="stats-premium-link text-primary">Ödemeler</a>
                                                    <span class="stats-premium-chip">Nakit</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- end col -->
                                </div> <!-- end row-->

                                          <div class="row mt-4 pt-1">
                             <?php
$aylar_tr = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// SATIŞLAR (durum = 0)
$sql_satis = "
    SELECT 
        MONTH(tarih) ay,
        SUM(tutar) toplam
    FROM satislar
    WHERE 
        durum = 0
        AND YEAR(tarih) = YEAR(CURDATE())
    GROUP BY MONTH(tarih)
";
$satislar = $db->query($sql_satis)->fetchAll(PDO::FETCH_KEY_PAIR);

// ÖDEMELER (durum = 0)
$sql_odeme = "
    SELECT 
        MONTH(tarih) ay,
        SUM(tutar) toplam
    FROM odemeler
    WHERE 
        durum = 0
        AND YEAR(tarih) = YEAR(CURDATE())
    GROUP BY MONTH(tarih)
";
$odemeler = $db->query($sql_odeme)->fetchAll(PDO::FETCH_KEY_PAIR);

// Grafik için diziler
$aylar = [];
$satis_data = [];
$odeme_data = [];

for ($i = 1; $i <= 12; $i++) {
    $aylar[] = $aylar_tr[$i];
    $satis_data[] = isset($satislar[$i]) ? (float)$satislar[$i] : 0;
    $odeme_data[] = isset($odemeler[$i]) ? (float)$odemeler[$i] : 0;
}
?>
                                    <div class="col-xl-8">
                                        <div class="card">
                                            <div class="card-header border-0 align-items-center d-flex">
                                                <h4 class="card-title mb-0 flex-grow-1">Aylara Göre Satışlar ve Ödemeler</h4>
                                                
                                            </div><!-- end card header -->

                                            <div class="card-header p-0 border-0 bg-light-subtle">
                                                <div class="row g-0 text-center">
                                                    <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0">
                                                            <h5 class="mb-1"><span class="counter-value" data-target="<?= $toplam_satis ?>">0</span></h5>
                                                            <p class="text-muted mb-0">Satışlar</p>
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <div class="col-6 col-sm-3">
                                                        <div class="p-3 border border-dashed border-start-0">
                                                            <h5 class="mb-1">$<span class="counter-value" data-target="<?= $toplam_odeme ?>">0</span></h5>
                                                            <p class="text-muted mb-0">Toplam Ödeme</p>
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    
                                                    
                                                </div>
                                            </div><!-- end card header -->

                                            <div class="card-body p-0 pb-2">
                                                <div class="w-100">
                                                   <div id="customer_impression_charts" class="apex-charts" dir="ltr"></div>
</div>
                                            </div><!-- end card body -->
                                        </div><!-- end card -->
                                    </div><!-- end col -->

                                    <div class="col-xl-4">
                                        <!-- card -->
                                        <?php
$sql = "
    SELECT 
        m.sehir AS bolge,
        SUM(s.tutar) AS toplam_satis
    FROM satislar s
    INNER JOIN musteriler m ON m.id = s.musteri_id
    GROUP BY m.sehir
    ORDER BY toplam_satis DESC
";

$stmt = $db->query($sql);
$bolge_satislari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grafik için JS array
$bolgeler = [];
$satislar = [];
$genel_toplam = 0;

foreach ($bolge_satislari as $row) {
    $bolgeler[] = $row['bolge'];
    $satislar[] = (float)$row['toplam_satis'];
    $genel_toplam += $row['toplam_satis'];
}
?>
                                        <div class="card card-height-100">
                                            <div class="card-header align-items-center d-flex">
                                                <h4 class="card-title mb-0 flex-grow-1">Bölgelere Göre Satışlar</h4>
                                                <div class="flex-shrink-0">
                                                   
                                                </div>
                                            </div><!-- end card header -->

                                            <!-- card body -->
                                            <div class="card-body">

                                                

                                              

<div class="px-2 py-2 mt-1">
    <?php foreach ($bolge_satislari as $row): 
        $yuzde = $genel_toplam > 0 ? round(($row['toplam_satis'] / $genel_toplam) * 100) : 0;
    ?>
        <p class="mb-1">
            <?= htmlspecialchars($row['bolge']) ?>
            <span class="float-end"><?= $yuzde ?>%</span>
        </p>
        <div class="progress mt-2" style="height: 6px;">
            <div 
                class="progress-bar progress-bar-striped bg-primary"
                role="progressbar"
                style="width: <?= $yuzde ?>%"
                aria-valuenow="<?= $yuzde ?>"
                aria-valuemin="0"
                aria-valuemax="100">
            </div>
        </div>
    <?php endforeach; ?>
</div>
                                            </div>
                                            <!-- end card body -->
                                        </div>
                                        <!-- end card -->
                                    </div>
                                    <!-- end col -->
                                </div>

                                <div class="row">
                                    <div class="col-xl-6">
                                        <div class="card">
                                            <div class="card-header align-items-center d-flex">
                                                <h4 class="card-title mb-0 flex-grow-1">En Çok Satılan 5 Ürün</h4>
                                                
                                            </div><!-- end card header -->
<?php
$sql = "
    SELECT 
        u.id,
        u.urun_adi,
        u.barkod,
        u.satis_fiyat,
        u.stok,
        SUM(s.adet) AS toplam_satis
    FROM satislar s
    INNER JOIN urunler u ON u.id = s.urun_id
    WHERE s.durum = 0
    GROUP BY s.urun_id
    ORDER BY toplam_satis DESC
    LIMIT 5
";

$en_cok_satanlar = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
                                           <div class="card-body">
    <div class="table-responsive table-card">
        <table class="table table-hover table-centered align-middle table-nowrap mb-0">
            <tbody>

            <?php foreach ($en_cok_satanlar as $urun): 
                $genel_toplam = $urun['satis_fiyat'] * $urun['toplam_satis'];
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-light rounded p-1 me-2">
                                <img src="assets/Nextario-b.png" alt="" class="img-fluid d-block" />
                            </div>
                            <div>
                                <h5 class="fs-14 my-1">
                                    <a href="urun-duzenle.php?id=<?= $urun['id'] ?>" class="text-reset">
                                        <?= htmlspecialchars($urun['urun_adi']) ?>
                                    </a>
                                </h5>
                                <span class="text-muted"><?= htmlspecialchars($urun['barkod']) ?></span>
                            </div>
                        </div>
                    </td>

                    <td>
                        <h5 class="fs-14 my-1 fw-normal">
                            <?= number_format($urun['satis_fiyat'], 2, ',', '.') ?> ₺
                        </h5>
                        <span class="text-muted">Fiyat</span>
                    </td>

                    <td>
                        <h5 class="fs-14 my-1 fw-normal">
                            <?= $urun['toplam_satis'] ?> defa
                        </h5>
                        <span class="text-muted">Toplam Satış</span>
                    </td>

                    <td>
                        <h5 class="fs-14 my-1 fw-normal">
                            <?= $urun['stok'] ?>
                        </h5>
                        <span class="text-muted">Stok</span>
                    </td>

                    <td>
                        <h5 class="fs-14 my-1 fw-normal">
                            <?= number_format($genel_toplam, 2, ',', '.') ?> ₺
                        </h5>
                        <span class="text-muted">Genel Toplam</span>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

                                        </div>
                                    </div>
<?php
$sql = "
SELECT 
    u.id,
    u.namesurname,
    u.username,
    

    COUNT(DISTINCT s.id) AS satis_adet,
    COALESCE(SUM(s.tutar),0) AS toplam_satis_tutari,

    COUNT(DISTINCT o.id) AS odeme_adet,
    COALESCE(SUM(o.tutar),0) AS toplam_odeme_tutari

FROM users u

LEFT JOIN satislar s 
    ON s.satisi_yapan_id = u.id 
    AND s.durum = 0

LEFT JOIN odemeler o 
    ON o.odemeyi_alan = u.id 
    AND o.durum = 0

GROUP BY u.id
ORDER BY toplam_satis_tutari DESC
LIMIT 5
";

$en_cok_satis_yapanlar = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
                                 <div class="col-xl-6">
    <div class="card">
        <div class="card-header align-items-center d-flex">
            <h4 class="card-title mb-0 flex-grow-1">En Çok Satış Yapanlar</h4>
        </div>

        <div class="card-body">
            <div class="table-responsive table-card">
                <table class="table table-hover table-centered align-middle table-nowrap mb-0">
                    <tbody>

                    <?php foreach ($en_cok_satis_yapanlar as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded p-1 me-2">
                                        <img src="assets/Nextario-b.png" alt="" class="img-fluid d-block" />
                                    </div>
                                    <div>
                                        <h5 class="fs-14 my-1">
                                            <?= htmlspecialchars($user['namesurname']) ?>
                                        </h5>
                                        <span class="text-muted"><?= htmlspecialchars($user['username']) ?></span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <h5 class="fs-14 my-1 fw-normal">
                                    <?= $user['satis_adet'] ?> adet
                                </h5>
                                <span class="text-muted">Satış</span>
                            </td>

                            <td>
                                <h5 class="fs-14 my-1 fw-normal">
                                    <?= number_format($user['toplam_satis_tutari'], 2, ',', '.') ?> ₺
                                </h5>
                                <span class="text-muted">Satış Tutarı</span>
                            </td>

                            <td>
                                <h5 class="fs-14 my-1 fw-normal">
                                    <?= $user['odeme_adet'] ?> adet
                                </h5>
                                <span class="text-muted">Ödeme</span>
                            </td>

                            <td>
                                <h5 class="fs-14 my-1 fw-normal">
                                    <?= number_format($user['toplam_odeme_tutari'], 2, ',', '.') ?> ₺
                                </h5>
                                <span class="text-muted">Ödeme Tutarı</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


                                </div> <!-- end row-->

                              

                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->

                        
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> © Nextario.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Design & Develop by Seyda AŞAN
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

   

   

    <!-- JAVASCRIPT -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

    <!-- apexcharts -->
    <script>
var options = {
    chart: {
        type: 'bar',
        height: 269,
        toolbar: { show: false }
    },
    series: [{
        name: 'Toplam Satış',
        data: <?= json_encode($satislar) ?>
    }],
    xaxis: {
        categories: <?= json_encode($bolgeler) ?>
    },
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return val.toLocaleString('tr-TR') + ' ₺';
        }
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val.toLocaleString('tr-TR') + ' ₺';
            }
        }
    }
};

var chart = new ApexCharts(
    document.querySelector("#sales-by-locations"),
    options
);
chart.render();
</script>
    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
var options = {
    chart: {
        height: 320,
        type: 'area',
        toolbar: { show: false }
    },
    series: [
        {
            name: 'Satışlar',
            data: <?= json_encode($satis_data) ?>
        },
        {
            name: 'Ödemeler',
            data: <?= json_encode($odeme_data) ?>
        }
    ],
    xaxis: {
        categories: <?= json_encode($aylar) ?>
    },
    colors: ['#405189', '#0ab39c'],
    stroke: {
        curve: 'smooth',
        width: 3
    },
    dataLabels: {
        enabled: false
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return val.toLocaleString('tr-TR') + ' ₺';
            }
        }
    },
    legend: {
        position: 'top'
    }
};

var chart = new ApexCharts(
    document.querySelector("#customer_impression_charts"),
    options
);
chart.render();
</script>

    <!-- Vector map-->
    <script src="assets/libs/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/libs/jsvectormap/maps/world-merc.js"></script>

    <!--Swiper slider js-->
    <script src="assets/libs/swiper/swiper-bundle.min.js"></script>

    <!-- Dashboard init -->
    <script src="assets/js/pages/dashboard-ecommerce.init.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>