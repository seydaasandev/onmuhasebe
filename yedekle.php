<?php
require "config.php";
require "auth.php";

$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (strtolower($user['role']) === 'user') {
    header("Location: 403.php");
    exit;
}

$yedekDir = __DIR__ . '/yedekler/';
$mesaj = '';
$mesajTur = '';

if (isset($_GET['durum']) && $_GET['durum'] === 'ok' && isset($_GET['dosya'])) {
    $mesaj    = 'Yedek başarıyla oluşturuldu: <strong>' . htmlspecialchars(basename($_GET['dosya'])) . '</strong>';
    $mesajTur = 'success';
}

// ----- YEDEK İNDİR -----
if (isset($_GET['indir'])) {
    $dosya = basename($_GET['indir']);
    $tam = $yedekDir . $dosya;
    if (file_exists($tam) && pathinfo($tam, PATHINFO_EXTENSION) === 'sql') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $dosya . '"');
        header('Content-Length: ' . filesize($tam));
        header('Cache-Control: no-cache');
        readfile($tam);
        exit;
    }
}

// ----- YEDEK SİL -----
if (isset($_GET['sil'])) {
    $dosya = basename($_GET['sil']);
    $tam = $yedekDir . $dosya;
    if (file_exists($tam) && pathinfo($tam, PATHINFO_EXTENSION) === 'sql') {
        unlink($tam);
        $mesaj = 'Yedek dosyası silindi: ' . htmlspecialchars($dosya);
        $mesajTur = 'warning';
    }
}

