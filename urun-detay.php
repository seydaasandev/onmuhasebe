<?php
require "config.php";
session_start();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error' => 'Geçersiz id']); exit; }

$stmt = $db->prepare("SELECT satis_fiyat, kdv, stok FROM urunler WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['error' => 'Ürün bulunamadı']); exit; }

echo json_encode([
  'birim_fiyat' => (float)$row['satis_fiyat'],
  'kdv'         => (float)$row['kdv'],
  'stok'        => (int)$row['stok']
]);
