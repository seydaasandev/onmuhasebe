<?php
session_start();
require "config.php";
require "auth.php";
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.php"); 
    exit;
}
$user_id = $_SESSION['user_id'];
$musteriler = $db->query("SELECT id, musteri_adi FROM musteriler ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>
<meta charset="utf-8" />
<title>Yeni Sipariş | Nextario Muhasebe</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="assets/images/favicon.ico">

<!-- JQUERY + SELECT2 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="sepetim.js"></script>
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
                                <h4 class="mb-sm-0">Yeni Sipariş</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item">Yeni Sipariş Ekle</li>
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
          <span class="badge badge-label bg-info"><i class="mdi mdi-circle-medium"></i> Yeni Sipariş Kaydı</span>
      
      </div>

      <div class="card-body form-steps">
        <form method="POST" action="islem.php?islem=siparis_ekle" class="vertical-navs-step">
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
                <input type="number" id="faturano" name="faturano" value="SPRS"class="form-control">
              </div>
            </div>

            <!-- ORTA ALAN -->
            <div class="col-lg-6">
              <div class="tab-content">

                <!-- STEP 1: MÜŞTERİ -->
               <div class="tab-pane fade show active" id="step-musteri">
  <h5 class="mb-3">Müşteri Seç</h5>

  <select name="musteri_id" class="form-control select2" id="musteri_id">
    <option value="">Müşteri seçiniz</option>
    <?php foreach ($musteriler as $m): ?>
      <option value="<?= $m['id'] ?>">
        <?= htmlspecialchars($m['musteri_adi']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <div class="text-end mt-3">
   <button type="button" id="btnMusteriDevam" class="btn btn-success nexttab" data-nexttab="step-urun">Devam Et</button>

  </div>
</div>

                <!-- STEP 2: ÜRÜN -->
                <div class="tab-pane fade" id="step-urun">
                  <h5 class="mb-3">Ürün Seç</h5>
                  <select id="urun_id" name="urun_id" class="js-example-basic-single" style="width:100%"></select>

                  <div class="row g-3 mt-3">
                    <div class="col-md-6">
                      <label class="form-label">KDV</label>
                      <input type="number" id="kdv" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">İndirim (%)</label>
                      <input type="number" id="indirim" class="form-control" value="0" min="0" max="100" step="0.01">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Birim Fiyat</label>
                      <input type="number" id="birim_fiyat" class="form-control" readonly step="0.01">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Adet</label>
                      <input type="number" id="adet" class="form-control" value="1" min="1" step="1">
                      <small id="adet_error" class="text-danger d-none"></small>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Stok</label>
                      <input type="number" id="stok" class="form-control" readonly>
                    </div>
                  </div>

                  <div class="text-end mt-4">
                    <button type="button" id="sepeteEkle" class="btn btn-success">Sepete Ekle</button>
                  </div>
                </div>

              </div>
            </div>

            <!-- SAĞ ÖZET -->
            <div class="col-lg-3">
              <h5 class="fs-14 text-primary mb-3"><i class="ri-shopping-cart-fill me-2"></i> Sipariş Özeti</h5>
              <ul class="list-group mb-3" id="sepet_listesi">
                <li class="list-group-item text-muted text-center">Sepet boş</li>
              </ul>
              <ul class="list-group mb-3">
                <li class="list-group-item d-flex justify-content-between"><span>Ara Toplam</span><strong id="ara_toplam">0 ₺</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>İndirim</span><strong id="indirim_toplam">0 ₺</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>KDV</span><strong id="kdv_toplam">0 ₺</strong></li>
                <li class="list-group-item d-flex justify-content-between bg-light"><span>Genel Toplam</span><strong id="genel_toplam">0 ₺</strong></li>
              </ul>

              <!-- Gizli alanlar -->
              <input type="hidden" name="sepet_json" id="sepet_json">
              <input type="hidden" name="ara_toplam_val" id="ara_toplam_val">
              <input type="hidden" name="indirim_toplam_val" id="indirim_toplam_val">
              <input type="hidden" name="kdv_toplam_val" id="kdv_toplam_val">
              <input type="hidden" name="genel_toplam_val" id="genel_toplam_val">

              <button type="submit" class="btn btn-primary w-100 mt-3">Siparişi Kaydet</button>
            </div>

          </div>
        </form>
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

  <!-- JAVASCRIPT --> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

 <?php if(isset($_SESSION['mesaj'])): ?>
<script>
<?php if($_SESSION['mesaj'] == 'siparis_ok'): ?>
Swal.fire({
    icon: 'success',
    title: 'Sipariş başarıyla kaydedildi.',
    showConfirmButton: true
});
<?php elseif($_SESSION['mesaj'] == 'stok_yetersiz'): ?>
Swal.fire({
    icon: 'error',
    title: 'Stok Yetersiz!',
    text: "<?php echo $_SESSION['stok_hata_mesaji'] ?? ''; ?>",
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