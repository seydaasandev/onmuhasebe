<?php
session_start();
require "config.php";
require "log_helpers.php";
require "kur_helpers.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (!isset($_SESSION['user_id'])) {
    die('Yetkisiz erişim');
}

ensure_islem_loglari_table($db);

function urun_resmi_yukle($inputName, $mevcutResim = '')
{
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return [
            'ok' => true,
            'path' => $mevcutResim,
            'error' => ''
        ];
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'ok' => false,
            'path' => $mevcutResim,
            'error' => 'Yukleme hatasi olustu.'
        ];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return [
            'ok' => false,
            'path' => $mevcutResim,
            'error' => 'Dosya boyutu 5MB ustunde olamaz.'
        ];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $izinli = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($izinli[$mime])) {
        return [
            'ok' => false,
            'path' => $mevcutResim,
            'error' => 'Sadece JPG, PNG, WEBP dosyalari yuklenebilir.'
        ];
    }

    $uploadDir = __DIR__ . '/resimler/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0775);
    }

    if (!is_writable($uploadDir)) {
        return [
            'ok' => false,
            'path' => $mevcutResim,
            'error' => 'Resim klasoru yazma izni yok: resimler/'
        ];
    }

    $dosyaAdi = 'urun_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $izinli[$mime];
    $hedef = $uploadDir . $dosyaAdi;

    if (!move_uploaded_file($file['tmp_name'], $hedef)) {
        return [
            'ok' => false,
            'path' => $mevcutResim,
            'error' => 'Dosya kaydedilemedi. Klasor izni kontrol edin.'
        ];
    }

    if (!empty($mevcutResim) && strpos($mevcutResim, 'resimler/') === 0) {
        $eskiYol = __DIR__ . '/' . $mevcutResim;
        if (is_file($eskiYol)) {
            @unlink($eskiYol);
        }
    }

    return [
        'ok' => true,
        'path' => 'resimler/' . $dosyaAdi,
        'error' => ''
    ];
}

function get_rates_map(PDO $db): array
{
    $rates = ['TRY' => 1.0, 'EUR' => 0.0, 'USD' => 0.0, 'GBP' => 0.0];
    $rows = $db->query("SELECT para_birimi, kur FROM doviz_kurlari WHERE para_birimi IN ('TRY','EUR','USD','GBP')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $pb = strtoupper((string)$row['para_birimi']);
        $kur = (float)$row['kur'];
        if (isset($rates[$pb]) && $kur > 0) {
            $rates[$pb] = $kur;
        }
    }
    $rates['TRY'] = 1.0;
    return $rates;
}

