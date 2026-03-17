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
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Geçersiz satış ID");
}

$satis_id = (int)$_GET['id'];

/* SATIŞ */
$stmt = $db->prepare("
    SELECT s.*, 
           m.musteri_adi,
           u.urun_adi,
           u.kdv,
           u.stok,
           u.satis_euro as urun_fiyat,
           u.satis_fiyat as urun_fiyat_tl,
           u.barkod
    FROM satislar s
    INNER JOIN musteriler m ON m.id = s.musteri_id
    INNER JOIN urunler u ON u.id = s.urun_id
    WHERE s.id = ?
");
$stmt->execute([$satis_id]);
$satis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$satis) die("Satış bulunamadı");

$eurRate = (float)($db->query("SELECT kur FROM doviz_kurlari WHERE para_birimi='EUR' LIMIT 1")->fetchColumn() ?: 0);
if ((float)$satis['urun_fiyat'] <= 0 && $eurRate > 0) {
    $satis['urun_fiyat'] = (float)$satis['urun_fiyat_tl'] / $eurRate;
}

/* MÜŞTERİLER */
$musteriler = $db->query("
    SELECT id, musteri_adi 
    FROM musteriler 
    ORDER BY musteri_adi ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>
<meta charset="utf-8" />
<title>Satış Düzenle| Nextario Muhasebe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="assets/images/favicon.ico">

<!-- JQUERY + SELECT2 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="sepet.js"></script>
<!-- TEMA -->
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/icons.min.css" rel="stylesheet">
<link href="assets/css/app.min.css" rel="stylesheet">
<link href="assets/css/custom.min.css" rel="stylesheet">
<script src="assets/js/layout.js"></script>
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
                                <h4 class="mb-sm-0">Satış Düzenle</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item">Satış Düzenle</li>
                                    </ol>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- end page title -->
                    
      <div class="row">
  <div class="col-xl-12">
    <div class="card">
      <div class="card-header bg-primary text-white">
          <span class="badge badge-label bg-info"><i class="mdi mdi-circle-medium"></i> Satış Düzenle İşlem No <?= $satis['islem_no'] ?></span>
      
      </div>

      <div class="card-body form-steps">
<form method="POST" action="islem.php?islem=satis_guncelle" class="vertical-navs-step">
          <div class="row gy-4">

            <!-- SOL STEP MENÜ -->
            <div class="col-lg-3 border-end">
              <div class="nav flex-column custom-nav nav-pills" role="tablist">
                <button class="nav-link active mb-2" data-bs-toggle="pill" data-bs-target="#step-musteri" type="button" id="stepMusteriBtn"> <i class="ri-user-fill me-2"></i> Adım 1 - Müşteri </button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#step-urun" type="button">
                  <i class="ri-shopping-bag-3-fill me-2"></i> Adım 2 - Ürün
                </button>
              </div>
              <div class="mt-4">
                <label class="form-label">Fatura No</label>
                <input type="number" id="faturano" name="faturano" value="<?= $satis['fatura_no'] ?>" class="form-control" required readonly>
              </div>
            </div>

            <!-- ORTA ALAN -->
            <div class="col-lg-6">
              <div class="tab-content">

                <!-- STEP 1: MÜŞTERİ -->
               <div class="tab-pane fade show active" id="step-musteri">
                <h5 class="mb-3">Müşteri Seç</h5>

               <select name="musteri_id" id="musteri_id" class="form-control select2" required disabled>
                    <?php foreach ($musteriler as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $m['id']==$satis['musteri_id']?'selected':'' ?>>
                            <?= htmlspecialchars($m['musteri_adi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
<br><br>
<!-- Warning Alert -->
<div class="alert alert-warning alert-dismissible alert-additional fade show mb-0 material-shadow" role="alert">
    <div class="alert-body">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="ri-alert-line fs-16 align-middle"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading">Satış düzenlemesi mi yapıyorsun ?</h5>
                <p class="mb-0">Fatura içeriğinde ki bir satışı düzenlerken müşteri adı değiştirilemez. </p>
            </div>
        </div>
    </div>
    <div class="alert-content">
        <p class="mb-0">İstersen faturayı iptal edip tüm satışları tekrar yapabilirsin.</p>
    </div>
</div>
</div>

                <!-- STEP 2: ÜRÜN -->
                <div class="tab-pane fade" id="step-urun">
                  <h5 class="mb-3">Ürün Seç</h5>
                  <select id="urun_id" name="urun_id" class="js-example-basic-single" style="width:100%">
                    <option value="<?= $satis['urun_id'] ?>" selected>
                        <?= htmlspecialchars($satis['urun_adi']) ?>
                    </option>
                </select>

                  <div class="row g-3 mt-3">
                    <div class="col-md-6">
                      <label class="form-label">KDV</label>
                     <input type="number" id="kdv" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">İndirim (%)</label>
                      <input type="number" id="indirim_yuzde" class="form-control" value="0" min="0" max="100" step="0.01">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">KDV Dahil Tutar</label>
                       <input type="number" id="tutar" name="tutar" class="form-control" step="0.01" required readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Adet</label>
                      <input type="number" id="adet" name="adet" class="form-control" value="1" min="1" step="1" required>
                      <small id="adet_error" class="text-danger d-none"></small>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Stok</label>
                      <input type="number" id="stok" class="form-control" readonly>
                    </div>
                     <div class="col-md-6">
                         <label class="form-label">İndirim Toplamı</label>
                    <input type="number" id="indirim_toplami" name="indirim_toplami" class="form-control" step="0.01" readonly>
                    </div>
                  </div>

                  
                </div>

              </div>
            </div>

            <!-- SAĞ ÖZET -->
            <div class="col-lg-3">
              <h5 class="fs-14 text-primary mb-3"><i class="ri-shopping-cart-fill me-2"></i> Satış Özeti</h5>
              <ul class="list-group mb-3" id="sepet_listesi">
               
              </ul>
              <ul class="list-group">
    <li class="list-group-item d-flex justify-content-between">
        <span>Ara Toplam</span>
        <strong id="ara_toplam">0 €</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>İndirim</span>
        <strong id="indirim_toplam">0 €</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>KDV</span>
        <strong id="kdv_toplam">0 €</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between bg-light">
        <span>Genel Toplam</span>
        <strong id="genel_toplam_li">0 €</strong>
    </li>
</ul>

              <!-- Gizli alanlar -->
              <input type="hidden" name="sepet_json" id="sepet_json">
              <input type="hidden" name="ara_toplam_val" id="ara_toplam_val">
              <input type="hidden" name="indirim_toplam_val" id="indirim_toplam_val">
              <input type="hidden" name="kdv_toplam_val" id="kdv_toplam_val">
              <input type="hidden" name="genel_toplam_val" id="genel_toplam_val">
              <input type="hidden" name="satis_id" value="<?= $satis['id'] ?>">
              

              <button type="submit" class="btn btn-primary w-100 mt-3">Satışı Kaydet</button>
            </div>

          </div>
        </form>
 <script>
$(function() {
    $('#musteri_id').select2({ width: "100%" });

    $('#urun_id').select2({
        placeholder: "Ürün ara",
        width: "100%",
        ajax: {
            url: "urun-ara.php",
            dataType: "json",
            delay: 250,
            data: function(params) { return { term: params.term }; },
            processResults: function(data) { return data; }
        }
    });

    var birimFiyat = <?= $satis['urun_fiyat'] ?>;
    $('#kdv').val(<?= $satis['kdv'] ?>);
    $('#stok').val(<?= $satis['stok'] ?>);
    $('#adet').val(<?= $satis['adet'] ?>);
    $('#tutar').val(<?= $satis['tutar'] ?>);
    $('#kdv_tutar').val(0);
    $('#indirim_toplami').val(<?= $satis['indirim_toplami'] ?>);
    $('#indirim_yuzde').val(0);
    $('#genel_toplam').val(0);

    function hesaplaTutar() {
        var adet = parseFloat($('#adet').val()) || 0;
        var kdv = parseFloat($('#kdv').val()) || 0;
        var indirimYuzde = parseFloat($('#indirim_yuzde').val()) || 0;

        var tutar = birimFiyat * adet;
        var kdvTutar = tutar * kdv / 100;
        $('#kdv_tutar').val(kdvTutar.toFixed(2));

        var tutarKdvDahil = tutar + kdvTutar;
        $('#tutar').val(tutarKdvDahil.toFixed(2));

        var toplamIndirim = tutar * indirimYuzde / 100;
        $('#indirim_toplami').val(toplamIndirim.toFixed(2));

        var genelToplam = tutarKdvDahil - toplamIndirim;
        $('#genel_toplam').val(genelToplam.toFixed(2));
    }

    function resetUrun() {
        $('#adet').val('');
        $('#tutar').val('');
        $('#kdv_tutar').val('');
        $('#indirim_toplami').val('');
        $('#indirim_yuzde').val(0);
        $('#genel_toplam').val('');
    }

    $('#urun_id').on('change', function() {
        var urun_id = $(this).val();
        if(!urun_id) return;
        $.ajax({
            url: 'urun-detay.php',
            type: 'GET',
            data: { id: urun_id },
            dataType: 'json',
            success: function(data) {
                $('#kdv').val(data.kdv);
                $('#stok').val(data.stok);
                birimFiyat = data.birim_fiyat;  // unutma dediğin şekilde
                resetUrun();
            }
        });
    });

    $('#adet').on('input', function() {
        var adet = parseFloat($(this).val()) || 0;
        var stok = parseFloat($('#stok').val()) || 0;
        if(adet > stok){
            alert('Stok yetersiz! Maksimum ' + stok + ' adet seçebilirsiniz.');
            $(this).val(stok);
            adet = stok;
        }
        hesaplaTutar();
    });

    $('#indirim_yuzde').on('input', function() {
        var indirim = parseFloat($(this).val()) || 0;
        if(indirim > 100){
            $(this).val(100);
            indirim = 100;
        }
        hesaplaTutar();
    });

    hesaplaTutar();
});
</script>
<script>
$(function () {

    var birimFiyat = <?= $satis['urun_fiyat'] ?>;

    function hesaplaTutar() {

        var adet = parseFloat($('#adet').val()) || 0;
        var kdvOran = parseFloat($('#kdv').val()) || 0;
        var indirimYuzde = parseFloat($('#indirim_yuzde').val()) || 0;

        var araToplam = birimFiyat * adet;
        var kdvTutar = araToplam * kdvOran / 100;
        var indirimToplam = araToplam * indirimYuzde / 100;
        var genelToplam = (araToplam + kdvTutar) - indirimToplam;

        // 🔹 Görsel alanlar
        $('#ara_toplam').text(araToplam.toFixed(2) + ' €');
        $('#kdv_toplam').text(kdvTutar.toFixed(2) + ' €');
        $('#indirim_toplam').text(indirimToplam.toFixed(2) + ' €');
        $('#genel_toplam_li').text(genelToplam.toFixed(2) + ' €');

        // 🔹 Form inputları (POST GİDENLER)
        $('#tutar').val((araToplam + kdvTutar).toFixed(2));
        $('#kdv_toplam_val').val(kdvTutar.toFixed(2));        // 🔥 ASIL SORUN BUYDU
        $('#indirim_toplami').val(indirimToplam.toFixed(2));
        $('#genel_toplam_val').val(genelToplam.toFixed(2));
    }

    $('#adet, #indirim_yuzde').on('input', function () {
        var adet = parseFloat($('#adet').val()) || 0;
        var stok = parseFloat($('#stok').val()) || 0;

        if (adet > stok) {
            alert('Stok yetersiz! Maksimum ' + stok + ' adet.');
            $('#adet').val(stok);
        }
        hesaplaTutar();
    });

    $('#urun_id').on('change', function () {
        var urun_id = $(this).val();
        if (!urun_id) return;

        $.getJSON('urun-detay.php', { id: urun_id }, function (data) {
            $('#kdv').val(data.kdv);
            $('#stok').val(data.stok);
            birimFiyat = data.birim_fiyat;
            hesaplaTutar();
        });
    });

    // Sayfa ilk açılış
    hesaplaTutar();
});
</script>

      </div>
    </div>
  </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if($_SESSION['mesaj'] == 'satis_ok'): ?>
Swal.fire({
    icon: 'success',
    title: 'Satış başarıyla kaydedildi. Faturanız oluşturuldu.',
    showConfirmButton: true
});
<?php elseif($_SESSION['mesaj'] == 'stok_yetersiz'): ?>
Swal.fire({
    icon: 'error',
    title: 'Stok Yetersiz!',
    text: "<?php echo $_SESSION['stok_hata_mesaji']; ?>",
    showConfirmButton: true
});
<?php else: ?>
Swal.fire({
    icon: 'error',
    title: 'Bir hata meydana geldi',
    showConfirmButton: true
});
<?php endif; ?>
</script>
<?php unset($_SESSION['mesaj'], $_SESSION['stok_hata_mesaji']); endif; ?>




    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

<!-- Bootstrap (dropdown için şart) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- form wizard init -->
<script src="assets/js/pages/form-wizard.init.js"></script>
<!-- App js -->
<script src="assets/js/app.js"></script>
    
</body>

</html>