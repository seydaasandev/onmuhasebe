<?php
session_start();
require "config.php";
require "auth.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit;
}
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
$odeme_toplam = null;
$satis_toplam = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pazarlamaci_id = (int)$_POST['odemeyi_alan'];
    $baslangic = $_POST['baslangic'];
    $bitis = $_POST['bitis'] . " 23:59:59";

    /* ÖDEME TOPLAMI */
    $stmt = $db->prepare("
        SELECT SUM(tutar)
        FROM odemeler
        WHERE odemeyi_alan = ?
          AND tarih BETWEEN ? AND ?
          AND durum = 0
    ");
    $stmt->execute([$pazarlamaci_id, $baslangic, $bitis]);
    $odeme_toplam = $stmt->fetchColumn();

    /* SATIŞ TOPLAMI */
    $stmt = $db->prepare("
        SELECT SUM(tutar) - SUM(indirim_toplami)
        FROM satislar
        WHERE satisi_yapan = ?
          AND tarih BETWEEN ? AND ?
          AND durum = 0
    ");
    $stmt->execute([$pazarlamaci_id, $baslangic, $bitis]);
    $satis_toplam = $stmt->fetchColumn();
}

$users = $db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>


<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Pazarlamacı İstatistikleri | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                                <h4 class="mb-sm-0">Pazarlamacı İstatistikleri</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Ödeme Ekle</li>
                                    </ol>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Pazarlamacı İstatistikleri</h5>
                                </div>
                                <div class="card-body">
                      <form action="pazarlamaciistatistik.php" method="POST">


        <!-- SORUMLU -->
    <div class="mb-3">
        <label class="form-label">Pazarlamacı</label>
        <select name="odemeyi_alan"
                class="form-select"
                data-choices
                data-choices-search-enabled="true"
                data-choices-sorting-false
                required>

            <option value="">Seçiniz</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>">
                    <?= htmlspecialchars($u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    
    <div class="mb-3">
    <label class="form-label">Başlangıç Tarihi</label>
    <input type="date" name="baslangic" class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">Bitiş Tarihi</label>
    <input type="date" name="bitis" class="form-control" required>
</div>

   

  <br> 
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
 <div class="alert alert-info alert-dismissible alert-additional fade show mb-0 material-shadow" role="alert">
    <div class="alert-body">
<h5>💰 Ödemeler</h5>
<?php if ($odeme_toplam > 0): ?>
    <strong><?= number_format($odeme_toplam, 2, ',', '.') ?> ₺</strong>
<?php else: ?>
    <span class="text-danger">Bu tarihler arasında ödeme almadı.</span>
<?php endif; ?>

<hr>

<h5>🛒 Satışlar (İndirim Düşülmüş)</h5>
<?php if ($satis_toplam > 0): ?>
    <strong><?= number_format($satis_toplam, 2, ',', '.') ?> ₺</strong>
<?php else: ?>
    <span class="text-danger">Bu tarihler arasında satış yapmadı.</span>
    
<?php endif; ?>
 <br>
<div class="alert-content">
<p class="mb-0">
    Bu değerler <?= htmlspecialchars($baslangic) ?> ile <?= htmlspecialchars($bitis) ?> tarihleri arasındadır.
</p>    </div>
<?php endif; ?>
</div>
</div>
     <br>
    <button type="submit" class="btn btn-primary rounded-pill">
        <i class="ri-money-dollar-circle-line me-1"></i> Sonuçları Göster
    </button>

</form>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end row-->

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
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    </div>

    
    
    
<!-- JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if(isset($_SESSION['mesaj'])): ?>

<script>
<?php if($_SESSION['mesaj'] == "ekle_ok"): ?>
Swal.fire({
    icon: 'success',
    title: 'Başarılı!',
    text: 'Ürün başarıyla eklendi.',
    confirmButtonText: 'Tamam'
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "ekle_no"): ?>
Swal.fire({
    icon: 'error',
    title: 'Hata!',
    text: 'Ürün eklenirken bir hata oluştu.',
    confirmButtonText: 'Tamam'
});
<?php endif; ?>
</script>

<?php unset($_SESSION['mesaj']); endif; ?>
<script src="assets/js/pages/select2.init.js"></script>

    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

 <!--jquery cdn-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!--select2 cdn-->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="assets/js/pages/select2.init.js"></script>

    <!-- App js -->
    <script src="assets/js/app.js"></script>


</body>

</html>