/* =========================================
   ÜRÜN SİLME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "urun_sil") {

    $id = intval($_GET['id']);

    $urunStmt = $db->prepare("SELECT urun_adi, stok, barkod FROM urunler WHERE id = ? AND durum = 0 LIMIT 1");
    $urunStmt->execute([$id]);
    $urun = $urunStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $guncelle = $db->prepare("
        UPDATE urunler 
        SET durum = 1 
        WHERE id = ? AND durum = 0
    ");
        $sonuc = $guncelle->execute([$id]);

        if ($sonuc && $guncelle->rowCount() > 0) {
            log_islem(
                $db,
                'urun',
                'sil',
                $id,
                log_format_pairs([
                    'Urun' => $urun['urun_adi'] ?? null,
                    'Stok' => $urun['stok'] ?? null,
                    'Barkod' => $urun['barkod'] ?? null,
                ]),
                $urun
            );
            $_SESSION['mesaj'] = "sil_ok";
        } else {
            $_SESSION['mesaj'] = "sil_no";
        }

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cacheurunler/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    header("Location: urunler.php");
    exit;
}

/* =========================================
   ÜRÜN DÜZENLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "urun_duzenle") {

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        header("Location: urunler.php");
        exit;
    }

    $urunSorgu = $db->prepare("SELECT urun_adi, stok, barkod, satis_fiyat, satis_euro, resim FROM urunler WHERE id = ?");
    $urunSorgu->execute([$id]);
    $mevcutUrun = $urunSorgu->fetch(PDO::FETCH_ASSOC);
    if (!$mevcutUrun) {
        $_SESSION['mesaj'] = "duzenle_no";
        header("Location: urunler.php");
        exit;
    }

    $upload = urun_resmi_yukle('resim', $mevcutUrun['resim'] ?? '');
    if (!$upload['ok']) {
        $_SESSION['mesaj'] = "duzenle_no";
        $_SESSION['mesaj_detay'] = $upload['error'];
        header("Location: urun-duzenle.php?id=$id");
        exit;
    }

    $urun_adi = $_POST['urun_adi'];
    $stok = $_POST['stok'];
    $marka = trim($_POST['marka'] ?? '');
    $cins = trim($_POST['cins'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $raf_bolumu = $_POST['raf_bolumu'] ?? '';
    $kdv = $_POST['kdv'];
    $satis_fiyat = (float)($_POST['satis_fiyat'] ?? 0);
    $satis_euro = (float)($_POST['satis_euro'] ?? 0);
    $satis_dolar = (float)($_POST['satis_dolar'] ?? 0);
    $satis_sterlin = (float)($_POST['satis_sterlin'] ?? 0);
    $barkod = $_POST['barkod'];
    $ana_kategori_id = (int)($_POST['ana_kategori_id'] ?? 0) ?: null;
    $marka_id        = (int)($_POST['marka_id']        ?? 0) ?: null;
    $model_id        = (int)($_POST['model_id']        ?? 0) ?: null;
    $cins_id         = (int)($_POST['cins_id']         ?? 0) ?: null;
    $kategori_id     = (int)($_POST['kategori_id']     ?? 0) ?: null;

    if ($marka === '' && $marka_id) {
        $s = $db->prepare("SELECT ad FROM markalar WHERE id = ? LIMIT 1");
        $s->execute([$marka_id]);
        $marka = (string)($s->fetchColumn() ?: '');
    }
    if ($cins === '' && $cins_id) {
        $s = $db->prepare("SELECT ad FROM cinsler WHERE id = ? LIMIT 1");
        $s->execute([$cins_id]);
        $cins = (string)($s->fetchColumn() ?: '');
    }
    if ($kategori === '' && $kategori_id) {
        $s = $db->prepare("SELECT ad FROM kategoriler WHERE id = ? LIMIT 1");
        $s->execute([$kategori_id]);
        $kategori = (string)($s->fetchColumn() ?: '');
    }

    $rates = get_rates_map($db);
    if ($satis_euro > 0 && $rates['EUR'] > 0) {
        $satis_fiyat = round($satis_euro * $rates['EUR'], 2);
    } elseif ($satis_fiyat > 0 && $rates['EUR'] > 0 && $satis_euro <= 0) {
        $satis_euro = round($satis_fiyat / $rates['EUR'], 2);
    }

    $update = $db->prepare("UPDATE urunler SET 
        urun_adi=?, 
        stok=?, 
        marka=?, 
        cins=?,
        kategori=?,
        raf_bolumu=?,
        kdv=?, 
        satis_fiyat=?, 
        satis_euro=?,
        satis_dolar=?,
        satis_sterlin=?,
        barkod=?, 
        web=?,
        resim=?,
        ana_kategori_id=?,
        marka_id=?,
        model_id=?,
        cins_id=?,
        kategori_id=?
    WHERE id=?")->execute([
        $urun_adi,
        $stok,
        $marka,
        $cins,
        $kategori,
        $raf_bolumu,
        $kdv,
        $satis_fiyat,
        $satis_euro,
        $satis_dolar,
        $satis_sterlin,
        $barkod,
        (int)($_POST['web'] ?? 0),
        $upload['path'],
        $ana_kategori_id,
        $marka_id,
        $model_id,
        $cins_id,
        $kategori_id,
        $id
    ]);

    if ($update) {
        log_islem(
            $db,
            'urun',
            'duzenle',
            $id,
            log_format_pairs([
                'Urun' => $urun_adi,
                'Eski stok' => $mevcutUrun['stok'] ?? null,
                'Yeni stok' => $stok,
                'Barkod' => $barkod,
            ]),
            [
                'once' => [
                    'urun_adi' => $mevcutUrun['urun_adi'] ?? null,
                    'stok' => $mevcutUrun['stok'] ?? null,
                    'barkod' => $mevcutUrun['barkod'] ?? null,
                    'satis_fiyat' => $mevcutUrun['satis_fiyat'] ?? null,
                    'satis_euro' => $mevcutUrun['satis_euro'] ?? null,
                ],
                'sonra' => [
                    'urun_adi' => $urun_adi,
                    'stok' => $stok,
                    'barkod' => $barkod,
                    'satis_fiyat' => $satis_fiyat,
                    'satis_euro' => $satis_euro,
                ]
            ]
        );
        $_SESSION['mesaj'] = "duzenle_ok";
    } else {
        $_SESSION['mesaj'] = "duzenle_no";
    }

    // Cache temizle
    $cacheDir = __DIR__ . '/ajax/cacheurunler/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    // Parametre temiz -> tekrar uyarı vermez
    header("Location: urun-duzenle.php?id=$id");
    exit;
}

/* =========================================
   ÜRÜN EKLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "urun_ekle") {

    $urun_adi   = $_POST['urun_adi'] ?? '';
    $stok       = $_POST['stok'] ?? 0;
    $marka      = trim($_POST['marka'] ?? '');
    $cins       = trim($_POST['cins'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? '');
    $raf_bolumu = $_POST['raf_bolumu'] ?? '';
    $kdv        = $_POST['kdv'] ?? 0;
    $barkod     = $_POST['barkod'] ?? '';
    $satis_fiyat = (float)($_POST['satis_fiyat'] ?? 0);
    $satis_euro = (float)($_POST['satis_euro'] ?? 0);
    $satis_dolar = (float)($_POST['satis_dolar'] ?? 0);
    $satis_sterlin = (float)($_POST['satis_sterlin'] ?? 0);
    $ana_kategori_id = (int)($_POST['ana_kategori_id'] ?? 0) ?: null;
    $marka_id        = (int)($_POST['marka_id']        ?? 0) ?: null;
    $model_id        = (int)($_POST['model_id']        ?? 0) ?: null;
    $cins_id         = (int)($_POST['cins_id']         ?? 0) ?: null;
    $kategori_id     = (int)($_POST['kategori_id']     ?? 0) ?: null;

    if ($marka === '' && $marka_id) {
        $s = $db->prepare("SELECT ad FROM markalar WHERE id = ? LIMIT 1");
        $s->execute([$marka_id]);
        $marka = (string)($s->fetchColumn() ?: '');
    }
    if ($cins === '' && $cins_id) {
        $s = $db->prepare("SELECT ad FROM cinsler WHERE id = ? LIMIT 1");
        $s->execute([$cins_id]);
        $cins = (string)($s->fetchColumn() ?: '');
    }
    if ($kategori === '' && $kategori_id) {
        $s = $db->prepare("SELECT ad FROM kategoriler WHERE id = ? LIMIT 1");
        $s->execute([$kategori_id]);
        $kategori = (string)($s->fetchColumn() ?: '');
    }

    $rates = get_rates_map($db);
    if ($satis_euro > 0 && $rates['EUR'] > 0) {
        $satis_fiyat = round($satis_euro * $rates['EUR'], 2);
    } elseif ($satis_fiyat > 0 && $rates['EUR'] > 0 && $satis_euro <= 0) {
        $satis_euro = round($satis_fiyat / $rates['EUR'], 2);
    }

    $upload = urun_resmi_yukle('resim', '');
    if (!$upload['ok']) {
        $_SESSION['mesaj'] = "ekle_no";
        $_SESSION['mesaj_detay'] = $upload['error'];
        header("Location: yeni-urun.php");
        exit;
    }

    if ($urun_adi == "" || $kdv == "") {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: yeni-urun.php");
        exit;
    }

    $ekle = $db->prepare("
        INSERT INTO urunler 
        (urun_adi, stok, marka, cins, kategori, raf_bolumu, kdv, barkod, satis_fiyat, satis_euro, satis_dolar, satis_sterlin, web, resim, ana_kategori_id, marka_id, model_id, cins_id, kategori_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $sonuc = $ekle->execute([
        $urun_adi,
        $stok,
        $marka,
        $cins,
        $kategori,
        $raf_bolumu,
        $kdv,
        $barkod,
        $satis_fiyat,
        $satis_euro,
        $satis_dolar,
        $satis_sterlin,
        (int)($_POST['web'] ?? 0),
        $upload['path'],
        $ana_kategori_id,
        $marka_id,
        $model_id,
        $cins_id,
        $kategori_id
    ]);

    if ($sonuc) {
        $urunId = (int)$db->lastInsertId();
        log_islem(
            $db,
            'urun',
            'ekle',
            $urunId,
            log_format_pairs([
                'Urun' => $urun_adi,
                'Stok' => $stok,
                'Barkod' => $barkod,
                'Raf' => $raf_bolumu,
            ]),
            [
                'urun_adi' => $urun_adi,
                'stok' => $stok,
                'barkod' => $barkod,
                'satis_fiyat' => $satis_fiyat,
                'satis_euro' => $satis_euro,
            ]
        );
        $_SESSION['mesaj'] = "ekle_ok";
        header("Location: urunler.php");
    } else {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: yeni-urun.php");
    }

    // Cache temizle
    $cacheDir = __DIR__ . '/ajax/cacheurunler/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    exit;
}

/* =========================================
   KULLANICI EKLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "kullanici_ekle") {

    $username = $_POST['username'] ?? '';
    $namesurname = $_POST['namesurname'] ?? '';
    $email    = $_POST['email'] ?? '';
    $phone    = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if ($username == "" || $email == "" || $password == "") {
        $_SESSION['mesaj'] = "kullanici_ekle_no";
        header("Location: yeni-kullanici.php");
        exit;
    }

    // Şifreyi hash’le
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $ekle = $db->prepare("INSERT INTO users (username, password, email, phone, role, namesurname) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $sonuc = $ekle->execute([$username, $password_hash, $email, $phone, $role, $namesurname]);

        if ($sonuc) {
            $_SESSION['mesaj'] = "kullanici_ekle_ok";
            header("Location: kullanicilar.php");
        } else {
            $_SESSION['mesaj'] = "kullanici_ekle_no";
            header("Location: yeni-kullanici.php");
        }
    } catch (PDOException $e) {
        $_SESSION['mesaj'] = "kullanici_ekle_no";
        header("Location: yeni-kullanici.php");
        exit;
    }
    exit;
}

/* =========================================
   KULLANICI SİLME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "user_sil") {

    $id = intval($_GET['id']);

    // Sadece aktif (durum = 0) kullanıcıyı pasif yap
    $sil = $db->prepare("
        UPDATE users 
        SET durum = 1 
        WHERE id = ? AND durum = 0
    ");
    $sonuc = $sil->execute([$id]);

    if ($sonuc && $sil->rowCount() > 0) {
        $_SESSION['mesaj'] = "sil_ok";
    } else {
        $_SESSION['mesaj'] = "sil_no";
    }

    header("Location: kullanicilar.php");
    exit;
}

/* =========================================
   KULLANICI DÜZENLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "kullanici_duzenle") {

    $id = $_POST['id'];
    $username = $_POST['username'];
    $namesurname = $_POST['namesurname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role= $_POST['role'];

    $update = $db->prepare("UPDATE users SET 
       username=?,
       namesurname=?,
       email=?,
       phone=?,
       role=?
    WHERE id=?")->execute([
     $username,
     $namesurname,
     $email,
     $phone,
     $role,
     $id
    ]);

    if ($update) {
        $_SESSION['mesaj2'] = "duzenle_ok";
    } else {
        $_SESSION['mesaj2'] = "duzenle_no";
    }

    // Parametre temiz -> tekrar uyarı vermez
    header("Location: kullanici-duzenle.php?id=$id");
    exit;
}
/* =========================================
   ŞİFRE DÜZENLEME
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] === "sifre_duzenle") {

    session_start();

    $id    = (int)($_POST['id'] ?? 0);
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($id <= 0 || $pass1 === '' || $pass1 !== $pass2) {
        $_SESSION['mesaj'] = "no";
        header("Location: kullanici-duzenle.php?id=".$id);
        exit;
    }

    $hash = password_hash($pass1, PASSWORD_DEFAULT);

    $guncelle = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
    $sonuc = $guncelle->execute([
        ':pass' => $hash,
        ':id'   => $id
    ]);

    $_SESSION['mesaj'] = ($sonuc && $guncelle->rowCount() > 0) ? "ok" : "no";

    header("Location: kullanici-duzenle.php?id=".$id);
    exit;
}

/* =========================================
   MUSTERI DÜZENLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "musteri_duzenle") {

    $id         = (int)$_POST['id'];
    $musteri_adi= $_POST['musteri_adi'];
    $yetkili    = $_POST['yetkili'];
    $telefon    = $_POST['telefon'];
    $adres      = $_POST['adres'];
    $sehir      = $_POST['sehir'];
   $sorumlu    = $_POST['sorumlu'] !== '' ? (int)$_POST['sorumlu'] : null;

    $eskiMusteriStmt = $db->prepare("SELECT musteri_adi, yetkili, telefon, adres, sehir, sorumlu FROM musteriler WHERE id = ? LIMIT 1");
    $eskiMusteriStmt->execute([$id]);
    $eskiMusteri = $eskiMusteriStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $update = $db->prepare("
        UPDATE musteriler SET 
            musteri_adi=?,
            yetkili=?,
            telefon=?,
            adres=?,
            sehir=?,
            sorumlu=?
        WHERE id=?
    ");

    $sonuc = $update->execute([
        $musteri_adi,
        $yetkili,
        $telefon,
        $adres,
        $sehir,
        $sorumlu,
        $id
    ]);

  if ($sonuc) {
        log_islem(
            $db,
            'musteri',
            'duzenle',
            $id,
            log_format_pairs([
                'Musteri' => $musteri_adi,
                'Yetkili' => $yetkili,
                'Telefon' => $telefon,
                'Sehir' => $sehir,
            ]),
            [
                'once' => $eskiMusteri,
                'sonra' => [
                    'musteri_adi' => $musteri_adi,
                    'yetkili' => $yetkili,
                    'telefon' => $telefon,
                    'adres' => $adres,
                    'sehir' => $sehir,
                    'sorumlu' => $sorumlu,
                ]
            ]
        );
        $_SESSION['mesaj'] = "duzenle_ok";
    } else {
        $_SESSION['mesaj'] = "duzenle_no";
    }

    // Cache temizle
    $cacheDir = __DIR__ . '/ajax/cachemusteriler/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    // Parametre temiz -> tekrar uyarı vermez
    header("Location: musteri-duzenle.php?id=$id");
    exit;
}

/* =========================================
   MÜŞTERİ EKLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] === "musteri_ekle") {

    // Form verileri
    $musteri_adi = trim($_POST['musteri_adi'] ?? '');
    $yetkili     = trim($_POST['yetkili'] ?? '');
    $telefon     = trim($_POST['telefon'] ?? '');
    $adres       = trim($_POST['adres'] ?? '');
    $sehir       = trim($_POST['sehir'] ?? '');
    
    $sorumlu     = $_POST['sorumlu'] !== '' ? (int)$_POST['sorumlu'] : null;

    // Zorunlu alan kontrolü
    if (
        $musteri_adi === '' ||
        $yetkili === '' ||
        $telefon === '' ||
        $adres === '' ||
        $sehir === '' 
    ) {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: yeni-musteri.php");
        exit;
    }

    // INSERT
    $ekle = $db->prepare("
        INSERT INTO musteriler
        (musteri_adi, yetkili, telefon, adres, sehir, sorumlu)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $sonuc = $ekle->execute([
        $musteri_adi,
        $yetkili,
        $telefon,
        $adres,
        $sehir,
        $sorumlu
    ]);

    if ($sonuc) {
        $musteriId = (int)$db->lastInsertId();
        log_islem(
            $db,
            'musteri',
            'ekle',
            $musteriId,
            log_format_pairs([
                'Musteri' => $musteri_adi,
                'Yetkili' => $yetkili,
                'Telefon' => $telefon,
                'Sehir' => $sehir,
            ]),
            [
                'musteri_adi' => $musteri_adi,
                'yetkili' => $yetkili,
                'telefon' => $telefon,
                'adres' => $adres,
                'sehir' => $sehir,
                'sorumlu' => $sorumlu,
            ]
        );
        $_SESSION['mesaj'] = "ekle_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cachemusteriler/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        header("Location: tum-musteriler.php");
    } else {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: yeni-musteri.php");
    }
    exit;
}

/* =========================================
   MUSTERI SİLME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "musteri_sil") {

    $id = intval($_GET['id']);

    $musteriStmt = $db->prepare("SELECT musteri_adi, yetkili, telefon, sehir FROM musteriler WHERE id = ? AND durum = 0 LIMIT 1");
    $musteriStmt->execute([$id]);
    $musteri = $musteriStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $guncelle = $db->prepare("
        UPDATE musteriler 
        SET durum = 1 
        WHERE id = ? AND durum = 0
    ");
    $sonuc = $guncelle->execute([$id]);

    if ($sonuc && $guncelle->rowCount() > 0) {
        log_islem(
            $db,
            'musteri',
            'sil',
            $id,
            log_format_pairs([
                'Musteri' => $musteri['musteri_adi'] ?? null,
                'Yetkili' => $musteri['yetkili'] ?? null,
                'Telefon' => $musteri['telefon'] ?? null,
                'Sehir' => $musteri['sehir'] ?? null,
            ]),
            $musteri
        );
        $_SESSION['mesaj'] = "sil_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cachemusteriler/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

    } else {
        $_SESSION['mesaj'] = "sil_no";
    }

    header("Location: tum-musteriler.php");
    exit;
}

/* =========================================
   ÖDEME EKLEME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "odeme_ekle") {

    $musteri_id   = (int)($_POST['musteri_id'] ?? 0);
    $tutar        = (float)($_POST['tutar'] ?? 0);
    $makbuz_no    = $_POST['makbuz_no'] ?? '';
    $aciklama     = $_POST['aciklama'] ?? '';
  
    $odemeyi_alan = (int)($_POST['odemeyi_alan'] ?? 0);

    /* ZORUNLU ALAN KONTROLÜ */
    if ($musteri_id == 0 || $tutar <= 0 || $odemeyi_alan == 0) {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: odeme-ekle.php");
        exit;
    }

    $ekle = $db->prepare("
        INSERT INTO odemeler
        (musteri_id, tutar, makbuz_no, aciklama, odemeyi_alan, tarih, created_at, updated_at, durum)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW(), 0)
    ");

    $sonuc = $ekle->execute([
        $musteri_id,
        $tutar,
        $makbuz_no,
        $aciklama,
        $odemeyi_alan
    ]);

    if ($sonuc) {
        $musteriAdiStmt = $db->prepare("SELECT musteri_adi FROM musteriler WHERE id = ? LIMIT 1");
        $musteriAdiStmt->execute([$musteri_id]);
        $musteriAdi = (string)($musteriAdiStmt->fetchColumn() ?: '');

        $odemeId = (int)$db->lastInsertId();
        log_islem(
            $db,
            'odeme',
            'ekle',
            $odemeId,
            log_format_pairs([
                'Musteri' => $musteriAdi,
                'Tutar' => $tutar,
                'Makbuz' => $makbuz_no,
            ]),
            [
                'musteri_id' => $musteri_id,
                'musteri_adi' => $musteriAdi,
                'tutar' => $tutar,
                'makbuz_no' => $makbuz_no,
                'aciklama' => $aciklama,
                'odemeyi_alan' => $odemeyi_alan,
            ]
        );
        $_SESSION['mesaj'] = "ekle_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cacheodemeler/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        header("Location: tum-odemeler.php");
    } else {
        $_SESSION['mesaj'] = "ekle_no";
        header("Location: odeme-ekle.php");
    }
    exit;
}

