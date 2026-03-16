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


$sql = $db->prepare("SELECT * FROM urunler WHERE durum = 0 ORDER BY id DESC");
$sql->execute();
$urunler = $sql->fetchAll(PDO::FETCH_ASSOC);

$genel_kdvsiz_toplam = 0;
$genel_kdv_toplam    = 0;
$genel_kdvli_toplam  = 0;
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Ürünler | Nextario Muhasebe Programı</title>
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
                                <h4 class="mb-sm-0">Ürünler</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Ürünler</li>
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
                                    <h5 class="card-title mb-0">Ürünler Tablosu</h5>
                                </div>
                                <div class="card-body">
                                   <div class="table-responsive"> 
                                <table id="buttons-datatables" class="display table table-bordered dt-responsive dataTable dtr-inline collapsed" style="width:100%">
                                    
    <thead>
        <tr>
            <th>ID</th>
            <th>Ürün Adı</th>
             <th>Barkod</th>
            <th>Stok</th>
            <th>Marka</th>
            <th>Cins</th>
            <th>KDV</th>
            <th>Birim Fiyatı</th>
            <th>KDV'siz Toplam Fiyat</th>
            <th>KDV'li Toplam Fiyat</th>
            <th>Toplam KDV</th>
            
          
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($urunler as $urun): ?>
        <?php
    $kdvsiz = $urun['satis_fiyat'] * $urun['stok'];
    $kdv_tutari = $kdvsiz * $urun['kdv'] / 100;
    $kdvli = $kdvsiz + $kdv_tutari;
    // 🔥 GENEL TOPLAMLAR
    $genel_kdvsiz_toplam += $kdvsiz;
    $genel_kdv_toplam += $kdv_tutari;
    $genel_kdvli_toplam += $kdvli;
?>
            <tr>
                <td><?= $urun['id'] ?></td>
                <td><?= htmlspecialchars($urun['urun_adi']) ?></td>
                <td><?= htmlspecialchars($urun['barkod']) ?></td>
                <td><?= $urun['stok'] ?></td>
                <td><?= htmlspecialchars($urun['marka']) ?></td>
                <td><?= htmlspecialchars($urun['cins']) ?></td>
                <td>%<?= $urun['kdv'] ?></td>
                <td><?= number_format($urun['satis_fiyat'], 2, ',', '.') ?> ₺</td>
                <td><?= number_format($kdvsiz, 2, ',', '.') ?> ₺</td>
                <td><?= number_format($kdvli, 2, ',', '.') ?> ₺</td>
                <td><?= number_format($kdv_tutari, 2, ',', '.') ?> ₺</td>

                <td>
                    <a href="urun-duzenle.php?id=<?= $urun['id'] ?>" class="btn btn-primary btn-sm">
                        Düzenle
                    </a>

                    <a href="#" 
   class="btn btn-danger btn-sm urunSilBtn" 
   data-id="<?= $urun['id'] ?>">
   Sil
</a>
                </td>
            </tr>
        <?php endforeach; ?>

    </tbody>
    <tfoot>
    <tr style="font-weight:bold; background:#f8f9fa;">
        <td colspan="8" style="text-align:right;">GENEL TOPLAM</td>
        <td><?= number_format($genel_kdvsiz_toplam, 2, ',', '.') ?> ₺</td>
        <td><?= number_format($genel_kdvli_toplam, 2, ',', '.') ?> ₺</td>
        <td><?= number_format($genel_kdv_toplam, 2, ',', '.') ?> ₺</td>
        <td></td>
    </tr>
</tfoot>
</table>

                                    </div>
                                </div> </div>
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