<?php
require "config.php";
require "auth.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
                                <h4 class="mb-sm-0">Ürün Ekle</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Ürün Ekle</li>
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
                                    <h5 class="card-title mb-0">Ürün Ekle</h5>
                                </div>
                                <div class="card-body">
                              <form action="islem.php?islem=urun_ekle" method="POST">
    <div class="row">

        <!-- Ürün Adı -->
        <div class="col-6">
            <div class="mb-3">
                <label for="urun_adi" class="form-label">Ürün Adı</label>
                <input type="text" name="urun_adi" class="form-control" placeholder="Ürün adını girin" id="urun_adi" required>
            </div>
        </div>

        <!-- Marka -->
        <div class="col-6">
            <div class="mb-3">
                <label for="marka" class="form-label">Marka</label>
                <input type="text" name="marka" class="form-control" placeholder="Ürün markası" id="marka">
            </div>
        </div>
        <div class="col-6">
            <div class="mb-3">
                <label for="cins" class="form-label">Cins</label>
                <input type="text" name="cins" class="form-control" placeholder="Ürün cinsi" id="cins">
            </div>
        </div>

        <!-- Kategori -->
        <div class="col-6">
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori</label>
                <input type="text" name="kategori" class="form-control" placeholder="Ürün kategorisi" id="kategori">
            </div>
        </div>

        <!-- Raf Bölümü -->
        <div class="col-6">
            <div class="mb-3">
                <label for="raf_bolumu" class="form-label">Raf Bölümü</label>
                <input type="text" name="raf_bolumu" class="form-control" placeholder="Raf bölümü (örn: A-12)" id="raf_bolumu">
            </div>
        </div>

        <!-- Stok -->
        <div class="col-6">
            <div class="mb-3">
                <label for="stok" class="form-label">Stok Adedi</label>
                <input type="number" name="stok" class="form-control" placeholder="Stok miktarı" id="stok" required>
            </div>
        </div>

        <!-- Barkod -->
        <div class="col-6">
            <div class="input-group mb-3">
    <button class="btn btn-outline-primary" type="button" id="barkodKontrolBtn">
        Kontrol
    </button>
    <input type="text" class="form-control" name="barkod" id="barkod" placeholder="Barkod giriniz">
</div>
        </div>

        <!-- Alış Fiyatı -->
        <div class="col-6">
            <div class="mb-3">
                <label for="satis_fiyat" class="form-label">Satış Fiyatı TL</label>
                <input type="text" name="satis_fiyat" class="form-control" placeholder="0.00" id="satis_fiyat">
            </div>
        </div>

        

        <!-- KDV -->
        <div class="col-6">
            <div class="mb-3">
                <label for="kdv" class="form-label">KDV</label>
                <select class="form-select" name="kdv" id="kdv" required>
                    <option value="" selected>Seçiniz</option>
                    <option value="0">0%</option>
                    <option value="5">5%</option>
                    <option value="16">16%</option>
                    <option value="18">18%</option>
                     <option value="20">20%</option>
                </select>
            </div>
        </div>

        <!-- Web Göster -->
        <div class="col-6">
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="web" id="web" value="1">
                    <label class="form-check-label" for="web">
                        Web'de Göster
                    </label>
                </div>
            </div>
        </div>

        <!-- Gönder -->
        <div class="col-lg-12">
            <div class="text-end">
                <button type="submit" class="btn btn-success btn-label waves-effect waves-light rounded-pill">
                    <i class="ri-check-double-line label-icon align-middle rounded-pill fs-16 me-2"></i>
                    Ürünü Kaydet
                </button>
            </div>
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
<script>
document.getElementById("barkodKontrolBtn").addEventListener("click", function () {

    let barkod = document.getElementById("barkod").value.trim();

    if (barkod === "") {
        Swal.fire({
            icon: "warning",
            title: "Uyarı",
            text: "Lütfen barkod giriniz"
        });
        return;
    }

    fetch("islem.php?islem=barkod_kontrol", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "barkod=" + encodeURIComponent(barkod)
    })
    .then(response => response.json())
    .then(data => {
        if (data.durum === "var") {
            Swal.fire({
                icon: "info",
                title: "Barkod Kullanılıyor",
                html: `<b>Ürün:</b> ${data.urun_adi}`
            });
        } else {
            Swal.fire({
                icon: "success",
                title: "Barkod Boş",
                text: "Bu barkod kullanılmıyor"
            });
        }
    });

});

// Fiyat formatlama fonksiyonu
function formatNumber(num) {
    if (!num) return '';
    return parseFloat(num).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function unformatNumber(str) {
    if (!str) return '';
    return str.replace(/\./g, '').replace(',', '.');
}

// Sayfa yüklenince mevcut değerleri formatla (yeni ürün için boş)
document.addEventListener('DOMContentLoaded', function() {
    // Yeni ürün için boş, gerek yok
});

// Input event'leri
document.addEventListener('focusin', function(e) {
    if (e.target.matches('input[name="satis_fiyat"]')) {
        e.target.value = unformatNumber(e.target.value);
    }
});

document.addEventListener('focusout', function(e) {
    if (e.target.matches('input[name="satis_fiyat"]')) {
        e.target.value = formatNumber(e.target.value);
    }
});

// Form submit'te unformat et
document.querySelector('form').addEventListener('submit', function() {
    const inputs = document.querySelectorAll('input[name="satis_fiyat"]');
    inputs.forEach(input => {
        input.value = unformatNumber(input.value);
    });
});
</script>


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

    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!--datatable js-->

    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>