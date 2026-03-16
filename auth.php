<?php
session_start();
require "config.php";

// Eğer session yoksa ve remember_token cookie varsa kontrol et
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $rawToken  = $_COOKIE['remember_token'];
    $tokenHash = hash('sha256', $rawToken);

    $query = $db->prepare("SELECT id, username FROM users WHERE token = :token LIMIT 1");
    $query->execute(['token' => $tokenHash]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
    } else {
        // Geçersiz cookie → temizle
        setcookie("remember_token", "", time() - 3600, "/");
    }
}

// Eğer hala giriş yoksa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
