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

$varsayilanlar = [
    'TRY' => ['alis_kur' => 1, 'satis_kur' => 1, 'kur' => 1],
    'EUR' => ['alis_kur' => 0, 'satis_kur' => 0, 'kur' => 0],
    'USD' => ['alis_kur' => 0, 'satis_kur' => 0, 'kur' => 0],
    'GBP' => ['alis_kur' => 0, 'satis_kur' => 0, 'kur' => 0],
];

$kurlar = [];
$satirlar = $db->query("SELECT para_birimi, alis_kur, satis_kur, kur, kaynak, son_guncelleme FROM doviz_kurlari")->fetchAll(PDO::FETCH_ASSOC);
foreach ($satirlar as $s) {
    $pb = strtoupper($s['para_birimi']);
    $kurlar[$pb] = [
        'alis_kur' => (float)$s['alis_kur'],
        'satis_kur' => (float)$s['satis_kur'],
        'kur' => (float)$s['kur'],
        'kaynak' => $s['kaynak'] ?? '',
        'son_guncelleme' => $s['son_guncelleme'] ?? ''
    ];
}

foreach ($varsayilanlar as $pb => $v) {
    if (!isset($kurlar[$pb])) {
        $kurlar[$pb] = $v + ['kaynak' => 'varsayilan', 'son_guncelleme' => ''];
    }
}

$sirali = ['TRY', 'EUR', 'USD', 'GBP'];

?>


<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Döviz Kurları | Nextario Muhasebe Programı</title>
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
                                <h4 class="mb-sm-0">Döviz Kurları</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Döviz Kurları</li>
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
                                    <h5 class="card-title mb-0">Kur Yönetimi (Manuel + Otomatik)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 d-flex gap-2">
                                        <form action="islem.php?islem=kur_oto_cek" method="POST">
                                            <button type="submit" class="btn btn-primary">
                                                SunDoviz'den Otomatik Çek
                                            </button>
                                        </form>
                                        <form action="islem.php?islem=urun_fiyat_guncelle" method="POST" onsubmit="return confirm('Mevcut EUR/USD/GBP fiyatlarına göre TL satış fiyatları güncellensin mi?');">
                                            <button type="submit" class="btn btn-warning">
                                                Ürün Fiyatlarını Güncelle
                                            </button>
                                        </form>
                                        <small class="text-muted align-self-center">Kaynak: https://online.sundoviz.com/services/api.php</small>
                                    </div>

                                    <form action="islem.php?islem=kur_guncelle" method="POST">
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Para Birimi</th>
                                                        <th>Alış</th>
                                                        <th>Satış</th>
                                                        <th>Sistemde Kullanılan Kur</th>
                                                        <th>Kaynak</th>
                                                        <th>Son Güncelleme</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sirali as $pb):
                                                        $row = $kurlar[$pb] ?? ['alis_kur' => 0, 'satis_kur' => 0, 'kur' => 0, 'kaynak' => '', 'son_guncelleme' => ''];
                                                        $readonlyTry = $pb === 'TRY' ? 'readonly' : '';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($pb) ?></strong>
                                                            <input type="hidden" name="para_birimi[]" value="<?= htmlspecialchars($pb) ?>">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.0001" min="0" name="alis_kur[<?= htmlspecialchars($pb) ?>]" class="form-control" value="<?= number_format((float)$row['alis_kur'], 4, '.', '') ?>" <?= $readonlyTry ?>>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.0001" min="0" name="satis_kur[<?= htmlspecialchars($pb) ?>]" class="form-control" value="<?= number_format((float)$row['satis_kur'], 4, '.', '') ?>" <?= $readonlyTry ?>>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.0001" min="0" name="kur[<?= htmlspecialchars($pb) ?>]" class="form-control" value="<?= number_format((float)$row['kur'], 4, '.', '') ?>" <?= $readonlyTry ?>>
                                                        </td>
                                                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($row['kaynak'] ?: '-')) ?></span></td>
                                                        <td><?= htmlspecialchars((string)($row['son_guncelleme'] ?: '-')) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <button type="submit" class="btn btn-success">Kurları Kaydet</button>
                                    </form>

                                </div>

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
Swal.fire({
    icon: "<?php echo in_array($_SESSION['mesaj'], ['duzenle_ok','oto_ok','fiyat_ok']) ? 'success' : 'error'; ?>",
    title: "<?php echo $_SESSION['mesaj']=='oto_ok' ? 'Kurlar SunDoviz üzerinden güncellendi' : ($_SESSION['mesaj']=='duzenle_ok' ? 'Kurlar güncellendi' : ($_SESSION['mesaj']=='fiyat_ok' ? 'Ürün TL fiyatları güncellendi' : 'Güncellenemedi')); ?>",
    text: "<?php echo isset($_SESSION['mesaj_detay']) ? addslashes((string)$_SESSION['mesaj_detay']) : ''; ?>"
});
</script>
<?php unset($_SESSION['mesaj'], $_SESSION['mesaj_detay']); endif; ?>
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