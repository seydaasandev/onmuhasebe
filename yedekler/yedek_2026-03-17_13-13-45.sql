-- Veritabanı Yedeği: yedek_2026-03-17_13-13-45.sql
-- Tarih: 17.03.2026 13:13:45
-- Veritabanı: nextario_muhasebe

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;


-- Tablo: `ana_kategoriler`
DROP TABLE IF EXISTS `ana_kategoriler`;
CREATE TABLE `ana_kategoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ana_kategoriler` (`id`, `ad`, `created_at`) VALUES ('1', 'Otomobiller', '2026-03-17 09:46:11');
INSERT INTO `ana_kategoriler` (`id`, `ad`, `created_at`) VALUES ('2', 'Traktörler', '2026-03-17 09:46:18');
INSERT INTO `ana_kategoriler` (`id`, `ad`, `created_at`) VALUES ('3', 'İş Makineleri', '2026-03-17 09:46:26');


-- Tablo: `cinsler`
DROP TABLE IF EXISTS `cinsler`;
CREATE TABLE `cinsler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `model_id` (`model_id`),
  CONSTRAINT `cinsler_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `modeller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cinsler` (`id`, `model_id`, `ad`, `created_at`) VALUES ('2', '1', 'Motor Grubu', '2026-03-17 09:48:14');


