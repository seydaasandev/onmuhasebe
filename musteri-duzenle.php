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
    header("Location: musteriler.php");
    exit;
}

$id = intval($_GET['id']);

// Kullanıcı bilgilerini çek
$sql = $db->prepare("SELECT * FROM musteriler WHERE id = ?");
$sql->execute([$id]);
$musteri = $sql->fetch(PDO::FETCH_ASSOC);

if (!$musteri) {
    header("Location: kullanicilar.php");
    exit;
}
$users = $db->query("SELECT id, username FROM users WHERE durum = 0 ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

?>


<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Müşteriler | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

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

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                                <h4 class="mb-sm-0">Müşteri Düzenle |  <?= $musteri['musteri_adi'] ?></h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Müşteri Düzenle</li>
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
                                    <h5 class="card-title mb-0">Müşteri Düzenle</h5>
                                </div>
                                <div class="card-body">
                                <form action="islem.php?islem=musteri_duzenle" method="POST">

        <input type="hidden" name="id" value="<?= $musteri['id'] ?>">

        <div class="row">

            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Müşteri Adı</label>
                    <input type="text" name="musteri_adi" class="form-control" value="<?= $musteri['musteri_adi'] ?>" required>
                </div>
            </div>

            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Yetkili</label>
                    <input type="text" name="yetkili" class="form-control" value="<?= $musteri['yetkili'] ?>" required>
                </div>
            </div>

            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="telefon" class="form-control" value="<?= $musteri['telefon'] ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Adres</label>
                    <input type="text" name="adres" class="form-control" value="<?= $musteri['adres'] ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Şehir</label>
                    <input type="text" name="sehir" class="form-control" value="<?= $musteri['sehir'] ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori" class="form-control" value="<?= $musteri['kategori'] ?>">
                </div>
            </div>

            <div class="col-6">
    <div class="mb-3">
        <label class="form-label">Sorumlu</label>
        <select name="sorumlu" class="form-select">
            <option value="">Seçiniz</option>

            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"
                    <?= ($musteri['sorumlu'] == $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['username']) ?>
                </option>
            <?php endforeach; ?>

        </select>
    </div>
</div>



            <div class="col-12 text-end">
               <button type="submit" class="btn btn-success btn-label waves-effect waves-light rounded-pill"><i class="ri-check-double-line label-icon align-middle rounded-pill fs-16 me-2"></i> Kaydet</button>
               
               <a href="kullanicilar.php" class="btn btn-primary btn-label waves-effect waves-light rounded-pill">
    <i class="ri-user-smile-line label-icon align-middle rounded-pill fs-16 me-2"></i>
    Müşterilere Dön
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
    title: "<?php echo $_SESSION['mesaj']=='duzenle_ok' ? 'Güncellendi' : 'Güncellenemedi'; ?>",
     confirmButtonText: "Tamam"
});
</script>
<?php unset($_SESSION['mesaj']); endif; ?>




    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

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

    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>