// ----- YEDEK AL -----
if (isset($_POST['yedek_al'])) {
    $tarih    = date('Y-m-d_H-i-s');
    $dosyaAdi = "yedek_" . $tarih . ".sql";
    $tamYol   = $yedekDir . $dosyaAdi;

    $tablolar = [];
    $q = $db->query("SHOW TABLES");
    while ($row = $q->fetch(PDO::FETCH_NUM)) {
        $tablolar[] = $row[0];
    }

    $sql  = "-- Veritabanı Yedeği: {$dosyaAdi}\n";
    $sql .= "-- Tarih: " . date('d.m.Y H:i:s') . "\n";
    $sql .= "-- Veritabanı: {$dbname}\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    foreach ($tablolar as $tablo) {
        $q2  = $db->query("SHOW CREATE TABLE `{$tablo}`");
        $row = $q2->fetch(PDO::FETCH_NUM);
        $sql .= "\n-- Tablo: `{$tablo}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$tablo}`;\n";
        $sql .= $row[1] . ";\n\n";

        $q3     = $db->query("SELECT * FROM `{$tablo}`");
        $satirlar = $q3->fetchAll(PDO::FETCH_ASSOC);
        if (count($satirlar) > 0) {
            $kolonStr = implode('`, `', array_keys($satirlar[0]));
            foreach ($satirlar as $satir) {
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote($v), array_values($satir));
                $sql .= "INSERT INTO `{$tablo}` (`{$kolonStr}`) VALUES (" . implode(', ', $vals) . ");\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($tamYol, $sql);

    header('Location: yedekle.php?durum=ok&dosya=' . urlencode($dosyaAdi));
    exit;
}

// ----- MEVCUT YEDEKLERİ LİSTELE -----
$yedekler = [];
if (is_dir($yedekDir)) {
    $dosyalar = glob($yedekDir . '*.sql');
    if ($dosyalar) {
        usort($dosyalar, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach ($dosyalar as $d) {
            $yedekler[] = [
                'ad'     => basename($d),
                'boyut'  => round(filesize($d) / 1024, 1),
                'tarih'  => date('d.m.Y H:i:s', filemtime($d)),
            ];
        }
    }
}
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg"
    data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg"
    data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>
    <meta charset="utf-8" />
    <title>Veritabanı Yedekle | Nextario Muhasebe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <script src="assets/js/layout.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/css/custom.min.css" rel="stylesheet" />
</head>

<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Sayfa Başlığı -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Veritabanı Yedekleme</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="panel.php">Anasayfa</a></li>
                                <li class="breadcrumb-item active">Veritabanı Yedekle</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($mesaj): ?>
            <div class="alert alert-<?= $mesajTur === 'success' ? 'success' : 'warning' ?> alert-dismissible alert-border-left alert-dismissible fade show" role="alert">
                <i class="ri-<?= $mesajTur === 'success' ? 'checkbox-circle' : 'delete-bin-6' ?>-line me-2 align-middle"></i>
                <?= $mesaj ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Yedek Al Kartı -->
                <div class="col-xl-4 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-database-2-line"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0">Yeni Yedek Oluştur</h5>
                                    <p class="text-muted mb-0 fs-13">Tüm tablolar SQL formatında kaydedilir</p>
                                </div>
                            </div>
                            <p class="text-muted fs-14 mb-4">
                                Butona bastığınızda veritabanının tamamı <code>/yedekler/</code> klasörüne
                                tarih ve saat bilgisiyle kaydedilir.
                            </p>
                            <form method="POST">
                                <button type="submit" name="yedek_al" class="btn btn-primary w-100">
                                    <i class="ri-save-3-line me-1"></i> Yedek Al ve Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartı -->
                <div class="col-xl-4 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-archive-stack-line"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0">Yedek Durumu</h5>
                                    <p class="text-muted mb-0 fs-13">Mevcut yedek özeti</p>
                                </div>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <h3 class="mb-0 fw-bold text-primary"><?= count($yedekler) ?></h3>
                                    <p class="text-muted fs-13 mb-0">Toplam Yedek</p>
                                </div>
                                <div class="col-6">
                                    <h3 class="mb-0 fw-bold text-success">
                                        <?= count($yedekler) > 0 ? $yedekler[0]['boyut'] . ' KB' : '—' ?>
                                    </h3>
                                    <p class="text-muted fs-13 mb-0">Son Yedek Boyutu</p>
                                </div>
                            </div>
                            <?php if (count($yedekler) > 0): ?>
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="ri-time-line me-1"></i>
                                    Son yedek: <strong><?= $yedekler[0]['tarih'] ?></strong>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bilgi Kartı -->
                <div class="col-xl-4 col-md-12">
                    <div class="card card-animate border border-dashed border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-information-line"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0">Bilgi</h5>
                                    <p class="text-muted mb-0 fs-13">Yedekleme hakkında</p>
                                </div>
                            </div>
                            <ul class="list-unstyled fs-14 text-muted mb-0">
                                <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Tüm tablo yapıları yedeklenir</li>
                                <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Tüm veriler INSERT ile kaydedilir</li>
                                <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Dosyalar sunucuda <code>/yedekler/</code> klasöründe tutulur</li>
                                <li><i class="ri-check-line text-success me-1"></i> İstediğiniz yedeği bilgisayara indirebilirsiniz</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yedek Listesi -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">
                                <i class="ri-list-check-2 me-2 text-primary"></i>Mevcut Yedekler
                            </h4>
                            <span class="badge bg-primary-subtle text-primary fs-13"><?= count($yedekler) ?> dosya</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($yedekler) === 0): ?>
                            <div class="text-center py-5">
                                <i class="ri-inbox-line" style="font-size:50px; color:#c2c2c2;"></i>
                                <p class="text-muted mt-3">Henüz yedek bulunmuyor. İlk yedeği almak için yukarıdaki butonu kullanın.</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Dosya Adı</th>
                                            <th>Tarih / Saat</th>
                                            <th>Boyut</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($yedekler as $i => $y): ?>
                                        <tr>
                                            <td class="text-muted"><?= $i + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar-xs">
                                                        <span class="avatar-title rounded bg-success-subtle text-success">
                                                            <i class="ri-file-code-line"></i>
                                                        </span>
                                                    </span>
                                                    <code class="fs-13"><?= htmlspecialchars($y['ad']) ?></code>
                                                    <?php if ($i === 0): ?>
                                                        <span class="badge bg-success-subtle text-success">Son</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><i class="ri-time-line me-1 text-muted"></i><?= $y['tarih'] ?></td>
                                            <td><span class="badge bg-light text-dark"><?= $y['boyut'] ?> KB</span></td>
                                            <td class="text-end">
                                                <a href="?indir=<?= urlencode($y['ad']) ?>" class="btn btn-sm btn-soft-primary me-1">
                                                    <i class="ri-download-2-line me-1"></i>İndir
                                                </a>
                                                <a href="?sil=<?= urlencode($y['ad']) ?>"
                                                   class="btn btn-sm btn-soft-danger"
                                                   onclick="return confirm('<?= htmlspecialchars($y['ad']) ?> silinsin mi?')">
                                                    <i class="ri-delete-bin-6-line me-1"></i>Sil
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
