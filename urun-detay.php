<?php
require "config.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error' => 'Geçersiz id']); exit; }

$stmt = $db->prepare("SELECT satis_fiyat, satis_euro, kdv, stok FROM urunler WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['error' => 'Ürün bulunamadı']); exit; }

$eurRate = 0.0;
$kurStmt = $db->query("SELECT kur FROM doviz_kurlari WHERE para_birimi='EUR' LIMIT 1");
if ($kurStmt) {
    $eurRate = (float)$kurStmt->fetchColumn();
}

$birimFiyat = (float)($row['satis_euro'] ?? 0);
if ($birimFiyat <= 0 && $eurRate > 0) {
    $birimFiyat = (float)$row['satis_fiyat'] / $eurRate;
}

echo json_encode([
  'birim_fiyat' => (float)$birimFiyat,
  'kdv'         => (float)$row['kdv'],
  'stok'        => (int)$row['stok']
]);
