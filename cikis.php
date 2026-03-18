<?php
session_start();
require "config.php";
require "log_helpers.php";

// Eğer kullanıcı giriş yaptıysa token'ı da sıfırla
if (isset($_SESSION['user_id'])) {
    $logUserStmt = $db->prepare("SELECT username, namesurname FROM users WHERE id = :id LIMIT 1");
    $logUserStmt->execute(['id' => $_SESSION['user_id']]);
    $logUser = $logUserStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    log_islem(
        $db,
        'oturum',
        'cikis',
        (int)$_SESSION['user_id'],
        log_format_pairs([
            'Kullanici' => ($logUser['namesurname'] ?? '') ?: ($logUser['username'] ?? ''),
            'Username' => $logUser['username'] ?? '',
        ]),
        [
            'user_id' => (int)$_SESSION['user_id'],
            'username' => $logUser['username'] ?? null,
            'namesurname' => $logUser['namesurname'] ?? null,
        ]
    );

    $update = $db->prepare("UPDATE users SET token = NULL WHERE id = :id");
    $update->execute(['id' => $_SESSION['user_id']]);
}

// Session'ı tamamen bitir
$_SESSION = [];
session_unset();
session_destroy();

// Remember me cookie'yi temizle
if (isset($_COOKIE['remember_token'])) {
    setcookie("remember_token", "", time() - 3600, "/");
}

// Login sayfasına yönlendir
header("Location: index.php");
exit;
?>
