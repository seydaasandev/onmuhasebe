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
if (!isset($_GET['id'])) {
    header("Location: tum-odemeler.php");
    exit;
}

$id = (int) $_GET['id'];

/* ÖDEME */
$sql = $db->prepare("
    SELECT 
        o.*,
        m.musteri_adi,
        u.namesurname AS odemeyi_alan_adi
    FROM odemeler o
    INNER JOIN musteriler m ON m.id = o.musteri_id
    INNER JOIN users u ON u.id = o.odemeyi_alan
    WHERE o.id = ?
");
$sql->execute([$id]);
$odeme = $sql->fetch(PDO::FETCH_ASSOC);

if (!$odeme) {
    header("Location: tum-odemeler.php");
    exit;
}

/* MÜŞTERİLER */
$musteriler = $db->query("
    SELECT id, musteri_adi 
    FROM musteriler 
    ORDER BY musteri_adi ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* USERS */
$users = $db->query("
    SELECT id, username, namesurname 
    FROM users 
    WHERE durum = 0
    ORDER BY username ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>


<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Ödemeler | Nextario Muhasebe Programı</title>
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
                                <h4 class="mb-sm-0">Ödeme Düzenle |  <?= $odeme['musteri_adi'] ?></h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Ödeme Düzenle</li>
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
                                    <h5 class="card-title mb-0">Ödeme Düzenle</h5>
                                </div>
                                <div class="card-body">
                               <form action="islem.php?islem=odeme_duzenle" method="POST">

<input type="hidden" name="id" value="<?= $odeme['id'] ?>">

<div class="row">

    <!-- MÜŞTERİ -->
    <div class="col-6">
        <div class="mb-3">
            <label class="form-label">Müşteri</label>
            <select name="musteri_id"
                    class="js-example-basic-single"
                    required>
                <option value="">Müşteri seçiniz</option>
                <?php foreach ($musteriler as $m): ?>
                    <option value="<?= $m['id'] ?>"
                        <?= $odeme['musteri_id'] == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['musteri_adi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- TUTAR -->
    <div class="col-6">
        <div class="mb-3">
            <label class="form-label">Tutar</label>
            <input type="number" step="0.01" name="tutar"
                   class="form-control"
                   value="<?= $odeme['tutar'] ?>" required>
        </div>
    </div>

    <!-- MAKBUZ -->
    <div class="col-6">
        <div class="mb-3">
            <label class="form-label">Makbuz No</label>
            <input type="text" name="makbuz_no"
                   class="form-control"
                   value="<?= htmlspecialchars($odeme['makbuz_no'] ?? '') ?>">
        </div>
    </div>

    

    <!-- AÇIKLAMA -->
    <div class="col-6">
        <div class="mb-3">
            <label class="form-label">Açıklama</label>
           <input type="text" name="aciklama"
                      class="form-control" value="<?= htmlspecialchars($odeme['aciklama'] ?? '') ?>"
                      >
        </div>
    </div>

    <!-- ÖDEMEYİ ALAN -->
    <div class="col-6">
        <div class="mb-3">
            <label class="form-label">Ödemeyi Alan</label>
            <select name="odemeyi_alan" class="form-select" required>
                <option value="">Seçiniz</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"
                        <?= $odeme['odemeyi_alan'] == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['namesurname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- BUTONLAR -->
    <div class="col-12 text-end">
        <button type="submit" class="btn btn-success rounded-pill">
            <i class="ri-check-double-line me-1"></i> Kaydet
        </button>

        <a href="tum-odemeler.php" class="btn btn-primary rounded-pill">
            <i class="ri-arrow-go-back-line me-1"></i> Ödemelere Dön
        </a>
    </div>

</div>
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
Swal.fire({
    icon: "<?php echo $_SESSION['mesaj']=='duzenle_ok' ? 'success' : 'error'; ?>",
    title: "<?php echo $_SESSION['mesaj']=='duzenle_ok' ? 'Güncellendi' : 'Güncellenemedi'; ?>"
});
</script>
<?php unset($_SESSION['mesaj']); endif; ?>
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