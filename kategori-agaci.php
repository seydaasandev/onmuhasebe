<?php
require "config.php";
require "auth.php";
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$role = $stmt->fetchColumn();

if (strtolower($role) === 'user') {
    header("Location: 403.php");
    exit;
}

// ── Verileri çek ──────────────────────────────
$anaKategoriler = $db->query("SELECT * FROM ana_kategoriler ORDER BY ad")->fetchAll(PDO::FETCH_ASSOC);
$markalar       = $db->query("SELECT m.*, a.ad AS ana_adi FROM markalar m JOIN ana_kategoriler a ON a.id = m.ana_kategori_id ORDER BY a.ad, m.ad")->fetchAll(PDO::FETCH_ASSOC);
$modeller       = $db->query("SELECT mo.*, m.ad AS marka_adi FROM modeller mo JOIN markalar m ON m.id = mo.marka_id ORDER BY m.ad, mo.ad")->fetchAll(PDO::FETCH_ASSOC);
$cinsler        = $db->query("SELECT c.*, mo.ad AS model_adi FROM cinsler c JOIN modeller mo ON mo.id = c.model_id ORDER BY mo.ad, c.ad")->fetchAll(PDO::FETCH_ASSOC);
$kategorilerList= $db->query("SELECT k.*, c.ad AS cins_adi FROM kategoriler k JOIN cinsler c ON c.id = k.cins_id ORDER BY c.ad, k.ad")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg"
      data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg"
      data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">
