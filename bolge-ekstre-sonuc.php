<?php
require "config.php";
require "auth.php";


/* === ROL KONTROL === */
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if (strtolower($stmt->fetchColumn()) === 'user') {
    header("Location: 403.php");
    exit;
}

/* === POST KONTROL === */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ekstre-sorgula.php");
    exit;
}

$sehir      = $_POST['sehir'] ?? '';
$baslangic  = $_POST['baslangic'] . ' 00:00:00';
$bitis      = $_POST['bitis'] . ' 23:59:59';
$islem_turu = $_POST['islem_turu'];

/* ==========================
   ŞEHRE AİT MÜŞTERİLER
========================== */
$sql = "SELECT id, musteri_adi FROM musteriler WHERE durum = 0";
$params = [];
if ($sehir !== '') {
    $sql .= " AND sehir = ?";
    $params[] = $sehir;
}
$sql .= " ORDER BY musteri_adi ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ekstre_musteriler = [];

foreach ($musteriler as $musteri) {

    $m_id   = $musteri['id'];
    $m_name = $musteri['musteri_adi'];
    $ekstre = [];

    /* ======================
       TÜM SATIŞLAR (HESAPLAMA)
    ====================== */
    $stmtS = $db->prepare("
        SELECT tutar, fatura_no, tarih
        FROM satislar
        WHERE musteri_id = ?
          AND tarih BETWEEN ? AND ?
          AND durum = 0
        ORDER BY tarih DESC
    ");
    $stmtS->execute([$m_id, $baslangic, $bitis]);
    $tum_satislar = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    /* ======================
       TÜM ÖDEMELER (HESAPLAMA)
    ====================== */
    $stmtO = $db->prepare("
        SELECT tutar, makbuz_no, tarih
        FROM odemeler
        WHERE musteri_id = ?
          AND tarih BETWEEN ? AND ?
          AND durum = 0
        ORDER BY tarih ASC
    ");
    $stmtO->execute([$m_id, $baslangic, $bitis]);
    $tum_odemeler = $stmtO->fetchAll(PDO::FETCH_ASSOC);

    /* ======================
       DOĞRU TOPLAM HESAPLAR
    ====================== */
    $toplam_satis = array_sum(array_column($tum_satislar, 'tutar'));
    $toplam_odeme = array_sum(array_column($tum_odemeler, 'tutar'));
    $kalan_borc   = $toplam_satis - $toplam_odeme;

    /* ======================
       FATURALAR (borcu kapatan)
    ====================== */
    if ($islem_turu === 'fatura' || $islem_turu === 'tumu') {

        $kalan = $kalan_borc;

        foreach ($tum_satislar as $s) {
            if ($kalan <= 0) break;

            $kalan -= $s['tutar'];

            $ekstre[] = [
                'tur' => 'Satış',
                'islem_no' => $s['fatura_no'],
                'borc' => $s['tutar'],
                'alacak' => null,
                'tarih' => $s['tarih']
            ];
        }
    }

    /* ======================
       TÜM ÖDEMELER
    ====================== */
    if ($islem_turu === 'odeme' || $islem_turu === 'tumu') {
        foreach ($tum_odemeler as $o) {
            $ekstre[] = [
                'tur' => 'Ödeme',
                'islem_no' => $o['makbuz_no'],
                'borc' => null,
                'alacak' => $o['tutar'],
                'tarih' => $o['tarih']
            ];
        }
    }

    if (!empty($ekstre)) {
        usort($ekstre, fn($a,$b) => strtotime($a['tarih']) <=> strtotime($b['tarih']));

        $ekstre_musteriler[] = [
            'musteri_adi' => $m_name,
            'ekstre' => $ekstre,
            'toplam_satis' => $toplam_satis,
            'toplam_odeme' => $toplam_odeme,
            'donem_borcu' => $kalan_borc
        ];
    }
}
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

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
                            <?php foreach ($ekstre_musteriler as $musteri): ?>
    <h4><?= htmlspecialchars($musteri['musteri_adi']) ?></h4>
   <table class="datatable-buttons display table table-bordered dt-responsive" style="width:100%">
        <thead>
            <tr>
                <th>Tür</th>
                <th>İşlem No</th>
                <th>Açıklama</th>
                <th class="text-end">Borç</th>
                <th class="text-end">Alacak</th>
                <th>İşlemi Yapan</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($musteri['ekstre'] as $e): ?>
            <tr>
                <td>
                    <span class="badge bg-<?= $e['tur'] === 'Satış' ? 'danger' : 'success' ?>">
                        <?= htmlspecialchars($e['tur']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($e['islem_no']) ?></td>
                <td><?= htmlspecialchars($e['aciklama'] ?? ($e['tur'] === 'Satış' ? 'Satış faturası' : 'Ödeme')) ?></td>
                <td class="text-end text-danger">
                    <?= $e['borc'] !== null ? number_format($e['borc'], 2, ',', '.') . ' ₺' : '—' ?>
                </td>
                <td class="text-end text-success">
                    <?= $e['alacak'] !== null ? number_format($e['alacak'], 2, ',', '.') . ' ₺' : '—' ?>
                </td>
                <td><?= htmlspecialchars($e['kullanici']) ?></td>
                <td><?= date("d.m.Y H:i", strtotime($e['tarih'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Toplam:</th>
                <th class="text-end text-danger"><?= number_format($musteri['toplam_satis'], 2, ',', '.') ?> ₺</th>
                <th class="text-end text-success"><?= number_format($musteri['toplam_odeme'], 2, ',', '.') ?> ₺</th>
                <th colspan="2" class="text-end">Dönem Borcu: <?= number_format($musteri['donem_borcu'], 2, ',', '.') ?> ₺</th>
            </tr>
        </tfoot>
    </table>
    <hr>
<?php endforeach; ?>


<script>
$(document).ready(function () {

    $('.datatable-buttons').each(function () {

        if ($.fn.DataTable.isDataTable(this)) {
            return;
        }

        $(this).DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            paging: true,
            searching: true,
            ordering: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'Kopyala' },
                { extend: 'excel', text: 'Excel' },
                { extend: 'pdf', text: 'PDF' },
                { extend: 'print', text: 'Yazdır' }
            ]
        });

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

    <!-- App js -->
    <script src="assets/js/app.js"></script>

</body>

</html>