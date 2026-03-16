<?php
require "../config.php";
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT fiyat, kdv FROM urunler WHERE id = ? AND durum = 0");
    $stmt->execute([$id]);
    $urun = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($urun) {
        echo json_encode([
            'success' => true,
            'fiyat' => (float)$urun['fiyat'],
            'kdv' => (float)$urun['kdv']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?>