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
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Satışlar | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">
     <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!--datatable css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <!--datatable responsive css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
   

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
                                <h4 class="mb-sm-0">Satışlar</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Satışlar</li>
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
                                    <h5 class="card-title mb-0">Satışlar Tablosu</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
    <div class="col-md-3">
        <input type="date" id="start_date" class="form-control">
    </div>
    <div class="col-md-3">
        <input type="date" id="end_date" class="form-control">
    </div>
    <div class="col-md-2">
        <button id="filterBtn" class="btn btn-primary">Filtrele</button>
    </div>
</div>
                              <table id="satislar" class="display table table-bordered dt-responsive dataTable dtr-inline collapsed" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>İşlem No</th>
            <th>Müşteri</th>
            <th>Ürün</th>
            <th>Adet</th>
            <th>Birim Fiyat</th>
            <th>KDV</th>
            <th>İndirim</th>
            <th>Genel Toplam</th>
            <th>Satışı Yapan</th>
            <th>Tarih</th>
            <th>İşlem</th>
        </tr>
    </thead>
    
</table>
<script>
$(document).ready(function () {

    /* 🔥 TABLOYU DEĞİŞKENE ATA */
    var table = $('#satislar').DataTable({
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
            url: "ajax/satislar.php",
            type: "POST",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date   = $('#end_date').val();
            }
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
    const silBtn = e.target.closest('.satisSilBtn');
    if (!silBtn) return;

    e.preventDefault();
    let satisID = silBtn.getAttribute("data-id");

    Swal.fire({
        title: "Satışı silmek istediğinize emin misiniz?",
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

            const silBtnModal = document.getElementById("swal-sil-btn");
            const iptalBtnModal = document.getElementById("swal-iptal-btn");

            silBtnModal.addEventListener("click", () => {
                window.location.href = "islem.php?islem=satis_sil&id=" + satisID;
            });
            silBtnModal.addEventListener("touchend", () => {
                window.location.href = "islem.php?islem=satis_sil&id=" + satisID;
            });

            iptalBtnModal.addEventListener("click", () => Swal.close());
            iptalBtnModal.addEventListener("touchend", () => Swal.close());
        }
    });
});

</script>





<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_SESSION['mesaj'])): ?>
<script>
<?php if($_SESSION['mesaj'] == "satis_ok"): ?>
Swal.fire({
    icon: 'success',
    title: 'Başarılı!',
    text: 'Satış başarıyla eklendi.',
    confirmButtonText: 'Tamam'
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "ekle_no"): ?>
Swal.fire({
    icon: 'error',
    title: 'Hata!',
    text: 'Satış eklenirken bir hata oluştu.',
    confirmButtonText: 'Tamam'
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "sil_ok"): ?>
Swal.fire({
    icon: 'success',
    title: 'Silindi!',
    text: 'Satış başarıyla silindi.',
    confirmButtonText: 'Tamam'
});
<?php endif; ?>

<?php if($_SESSION['mesaj'] == "sil_no"): ?>
Swal.fire({
    icon: 'error',
    title: 'Silinemedi!',
    text: 'Silme işleminde bir hata oluştu.',
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

    

    <!-- App js -->
    <script src="assets/js/app.js"></script>
   
</body>

</html>