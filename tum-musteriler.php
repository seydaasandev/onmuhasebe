<?php
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
                                <h4 class="mb-sm-0">Müşteriler</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Müşteriler</li>
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
                                    <h5 class="card-title mb-0">Müşteriler Tablosu <a href="yeni-musteri.php" class="d-inline-flex focus-ring focus-ring-info py-1 px-2 text-decoration-none border rounded-2">+ Yeni Müşteri</a></h5>
                                    
                                    
  
                                </div>
                                <div class="card-body">
                                <table id="musteriler" class="display table table-bordered dt-responsive dataTable dtr-inline collapsed" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Müşteri Adı</th>
            <th>Yetkili</th>
            <th>Telefon</th>
            <th>Adres</th>
            <th>Şehir</th>
            <th>Sorumlu</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    
   
</table>

  <script>
$(document).ready(function () {

    /* 🔥 TABLOYU DEĞİŞKENE ATA */
    var table = $('#musteriler').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 100,
        lengthMenu: [[25,50,100],[25,50,100]],
        order: [[0, 'desc']],

        dom: 'Bfrtip',

        buttons: [
            { extend: 'copy',  text: 'Kopyala' },
            { extend: 'excel', text: 'Excel' },
            { extend: 'csv',   text: 'CSV' },
            { extend: 'pdf',   text: 'PDF' },
            { extend: 'print', text: 'Yazdır' }
        ],

        ajax: {
            url: "ajax/musteriler.php",
            type: "POST",
           
        },

        columnDefs: [
            { targets: -1, orderable: false }
        ]
    });

    /* 🔥 FİLTRE BUTONU */
    $('#filterBtn').on('click', function () {
        table.ajax.reload(); // ✅ artık çalışır
    });

});
</script>

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
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.musteriSilBtn');
    if (!btn) return;

    e.preventDefault();
    let musteriID = btn.getAttribute("data-id");

    Swal.fire({
        title: "Müşteriyi silmek istediğinize emin misiniz?",
        html: "Bu işlem geri alınamaz!",
        icon: "warning",
        showCancelButton: false,
        showConfirmButton: false,
        didOpen: () => {
            const swalContainer = Swal.getHtmlContainer();
            const customButtons = document.createElement("div");
            customButtons.style.marginTop = "20px";

            customButtons.innerHTML = `
                <button id="swal-sil-btn" type="button" class="btn btn-danger btn-label waves-effect waves-light rounded-pill me-2">
                    <i class="ri-delete-bin-line label-icon align-middle rounded-pill fs-16 me-2"></i> Sil
                </button>

                <button id="swal-iptal-btn" type="button" class="btn btn-secondary btn-label waves-effect waves-light rounded-pill">
                    <i class="ri-close-circle-line label-icon align-middle rounded-pill fs-16 me-2"></i> İptal
                </button>
            `;

            swalContainer.appendChild(customButtons);

            const silBtn = document.getElementById("swal-sil-btn");
            const iptalBtn = document.getElementById("swal-iptal-btn");

            // Sil butonu: click ve touchend
            silBtn.addEventListener("click", () => {
                window.location.href = "islem.php?islem=musteri_sil&id=" + musteriID;
            });
            silBtn.addEventListener("touchend", () => {
                window.location.href = "islem.php?islem=musteri_sil&id=" + musteriID;
            });

            // İptal butonu: click ve touchend
            iptalBtn.addEventListener("click", () => Swal.close());
            iptalBtn.addEventListener("touchend", () => Swal.close());
        }
    });
});
</script>




<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_SESSION['mesaj'])): ?>
<script>
<?php if($_SESSION['mesaj'] == "ekle_ok"): ?>
Swal.fire({
    icon: 'success',
    title: 'Başarılı!',
    text: 'Müşteri başarıyla eklendi.',
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "ekle_no"): ?>
Swal.fire({
    icon: 'error',
    title: 'Hata!',
    text: 'Müşteri eklenirken bir hata oluştu.',
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "sil_ok"): ?>
Swal.fire({
    icon: 'success',
    title: 'Silindi!',
    text: 'Müşteri başarıyla silindi.',
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "sil_no"): ?>
Swal.fire({
    icon: 'error',
    title: 'Silinemedi!',
    text: 'Silme işleminde bir hata oluştu.',
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

  
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>