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
$stmt = $db->prepare("
    SELECT 
        s.islem_no,
        s.fatura_no,
        s.fatura_durum,
        s.fatura_tutari,
        s.print,
        s.indirim_toplami,
        s.siparis,
        s.musteri_id,
        s.id,
        s.tarih,
        m.musteri_adi,
        u.namesurname AS satisi_yapan_adi
    FROM muhasebe s
    INNER JOIN musteriler m ON m.id = s.musteri_id
    INNER JOIN users u ON u.id = s.islemi_yapan_id
    WHERE s.durum = 0 AND s.siparis = 1
    ORDER BY s.id DESC
");
$stmt->execute();
$faturalar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Sipariş Faturaları | Nextario Muhasebe Programı</title>
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
                                <h4 class="mb-sm-0">Sipariş Faturaları</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Sipariş Faturaları</li>
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
                                    <h5 class="card-title mb-0">Sipariş Faturaları</h5>
                                </div>
                                <div class="card-body">
                              <table id="buttons-datatables"
       class="display table table-bordered dt-responsive"
       style="width:100%">

<thead>
<tr><th>ID</th>
    <th>İşlem No</th>
    <th>Müşteri</th>
    <th>Fatura Tutarı</th>
    <th>Toplam İndirim</th>
    <th>Fatura No</th>
    <th>Tarih</th>
    <th>Satışı Yapan</th>
    <th>İşlemler</th>
</tr>
</thead>

<tbody>
<?php foreach ($faturalar as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['id']) ?></td>
    <td><?= htmlspecialchars($f['islem_no']) ?></td>

    <td>
        <a href="musteri-hesap.php?id=<?= $f['musteri_id'] ?>"
           class="link-primary text-decoration-underline">
            <?= htmlspecialchars($f['musteri_adi']) ?>
        </a>
    </td>

    <td class="text-end">
        <span class="badge badge-label bg-secondary"><i class="mdi mdi-circle-medium"></i>  <?= number_format($f['fatura_tutari'], 2, ',', '.') ?> ₺</span>
       
    </td>

    <td class="text-end text-danger"><span class="badge badge-label bg-success"><i class="mdi mdi-circle-medium"></i>  <?= number_format($f['indirim_toplami'], 2, ',', '.') ?> ₺</span>
       
    </td>

    <td class="text-end">
        <span class="badge badge-label bg-warning"><i class="mdi mdi-circle-medium"></i> <?= ($f['fatura_no']) ?> </span>
        
    </td>

    <td><?= date("d.m.Y H:i", strtotime($f['tarih'])) ?></td>
    <td><?= htmlspecialchars($f['satisi_yapan_adi']) ?></td>

    <td>
    <a href="fatura-goruntule.php?islem_no=<?= urlencode($f['islem_no']) ?>"
       class="btn btn-success btn-sm btn-label waves-effect waves-light">
        <i class="ri-eye-line label-icon align-middle fs-16 me-2"></i>
        Görüntüle
    </a>

    <?php if ($f['siparis'] == 1): ?>
        <!-- AÇIK FATURA -->
        <a href="#"
           class="btn btn-warning btn-sm faturaOnayBtn"
           data-id="<?= $f['islem_no'] ?>">
            <i class="ri-lock-line me-1"></i> Onayla
        </a>
    <?php else: ?>
        <!-- KAPALI FATURA -->
        <button type="button"
                class="btn btn-secondary btn-sm"
                disabled>
            <i class="ri-lock-2-line me-1"></i> Onaylandı
        </button>
    <?php endif; ?>

    <a href="#"
       class="btn btn-danger btn-sm faturaSilBtn"
       data-id="<?= $f['islem_no'] ?>">
       Sil
    </a>
</td>
</tr>
<?php endforeach; ?>
</tbody>

</table>

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
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.faturaSilBtn');
    if (!btn) return;

    e.preventDefault();

    const faturaID = btn.getAttribute('data-id');
    if (!faturaID) return;

    Swal.fire({
        title: "Faturayı iptal etmek istediğinize emin misiniz?",
        text: "Bu işlem geri alınamaz!",
        icon: "warning",
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {

            const container = Swal.getHtmlContainer();
            if (!container) return;

            const wrapper = document.createElement('div');
            wrapper.className = "mt-4 text-center";

            wrapper.innerHTML = `
                <button type="button" id="swal-sil-btn"
                    class="btn btn-danger rounded-pill me-2">
                    <i class="ri-delete-bin-line me-1"></i> İptal Et
                </button>

                <button type="button" id="swal-iptal-btn"
                    class="btn btn-secondary rounded-pill">
                    <i class="ri-close-circle-line me-1"></i> Vazgeç
                </button>
            `;

            container.appendChild(wrapper);

            const silBtn   = document.getElementById('swal-sil-btn');
            const iptalBtn = document.getElementById('swal-iptal-btn');

            if (silBtn) {
                silBtn.addEventListener('click', function () {
                    window.location.href =
                        "islem.php?islem=fatura_iptal&islem_no=" + encodeURIComponent(faturaID);
                });
            }

            if (iptalBtn) {
                iptalBtn.addEventListener('click', function () {
                    Swal.close();
                });
            }
        }
    });
});
</script>
<script>
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.faturaKapatBtn');
    if (!btn) return;

    e.preventDefault();

    const faturaID = btn.getAttribute('data-id');
    if (!faturaID) return;

    Swal.fire({
        title: "Faturayı kapatmak istediğinize emin misiniz?",
        text: "Kapatılan fatura tekrar düzenlenemez.",
        icon: "question",
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {

            const container = Swal.getHtmlContainer();
            if (!container) return;

            const wrapper = document.createElement('div');
            wrapper.className = "mt-4 text-center";

            wrapper.innerHTML = `
                <button type="button" id="swal-kapat-btn"
                    class="btn btn-warning rounded-pill me-2">
                    <i class="ri-lock-line me-1"></i> Faturayı Kapat
                </button>

                <button type="button" id="swal-vazgec-btn"
                    class="btn btn-secondary rounded-pill">
                    <i class="ri-close-circle-line me-1"></i> Vazgeç
                </button>
            `;

            container.appendChild(wrapper);

            document.getElementById('swal-kapat-btn')
                ?.addEventListener('click', function () {
                    window.location.href =
                        "islem.php?islem=fatura_kapat&islem_no=" +
                        encodeURIComponent(faturaID);
                });

            document.getElementById('swal-vazgec-btn')
                ?.addEventListener('click', function () {
                    Swal.close();
                });
        }
    });
});
</script>
<script>
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.faturaOnayBtn');
    if (!btn) return;

    e.preventDefault();

    const faturaID = btn.getAttribute('data-id');
    if (!faturaID) return;

    Swal.fire({
        title: "Siparişi onaylamak istediğinize emin misiniz?",
        text: "Onaylanan fatura faturalar bölümüne gider.",
        icon: "question",
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {

            const container = Swal.getHtmlContainer();
            if (!container) return;

            const wrapper = document.createElement('div');
            wrapper.className = "mt-4 text-center";

            wrapper.innerHTML = `
                <button type="button" id="swal-kapat-btn"
                    class="btn btn-warning rounded-pill me-2">
                    <i class="ri-lock-line me-1"></i> Siparişi Onayla
                </button>

                <button type="button" id="swal-vazgec-btn"
                    class="btn btn-secondary rounded-pill">
                    <i class="ri-close-circle-line me-1"></i> Vazgeç
                </button>
            `;

            container.appendChild(wrapper);

            document.getElementById('swal-kapat-btn')
                ?.addEventListener('click', function () {
                    window.location.href =
                        "islem.php?islem=fatura_onay&islem_no=" +
                        encodeURIComponent(faturaID);
                });

            document.getElementById('swal-vazgec-btn')
                ?.addEventListener('click', function () {
                    Swal.close();
                });
        }
    });
});
</script>