/* =========================================
   ODEME SİLME İŞLEMİ
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "odeme_sil") {

    $id = intval($_GET['id']);

    $odemeStmt = $db->prepare("SELECT o.musteri_id, o.tutar, o.makbuz_no, o.aciklama, m.musteri_adi FROM odemeler o LEFT JOIN musteriler m ON m.id = o.musteri_id WHERE o.id = ? AND o.durum = 0 LIMIT 1");
    $odemeStmt->execute([$id]);
    $odeme = $odemeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // SADECE AKTİF ÖDEMEYİ SİL
    $sil = $db->prepare("
        UPDATE odemeler 
        SET durum = 1 
        WHERE id = ? AND durum = 0
    ");

    $sonuc = $sil->execute([$id]);

    if ($sonuc && $sil->rowCount() > 0) {
        log_islem(
            $db,
            'odeme',
            'sil',
            $id,
            log_format_pairs([
                'Musteri' => $odeme['musteri_adi'] ?? null,
                'Tutar' => $odeme['tutar'] ?? null,
                'Makbuz' => $odeme['makbuz_no'] ?? null,
            ]),
            $odeme
        );
        $_SESSION['mesaj'] = "sil_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cacheodemeler/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

    } else {
        $_SESSION['mesaj'] = "sil_no";
    }

    header("Location: tum-odemeler.php");
    exit;
}

/* =========================================
   ÖDEME DÜZENLE
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "odeme_duzenle") {

    $id           = $_POST['id'];
    $musteri_id   = $_POST['musteri_id'];
    $tutar        = $_POST['tutar'];
    $makbuz_no    = $_POST['makbuz_no'];
    $aciklama     = $_POST['aciklama'];
    $odemeyi_alan = $_POST['odemeyi_alan'];

    // ZORUNLU ALAN KONTROLÜ
    if ($musteri_id == "" || $tutar <= 0 || $odemeyi_alan == "") {
        $_SESSION['mesaj'] = "duzenle_no";
        header("Location: odeme-duzenle.php?id=$id");
        exit;
    }

    $eskiOdemeStmt = $db->prepare("SELECT musteri_id, tutar, makbuz_no, aciklama, odemeyi_alan FROM odemeler WHERE id = ? LIMIT 1");
    $eskiOdemeStmt->execute([$id]);
    $eskiOdeme = $eskiOdemeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $update = $db->prepare("
        UPDATE odemeler SET
            musteri_id = ?,
            tutar = ?,
            makbuz_no = ?,
            aciklama = ?,
            odemeyi_alan = ?,
            updated_at = NOW()
        WHERE id = ?
    ")->execute([
        $musteri_id,
        $tutar,
        $makbuz_no,
        $aciklama,
        $odemeyi_alan,
        $id
    ]);

    if ($update) {
        $musteriAdiStmt = $db->prepare("SELECT musteri_adi FROM musteriler WHERE id = ? LIMIT 1");
        $musteriAdiStmt->execute([$musteri_id]);
        $musteriAdi = (string)($musteriAdiStmt->fetchColumn() ?: '');

        log_islem(
            $db,
            'odeme',
            'duzenle',
            (int)$id,
            log_format_pairs([
                'Musteri' => $musteriAdi,
                'Tutar' => $tutar,
                'Makbuz' => $makbuz_no,
            ]),
            [
                'once' => $eskiOdeme,
                'sonra' => [
                    'musteri_id' => $musteri_id,
                    'tutar' => $tutar,
                    'makbuz_no' => $makbuz_no,
                    'aciklama' => $aciklama,
                    'odemeyi_alan' => $odemeyi_alan,
                ]
            ]
        );
        $_SESSION['mesaj'] = "duzenle_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cacheodemeler/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        header("Location: tum-odemeler.php");
    } else  {
        $_SESSION['mesaj'] = "duzenle_no";
        header("Location: odeme-duzenle.php?id=$id");
    }
    exit;
}

/* =========================================
  SATIŞ EKLE
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] === 'satis_ekle') {

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (empty($_POST['musteri_id'])) die('HATA: Musteri secilmedi');
    if (empty($_SESSION['user_id'])) die('HATA: Kullanici bulunamadi');
    if (!isset($_POST['sepet_json'])) die('HATA: Sepet verisi gelmedi');

    $sepet = json_decode($_POST['sepet_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($sepet)) {
        die('HATA: Sepet bos veya gecersiz');
    }

    $musteri_id  = (int) $_POST['musteri_id'];
    $user_id     = (int) $_SESSION['user_id'];
    $faturano    = $_POST['faturano'] ?? null;

    // BENZERSIZ ISLEM NO
    do {
        $islem_no = substr(str_shuffle('0123456789'), 0, 10);
        $q = $db->prepare("SELECT COUNT(*) FROM satislar WHERE islem_no = ?");
        $q->execute([$islem_no]);
    } while ($q->fetchColumn() > 0);

    try {
        $db->beginTransaction();

        $stokDus = $db->prepare("
            UPDATE urunler 
            SET stok = stok - ? 
            WHERE id = ? AND stok >= ?
        ");

        $satisEkle = $db->prepare("
            INSERT INTO satislar
            (musteri_id, urun_id, adet, kdv_toplami, birim_fiyat, indirim_toplami, genel_tutar,
             islem_no, satisi_yapan_id, fatura_no, tutar, tarih)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        // TOPLAM HESAPLAR (MUHASEBE İÇİN)
        $toplam_fatura = 0;
        $toplam_kdv = 0;
        $toplam_indirim = 0;

        foreach ($sepet as $urun) {

            $urun_id  = (int)$urun['id'];
            $adet     = (int)$urun['adet'];
            $fiyat    = (float)str_replace(',', '.', $urun['fiyat']);
            $kdv_orani = (float)str_replace(',', '.', ($urun['kdv'] ?? 0));
            $indirim_orani = (float)str_replace(',', '.', ($urun['indirim'] ?? 0));
            $indirim_orani = max(0, min(100, $indirim_orani));

            $stokDus->execute([$adet, $urun_id, $adet]);
            if ($stokDus->rowCount() === 0) {
                throw new Exception("Stok yetersiz (Ürün ID: $urun_id)");
            }

            $tutar = $fiyat * $adet;
            $indirim = $tutar * $indirim_orani / 100;
            $indirimli_tutar = $tutar - $indirim;
            $kdv = $tutar * $kdv_orani / 100;
            $genel = $indirimli_tutar + $kdv;

            $satisEkle->execute([
                $musteri_id,
                $urun_id,
                $adet,
                $kdv,
                $fiyat,
                $indirim,
                $genel,
                $islem_no,
                $user_id,
                $faturano,
                $tutar
            ]);

            $toplam_fatura += $tutar;
            $toplam_kdv += $kdv;
            $toplam_indirim += $indirim;
        }

        // === MUHASEBE KAYDI ===
        $net_fatura = $toplam_fatura + $toplam_kdv - $toplam_indirim;

        $muhasebeEkle = $db->prepare("
            INSERT INTO muhasebe
            (musteri_id, fatura_no, fatura_tutari, islemi_yapan_id,
             islem_no, indirim_toplami, ISLEMSEBEP, tarih, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'F', NOW(), NOW(), NOW())
        ");

        $muhasebeEkle->execute([
            $musteri_id,
            $faturano,
            $net_fatura,
            $user_id,
            $islem_no,
            $toplam_indirim
        ]);

        $db->commit();

        $musteriAdiStmt = $db->prepare("SELECT musteri_adi FROM musteriler WHERE id = ? LIMIT 1");
        $musteriAdiStmt->execute([$musteri_id]);
        $musteriAdi = (string)($musteriAdiStmt->fetchColumn() ?: '');

        log_islem(
            $db,
            'satis',
            'ekle',
            null,
            log_format_pairs([
                'Islem No' => $islem_no,
                'Musteri' => $musteriAdi,
                'Kalem' => count($sepet),
                'Toplam' => round($net_fatura, 2),
            ]),
            [
                'islem_no' => $islem_no,
                'musteri_id' => $musteri_id,
                'musteri_adi' => $musteriAdi,
                'fatura_no' => $faturano,
                'kalem_sayisi' => count($sepet),
                'toplam_fatura' => $toplam_fatura,
                'toplam_kdv' => $toplam_kdv,
                'toplam_indirim' => $toplam_indirim,
                'net_fatura' => $net_fatura,
            ]
        );

        $_SESSION['mesaj'] = 'satis_ok';
        header('Location: yeni-satis.php');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die('SATIS HATASI: ' . $e->getMessage());
    }
}


/* =========================================
  SATIŞ SİL
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] === "satis_sil") {

    $satis_id = (int)($_GET['id'] ?? 0);
    if (!$satis_id) {
        $_SESSION['mesaj'] = "sil_no";
        header("Location: tum-satislar.php");
        exit;
    }

    try {
        $db->beginTransaction();

        // 1️⃣ Silinecek satışın bilgilerini al
        $stmt = $db->prepare("
            SELECT s.urun_id, s.adet, s.islem_no, s.musteri_id, s.tutar, u.urun_adi, m.musteri_adi
            FROM satislar 
            s
            LEFT JOIN urunler u ON u.id = s.urun_id
            LEFT JOIN musteriler m ON m.id = s.musteri_id
            WHERE s.id = ? AND s.durum = 0
        ");
        $stmt->execute([$satis_id]);
        $satis = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$satis) {
            throw new Exception("Satış bulunamadı");
        }

        // 2️⃣ Stok iade et
        $stokIade = $db->prepare("
            UPDATE urunler 
            SET stok = stok + ? 
            WHERE id = ?
        ");
        $stokIade->execute([
            $satis['adet'],
            $satis['urun_id']
        ]);

        // 3️⃣ Satışı soft sil
        $iptalSatis = $db->prepare("
            UPDATE satislar 
            SET durum = 1 
            WHERE id = ?
        ");
        $iptalSatis->execute([$satis_id]);

        // 4️⃣ Muhasebe kaydını güncelle (tutar çıkar)
        // Bu kısım basit için atlanabilir, karmaşık olduğu için

        $db->commit();

        log_islem(
            $db,
            'satis',
            'sil',
            $satis_id,
            log_format_pairs([
                'Islem No' => $satis['islem_no'] ?? null,
                'Musteri' => $satis['musteri_adi'] ?? null,
                'Urun' => $satis['urun_adi'] ?? null,
                'Adet' => $satis['adet'] ?? null,
                'Tutar' => $satis['tutar'] ?? null,
            ]),
            $satis
        );

        $_SESSION['mesaj'] = "sil_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj'] = "sil_no";
        // DEBUG İÇİN:
        // die("SATIŞ SİLME HATASI: " . $e->getMessage());
    }

    header("Location: tum-satislar.php");
    exit;
}

/* =========================================
   SATIŞ DÜZENLE
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] === "satis_duzenle") {

    $id = (int)($_POST['id'] ?? 0);
    $musteri_id = (int)($_POST['musteri_id'] ?? 0);
    $urun_id = (int)($_POST['urun_id'] ?? 0);
    $adet = (int)($_POST['adet'] ?? 0);
    $satisi_yapan = (int)($_POST['satisi_yapan'] ?? 0);

    if (!$id || !$musteri_id || !$urun_id || !$adet || !$satisi_yapan) {
        $_SESSION['mesaj'] = "duzenle_no";
        header("Location: satis-duzenle.php?id=$id");
        exit;
    }

    try {
        $db->beginTransaction();

        // 1️⃣ Eski satış bilgilerini al
        $eskiSatis = $db->prepare("
            SELECT urun_id, adet
            FROM satislar
            WHERE id = ? AND durum = 0
        ");
        $eskiSatis->execute([$id]);
        $eski = $eskiSatis->fetch(PDO::FETCH_ASSOC);

        if (!$eski) {
            throw new Exception("Satış bulunamadı");
        }

        // 2️⃣ Eğer ürün değiştiyse, eski ürünü stoğa geri ekle ve yeni üründen düş
        if ($eski['urun_id'] != $urun_id) {
            // Eski ürünü stoğa geri ekle
            $stokIade = $db->prepare("UPDATE urunler SET stok = stok + ? WHERE id = ?");
            $stokIade->execute([$eski['adet'], $eski['urun_id']]);

            // Yeni üründen adet kadar düş
            $stokDus = $db->prepare("UPDATE urunler SET stok = stok - ? WHERE id = ? AND stok >= ?");
            $stokDus->execute([$adet, $urun_id, $adet]);
            if ($stokDus->rowCount() === 0) {
                throw new Exception("Yeni ürün için yeterli stok yok");
            }
        } elseif ($eski['adet'] != $adet) {
            // Sadece adet değişti, farkı stoğa ekle/çıkar
            $fark = $adet - $eski['adet'];
            if ($fark > 0) {
                // Adet arttı, stoğu düşür
                $stokDus = $db->prepare("UPDATE urunler SET stok = stok - ? WHERE id = ? AND stok >= ?");
                $stokDus->execute([$fark, $urun_id, $fark]);
                if ($stokDus->rowCount() === 0) {
                    throw new Exception("Yeterli stok yok");
                }
            } else {
                // Adet azaldı, stoğu artır
                $stokArtir = $db->prepare("UPDATE urunler SET stok = stok + ? WHERE id = ?");
                $stokArtir->execute([abs($fark), $urun_id]);
            }
        }

        // 3️⃣ Ürün bilgilerini al (fiyat, kdv için)
        $urun = $db->prepare("SELECT urun_adi, satis_euro, satis_fiyat, kdv FROM urunler WHERE id = ?");
        $urun->execute([$urun_id]);
        $urunBilgi = $urun->fetch(PDO::FETCH_ASSOC);

        if (!$urunBilgi) {
            throw new Exception("Ürün bulunamadı");
        }

        // 4️⃣ KDV ve toplam hesapla
        $rates = get_rates_map($db);
        $birim_fiyat = (float)($urunBilgi['satis_euro'] ?? 0);
        if ($birim_fiyat <= 0 && $rates['EUR'] > 0) {
            $birim_fiyat = (float)$urunBilgi['satis_fiyat'] / $rates['EUR'];
        }
        $kdv_orani = (float)$urunBilgi['kdv'];
        $tutar = $birim_fiyat * $adet;
        $kdv_toplami = ($tutar * $kdv_orani) / 100;
        $genel_tutar = $tutar + $kdv_toplami;

        // 5️⃣ Satışı güncelle
        $update = $db->prepare("
            UPDATE satislar SET
                musteri_id = ?,
                urun_id = ?,
                adet = ?,
                tutar = ?,
                kdv_toplami = ?,
                genel_tutar = ?,
                satisi_yapan_id = ?
            WHERE id = ?
        ");
        $update->execute([
            $musteri_id,
            $urun_id,
            $adet,
            $tutar,
            $kdv_toplami,
            $genel_tutar,
            $satisi_yapan,
            $id
        ]);

        $db->commit();

        log_islem(
            $db,
            'satis',
            'duzenle',
            $id,
            log_format_pairs([
                'Satis ID' => $id,
                'Musteri ID' => $musteri_id,
                'Urun ID' => $urun_id,
                'Adet' => $adet,
            ]),
            [
                'once' => $eski,
                'sonra' => [
                    'musteri_id' => $musteri_id,
                    'urun_id' => $urun_id,
                    'adet' => $adet,
                    'satisi_yapan_id' => $satisi_yapan,
                    'tutar' => $tutar,
                    'kdv_toplami' => $kdv_toplami,
                    'genel_tutar' => $genel_tutar,
                ]
            ]
        );

        $_SESSION['mesaj'] = "duzenle_ok";

        // Cache temizle
        $cacheDir = __DIR__ . '/ajax/cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj'] = "duzenle_no";
        // DEBUG İÇİN:
        // die("SATIŞ DÜZENLEME HATASI: " . $e->getMessage());
    }

    header("Location: tum-satislar.php");
    exit;
}

/* =========================================
  FATURA İPTAL
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] === 'fatura_iptal') {

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (empty($_GET['islem_no'])) {
        die("HATA: islem_no gelmedi");
    }

    $islem_no = trim($_GET['islem_no']);

    try {
        $db->beginTransaction();

        /* =====================================
           1️⃣ SATIŞLARI AL
        ===================================== */
        $satislar = $db->prepare("
            SELECT urun_id, adet
            FROM satislar
            WHERE islem_no = ? AND durum = 0
        ");
        $satislar->execute([$islem_no]);
        $liste = $satislar->fetchAll(PDO::FETCH_ASSOC);

        if (!$liste) {
            throw new Exception("İptal edilecek satış bulunamadı");
        }

        /* =====================================
           2️⃣ STOK İADESİ
        ===================================== */
        $stokIade = $db->prepare("
            UPDATE urunler
            SET stok = stok + ?
            WHERE id = ?
        ");

        foreach ($liste as $satis) {
            $stokIade->execute([
                (int)$satis['adet'],
                (int)$satis['urun_id']
            ]);
        }

        /* =====================================
           3️⃣ SATIŞLARI İPTAL ET
        ===================================== */
        $satisIptal = $db->prepare("
            UPDATE satislar
            SET durum = 1
            WHERE islem_no = ?
        ");
        $satisIptal->execute([$islem_no]);

        /* =====================================
           4️⃣ MUHASEBE KAYDINI İPTAL ET
        ===================================== */
        $muhasebeIptal = $db->prepare("
            UPDATE muhasebe
            SET 
                durum = 1,
                fatura_durum = 1,
                updated_at = NOW()
            WHERE islem_no = ?
        ");
        $muhasebeIptal->execute([$islem_no]);

        $db->commit();

        $_SESSION['mesaj'] = 'iptal_ok';

    } catch (Exception $e) {
        $db->rollBack();
        die("IPTAL HATASI: " . $e->getMessage());
    }

    header("Location: faturalar.php");
    exit;
}


/* =========================================
  SATIŞ DÜZENLE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_GET['islem'] === 'satis_guncelle') {

    try {
        $db->beginTransaction();

        $satis_id        = (int)$_POST['satis_id'];
        $urun_id         = (int)$_POST['urun_id'];
        $adet            = (float)$_POST['adet'];
        $indirim_toplam  = (float)$_POST['indirim_toplami'];
        $kdv_toplam      = (float)$_POST['kdv_toplam_val'];

        /* 1️⃣ ESKİ SATIŞ */
        $stmt = $db->prepare("SELECT urun_id, adet FROM satislar WHERE id = ?");
        $stmt->execute([$satis_id]);
        $eskiSatis = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$eskiSatis) {
            throw new Exception("Satış bulunamadı");
        }

        /* 2️⃣ ESKİ STOK GERİ EKLE */
        $db->prepare("
            UPDATE urunler 
            SET stok = stok + ? 
            WHERE id = ?
        ")->execute([$eskiSatis['adet'], $eskiSatis['urun_id']]);

        /* 3️⃣ ÜRÜN BİLGİSİ */
        $stmt = $db->prepare("
            SELECT stok, satis_euro, satis_fiyat 
            FROM urunler 
            WHERE id = ?
        ");
        $stmt->execute([$urun_id]);
        $urun = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$urun) {
            throw new Exception("Ürün bulunamadı");
        }

        /* 4️⃣ STOK KONTROL */
        if ($urun['stok'] < $adet) {
            throw new Exception("Stok yetersiz! Mevcut stok: {$urun['stok']}");
        }

        /* 5️⃣ TUTAR HESAPLA
           tutar = (adet × satış_fiyat) + kdv_toplam
        */
        $rates = get_rates_map($db);
        $birimFiyatEur = (float)($urun['satis_euro'] ?? 0);
        if ($birimFiyatEur <= 0 && $rates['EUR'] > 0) {
            $birimFiyatEur = (float)$urun['satis_fiyat'] / $rates['EUR'];
        }
        $tutar = ($adet * $birimFiyatEur) + $kdv_toplam;

        /* 6️⃣ SATIŞ GÜNCELLE */
        $db->prepare("
            UPDATE satislar SET
                urun_id = ?,
                adet = ?,
                indirim_toplami = ?,
                kdv_toplami = ?,
                tutar = ?
            WHERE id = ?
        ")->execute([
            $urun_id,
            $adet,
            $indirim_toplam,
            $kdv_toplam,
            $tutar,
            $satis_id
        ]);

        /* 7️⃣ YENİ STOK DÜŞ */
        $db->prepare("
            UPDATE urunler 
            SET stok = stok - ?
            WHERE id = ?
        ")->execute([$adet, $urun_id]);

        $db->commit();

        log_islem(
            $db,
            'satis',
            'duzenle',
            $satis_id,
            log_format_pairs([
                'Satis ID' => $satis_id,
                'Urun ID' => $urun_id,
                'Adet' => $adet,
                'Tutar' => round($tutar, 2),
            ]),
            [
                'once' => $eskiSatis,
                'sonra' => [
                    'urun_id' => $urun_id,
                    'adet' => $adet,
                    'indirim_toplami' => $indirim_toplam,
                    'kdv_toplami' => $kdv_toplam,
                    'tutar' => $tutar,
                ]
            ]
        );

        $_SESSION['mesaj'] = 'satis_ok';
        header("Location:tum-satislar.php");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj'] = 'stok_yetersiz';
        $_SESSION['stok_hata_mesaji'] = $e->getMessage();
        header("Location:satisi-duzenle.php?id=".$satis_id);
        exit;
    }
}
/* =========================================
   BARKOD KONTROL İŞLEMİ (AJAX)
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] == "barkod_kontrol") {

    header("Content-Type: application/json");

    $barkod = $_POST['barkod'] ?? '';

    if ($barkod == "") {
        echo json_encode([
            "durum" => "bos"
        ]);
        exit;
    }

    $sorgu = $db->prepare("SELECT urun_adi FROM urunler WHERE barkod = ? LIMIT 1");
    $sorgu->execute([$barkod]);

    if ($sorgu->rowCount() > 0) {
        $urun = $sorgu->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            "durum" => "var",
            "urun_adi" => $urun['urun_adi']
        ]);
    } else {
        echo json_encode([
            "durum" => "yok"
        ]);
    }
    exit;
}

/* =========================================
  FATURA KAPAT
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] == "fatura_kapat") {

    if(!isset($_GET['islem_no']) || empty($_GET['islem_no'])) {
        die("Geçersiz işlem");
    }

    $islem_no = $_GET['islem_no'];

    $stmt = $db->prepare("
        UPDATE satislar
        SET fatura_durum = 1
        WHERE islem_no = ? AND fatura_durum = 0
    ");
    $stmt->execute([$islem_no]);

    header("Location: faturalar.php");
    exit;
}

/* =========================================
  SİPARİŞ EKLE


if (isset($_GET['islem']) && $_GET['islem'] === 'siparis_ekle') {

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (empty($_POST['musteri_id'])) {
        die('HATA: Musteri secilmedi');
    }

    if (empty($_SESSION['user_id'])) {
        die('HATA: Kullanici bulunamadi');
    }

    if (!isset($_POST['sepet_json'])) {
        die('HATA: Sepet verisi gelmedi');
    }

    $sepet = json_decode($_POST['sepet_json'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('JSON HATASI: ' . json_last_error_msg());
    }

    if (!is_array($sepet) || count($sepet) === 0) {
        die('HATA: Sepet bos');
    }

    $musteri_id = (int) $_POST['musteri_id'];
    $user_id    = (int) $_SESSION['user_id'];
    $faturano   = $_POST['faturano'] ?? null;

    // BENZERSIZ ISLEM NO
    do {
        $islem_no = substr(str_shuffle('0123456789'), 0, 10);
        $sorgu = $db->prepare("SELECT COUNT(*) FROM satislar WHERE islem_no = ?");
        $sorgu->execute([$islem_no]);
    } while ($sorgu->fetchColumn() > 0);

    try {
        $db->beginTransaction();

        // Stok düşme sorgusu
        $stokDus = $db->prepare("
            UPDATE urunler 
            SET stok = stok - ? 
            WHERE id = ? AND stok >= ?
        ");

        // Sipariş ekleme sorgusu
        $siparisEkle = $db->prepare("
            INSERT INTO satislar
            (musteri_id, urun_id, adet, kdv_toplami, indirim_toplami, tutar, islem_no, satisi_yapan_id, fatura_no, siparis)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($sepet as $urun) {

            if (empty($urun['id']) || empty($urun['adet']) || empty($urun['genel'])) {
                throw new Exception('Sepet urun verisi eksik');
            }

            $urun_id = (int) $urun['id'];
            $adet    = (int) $urun['adet'];

            $kdv     = (float) str_replace(',', '.', $urun['kdvTutar'] ?? 0);
            $indirim = (float) str_replace(',', '.', $urun['indirimTutar'] ?? 0);
            $tutar   = (float) str_replace(',', '.', $urun['genel']);

            $stokDus->execute([$adet, $urun_id, $adet]);

            if ($stokDus->rowCount() === 0) {
                throw new Exception("Stok yetersiz (Urun ID: $urun_id)");
            }

            // Sipariş ekleme → siparis sütunu 1
            $siparisEkle->execute([
                $musteri_id,
                $urun_id,
                $adet,
                $kdv,
                $indirim,
                $tutar,
                $islem_no,
                $user_id,
                $faturano,
                1 // siparis sütunu
            ]);
        }

        $db->commit();

        $_SESSION['mesaj'] = 'satis_ok';
        header('Location: yeni-siparis.php');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die('SATIS HATASI: ' . $e->getMessage());
    }
}
========================================= */
if (isset($_GET['islem']) && $_GET['islem'] === 'siparis_ekle') {

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (empty($_POST['musteri_id'])) die('HATA: Musteri secilmedi');
    if (empty($_SESSION['user_id'])) die('HATA: Kullanici bulunamadi');
    if (empty($_POST['sepet_json'])) die('HATA: Sepet verisi gelmedi');

    $sepet = json_decode($_POST['sepet_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($sepet)) {
        die('HATA: Sepet gecersiz');
    }

    $musteri_id = (int) $_POST['musteri_id'];
    $user_id    = (int) $_SESSION['user_id'];
    $faturano   = $_POST['faturano'] ?? null;

    // BENZERSIZ ISLEM NO
    do {
        $islem_no = substr(str_shuffle('0123456789'), 0, 10);
        $q = $db->prepare("SELECT COUNT(*) FROM satislar WHERE islem_no = ?");
        $q->execute([$islem_no]);
    } while ($q->fetchColumn() > 0);

    try {
        $db->beginTransaction();

        // STOK DÜŞ
        $stokDus = $db->prepare("
            UPDATE urunler 
            SET stok = stok - ? 
            WHERE id = ? AND stok >= ?
        ");

        // SATIŞ EKLE
        $satisEkle = $db->prepare("
            INSERT INTO satislar
            (musteri_id, urun_id, adet, kdv_toplami, birim_fiyat, indirim_toplami, genel_tutar,
             islem_no, satisi_yapan_id, fatura_no, tutar, tarih, siparis)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        $toplam_fatura  = 0;
        $toplam_kdv     = 0;
        $toplam_indirim = 0;

        foreach ($sepet as $urun) {

            $urun_id = (int)$urun['id'];
            $adet    = (int)$urun['adet'];
            $fiyat   = (float)str_replace(',', '.', $urun['fiyat']);
            $kdv_orani = (float)str_replace(',', '.', ($urun['kdv'] ?? 0));
            $indirim_orani = (float)str_replace(',', '.', ($urun['indirim'] ?? 0));
            $indirim_orani = max(0, min(100, $indirim_orani));

            $stokDus->execute([$adet, $urun_id, $adet]);
            if ($stokDus->rowCount() === 0) {
                throw new Exception("Stok yetersiz (Ürün ID: $urun_id)");
            }

            $tutar = $fiyat * $adet;
            $indirim = $tutar * $indirim_orani / 100;
            $indirimli_tutar = $tutar - $indirim;
            $kdv = $tutar * $kdv_orani / 100;
            $genel = $indirimli_tutar + $kdv;

            $satisEkle->execute([
                $musteri_id,
                $urun_id,
                $adet,
                $kdv,
                $fiyat,
                $indirim,
                $genel,
                $islem_no,
                $user_id,
                $faturano,
                $tutar,
                1 // siparis
            ]);

            $toplam_fatura  += $tutar;
            $toplam_kdv     += $kdv;
            $toplam_indirim += $indirim;
        }

        // MUHASEBE KAYDI
        $net_fatura = $toplam_fatura + $toplam_kdv - $toplam_indirim;

        $muhasebeEkle = $db->prepare("
            INSERT INTO muhasebe
            (musteri_id, fatura_no, fatura_tutari, islemi_yapan_id,
             islem_no, indirim_toplami, siparis, ISLEMSEBEP, tarih, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'F', NOW(), NOW(), NOW())
        ");

        $muhasebeEkle->execute([
            $musteri_id,
            $faturano,
            $net_fatura,
            $user_id,
            $islem_no,
            $toplam_indirim,
            1 // siparis
        ]);

        $db->commit();

        $musteriAdiStmt = $db->prepare("SELECT musteri_adi FROM musteriler WHERE id = ? LIMIT 1");
        $musteriAdiStmt->execute([$musteri_id]);
        $musteriAdi = (string)($musteriAdiStmt->fetchColumn() ?: '');

        log_islem(
            $db,
            'satis',
            'ekle',
            null,
            log_format_pairs([
                'Islem No' => $islem_no,
                'Musteri' => $musteriAdi,
                'Kalem' => count($sepet),
                'Toplam' => round($net_fatura, 2),
                'Tip' => 'Siparis',
            ]),
            [
                'tip' => 'siparis',
                'islem_no' => $islem_no,
                'musteri_id' => $musteri_id,
                'musteri_adi' => $musteriAdi,
                'fatura_no' => $faturano,
                'kalem_sayisi' => count($sepet),
                'toplam_fatura' => $toplam_fatura,
                'toplam_kdv' => $toplam_kdv,
                'toplam_indirim' => $toplam_indirim,
                'net_fatura' => $net_fatura,
            ]
        );

        $_SESSION['mesaj'] = 'siparis_ok';
        header('Location: yeni-siparis.php');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die('SIPARIS HATASI: ' . $e->getMessage());
    }
}



/* =========================================
  SİPARİŞ ONAYLA
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] === "fatura_onay") {

    if (empty($_GET['islem_no'])) {
        die("Geçersiz işlem");
    }

    $islem_no = $_GET['islem_no'];

    try {
        $db->beginTransaction();

        // 1️⃣ MUHASEBE → siparis = 0
        $stmt1 = $db->prepare("
            UPDATE muhasebe
            SET siparis = 0
            WHERE islem_no = ? AND siparis = 1
        ");
        $stmt1->execute([$islem_no]);

        // 2️⃣ SATIŞLAR → siparis = 0
        $stmt2 = $db->prepare("
            UPDATE satislar
            SET siparis = 0
            WHERE islem_no = ? AND siparis = 1
        ");
        $stmt2->execute([$islem_no]);

        $db->commit();

        $_SESSION['mesaj'] = 'onay_ok';

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj'] = 'onay_hata';
    }

    header("Location: dis-faturalar.php");
    exit;
}

/* =========================================
  FATURA DÜZENLE
========================================= */

if (isset($_GET['islem']) && $_GET['islem'] === "fatura_duzenle") {

    $id           = (int)$_POST['id'];
    $islem_no     = trim($_POST['islem_no']);
    $musteri_id   = (int)$_POST['musteri_id'];
    $fatura_no    = trim($_POST['fatura_no']);
    $satisi_yapan = (int)$_POST['satisi_yapan'];

    if ($islem_no === '') {
        $_SESSION['mesaj'] = 'duzenle_hata';
        header("Location: faturalar.php");
        exit;
    }

    try {
        $db->beginTransaction();

        /* =========================
           MUHASEBE GÜNCELLE
        ========================= */
        $muhasebe = $db->prepare("
            UPDATE muhasebe
            SET 
                musteri_id = ?,
                fatura_no = ?,
                islemi_yapan_id = ?
            WHERE islem_no = ?
        ");
        $muhasebe->execute([
            $musteri_id,
            $fatura_no,
            $satisi_yapan,
            $islem_no
        ]);

        /* =========================
           SATIŞLAR GÜNCELLE
        ========================= */
        $satislar = $db->prepare("
            UPDATE satislar
            SET
                musteri_id = ?,
                satisi_yapan_id = ?
            WHERE islem_no = ?
        ");
        $satislar->execute([
            $musteri_id,
            $satisi_yapan,
            $islem_no
        ]);

        $db->commit();

        $_SESSION['mesaj'] = 'duzenle_ok';

    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mesaj'] = 'duzenle_hata';
        // İstersen logla:
        // error_log($e->getMessage());
    }

    header("Location: faturalar.php");
    exit;
}


/* ===============================
   KATEGORİ AĞACI CRUD
================================ */

// ANA KATEGORİ EKLE
if ($_GET['islem'] == 'ana_kategori_ekle') {
    $ad = trim($_POST['ad'] ?? '');
    if ($ad !== '') {
        $db->prepare("INSERT INTO ana_kategoriler (ad) VALUES (?)")->execute([$ad]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// ANA KATEGORİ SİL
if ($_GET['islem'] == 'ana_kategori_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM ana_kategoriler WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// MARKA EKLE
if ($_GET['islem'] == 'marka_ekle') {
    $ad             = trim($_POST['ad'] ?? '');
    $ana_kategori_id = (int)($_POST['ana_kategori_id'] ?? 0);
    if ($ad !== '' && $ana_kategori_id > 0) {
        $db->prepare("INSERT INTO markalar (ana_kategori_id, ad) VALUES (?, ?)")->execute([$ana_kategori_id, $ad]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// MARKA SİL
if ($_GET['islem'] == 'marka_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM markalar WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// MODEL EKLE
if ($_GET['islem'] == 'model_ekle') {
    $ad       = trim($_POST['ad'] ?? '');
    $marka_id = (int)($_POST['marka_id'] ?? 0);
    if ($ad !== '' && $marka_id > 0) {
        $db->prepare("INSERT INTO modeller (marka_id, ad) VALUES (?, ?)")->execute([$marka_id, $ad]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// MODEL SİL
if ($_GET['islem'] == 'model_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM modeller WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// CİNS EKLE
if ($_GET['islem'] == 'cins_ekle') {
    $ad       = trim($_POST['ad'] ?? '');
    $model_id = (int)($_POST['model_id'] ?? 0);
    if ($ad !== '' && $model_id > 0) {
        $db->prepare("INSERT INTO cinsler (model_id, ad) VALUES (?, ?)")->execute([$model_id, $ad]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// CİNS SİL
if ($_GET['islem'] == 'cins_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM cinsler WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// KATEGORİ EKLE
if ($_GET['islem'] == 'kategori_ekle') {
    $ad      = trim($_POST['ad'] ?? '');
    $cins_id = (int)($_POST['cins_id'] ?? 0);
    if ($ad !== '' && $cins_id > 0) {
        $db->prepare("INSERT INTO kategoriler (cins_id, ad) VALUES (?, ?)")->execute([$cins_id, $ad]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

// KATEGORİ SİL
if ($_GET['islem'] == 'kategori_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM kategoriler WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = 'ok';
    } else {
        $_SESSION['mesaj'] = 'hata';
    }
    header("Location: kategori-agaci.php");
    exit;
}

/* ===============================
   KUR GÜNCELLE
================================ */
if ($_GET['islem'] == 'kur_guncelle') {

    $paraBirimleri = $_POST['para_birimi'] ?? [];
    $alislar = $_POST['alis_kur'] ?? [];
    $satislar = $_POST['satis_kur'] ?? [];
    $kurlar = $_POST['kur'] ?? [];

    if (!is_array($paraBirimleri) || empty($paraBirimleri)) {
        $_SESSION['mesaj'] = 'hata';
        header("Location: kur.php");
        exit;
    }

    try {
        $db->beginTransaction();

        $upsert = $db->prepare(" 
            INSERT INTO doviz_kurlari (para_birimi, alis_kur, satis_kur, kur, kaynak, son_guncelleme)
            VALUES (?, ?, ?, ?, 'manuel', NOW())
            ON DUPLICATE KEY UPDATE
                alis_kur = VALUES(alis_kur),
                satis_kur = VALUES(satis_kur),
                kur = VALUES(kur),
                kaynak = 'manuel',
                son_guncelleme = NOW()
        ");

        foreach ($paraBirimleri as $pbRaw) {
            $pb = strtoupper(trim((string)$pbRaw));
            if ($pb === '') {
                continue;
            }

            $alis = (float)($alislar[$pb] ?? 0);
            $satis = (float)($satislar[$pb] ?? 0);
            $kur = (float)($kurlar[$pb] ?? 0);

            if ($pb === 'TRY') {
                $alis = 1;
                $satis = 1;
                $kur = 1;
            } else {
                if ($alis <= 0 && $satis > 0) $alis = $satis;
                if ($satis <= 0 && $alis > 0) $satis = $alis;
                if ($kur <= 0) $kur = $satis > 0 ? $satis : $alis;
            }

            if ($alis <= 0 || $satis <= 0 || $kur <= 0) {
                continue;
            }

            $upsert->execute([$pb, $alis, $satis, $kur]);
        }

        // Eski yapıyı bozmayalım: kur tablosunda EUR kuru tutulmaya devam etsin
        $eurKur = $db->prepare("SELECT kur FROM doviz_kurlari WHERE para_birimi='EUR' LIMIT 1");
        $eurKur->execute();
        $eur = (float)$eurKur->fetchColumn();
        if ($eur > 0) {
            $id = $db->query("SELECT id FROM kur LIMIT 1")->fetchColumn();
            if ($id) {
                $db->prepare("UPDATE kur SET kur=? WHERE id=?")->execute([$eur, $id]);
            } else {
                $db->prepare("INSERT INTO kur (kur) VALUES (?)")->execute([$eur]);
            }
        }

        $db->commit();
        $_SESSION['mesaj'] = 'duzenle_ok';
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['mesaj'] = 'hata';
    }

    header("Location: kur.php");
    exit;
}

/* ===============================
   KUR OTOMATİK ÇEK (SUNDOVIZ)
================================ */
if ($_GET['islem'] == 'kur_oto_cek') {

    $sonuc = kur_otomatik_guncelle($db);
    $_SESSION['mesaj'] = !empty($sonuc['ok']) ? 'oto_ok' : 'hata';
    $_SESSION['mesaj_detay'] = (string)($sonuc['message'] ?? '');

    header("Location: kur.php");
    exit;
}


/* ===============================
   TÜM ÜRÜN FİYATLARINI GÜNCELLE
================================ */
if ($_GET['islem'] == 'urun_fiyat_guncelle') {

    try {
        $kurRows = $db->query("SELECT para_birimi, kur FROM doviz_kurlari WHERE para_birimi IN ('TRY','EUR','USD','GBP')")->fetchAll(PDO::FETCH_ASSOC);
        $rates = ['TRY' => 1.0, 'EUR' => 0.0, 'USD' => 0.0, 'GBP' => 0.0];

        foreach ($kurRows as $row) {
            $pb = strtoupper((string)$row['para_birimi']);
            $kur = (float)$row['kur'];
            if (isset($rates[$pb]) && $kur > 0) {
                $rates[$pb] = $kur;
            }
        }

        if ($rates['EUR'] <= 0 && $rates['USD'] <= 0 && $rates['GBP'] <= 0) {
            $_SESSION['mesaj'] = 'hata';
            $_SESSION['mesaj_detay'] = 'Geçerli EUR/USD/GBP kuru bulunamadı.';
            header("Location: kur.php");
            exit;
        }

        $db->beginTransaction();

        $urunler = $db->query("SELECT id, satis_euro, satis_dolar, satis_sterlin FROM urunler WHERE durum = 0")->fetchAll(PDO::FETCH_ASSOC);
        $upd = $db->prepare("UPDATE urunler SET satis_fiyat = ? WHERE id = ?");

        $islenen = 0;
        $guncellenen = 0;
        foreach ($urunler as $u) {
            $id = (int)$u['id'];
            $eur = (float)($u['satis_euro'] ?? 0);
            $usd = (float)($u['satis_dolar'] ?? 0);
            $gbp = (float)($u['satis_sterlin'] ?? 0);
            $mevcutTl = (float)($u['satis_fiyat'] ?? 0);

            $yeniTl = null;
            if ($eur > 0 && $rates['EUR'] > 0) {
                $yeniTl = $eur * $rates['EUR'];
            } elseif ($gbp > 0 && $rates['GBP'] > 0) {
                $yeniTl = $gbp * $rates['GBP'];
            } elseif ($usd > 0 && $rates['USD'] > 0) {
                $yeniTl = $usd * $rates['USD'];
            }

            if ($yeniTl === null) {
                continue;
            }

            $islenen++;
            $yeniTl = round($yeniTl, 2);
            $upd->execute([$yeniTl, $id]);
            if (abs($mevcutTl - $yeniTl) >= 0.01) {
                $guncellenen++;
            }
        }

        $db->commit();

        $_SESSION['mesaj'] = 'fiyat_ok';
        $_SESSION['mesaj_detay'] = $islenen . ' ürün hesaplandı, ' . $guncellenen . ' ürünün TL satış fiyatı değişti.';
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['mesaj'] = 'hata';
        $_SESSION['mesaj_detay'] = 'Ürün fiyatları güncellenirken hata oluştu.';
    }

    // Cache temizle
    $cacheDir = __DIR__ . '/ajax/cacheurunler/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    header("Location: kur.php");
    exit;
}


/* ===============================
   Müşteri Aktar
================================ */

if ($_GET['islem'] != 'musteri_aktar') {
    header("Location: musteri-aktar.php");
    exit;
}

$eski = (int)$_POST['eski_pazarlamaci'];
$yeni = (int)$_POST['yeni_pazarlamaci'];

if ($eski <= 0 || $yeni <= 0 || $eski == $yeni) {
    $_SESSION['mesaj'] = 'hata';
    header("Location: musteri-aktar.php");
    exit;
}

try {
    $db->beginTransaction();

    $db->prepare("\n        UPDATE musteriler \n        SET sorumlu = ? \n        WHERE sorumlu = ?\n    ")->execute([$yeni, $eski]);

    $db->prepare("\n        UPDATE satislar \n        SET satisi_yapan_id = ? \n        WHERE satisi_yapan_id = ?\n    ")->execute([$yeni, $eski]);

    $db->prepare("\n        UPDATE odemeler \n        SET odemeyi_alan = ? \n        WHERE odemeyi_alan = ?\n    ")->execute([$yeni, $eski]);

    $db->commit();
    $_SESSION['mesaj'] = 'ok';

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['mesaj'] = 'hata';
}

header("Location: musteri-aktar.php");
exit;


?>
