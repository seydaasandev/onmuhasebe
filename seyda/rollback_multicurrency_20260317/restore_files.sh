#!/bin/bash
set -e
cd /home/nextario/public_html/eski
cp seyda/rollback_multicurrency_20260317/yeni-satis.php yeni-satis.php
cp seyda/rollback_multicurrency_20260317/satis-duzenle.php satis-duzenle.php
cp seyda/rollback_multicurrency_20260317/sepetim.js sepetim.js
cp seyda/rollback_multicurrency_20260317/islem.php islem.php
cp seyda/rollback_multicurrency_20260317/tum-satislar.php tum-satislar.php
cp seyda/rollback_multicurrency_20260317/satislar.php ajax/satislar.php
echo "Dosyalar geri yüklendi"
