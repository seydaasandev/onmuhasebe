-- Multi-currency geri alma SQL
ALTER TABLE satislar DROP COLUMN IF EXISTS doviz_kuru;
ALTER TABLE satislar DROP COLUMN IF EXISTS birim_fiyat_doviz;
ALTER TABLE satislar DROP COLUMN IF EXISTS tutar_doviz;
ALTER TABLE satislar DROP COLUMN IF EXISTS kdv_toplami_doviz;
ALTER TABLE satislar DROP COLUMN IF EXISTS indirim_toplami_doviz;
ALTER TABLE satislar DROP COLUMN IF EXISTS genel_tutar_doviz;
DROP TABLE IF EXISTS doviz_kurlari;