<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['mesaj'])): ?>
<script>
<?php
switch ($_SESSION['mesaj']) {

    case 'onay_ok':
        echo "Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: 'Sipariş başarıyla onaylandı.',
            confirmButtonText: 'Tamam'
        });";
        break;

    case 'onay_hata':
        echo "Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Sipariş onaylanırken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });";
        break;

    case 'ekle_ok':
        echo "Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: 'Ödeme başarıyla eklendi.',
            confirmButtonText: 'Tamam'
        });";
        break;

    case 'ekle_no':
        echo "Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Ödeme eklenirken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });";
        break;

    case 'sil_ok':
        echo "Swal.fire({
            icon: 'success',
            title: 'Silindi!',
            text: 'Ödeme başarıyla silindi.',
            confirmButtonText: 'Tamam'
        });";
        break;

    case 'sil_no':
        echo "Swal.fire({
            icon: 'error',
            title: 'Silinemedi!',
            text: 'Silme işleminde bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });";
        break;
}
?>
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
    <script>
$(document).ready(function () {

    if ($.fn.DataTable.isDataTable('#buttons-datatables')) {
        $('#buttons-datatables').DataTable().destroy();
    }

    $('#buttons-datatables').DataTable({
        dom: "Bfrtip",
        buttons: ["copy","csv","excel","print","pdf"],
        order: [[0, "desc"]],
        columnDefs: [
            { targets: 0, type: "num" }
        ]
    });

});
</script>
</body>

</html>