-- Tablo: `doviz_kurlari`
DROP TABLE IF EXISTS `doviz_kurlari`;
CREATE TABLE `doviz_kurlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `para_birimi` varchar(10) NOT NULL,
  `kur` decimal(18,6) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `alis_kur` decimal(18,6) DEFAULT NULL,
  `satis_kur` decimal(18,6) DEFAULT NULL,
  `kaynak` varchar(64) DEFAULT NULL,
  `son_guncelleme` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `para_birimi` (`para_birimi`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `doviz_kurlari` (`id`, `para_birimi`, `kur`, `updated_at`, `alis_kur`, `satis_kur`, `kaynak`, `son_guncelleme`) VALUES ('1', 'TRY', '1.000000', '2026-03-17 11:19:26', '1.000000', '1.000000', 'sundoviz', '2026-03-17 08:19:26');
INSERT INTO `doviz_kurlari` (`id`, `para_birimi`, `kur`, `updated_at`, `alis_kur`, `satis_kur`, `kaynak`, `son_guncelleme`) VALUES ('2', 'EUR', '50.950000', '2026-03-17 11:19:26', '50.500000', '50.950000', 'sundoviz', '2026-03-17 08:55:35');
INSERT INTO `doviz_kurlari` (`id`, `para_birimi`, `kur`, `updated_at`, `alis_kur`, `satis_kur`, `kaynak`, `son_guncelleme`) VALUES ('3', 'USD', '44.350000', '2026-03-17 11:19:26', '44.100000', '44.350000', 'sundoviz', '2026-03-17 08:55:35');
INSERT INTO `doviz_kurlari` (`id`, `para_birimi`, `kur`, `updated_at`, `alis_kur`, `satis_kur`, `kaynak`, `son_guncelleme`) VALUES ('4', 'GBP', '59.000000', '2026-03-17 11:19:26', '58.600000', '59.000000', 'sundoviz', '2026-03-17 08:55:35');


-- Tablo: `kategoriler`
DROP TABLE IF EXISTS `kategoriler`;
CREATE TABLE `kategoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cins_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cins_id` (`cins_id`),
  CONSTRAINT `kategoriler_ibfk_1` FOREIGN KEY (`cins_id`) REFERENCES `cinsler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kategoriler` (`id`, `cins_id`, `ad`, `created_at`) VALUES ('1', '2', 'Motor Kulağı', '2026-03-17 09:48:30');


-- Tablo: `kur`
DROP TABLE IF EXISTS `kur`;
CREATE TABLE `kur` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kur` decimal(10,4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `kur` (`id`, `kur`) VALUES ('1', '50.9500');


-- Tablo: `markalar`
DROP TABLE IF EXISTS `markalar`;
CREATE TABLE `markalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ana_kategori_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ana_kategori_id` (`ana_kategori_id`),
  CONSTRAINT `markalar_ibfk_1` FOREIGN KEY (`ana_kategori_id`) REFERENCES `ana_kategoriler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `markalar` (`id`, `ana_kategori_id`, `ad`, `created_at`) VALUES ('1', '1', 'Toyota', '2026-03-17 09:46:34');
INSERT INTO `markalar` (`id`, `ana_kategori_id`, `ad`, `created_at`) VALUES ('3', '1', 'Suzuki', '2026-03-17 09:46:55');


-- Tablo: `modeller`
DROP TABLE IF EXISTS `modeller`;
CREATE TABLE `modeller` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `marka_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `marka_id` (`marka_id`),
  CONSTRAINT `modeller_ibfk_1` FOREIGN KEY (`marka_id`) REFERENCES `markalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `modeller` (`id`, `marka_id`, `ad`, `created_at`) VALUES ('1', '1', 'Corolla', '2026-03-17 09:47:10');


-- Tablo: `muhasebe`
DROP TABLE IF EXISTS `muhasebe`;
CREATE TABLE `muhasebe` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `musteri_id` int(10) unsigned NOT NULL,
  `fatura_no` varchar(100) DEFAULT NULL,
  `fatura_tutari` decimal(15,2) NOT NULL DEFAULT 0.00,
  `islemi_yapan_id` int(10) unsigned NOT NULL,
  `islem_no` varchar(50) NOT NULL,
  `indirim_toplami` decimal(15,2) NOT NULL DEFAULT 0.00,
  `siparis` tinyint(1) NOT NULL DEFAULT 0,
  `ISLEMSEBEP` varchar(10) NOT NULL DEFAULT 'F',
  `tarih` datetime NOT NULL,
  `durum` tinyint(1) NOT NULL DEFAULT 0,
  `fatura_durum` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_islem_no` (`islem_no`),
  KEY `idx_musteri_id` (`musteri_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('5', '8', '1', '118.00', '1', '2645890371', '0.00', '0', 'F', '2026-03-16 18:46:40', '0', '0', '2026-03-16 18:46:40', '2026-03-16 18:46:40');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('6', '8', '2', '68.00', '1', '2871965034', '50.00', '0', 'F', '2026-03-16 18:49:12', '0', '0', '2026-03-16 18:49:12', '2026-03-16 18:49:12');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('7', '8', '3', '234.00', '1', '9275306841', '0.00', '0', 'F', '2026-03-16 18:50:59', '0', '1', '2026-03-16 18:50:59', '2026-03-16 18:57:21');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('8', '8', '47', '1160.00', '1', '3049182675', '0.00', '0', 'F', '2026-03-17 10:07:01', '0', '0', '2026-03-17 10:07:01', '2026-03-17 10:07:01');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('9', '8', '33', '118.00', '1', '2397418560', '0.00', '0', 'F', '2026-03-17 10:14:46', '0', '0', '2026-03-17 10:14:46', '2026-03-17 10:14:46');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('10', '8', '888', '684.40', '1', '4650781329', '0.00', '0', 'F', '2026-03-17 10:47:12', '0', '0', '2026-03-17 10:47:12', '2026-03-17 10:47:12');
INSERT INTO `muhasebe` (`id`, `musteri_id`, `fatura_no`, `fatura_tutari`, `islemi_yapan_id`, `islem_no`, `indirim_toplami`, `siparis`, `ISLEMSEBEP`, `tarih`, `durum`, `fatura_durum`, `created_at`, `updated_at`) VALUES ('11', '8', '55', '13.43', '1', '1508964732', '0.00', '0', 'F', '2026-03-17 10:58:52', '0', '0', '2026-03-17 10:58:52', '2026-03-17 10:58:52');


-- Tablo: `musteriler`
DROP TABLE IF EXISTS `musteriler`;
CREATE TABLE `musteriler` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `musteri_adi` varchar(255) NOT NULL,
  `yetkili` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `sorumlu` int(11) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `sehir` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `durum` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('1', 'seyda aşan', 'Ahmet Yılmazu', '+905551112233', 'Sarı Çizmeli Sokak Ali Demir Aptu', '2', 'Yılmazuuı', 'Antep', '2025-12-15 12:49:40', '2026-03-16 18:46:10', '1');
INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('2', 'ABC Ticaretu', 'Ahmet Yılmazu', '+357 05391063431', 'Sarı Çizmeli Sokak Ali Demir Apt', '12', 'Yılmazuuı', 'İstanbul', '2025-12-15 13:58:23', '2026-03-16 18:46:05', '1');
INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('5', 'Test', 'test', '+357 05391063431', 'Sarı Çizmeli Sokak Ali Demir Apt', '2', 'test', 'İstanbul', '2025-12-18 20:10:01', '2026-03-16 18:46:16', '1');
INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('6', 'test 55', 'test 55', '+357 05391063431', 'Sarı Çizmeli Sokak Ali Demir Apt', '2', 'test', 'İstanbul', '2025-12-22 16:58:46', '2026-03-16 18:46:19', '1');
INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('7', 'seyda aşan', 'Ahmet Yılmazu', '+357 05391063431', 'Sarı Çizmeli Sokak Ali Demir Apt', '12', NULL, 'İstanbul', '2026-03-16 14:02:40', '2026-03-16 18:46:13', '1');
INSERT INTO `musteriler` (`id`, `musteri_adi`, `yetkili`, `telefon`, `adres`, `sorumlu`, `kategori`, `sehir`, `created_at`, `updated_at`, `durum`) VALUES ('8', 'Seyda AŞAN', 'Seyda AŞAN', '+905391063431', 'KALEİÇİ MAH. ÖKTEN SK. NO: 8 ÇATALCA / İSTANBUL', '1', NULL, 'İstanbul', '2026-03-16 18:45:58', NULL, '0');


-- Tablo: `odemeler`
DROP TABLE IF EXISTS `odemeler`;
CREATE TABLE `odemeler` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `musteri_id` int(10) unsigned NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `makbuz_no` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `odemeyi_alan` int(10) unsigned NOT NULL,
  `tarih` datetime NOT NULL DEFAULT current_timestamp(),
  `odeme_turu` enum('Nakit','Çek','Kredi Kartı','Havale') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `durum` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_odeme_musteri` (`musteri_id`),
  KEY `fk_odeme_user` (`odemeyi_alan`),
  CONSTRAINT `fk_odeme_musteri` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_odeme_user` FOREIGN KEY (`odemeyi_alan`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `odemeler` (`id`, `musteri_id`, `tutar`, `makbuz_no`, `aciklama`, `odemeyi_alan`, `tarih`, `odeme_turu`, `created_at`, `updated_at`, `durum`) VALUES ('19', '8', '300.00', '123', 'test', '13', '2026-03-16 18:59:05', 'Nakit', '2026-03-16 18:59:05', '2026-03-16 18:59:05', '0');
INSERT INTO `odemeler` (`id`, `musteri_id`, `tutar`, `makbuz_no`, `aciklama`, `odemeyi_alan`, `tarih`, `odeme_turu`, `created_at`, `updated_at`, `durum`) VALUES ('20', '8', '500.00', '444', '444', '13', '2026-03-17 11:12:50', 'Nakit', '2026-03-17 11:12:50', '2026-03-17 11:12:50', '0');


-- Tablo: `satislar`
DROP TABLE IF EXISTS `satislar`;
CREATE TABLE `satislar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `musteri_id` int(11) NOT NULL,
  `satisi_yapan_id` int(11) DEFAULT NULL,
  `urun_id` int(11) NOT NULL,
  `adet` int(11) NOT NULL DEFAULT 1,
  `para_birimi` varchar(10) NOT NULL DEFAULT 'TRY',
  `kdv_toplami` decimal(10,2) NOT NULL DEFAULT 0.00,
  `indirim_toplami` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tutar` decimal(10,2) NOT NULL DEFAULT 0.00,
  `genel_tutar` decimal(10,2) NOT NULL,
  `birim_fiyat` decimal(10,2) NOT NULL,
  `islem_no` varchar(10) NOT NULL,
  `fatura_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tarih` datetime NOT NULL DEFAULT current_timestamp(),
  `durum` int(11) NOT NULL DEFAULT 0,
  `fatura_durum` int(11) DEFAULT 0,
  `print` int(11) NOT NULL DEFAULT 0,
  `siparis` int(11) NOT NULL DEFAULT 0,
  `doviz_kuru` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `birim_fiyat_doviz` decimal(12,2) DEFAULT NULL,
  `tutar_doviz` decimal(12,2) DEFAULT NULL,
  `kdv_toplami_doviz` decimal(12,2) DEFAULT NULL,
  `indirim_toplami_doviz` decimal(12,2) DEFAULT NULL,
  `genel_tutar_doviz` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('59', '8', '1', '32', '1', 'TRY', '18.00', '0.00', '100.00', '118.00', '100.00', '2645890371', '1', '2026-03-16 18:46:40', '0', '0', '0', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('60', '8', '1', '32', '1', 'TRY', '18.00', '50.00', '100.00', '68.00', '100.00', '2871965034', '2', '2026-03-16 18:49:12', '0', '0', '0', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('61', '8', '1', '32', '2', 'TRY', '36.00', '0.00', '200.00', '236.00', '100.00', '9275306841', '3', '2026-03-16 18:50:59', '0', '0', '1', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('62', '8', '1', '33', '1', 'TRY', '16.00', '0.00', '100.00', '116.00', '100.00', '9275306841', '3', '2026-03-16 18:50:59', '0', '0', '1', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('63', '8', '1', '33', '1', 'EUR', '160.00', '0.00', '1000.00', '1160.00', '1000.00', '3049182675', '47', '2026-03-17 10:07:01', '0', '0', '0', '0', '10.000000', '100.00', '100.00', '16.00', '0.00', '116.00');
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('64', '8', '1', '32', '1', 'TRY', '18.00', '0.00', '100.00', '118.00', '100.00', '2397418560', '33', '2026-03-17 10:14:46', '0', '0', '0', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('65', '8', '1', '34', '1', 'TRY', '94.40', '0.00', '590.00', '684.40', '590.00', '4650781329', '888', '2026-03-17 10:47:12', '0', '0', '0', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `satislar` (`id`, `musteri_id`, `satisi_yapan_id`, `urun_id`, `adet`, `para_birimi`, `kdv_toplami`, `indirim_toplami`, `tutar`, `genel_tutar`, `birim_fiyat`, `islem_no`, `fatura_no`, `tarih`, `durum`, `fatura_durum`, `print`, `siparis`, `doviz_kuru`, `birim_fiyat_doviz`, `tutar_doviz`, `kdv_toplami_doviz`, `indirim_toplami_doviz`, `genel_tutar_doviz`) VALUES ('66', '8', '1', '34', '1', 'TRY', '1.85', '0.00', '11.58', '13.43', '11.58', '1508964732', '55', '2026-03-17 10:58:52', '0', '0', '0', '0', '1.000000', NULL, NULL, NULL, NULL, NULL);


-- Tablo: `urunler`
DROP TABLE IF EXISTS `urunler`;
CREATE TABLE `urunler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `urun_adi` varchar(255) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `cins` varchar(100) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `raf_bolumu` varchar(100) DEFAULT NULL,
  `marka` varchar(100) DEFAULT NULL,
  `kdv` decimal(5,2) NOT NULL DEFAULT 0.00,
  `barkod` varchar(100) DEFAULT NULL,
  `satis_fiyat` decimal(10,2) DEFAULT 0.00,
  `satis_euro` decimal(10,2) DEFAULT NULL,
  `satis_dolar` decimal(10,2) DEFAULT NULL,
  `satis_sterlin` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `durum` int(11) NOT NULL DEFAULT 0,
  `web` tinyint(1) NOT NULL DEFAULT 0,
  `resim` varchar(255) DEFAULT NULL,
  `ana_kategori_id` int(11) DEFAULT NULL,
  `marka_id` int(11) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `cins_id` int(11) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `urunler` (`id`, `urun_adi`, `stok`, `cins`, `kategori`, `raf_bolumu`, `marka`, `kdv`, `barkod`, `satis_fiyat`, `satis_euro`, `satis_dolar`, `satis_sterlin`, `created_at`, `durum`, `web`, `resim`, `ana_kategori_id`, `marka_id`, `model_id`, `cins_id`, `kategori_id`) VALUES ('32', 'Test Ürün', '51', 'Motor Grubu', 'Motor Kulağı', 'A879', 'Toyota', '18.00', '111', '100.00', NULL, NULL, NULL, '2026-03-16 18:45:39', '0', '1', '', '1', '1', '1', '2', '1');
INSERT INTO `urunler` (`id`, `urun_adi`, `stok`, `cins`, `kategori`, `raf_bolumu`, `marka`, `kdv`, `barkod`, `satis_fiyat`, `satis_euro`, `satis_dolar`, `satis_sterlin`, `created_at`, `durum`, `web`, `resim`, `ana_kategori_id`, `marka_id`, `model_id`, `cins_id`, `kategori_id`) VALUES ('33', 'Test Ürün1', '28', 'Motor Grubu', 'Motor Kulağı', 'A873', 'Toyota', '16.00', '12', '100.00', NULL, NULL, NULL, '2026-03-16 18:50:35', '0', '1', 'resimler/urun_20260316_204327_472ffafb.png', '1', '1', '1', '2', '1');
INSERT INTO `urunler` (`id`, `urun_adi`, `stok`, `cins`, `kategori`, `raf_bolumu`, `marka`, `kdv`, `barkod`, `satis_fiyat`, `satis_euro`, `satis_dolar`, `satis_sterlin`, `created_at`, `durum`, `web`, `resim`, `ana_kategori_id`, `marka_id`, `model_id`, `cins_id`, `kategori_id`) VALUES ('34', 'Döviz Test', '28', 'Motor Grubu', 'Motor Kulağı', 'A8794', 'Toyota', '16.00', '34343', '590.00', '11.58', '13.30', '10.00', '2026-03-17 10:33:20', '0', '1', 'resimler/urun_20260317_073320_4d01f6b5.jpg', '1', '1', '1', '2', '1');


-- Tablo: `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `namesurname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `durum` int(11) NOT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique` (`username`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `namesurname`, `password`, `email`, `phone`, `last_login`, `role`, `durum`, `token`) VALUES ('1', 'admin', 'Seyda AŞAN3', '$2y$10$1kJV2w9V8ocI6IdFd6XkTeqFAflJLZk8PfoHNHVe1mjYJXqJ2n72q', 'info@seydaasan.com.tr', '+905391063431', '2026-03-17 16:07:03', 'admin', '0', NULL);
INSERT INTO `users` (`id`, `username`, `namesurname`, `password`, `email`, `phone`, `last_login`, `role`, `durum`, `token`) VALUES ('13', 'Gunkut', 'Günküt Muhasebe', '$2y$10$Cn.VCopiOn0autvdpsgp2.CcgPFdiHwqJEWG8WUQQSzG1Ew3cAOTW', 'gunkut1@gmail.com', '+905391063431', '2026-03-16 18:55:04', 'admin', '0', NULL);

SET FOREIGN_KEY_CHECKS=1;
