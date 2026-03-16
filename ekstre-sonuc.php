<?php
require "config.php";
require "auth.php";


/* === ROL KONTROL === */
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if (strtolower($role) === 'user') {
    header("Location: 403.php");
    exit;
}

/* === POST KONTROL === */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ekstre-sorgula.php");
    exit;
}

$musteri_id = (int)$_POST['musteri_id'];
$baslangic  = $_POST['baslangic']; // YYYY-MM-DD
$bitis      = $_POST['bitis'];     // YYYY-MM-DD
$islem_turu = $_POST['islem_turu'];

/* === TARİH ARALIĞI (ÇOK ÖNEMLİ) === */
$baslangic_dt = $baslangic . ' 00:00:00';
$bitis_dt     = $bitis . ' 23:59:59';

$ekstre = [];

/* ==========================
   SATIŞLAR (BORÇ)
========================== */
if ($islem_turu === 'fatura' || $islem_turu === 'tumu') {

$sql = "
SELECT 
    s.id,
    s.tutar,
    s.indirim_toplami,
    s.fatura_no,
    s.tarih,
    m.musteri_adi,
    u.username AS kullanici
FROM satislar s
JOIN musteriler m ON m.id = s.musteri_id
LEFT JOIN users u ON u.id = s.satisi_yapan_id
WHERE s.musteri_id = ?
AND s.tarih BETWEEN ? AND ?
AND s.durum = 0
ORDER BY s.tarih ASC
";

$stmt = $db->prepare($sql);
$stmt->execute([$musteri_id, $baslangic_dt, $bitis_dt]);

    while ($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ekstre[] = [
            'id'          => $s['id'],
            'tur'         => 'Satış',
            'islem_no'    => $s['fatura_no'],
            'musteri_adi' => $s['musteri_adi'],
            'aciklama'    => 'Satış faturası',
            'borc'        => $s['tutar'],
            'alacak'      => null,
            'kullanici'   => $s['kullanici'],
            'tarih'       => $s['tarih']
        ];
    }
}

/* ==========================
   ÖDEMELER (ALACAK)
========================== */
if ($islem_turu === 'odeme' || $islem_turu === 'tumu') {

$sql = "
SELECT 
    o.id,
    o.tutar,
    o.makbuz_no,
    o.aciklama,
    o.tarih,
    m.musteri_adi,
    u.username AS kullanici
FROM odemeler o
JOIN musteriler m ON m.id = o.musteri_id
LEFT JOIN users u ON u.id = o.odemeyi_alan
WHERE o.musteri_id = ?
AND o.tarih BETWEEN ? AND ?
AND o.durum = 0
ORDER BY o.tarih ASC
";

$stmt = $db->prepare($sql);
$stmt->execute([$musteri_id, $baslangic_dt, $bitis_dt]);

    while ($o = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ekstre[] = [
            'id'          => $o['id'],
            'tur'         => 'Ödeme',
            'islem_no'    => $o['makbuz_no'],
            'musteri_adi' => $o['musteri_adi'],
            'aciklama'    => $o['aciklama'],
            'borc'        => null,
            'alacak'      => $o['tutar'],
            'kullanici'   => $o['kullanici'],
            'tarih'       => $o['tarih']
        ];
    }
}

/* === TARİHE GÖRE SIRALA === */
usort($ekstre, function ($a, $b) {
    return strtotime($a['tarih']) <=> strtotime($b['tarih']);
});

/* ==========================
   TOPLAM ÖDEME
========================== */
$sql = "
    SELECT COALESCE(SUM(tutar),0)
    FROM odemeler
    WHERE musteri_id = ?
      AND tarih BETWEEN ? AND ?
      AND durum = 0
";
$stmt = $db->prepare($sql);
$stmt->execute([$musteri_id, $baslangic_dt, $bitis_dt]);
$toplam_odeme = $stmt->fetchColumn();

/* ==========================
   TOPLAM SATIŞ
========================== */
$sql = "
    SELECT COALESCE(SUM(tutar),0)
    FROM satislar
    WHERE musteri_id = ?
      AND tarih BETWEEN ? AND ?
      AND durum = 0
";
$stmt = $db->prepare($sql);
$stmt->execute([$musteri_id, $baslangic_dt, $bitis_dt]);
$toplam_satis = $stmt->fetchColumn();
?>




<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

     <meta charset="utf-8" />
    <title>Ekstre Tablosu| Nextario Muhasebe Programı</title>
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
                                <h4 class="mb-sm-0">Ekstre Tablosu</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Ekstre Tablosu</li>
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
                                    <h5 class="card-title mb-0">Ekstre Tablosu</h5>
                                </div>
                                <div class="card-body">
                             <table id="buttons-datatables" class="display table table-bordered dt-responsive">
<thead>
<tr>
    <th>ID</th>
    <th>Tür</th>
    <th>İşlem No</th>
    <th>Müşteri</th>
    <th>Açıklama</th>
    <th class="text-end">Borç</th>
    <th class="text-end">Alacak</th>
    <th>İşlemi Yapan</th>
    <th>Tarih</th>
</tr>
</thead>

<tbody>
<?php foreach ($ekstre as $e): ?>
<tr>
    <td><?= $e['id'] ?></td>

    <td>
        <span class="badge bg-<?= $e['tur'] === 'Satış' ? 'danger' : 'success' ?>">
            <?= $e['tur'] ?>
        </span>
    </td>

    <td><?= htmlspecialchars($e['islem_no']) ?></td>

    <td><?= htmlspecialchars($e['musteri_adi']) ?></td>

    <td><?= htmlspecialchars($e['aciklama']) ?></td>

    <td class="text-end text-danger">
        <?= $e['borc'] ? number_format($e['borc'],2,',','.') . ' ₺' : '—' ?>
    </td>

    <td class="text-end text-success">
        <?= $e['alacak'] ? number_format($e['alacak'],2,',','.') . ' ₺' : '—' ?>
    </td>

    <td><?= htmlspecialchars($e['kullanici']) ?></td>

    <td><?= date("d.m.Y H:i", strtotime($e['tarih'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>  
<div class="alert alert-info">
    <strong>
        <?= date('d.m.Y', strtotime($baslangic)) ?> – 
        <?= date('d.m.Y', strtotime($bitis)) ?>
    </strong>
    tarihleri arasında:
    <br>
    <b>Toplam Satış:</b> 
    <?= number_format($toplam_satis, 2, ',', '.') ?> ₺
    <br>
    <b>Toplam Ödeme:</b> 
    <?= number_format($toplam_odeme, 2, ',', '.') ?> ₺<br>
    <b>Dönem Borçu:</b> 
    <?= number_format($toplam_satis - $toplam_odeme, 2, ',', '.') ?> ₺
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



 

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    </div>

 









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