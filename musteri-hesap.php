<?php

require "config.php";
require "auth.php";
$user_id = $_SESSION['user_id'];

// Kullanıcı rolünü çek
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$role = $stmt->fetchColumn();

// Eğer role 'user' ise erişimi engelle
if (strtolower($role) === 'user') {
    header("Location: 403.php");
    exit;
}
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Geçersiz müşteri");
$musteri_id = (int)$_GET['id'];

/* MÜŞTERİ BİLGİLERİ */
$musteri = $db->prepare("
    SELECT 
        musteri_adi,
        yetkili,
        adres,
        sehir,
        telefon
    FROM musteriler
    WHERE id = ? 
");
$musteri->execute([$musteri_id]);
$m = $musteri->fetch(PDO::FETCH_ASSOC);
if(!$m) die("Müşteri bulunamadı");

/* TOPLAM ÖDEME */
$odeme = $db->prepare("
    SELECT COALESCE(SUM(tutar), 0) 
    FROM odemeler 
    WHERE musteri_id = ? AND durum = 0
");
$odeme->execute([$musteri_id]);
$toplam_odeme = $odeme->fetchColumn();


/* TOPLAM İNDİRİM  */
$indirim = $db->prepare("
    SELECT COALESCE(SUM(indirim_toplami), 0)
    FROM satislar
    WHERE musteri_id = ? AND durum = 0
");
$indirim->execute([$musteri_id]);
$toplam_indirim = $indirim->fetchColumn();

/* TOPLAM SATIŞ */
$borc = $db->prepare("
    SELECT COALESCE(SUM(genel_tutar), 0)
    FROM satislar
    WHERE musteri_id = ? AND durum = 0
");
$borc->execute([$musteri_id]);
$toplam_satis = $borc->fetchColumn();






/* KALAN BORÇ */
$kalan_borc = $toplam_satis - $toplam_odeme;



/* SON SATIŞLAR – ÜRÜN ADI İLE */
$satislar = $db->prepare("
    SELECT 
        s.*,
        u.urun_adi
    FROM satislar s
    LEFT JOIN urunler u ON u.id = s.urun_id
    WHERE s.musteri_id = ?
      AND s.durum = 0
    ORDER BY s.tarih DESC
    LIMIT 20
");
$satislar->execute([$musteri_id]);
$satislar = $satislar->fetchAll(PDO::FETCH_ASSOC);

/* SON ÖDEMELER */
$odemeler = $db->prepare("
    SELECT *
    FROM odemeler
    WHERE musteri_id = ? AND durum = 0
    ORDER BY tarih DESC
    LIMIT 20
");
$odemeler->execute([$musteri_id]);
$odemeler = $odemeler->fetchAll(PDO::FETCH_ASSOC);

/* SON FATURALAR */
$faturalar = $db->prepare("
    SELECT *
    FROM muhasebe
    WHERE musteri_id = ? AND durum = 0 AND ISLEMSEBEP = 'F'
    ORDER BY tarih DESC
    LIMIT 20
");
$faturalar->execute([$musteri_id]);
$faturalar = $faturalar->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

    <meta charset="utf-8" />
    <title>Müşteri Hesap | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- swiper css -->
    <link rel="stylesheet" href="assets/libs/swiper/swiper-bundle.min.css">
    <!--datatable css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <!--datatable responsive css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

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
                    <div class="profile-foreground position-relative mx-n4 mt-n4">
                        <div class="profile-wid-bg">
                            <img src="assets/images/profile-bg.jpg" alt="" class="profile-wid-img" />
                        </div>
                    </div>
                    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
    <div class="row g-4">
        <div class="col-auto">
            <div class="avatar-lg">
                <img src="assets/avatar.png" alt="user-img"
                     class="img-thumbnail rounded-circle" />
            </div>
        </div>

        <div class="col">
            <div class="p-2">
                <h3 class="text-white mb-1">
                    <?= htmlspecialchars($m['musteri_adi']) ?>
                </h3>

                <p class="text-white text-opacity-75">
                    <?= htmlspecialchars($m['yetkili']) ?>
                </p>

                <div class="hstack text-white-50 gap-1">
                    <div class="me-2">
                        <i class="ri-map-pin-user-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>
                        <?= htmlspecialchars($m['adres']) ?> / <?= htmlspecialchars($m['sehir']) ?>
                    </div>
                    <div>
                        <i class="ri-building-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>
                        <?= htmlspecialchars($m['telefon']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-auto order-last order-lg-0">
            <div class="row text text-white-50 text-center">

                <div class="col-lg-3 col-3">
                    <div class="p-2">
                        <h4 class="text-white mb-1">
                            <?= number_format($toplam_odeme, 2, ',', '.') ?> ₺
                        </h4>
                        <p class="fs-14 mb-0">Toplam Ödeme</p>
                    </div>
                </div>

                <div class="col-lg-3 col-3">
                    <div class="p-2">
                        <h4 class="text-white mb-1">
                            <?= number_format($toplam_satis, 2, ',', '.') ?> ₺
                        </h4>
                        <p class="fs-14 mb-0">Toplam Satın Alma</p>
                    </div>
                </div>

                <div class="col-lg-3 col-3">
                    <div class="p-2">
                        <h4 class="text-white mb-1 <?= $kalan_borc > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($kalan_borc, 2, ',', '.') ?> ₺
                        </h4>
                        <p class="fs-14 mb-0">Kalan Borç</p>
                    </div>
                </div>
                <div class="col-lg-3 col-3">
                    <div class="p-2">
                        <h4 class="text-white mb-1 <?= $toplam_indirim > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($toplam_indirim, 2, ',', '.') ?> ₺
                        </h4>
                        <p class="fs-14 mb-0">Toplam İndirim</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


                    <div class="row">
                        <div class="col-lg-12">
                            <div>
                                <div class="d-flex profile-wrapper">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-pills animation-nav profile-nav gap-2 gap-lg-3 flex-grow-1" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link fs-14 active" data-bs-toggle="tab" href="#overview-tab" role="tab">
                                                <i class="ri-airplay-fill d-inline-block d-md-none"></i> <span class="d-none d-md-inline-block">Son Satışlar</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#activities" role="tab">
                                                <i class="ri-list-unordered d-inline-block d-md-none"></i> <span class="d-none d-md-inline-block">Son Ödemeler</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link fs-14" data-bs-toggle="tab" href="#projects" role="tab">
                                                <i class="ri-price-tag-line d-inline-block d-md-none"></i> <span class="d-none d-md-inline-block">Son Faturalar</span>
                                            </a>
                                        </li>
                                    </ul>
                                    
                                </div>
                                <!-- Tab panes -->
                                <div class="tab-content pt-4 text-muted">
                                    <div class="tab-pane active" id="overview-tab" role="tabpanel">
                                        <div class="row">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="card-body">
                                                        <table class="datatable-buttons display table table-bordered dt-responsive" style="width:100%">
    <thead>
        <tr><th>ID</th>
            <th>Tarih</th>
            <th>Ürün Adı</th>
            <th>Adet</th>
            <th>Tutar</th>
            <th>İndirim</th>
            <th>KDV</th>
            <th>Genel Toplam</th>
            <th>Durum</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($satislar as $s): ?>
        <tr><td><?= $s['id'] ?></td>
            <td><?= $s['tarih'] ?></td>
           <td><?= htmlspecialchars($s['urun_adi'] ?? '-') ?></td>
            <td><?= htmlspecialchars($s['adet'] ?? '-') ?></td>
            <td><?= number_format($s['tutar'],2,',','.') ?> ₺</td>
            <td><?= number_format($s['indirim_toplami'],2,',','.') ?> ₺</td>
            <td><?= number_format($s['kdv_toplami'],2,',','.') ?> ₺</td>
                        <td><?= number_format($s['genel_tutar'],2,',','.') ?> ₺</td>
            <td>
                <?= $s['fatura_durum'] == 1
                    ? '<span class="badge bg-secondary">Kapalı</span>'
                    : '<span class="badge bg-warning">Açık</span>' ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    $.fn.dataTable
        .tables({ visible: true, api: true })
        .columns.adjust()
        .responsive.recalc();
});
</script>
     
</div>

                                                </div>
                                            </div>
                                        </div>
                                        <!--end row-->
                                    </div>
                                    <div class="tab-pane fade" id="activities" role="tabpanel">
                                        <div class="card">
                                            <div class="card-body">
                        <table class="datatable-buttons display table table-bordered dt-responsive" style="width:100%">
    <thead>
        <tr>
            <th>Tarih</th>
            <th>Makbuz No</th>
            <th>Tutar</th>
           
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($odemeler as $o): ?>
        <tr>
            <td><?= $o['tarih'] ?></td>
            <td><?= htmlspecialchars($o['makbuz_no'] ?? '-') ?></td>
            <td><?= number_format($o['tutar'],2,',','.') ?> ₺</td>
            <td><?= htmlspecialchars($o['fatura_no'] ?? '-') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    $.fn.dataTable
        .tables({ visible: true, api: true })
        .columns.adjust()
        .responsive.recalc();
});
</script>
                                            </div>
                                            <!--end card-body-->
                                        </div>
                                        <!--end card-->
                                    </div>
                                    <!--end tab-pane-->
                                    <div class="tab-pane fade" id="projects" role="tabpanel">
                                        <div class="card">
                                            <div class="card-body"><table class="datatable-buttons display table table-bordered dt-responsive" style="width:100%">
<thead>
<tr>
    <th>Tarih</th>
    <th>Fatura No</th>
    <th>İşlem No</th>
    <th>Tutar</th>
    <th>Durum</th>
</tr>
</thead>
<tbody>
<?php foreach ($faturalar as $f): ?>
<tr>
    <td><?= $f['tarih'] ?></td>
    <td><?= htmlspecialchars($f['fatura_no'] ?? '-') ?></td>
    <td><?= htmlspecialchars($f['islem_no'] ?? '-') ?></td>
    <td><?= number_format($f['fatura_tutari'],2,',','.') ?> ₺</td>
    <td>
        <?= (int)$f['fatura_durum'] === 1
            ? '<span class="badge bg-secondary">Kapalı</span>'
            : '<span class="badge bg-warning">Açık</span>' ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<script>
$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    $.fn.dataTable
        .tables({ visible: true, api: true })
        .columns.adjust()
        .responsive.recalc();
});
</script>
                                            </div>
                                            <!--end card-body-->
                                        </div>
                                        <!--end card-->
                                    </div>
                                    <!--end tab-pane-->
                                </div>
                                <!--end tab-content-->
                            </div>
                        </div>
                        <!--end col-->
                    </div>
                    <!--end row-->

                </div><!-- container-fluid -->
            </div><!-- End Page-content -->

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
        </div><!-- end main content-->

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

   
<script>
$(document).ready(function () {

    $('.datatable-buttons').each(function () {

        if ($.fn.DataTable.isDataTable(this)) {
            return;
        }

        $(this).DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            paging: true,
            searching: true,
            ordering: false,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'Kopyala' },
                { extend: 'excel', text: 'Excel' },
                { extend: 'pdf', text: 'PDF' },
                { extend: 'print', text: 'Yazdır' }
            ]
        });

    });

});

</script>
        
 <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
     <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

   

    <!--datatable js-->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

    
    

    <!-- profile init js -->
    <script src="assets/js/pages/profile.init.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>