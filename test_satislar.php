<?php
require "config.php";
try {
    $stmt = $db->query("DESCRIBE satislar");
    echo "Satislar tablosu yapısı:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']}: {$row['Type']}\n";
    }
    
    echo "\n";
    $stmt = $db->query("SELECT COUNT(*) FROM satislar WHERE durum = 0 AND siparis = 0");
    $count = $stmt->fetchColumn();
    echo "Aktif satış sayısı: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT id, musteri_id, urun_id, satisi_yapan, satisi_yapan_id FROM satislar WHERE durum = 0 AND siparis = 0 LIMIT 3");
        echo "Örnek kayıtlar:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$row['id']}, Müşteri: {$row['musteri_id']}, Ürün: {$row['urun_id']}, Satisi Yapan: {$row['satisi_yapan']}, Satisi Yapan ID: {$row['satisi_yapan_id']}\n";
        }
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
