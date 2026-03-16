<?php
session_start();
require "config.php";


$term = $_GET['term'] ?? '';
$term = trim($term);

$data = [];

if ($term !== '') {
    $stmt = $db->prepare("
        SELECT id, urun_adi, barkod 
        FROM urunler 
        WHERE durum = 0
          AND (urun_adi LIKE :term OR barkod LIKE :term)
        ORDER BY urun_adi ASC
        LIMIT 20
    ");
    $stmt->execute([
        ':term' => "%$term%"
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'id'   => $row['id'],
            'text' => $row['urun_adi'] . ' (' . $row['barkod'] . ')'
        ];
    }
}

echo json_encode([
    'results' => $data
]);
