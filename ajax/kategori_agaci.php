<?php
session_start();
require "../config.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$tip   = $_GET['tip']    ?? '';
$ustId = (int)($_GET['ust_id'] ?? 0);

switch ($tip) {
    case 'markalar':
        if ($ustId <= 0) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, ad FROM markalar WHERE ana_kategori_id = ? ORDER BY ad");
        $stmt->execute([$ustId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'modeller':
        if ($ustId <= 0) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, ad FROM modeller WHERE marka_id = ? ORDER BY ad");
        $stmt->execute([$ustId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'cinsler':
        if ($ustId <= 0) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, ad FROM cinsler WHERE model_id = ? ORDER BY ad");
        $stmt->execute([$ustId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'kategoriler':
        if ($ustId <= 0) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, ad FROM kategoriler WHERE cins_id = ? ORDER BY ad");
        $stmt->execute([$ustId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        echo json_encode([]);
}
