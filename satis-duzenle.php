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

if (!isset($_GET['id'])) {
    header("Location: tum-satislar.php");
    exit;
}

$id = (int) $_GET['id'];

// Satış bilgilerini çek
$satis = $db->prepare("
    SELECT s.*, m.musteri_adi, p.urun_adi, u.namesurname
    FROM satislar s
    LEFT JOIN musteriler m ON m.id = s.musteri_id
    LEFT JOIN urunler p ON p.id = s.urun_id
    LEFT JOIN users u ON u.id = s.satisi_yapan_id
    WHERE s.id = ? AND s.durum = 0
");
$satis->execute([$id]);
$satis = $satis->fetch(PDO::FETCH_ASSOC);

if (!$satis) {
    header("Location: tum-satislar.php");
    exit;
}

// Mevcut satışın birim fiyatını ve KDV oranını hesapla
$birim_fiyat = $satis['adet'] > 0 ? $satis['tutar'] / $satis['adet'] : 0;
$kdv_orani = $satis['tutar'] > 0 ? ($satis['kdv_toplami'] / $satis['tutar']) * 100 : 0;

// Toplamları hesapla
$ara_toplam = $birim_fiyat * $satis['adet'];
$kdv_tutari = ($ara_toplam * $kdv_orani) / 100;
$genel_toplam = $ara_toplam + $kdv_tutari;

// Dropdown verileri
$musteriler = $db->query("SELECT id, musteri_adi FROM musteriler WHERE durum = 0 ORDER BY musteri_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
$urunler = $db->query("SELECT id, urun_adi, satis_fiyat, kdv FROM urunler WHERE durum = 0 OR id = {$satis['urun_id']} ORDER BY urun_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query("SELECT id, namesurname FROM users WHERE durum = 0 ORDER BY namesurname ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>
    <meta charset="utf-8" />
    <title>Satış Düzenle | Nextario Muhasebe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Satış düzenleme sayfası" name="description" />
    <meta content="Nextario" name="author" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/layout.js"></script>
</head>

<body>
    <div id="layout-wrapper">
        <?php require "head.php"; ?>
        <?php require "sidebar.php"; ?>
        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                                <h4 class="mb-sm-0">Satış Düzenle | İşlem No: <?= htmlspecialchars($satis['islem_no']) ?></h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Nextario</a></li>
                                        <li class="breadcrumb-item active">Satış Düzenle</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Satış Düzenle</h5>
                                </div>
                                <div class="card-body">
                                    <form action="islem.php?islem=satis_duzenle" method="POST">
                                        <input type="hidden" name="id" value="<?= $satis['id'] ?>">

                                        <div class="row">
                                            <!-- İşlem No (Salt Okunur) -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">İşlem No</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($satis['islem_no']) ?>" readonly>
                                                </div>
                                            </div>

                                            <!-- Müşteri -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Müşteri</label>
                                                    <select name="musteri_id" class="form-select" required>
                                                        <option value="">Müşteri seçiniz</option>
                                                        <?php foreach ($musteriler as $m): ?>
                                                            <option value="<?= $m['id'] ?>" <?= $satis['musteri_id'] == $m['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($m['musteri_adi']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Ürün -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Ürün</label>
                                                    <select name="urun_id" id="urun_id" class="form-select" required>
                                                        <option value="">Ürün seçiniz</option>
                                                        <?php foreach ($urunler as $u): ?>
                                                            <option value="<?= $u['id'] ?>" data-fiyat="<?= $u['satis_fiyat'] ?>" data-kdv="<?= $u['kdv'] ?>" <?= $satis['urun_id'] == $u['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($u['urun_adi']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Adet -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Adet</label>
                                                    <input type="number" name="adet" id="adet" class="form-control" value="<?= $satis['adet'] ?>" min="1" required>
                                                </div>
                                            </div>

                                            <!-- Birim Fiyat -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Birim Fiyat</label>
                                                    <input type="number" id="birim_fiyat" class="form-control" step="0.01" value="<?= number_format($birim_fiyat, 2, '.', '') ?>" readonly>
                                                </div>
                                            </div>

                                            <!-- KDV Oranı -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">KDV Oranı (%)</label>
                                                    <input type="number" id="kdv_orani" class="form-control" step="0.01" value="<?= number_format($kdv_orani, 2, '.', '') ?>" readonly>
                                                </div>
                                            </div>

                                            <!-- Ara Toplam -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Ara Toplam</label>
                                                    <input type="text" id="ara_toplam" class="form-control" value="<?= number_format($ara_toplam, 2, ',', '.') ?> ₺" readonly>
                                                </div>
                                            </div>

                                            <!-- KDV Tutarı -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">KDV Tutarı</label>
                                                    <input type="text" id="kdv_tutari" class="form-control" value="<?= number_format($kdv_tutari, 2, ',', '.') ?> ₺" readonly>
                                                </div>
                                            </div>

                                            <!-- Genel Toplam -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Genel Toplam</label>
                                                    <input type="text" id="genel_toplam" class="form-control" value="<?= number_format($genel_toplam, 2, ',', '.') ?> ₺" readonly>
                                                </div>
                                            </div>

                                            <!-- Satışı Yapan -->
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Satışı Yapan</label>
                                                    <select name="satisi_yapan" class="form-select" required>
                                                        <option value="">Seçiniz</option>
                                                        <?php foreach ($users as $u): ?>
                                                            <option value="<?= $u['id'] ?>" <?= $satis['satisi_yapan_id'] == $u['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($u['namesurname']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-success rounded-pill">
                                                    <i class="ri-check-double-line me-1"></i> Kaydet
                                                </button>
                                                <a href="tum-satislar.php" class="btn btn-primary rounded-pill">
                                                    <i class="ri-arrow-go-back-line me-1"></i> Satışlara Dön
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    </div>

    <script>
    $(document).ready(function() {
        $('.form-select').select2();

        // Sayfa yüklendiğinde mevcut ürünü yükle
        loadUrunData();

        // Ürün değiştiğinde
        $('#urun_id').change(function() {
            loadUrunData();
        });

        // Adet değiştiğinde
        $('#adet').change(function() {
            hesaplaToplam();
        });

        // Form submit öncesi onay
        $('form').submit(function(e) {
            e.preventDefault();

            var eskiMusteri = "<?= addslashes($satis['musteri_adi']) ?>";
            var yeniMusteri = $('#musteri_id option:selected').text();
            var eskiUrun = "<?= addslashes($satis['urun_adi']) ?>";
            var yeniUrun = $('#urun_id option:selected').text();
            var eskiAdet = <?= $satis['adet'] ?>;
            var yeniAdet = $('#adet').val();
            var eskiSatici = "<?= addslashes($satis['namesurname']) ?>";
            var yeniSatici = $('[name=satisi_yapan] option:selected').text();

            var degisiklikler = [];

            if (eskiMusteri !== yeniMusteri) {
                degisiklikler.push("Müşteri: " + eskiMusteri + " → " + yeniMusteri);
            }
            if (eskiUrun !== yeniUrun) {
                degisiklikler.push("Ürün: " + eskiUrun + " → " + yeniUrun);
            }
            if (eskiAdet != yeniAdet) {
                degisiklikler.push("Adet: " + eskiAdet + " → " + yeniAdet);
            }
            if (eskiSatici !== yeniSatici) {
                degisiklikler.push("Satışı Yapan: " + eskiSatici + " → " + yeniSatici);
            }

            if (degisiklikler.length === 0) {
                alert('Hiçbir değişiklik yapmadınız!');
                return false;
            }

            var mesaj = "Aşağıdaki değişiklikleri yapmak istediğinizden emin misiniz?\n\n" + degisiklikler.join("\n");

            if (confirm(mesaj)) {
                this.submit();
            }
        });

        function loadUrunData() {
            var selectedOption = $('#urun_id option:selected');
            var fiyat = selectedOption.data('fiyat') || 0;
            var kdv = selectedOption.data('kdv') || 0;
            $('#birim_fiyat').val(fiyat);
            $('#kdv_orani').val(kdv);
            hesaplaToplam();
        }

        function hesaplaToplam() {
            var fiyat = parseFloat($('#birim_fiyat').val()) || 0;
            var adet = parseInt($('#adet').val()) || 0;
            var kdvOrani = parseFloat($('#kdv_orani').val()) || 0;

            var araToplam = fiyat * adet;
            var kdvTutari = (araToplam * kdvOrani) / 100;
            var genelToplam = araToplam + kdvTutari;

            $('#ara_toplam').val(araToplam.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺');
            $('#kdv_tutari').val(kdvTutari.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺');
            $('#genel_toplam').val(genelToplam.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺');
        }
    });
    </script>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>

</body>
</html>