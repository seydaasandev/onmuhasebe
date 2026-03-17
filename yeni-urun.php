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

$kurMap = ['TRY' => 1.0, 'EUR' => 1.0, 'USD' => 1.0, 'GBP' => 1.0];
$kurRows = $db->query("SELECT para_birimi, kur FROM doviz_kurlari WHERE para_birimi IN ('TRY','EUR','USD','GBP')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($kurRows as $row) {
    $pb = strtoupper((string)$row['para_birimi']);
    $kur = (float)$row['kur'];
    if (isset($kurMap[$pb]) && $kur > 0) {
        $kurMap[$pb] = $kur;
    }
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
                              <form action="islem.php?islem=urun_ekle" method="POST" enctype="multipart/form-data">
    <div class="row">

        <!-- Ürün Adı -->
        <div class="col-6">
            <div class="mb-3">
                <label for="urun_adi" class="form-label">Ürün Adı</label>
                <input type="text" name="urun_adi" class="form-control" placeholder="Ürün adını girin" id="urun_adi" required>
            </div>
        </div>

        <!-- Ana Kategori / Marka / Model / Cins / Kategori (Kategori Ağacı) -->
        <div class="col-12"><h6 class="text-muted border-bottom pb-1 mb-2"><i class="ri-node-tree me-1"></i> Kategori Ağacı</h6></div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Ana Kategori</label>
                <select name="ana_kategori_id" id="nu_ana_kat" class="form-select select2-agac">
                    <option value="">Seçiniz</option>
                    <?php
                    $akListNu = $db->query("SELECT id,ad FROM ana_kategoriler ORDER BY ad")->fetchAll(PDO::FETCH_ASSOC);
                    foreach($akListNu as $ak): ?>
                    <option value="<?= $ak['id'] ?>"><?= htmlspecialchars($ak['ad']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Marka</label>
                <select name="marka_id" id="nu_marka" class="form-select select2-agac">
                    <option value="">Önce Ana Kategori Seçin</option>
                </select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Model</label>
                <select name="model_id" id="nu_model" class="form-select select2-agac">
                    <option value="">Önce Marka Seçin</option>
                </select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Cins</label>
                <select name="cins_id" id="nu_cins" class="form-select select2-agac">
                    <option value="">Önce Model Seçin</option>
                </select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <select name="kategori_id" id="nu_kategori" class="form-select select2-agac">
                    <option value="">Önce Cins Seçin</option>
                </select>
            </div>
        </div>
        <div class="col-12"><hr></div>

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
                <label for="satis_euro" class="form-label">Satış Fiyatı EURO (Ana)</label>
                <input type="text" name="satis_euro" class="form-control" placeholder="0.00" id="satis_euro">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="satis_fiyat" class="form-label">Satış Fiyatı TL (Kurdan)</label>
                <input type="text" name="satis_fiyat" class="form-control" placeholder="0.00" id="satis_fiyat">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="satis_dolar" class="form-label">Satış Fiyatı DOLAR</label>
                <input type="text" name="satis_dolar" class="form-control" placeholder="0.00" id="satis_dolar">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="satis_sterlin" class="form-label">Satış Fiyatı STERLIN</label>
                <input type="text" name="satis_sterlin" class="form-control" placeholder="0.00" id="satis_sterlin">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="resim" class="form-label">Ürün Resmi</label>
                <input type="file" name="resim" class="form-control" id="resim" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">JPG, PNG veya WEBP yükleyin.</small>
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
const FX_RATES = <?= json_encode($kurMap, JSON_UNESCAPED_UNICODE) ?>;
const PRICE_FIELDS = {
    satis_fiyat: 'TRY',
    satis_euro: 'EUR',
    satis_dolar: 'USD',
    satis_sterlin: 'GBP'
};
let isSyncingPrices = false;

function formatNumber(num) {
    if (num === '' || num === null || typeof num === 'undefined') return '';
    const n = parseFloat(num);
    if (isNaN(n)) return '';
    return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function unformatNumber(str) {
    if (!str) return '';
    return String(str).replace(/\./g, '').replace(',', '.');
}

function parseInputValue(el) {
    const v = parseFloat(unformatNumber(el.value));
    return isNaN(v) ? null : v;
}

function syncPricesFrom(sourceName) {
    if (isSyncingPrices) return;
    const sourceEl = document.querySelector(`input[name="${sourceName}"]`);
    if (!sourceEl) return;

    const sourceCurrency = PRICE_FIELDS[sourceName];
    const sourceRate = parseFloat(FX_RATES[sourceCurrency] || 0);
    const sourceValue = parseInputValue(sourceEl);
    if (!sourceRate || sourceRate <= 0 || sourceValue === null) return;

    const tlBase = sourceValue * sourceRate;
    isSyncingPrices = true;

    Object.keys(PRICE_FIELDS).forEach((fieldName) => {
        if (fieldName === sourceName) return;
        const targetEl = document.querySelector(`input[name="${fieldName}"]`);
        if (!targetEl) return;
        const targetRate = parseFloat(FX_RATES[PRICE_FIELDS[fieldName]] || 0);
        if (!targetRate || targetRate <= 0) return;
        const targetVal = tlBase / targetRate;
        targetEl.value = formatNumber(targetVal);
    });

    isSyncingPrices = false;
}

document.addEventListener('focusin', function(e) {
    if (e.target.matches('input[name="satis_fiyat"], input[name="satis_euro"], input[name="satis_dolar"], input[name="satis_sterlin"]')) {
        e.target.value = unformatNumber(e.target.value);
    }
});

document.addEventListener('input', function(e) {
    if (e.target.matches('input[name="satis_fiyat"], input[name="satis_euro"], input[name="satis_dolar"], input[name="satis_sterlin"]')) {
        syncPricesFrom(e.target.name);
    }
});

document.addEventListener('focusout', function(e) {
    if (e.target.matches('input[name="satis_fiyat"], input[name="satis_euro"], input[name="satis_dolar"], input[name="satis_sterlin"]')) {
        const parsed = parseInputValue(e.target);
        e.target.value = parsed === null ? '' : formatNumber(parsed);
    }
});

document.querySelector('form').addEventListener('submit', function() {
    const inputs = document.querySelectorAll('input[name="satis_fiyat"], input[name="satis_euro"], input[name="satis_dolar"], input[name="satis_sterlin"]');
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-agac').select2({ width: '100%', language: { noResults: function() { return 'Sonuç bulunamadı'; } } });

    function loadSel(sel, url, ph) {
        $(sel).html('<option value="">Yükleniyor…</option>').prop('disabled', true);
        $.getJSON(url, function(data) {
            var h = '<option value="">' + ph + '</option>';
            $.each(data, function(i, r) { h += '<option value="' + r.id + '">' + r.ad + '</option>'; });
            $(sel).html(h).prop('disabled', false).trigger('change.select2');
        });
    }
    function resetSel(sel, ph) {
        $(sel).html('<option value="">' + ph + '</option>').prop('disabled', false).trigger('change.select2');
    }

    $('#nu_ana_kat').on('change', function() {
        var id = $(this).val();
        resetSel('#nu_model', 'Önce Marka Seçin');
        resetSel('#nu_cins', 'Önce Model Seçin');
        resetSel('#nu_kategori', 'Önce Cins Seçin');
        if (id) loadSel('#nu_marka', 'ajax/kategori_agaci.php?tip=markalar&ust_id=' + id, 'Marka Seçin');
        else resetSel('#nu_marka', 'Önce Ana Kategori Seçin');
    });
    $('#nu_marka').on('change', function() {
        var id = $(this).val();
        resetSel('#nu_cins', 'Önce Model Seçin');
        resetSel('#nu_kategori', 'Önce Cins Seçin');
        if (id) loadSel('#nu_model', 'ajax/kategori_agaci.php?tip=modeller&ust_id=' + id, 'Model Seçin');
        else resetSel('#nu_model', 'Önce Marka Seçin');
    });
    $('#nu_model').on('change', function() {
        var id = $(this).val();
        resetSel('#nu_kategori', 'Önce Cins Seçin');
        if (id) loadSel('#nu_cins', 'ajax/kategori_agaci.php?tip=cinsler&ust_id=' + id, 'Cins Seçin');
        else resetSel('#nu_cins', 'Önce Model Seçin');
    });
    $('#nu_cins').on('change', function() {
        var id = $(this).val();
        if (id) loadSel('#nu_kategori', 'ajax/kategori_agaci.php?tip=kategoriler&ust_id=' + id, 'Kategori Seçin');
        else resetSel('#nu_kategori', 'Önce Cins Seçin');
    });
});
</script>

    <!--datatable js-->

    <script src="assets/js/pages/datatables.init.js"></script>
    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>

</html>