<head>
    <meta charset="utf-8" />
    <title>Kategori Ağacı | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <script src="assets/js/layout.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.min.css" rel="stylesheet" />
    <style>
        .tree-node { border-left: 3px solid #0ab39c; padding-left: 12px; margin-bottom: 4px; }
        .badge-ana { background: #405189; }
        .badge-marka { background: #0ab39c; }
        .badge-model { background: #f06548; }
        .badge-cins  { background: #f7b84b; color:#000; }
        .badge-kat   { background: #299cdb; }
        .list-group-item { padding: 8px 12px; }
        .del-btn { opacity:0.6; } .del-btn:hover { opacity:1; }
    </style>
</head>
<body>
<div id="layout-wrapper">
<?php require "head.php"; ?>
<?php require "sidebar.php"; ?>
<div class="vertical-overlay"></div>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Başlık -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                        <h4 class="mb-sm-0">Kategori Ağacı</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Nextario</a></li>
                                <li class="breadcrumb-item active">Kategori Ağacı</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İçerik -->
            <div class="row">

                <!-- ═══════════════════════════════════════
                     ANA KATEGORİLER
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <span class="badge badge-ana me-2">1</span>
                            <h5 class="card-title mb-0">Ana Kategoriler</h5>
                        </div>
                        <div class="card-body">
                            <form action="islem.php?islem=ana_kategori_ekle" method="POST" class="d-flex gap-2 mb-3">
                                <input type="text" name="ad" class="form-control" placeholder="Ana kategori adı…" required>
                                <button class="btn btn-primary btn-sm text-nowrap">Ekle</button>
                            </form>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($anaKategoriler as $ak): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><span class="badge badge-ana me-1">A</span> <?= htmlspecialchars($ak['ad']) ?></span>
                                    <form action="islem.php?islem=ana_kategori_sil" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?\nBağlı tüm marka/model/cins/kategoriler de silinecek!')">
                                        <input type="hidden" name="id" value="<?= $ak['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger del-btn"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($anaKategoriler)): ?>
                                <li class="list-group-item text-muted">Henüz kayıt yok</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     MARKALAR
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <span class="badge badge-marka me-2">2</span>
                            <h5 class="card-title mb-0">Markalar</h5>
                        </div>
                        <div class="card-body">
                            <form action="islem.php?islem=marka_ekle" method="POST" class="mb-3">
                                <div class="d-flex gap-2 mb-2">
                                    <select name="ana_kategori_id" class="form-select" required>
                                        <option value="">Ana Kategori Seç</option>
                                        <?php foreach ($anaKategoriler as $ak): ?>
                                        <option value="<?= $ak['id'] ?>"><?= htmlspecialchars($ak['ad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" name="ad" class="form-control" placeholder="Marka adı…" required>
                                    <button class="btn btn-success btn-sm text-nowrap">Ekle</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush" style="max-height:320px;overflow-y:auto;">
                                <?php foreach ($markalar as $m): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <small class="text-muted"><?= htmlspecialchars($m['ana_adi']) ?> /</small>
                                        <span class="badge badge-marka ms-1"><?= htmlspecialchars($m['ad']) ?></span>
                                    </span>
                                    <form action="islem.php?islem=marka_sil" method="POST" onsubmit="return confirm('Silmek istiyor musunuz?')">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger del-btn"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($markalar)): ?>
                                <li class="list-group-item text-muted">Henüz kayıt yok</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     MODELLER
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <span class="badge badge-model me-2">3</span>
                            <h5 class="card-title mb-0">Modeller</h5>
                        </div>
                        <div class="card-body">
                            <form action="islem.php?islem=model_ekle" method="POST" class="mb-3">
                                <div class="d-flex gap-2 mb-2">
                                    <select name="ana_kategori_id" id="model_ana_kat" class="form-select" required>
                                        <option value="">Ana Kategori Seç</option>
                                        <?php foreach ($anaKategoriler as $ak): ?>
                                        <option value="<?= $ak['id'] ?>"><?= htmlspecialchars($ak['ad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="marka_id" id="model_marka" class="form-select" required>
                                        <option value="">Önce Ana Kategori Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" name="ad" class="form-control" placeholder="Model adı…" required>
                                    <button class="btn btn-warning btn-sm text-nowrap text-dark">Ekle</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush" style="max-height:280px;overflow-y:auto;">
                                <?php foreach ($modeller as $mo): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <small class="text-muted"><?= htmlspecialchars($mo['marka_adi']) ?> /</small>
                                        <span class="badge badge-model ms-1"><?= htmlspecialchars($mo['ad']) ?></span>
                                    </span>
                                    <form action="islem.php?islem=model_sil" method="POST" onsubmit="return confirm('Silmek istiyor musunuz?')">
                                        <input type="hidden" name="id" value="<?= $mo['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger del-btn"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($modeller)): ?>
                                <li class="list-group-item text-muted">Henüz kayıt yok</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     CİNSLER
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <span class="badge badge-cins me-2">4</span>
                            <h5 class="card-title mb-0">Cinsler</h5>
                        </div>
                        <div class="card-body">
                            <form action="islem.php?islem=cins_ekle" method="POST" class="mb-3">
                                <div class="d-flex gap-2 mb-2">
                                    <select name="ana_kategori_id" id="cins_ana_kat" class="form-select">
                                        <option value="">Ana Kategori Seç</option>
                                        <?php foreach ($anaKategoriler as $ak): ?>
                                        <option value="<?= $ak['id'] ?>"><?= htmlspecialchars($ak['ad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="marka_id" id="cins_marka" class="form-select">
                                        <option value="">Önce Ana Kategori Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="model_id" id="cins_model" class="form-select" required>
                                        <option value="">Önce Marka Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" name="ad" class="form-control" placeholder="Cins adı…" required>
                                    <button class="btn btn-warning btn-sm text-nowrap">Ekle</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush" style="max-height:260px;overflow-y:auto;">
                                <?php foreach ($cinsler as $c): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <small class="text-muted"><?= htmlspecialchars($c['model_adi']) ?> /</small>
                                        <span class="badge badge-cins ms-1"><?= htmlspecialchars($c['ad']) ?></span>
                                    </span>
                                    <form action="islem.php?islem=cins_sil" method="POST" onsubmit="return confirm('Silmek istiyor musunuz?')">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger del-btn"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($cinsler)): ?>
                                <li class="list-group-item text-muted">Henüz kayıt yok</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     KATEGORİLER
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <span class="badge badge-kat me-2">5</span>
                            <h5 class="card-title mb-0">Kategoriler</h5>
                        </div>
                        <div class="card-body">
                            <form action="islem.php?islem=kategori_ekle" method="POST" class="mb-3">
                                <div class="d-flex gap-2 mb-2">
                                    <select name="ana_kategori_id" id="kat_ana_kat" class="form-select">
                                        <option value="">Ana Kategori Seç</option>
                                        <?php foreach ($anaKategoriler as $ak): ?>
                                        <option value="<?= $ak['id'] ?>"><?= htmlspecialchars($ak['ad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="marka_id" id="kat_marka" class="form-select">
                                        <option value="">Önce Ana Kategori Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="model_id" id="kat_model" class="form-select">
                                        <option value="">Önce Marka Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <select name="cins_id" id="kat_cins" class="form-select" required>
                                        <option value="">Önce Model Seçin</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" name="ad" class="form-control" placeholder="Kategori adı…" required>
                                    <button class="btn btn-info btn-sm text-nowrap">Ekle</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush" style="max-height:230px;overflow-y:auto;">
                                <?php foreach ($kategorilerList as $k): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <small class="text-muted"><?= htmlspecialchars($k['cins_adi']) ?> /</small>
                                        <span class="badge badge-kat ms-1"><?= htmlspecialchars($k['ad']) ?></span>
                                    </span>
                                    <form action="islem.php?islem=kategori_sil" method="POST" onsubmit="return confirm('Silmek istiyor musunuz?')">
                                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger del-btn"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if (empty($kategorilerList)): ?>
                                <li class="list-group-item text-muted">Henüz kayıt yok</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════
                     AĞAÇ GÖRÜNÜMÜ
                     ═══════════════════════════════════════ -->
                <div class="col-xl-4 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="ri-node-tree me-1"></i> Tam Ağaç</h5>
                        </div>
                        <div class="card-body" style="max-height:560px;overflow-y:auto;">
                            <?php foreach ($anaKategoriler as $ak): ?>
                            <div class="mb-2">
                                <strong><span class="badge badge-ana">A</span> <?= htmlspecialchars($ak['ad']) ?></strong>
                                <?php
                                $akMarkalar = array_filter($markalar, fn($m) => $m['ana_kategori_id'] == $ak['id']);
                                foreach ($akMarkalar as $m):
                                    $mModeller = array_filter($modeller, fn($mo) => $mo['marka_id'] == $m['id']);
                                ?>
                                <div class="ms-3 mt-1">
                                    <span class="badge badge-marka">M</span> <?= htmlspecialchars($m['ad']) ?>
                                    <?php foreach ($mModeller as $mo):
                                        $moCinsler = array_filter($cinsler, fn($c) => $c['model_id'] == $mo['id']);
                                    ?>
                                    <div class="ms-3 mt-1">
                                        <span class="badge badge-model">Mo</span> <?= htmlspecialchars($mo['ad']) ?>
                                        <?php foreach ($moCinsler as $c):
                                            $cKats = array_filter($kategorilerList, fn($k) => $k['cins_id'] == $c['id']);
                                        ?>
                                        <div class="ms-3 mt-1">
                                            <span class="badge badge-cins">C</span> <?= htmlspecialchars($c['ad']) ?>
                                            <?php foreach ($cKats as $k): ?>
                                            <div class="ms-3 mt-1">
                                                <span class="badge badge-kat">K</span> <?= htmlspecialchars($k['ad']) ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($anaKategoriler)): ?>
                            <p class="text-muted">Ağaç boş – önce ana kategoriler ekleyin.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!--end row-->

        </div>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © Nextario.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">Design &amp; Develop by Seyda AŞAN</div>
                </div>
            </div>
        </div>
    </footer>
</div>

</div><!-- END layout-wrapper -->

<button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top"><i class="ri-arrow-up-line"></i></button>

<div id="preloader">
    <div id="status">
        <div class="spinner-border text-primary avatar-sm" role="status"><span class="visually-hidden">Yükleniyor…</span></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if(isset($_SESSION['mesaj'])): ?>
<script>
Swal.fire({
    icon: "<?= in_array($_SESSION['mesaj'], ['ok']) ? 'success' : 'error' ?>",
    title: "<?= $_SESSION['mesaj'] === 'ok' ? 'Başarılı' : 'Hata oluştu' ?>",
    timer: 1500, showConfirmButton: false
});
</script>
<?php unset($_SESSION['mesaj']); endif; ?>

<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/libs/feather-icons/feather.min.js"></script>
<script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="assets/js/plugins.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="assets/js/app.js"></script>
<script>
// ── Yardımcı: bağımlı select doldur ─────────────────────
function loadSelect(selectEl, url, placeholder) {
    $(selectEl).html('<option value="">Yükleniyor…</option>').prop('disabled', true);
    $.getJSON(url, function(data) {
        var html = '<option value="">' + placeholder + '</option>';
        $.each(data, function(i, row) {
            html += '<option value="' + row.id + '">' + row.ad + '</option>';
        });
        $(selectEl).html(html).prop('disabled', false);
    });
}
function resetSelect(selectEl, placeholder) {
    $(selectEl).html('<option value="">' + placeholder + '</option>').prop('disabled', false);
}

// ── Modeller formu ────────────────────────────────────────
$('#model_ana_kat').on('change', function() {
    var id = $(this).val();
    if (id) {
        loadSelect('#model_marka', 'ajax/kategori_agaci.php?tip=markalar&ust_id=' + id, 'Marka Seçin');
    } else {
        resetSelect('#model_marka', 'Önce Ana Kategori Seçin');
    }
});

// ── Cinsler formu ────────────────────────────────────────
$('#cins_ana_kat').on('change', function() {
    var id = $(this).val();
    resetSelect('#cins_model', 'Önce Marka Seçin');
    if (id) {
        loadSelect('#cins_marka', 'ajax/kategori_agaci.php?tip=markalar&ust_id=' + id, 'Marka Seçin');
    } else {
        resetSelect('#cins_marka', 'Önce Ana Kategori Seçin');
    }
});
$('#cins_marka').on('change', function() {
    var id = $(this).val();
    if (id) {
        loadSelect('#cins_model', 'ajax/kategori_agaci.php?tip=modeller&ust_id=' + id, 'Model Seçin');
    } else {
        resetSelect('#cins_model', 'Önce Marka Seçin');
    }
});

// ── Kategoriler formu ────────────────────────────────────
$('#kat_ana_kat').on('change', function() {
    var id = $(this).val();
    resetSelect('#kat_model', 'Önce Marka Seçin');
    resetSelect('#kat_cins', 'Önce Model Seçin');
    if (id) {
        loadSelect('#kat_marka', 'ajax/kategori_agaci.php?tip=markalar&ust_id=' + id, 'Marka Seçin');
    } else {
        resetSelect('#kat_marka', 'Önce Ana Kategori Seçin');
    }
});
$('#kat_marka').on('change', function() {
    var id = $(this).val();
    resetSelect('#kat_cins', 'Önce Model Seçin');
    if (id) {
        loadSelect('#kat_model', 'ajax/kategori_agaci.php?tip=modeller&ust_id=' + id, 'Model Seçin');
    } else {
        resetSelect('#kat_model', 'Önce Marka Seçin');
    }
});
$('#kat_model').on('change', function() {
    var id = $(this).val();
    if (id) {
        loadSelect('#kat_cins', 'ajax/kategori_agaci.php?tip=cinsler&ust_id=' + id, 'Cins Seçin');
    } else {
        resetSelect('#kat_cins', 'Önce Model Seçin');
    }
});
</script>
</body>
</html>
