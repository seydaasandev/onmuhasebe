<?php
require "config.php";
require "auth.php";

// Sadece admin yetkisi
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (strtolower($user['role']) === 'user') {
    header("Location: 403.php");
    exit;
}

// Yedek al butonu tetiklendi mi?
if (isset($_POST['yedek_al'])) {

    $tarih = date('Y-m-d_H-i-s');
    $dosyaAdi = "yedek_" . $tarih . ".sql";

    // Tüm tabloları çek
    $tablolar = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tablolar[] = $row[0];
    }

    $sql = "-- Veritabanı Yedeği: {$dosyaAdi}\n";
    $sql .= "-- Tarih: " . date('d.m.Y H:i:s') . "\n";
    $sql .= "-- Veritabanı: " . $dbname . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "SET NAMES utf8mb4;\n\n";

    foreach ($tablolar as $tablo) {
        // CREATE TABLE
        $stmt = $db->query("SHOW CREATE TABLE `$tablo`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql .= "\n-- ----------------------------\n";
        $sql .= "-- Tablo yapısı: `$tablo`\n";
        $sql .= "-- ----------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$tablo`;\n";
        $sql .= $row[1] . ";\n\n";

        // INSERT satirlari
        $stmt2 = $db->query("SELECT * FROM `$tablo`");
        $satirlar = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($satirlar) > 0) {
            $sql .= "-- Veriler: `$tablo`\n";
            $kolonlar = array_keys($satirlar[0]);
            $kolonStr = implode("`, `", $kolonlar);

            foreach ($satirlar as $satir) {
                $degerler = array_map(function ($val) use ($db) {
                    if ($val === null) return "NULL";
                    return $db->quote($val);
                }, array_values($satir));
                $sql .= "INSERT INTO `$tablo` (`$kolonStr`) VALUES (" . implode(", ", $degerler) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Dosyayı indir
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
    header('Content-Length: ' . strlen($sql));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo $sql;
    exit;
}
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg"
    data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg"
    data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">
<?php include 'head.php'; ?>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Veritabanı Yedekle</h4>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="ri-database-2-line" style="font-size: 60px; color: #405189;"></i>
                            <h5 class="mt-3 mb-2">Veritabanı Yedekleme</h5>
                            <p class="text-muted mb-4">
                                Butona bastığınızda tüm veritabanı <strong>.sql</strong> formatında,
                                tarih ve saat bilgisiyle bilgisayarınıza indirilecek.
                            </p>
                            <form method="POST">
                                <button type="submit" name="yedek_al" class="btn btn-primary btn-lg">
                                    <i class="ri-download-cloud-2-line me-2"></i>
                                    Yedek Al ve İndir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

    <!-- App js -->
    <script src="assets/js/app.js"></script>
</body>
</html>
