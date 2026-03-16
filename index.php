<?php
session_start();
require "config.php";

$alert = ""; // SweetAlert mesajı için değişken
$redirect = "panel.php"; // varsayılan yönlendirme

// Redirect parametresini GET veya POST'tan al
if (!empty($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
} elseif (!empty($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Kullanıcıyı çek
    $query = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $query->execute(['username' => $username]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {

            // giriş başarılı
            $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $update->execute(['id' => $user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['username'] = $user['namesurname'];

            // Remember me seçiliyse cookie oluştur
          if (!empty($_POST['remember'])) {
    // Rastgele token üret
    $rawToken  = bin2hex(random_bytes(32)); // 64 karakter
    $tokenHash = hash('sha256', $rawToken); // hashlenmiş hali

    // Cookie’ye düz token yaz (kullanıcıda saklanacak)
    setcookie("remember_token", $rawToken, time() + (86400 * 30), "/", "", true, true);

    // Veritabanına hash yaz
    $updateToken = $db->prepare("UPDATE users SET token = :token WHERE id = :id");
    $updateToken->execute([
        'token' => $tokenHash,
        'id'    => $user['id']
    ]);
}

            $alert = "success"; // SweetAlert için flag

        } else {
            $alert = "wrong_password"; // Hatalı şifre
        }
    } else {
        $alert = "user_not_found"; // Kullanıcı yok
    }
}
?>

<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">

<head>
    <meta charset="utf-8" />
    <title>Giriş Yap | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />

    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <script src="assets/js/layout.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif}
        .brand-panel{background:linear-gradient(135deg,#0e2a47,#1c4e80);position:relative;padding:64px}
        .brand-panel:before{content:"";position:absolute;inset:0;background:url('assets/images/sidebar/img-4.jpg') center/cover;opacity:.15}
        .brand-overlay{position:relative;display:flex;flex-direction:column;min-height:100%}
        .brand-overlay .lead{max-width:520px}
        .card.corporate{box-shadow:0 10px 30px rgba(13,30,70,.08);border:0;border-radius:16px}
        .btn-primary{background:#1c4e80;border-color:#1c4e80}
        .btn-primary:hover{background:#173f69;border-color:#173f69}
        .form-control{border-radius:10px}
        .footer-links a{color:#6c757d;text-decoration:none}
        .footer-links a:hover{color:#1c4e80}
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid min-vh-100 d-flex align-items-stretch p-0">
        <div class="col-lg-6 brand-panel text-white d-none d-lg-block">
            <div class="brand-overlay">
                <a href="index.php" class="d-inline-block">
                    <img src="assets/Nextario-b.png" alt="Nextario" height="88">
                </a>
                <h1 class="display-6 fw-bold mt-4">Nextario Ön Muhasebe</h1>
                <p class="lead opacity-75">Kurumsal işletmeler için güvenli, hızlı ve modern çözüm.</p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-2"><i class="ri-shield-check-line me-2"></i> Veri güvenliği</li>
                    <li class="mb-2"><i class="ri-bar-chart-2-line me-2"></i> Akıllı raporlama</li>
                    <li class="mb-2"><i class="ri-customer-service-2-line me-2"></i> 7/24 destek</li>
                </ul>
                <div class="mt-auto pt-4">
                    <span class="badge bg-light text-dark me-2">ISO 27001</span>
                    <span class="badge bg-light text-dark">GDPR uyumlu</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-lg-5">
            <div class="w-100" style="max-width: 460px;">
                <div class="text-center mb-4">
                    <h5 class="text-primary mb-1">Tekrar Hoşgeldiniz</h5>
                    <p class="text-muted">Hesabınıza güvenle giriş yapın.</p>
                </div>
                <div class="card corporate">
                    <div class="card-body p-4">
                        <form method="post" action="index.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adınız</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı adınızı giriniz." required>
                            </div>
                            <div class="mb-3">
                                <div class="float-end">
                                    <a href="tel:+905391063431" class="text-muted">Şifreni mi unuttun?</a>
                                </div>
                                <label class="form-label" for="password-input">Şifreniz</label>
                                <div class="position-relative auth-pass-inputgroup mb-3">
                                    <input type="password" class="form-control pe-5 password-input" placeholder="Şifrenizi giriniz." id="password-input" name="password" required>
                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon">
                                        <i class="ri-eye-fill align-middle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auth-remember-check" name="remember" value="1">
                                <label class="form-check-label" for="auth-remember-check">Beni Hatırla</label>
                            </div>
                            <div class="mt-4">
                                <button class="btn btn-primary w-100" type="submit">Giriş Yap</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mt-4 text-center footer-links">
                    <p class="mb-1">Sorun mu yaşıyorsunuz? <a href="tel:+905391063431" class="fw-semibold">Bize ulaşın</a></p>
                    <p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> Nextario</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="assets/js/plugins.js"></script>
    <script src="assets/js/pages/password-addon.init.js"></script>

    <?php if ($alert == "success") { ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Giriş Başarılı!',
                text: 'Yönlendiriliyorsunuz...',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = "<?php echo $redirect; ?>";
            });
        </script>
    <?php } elseif ($alert == "wrong_password") { ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Hatalı Şifre!',
                text: 'Lütfen şifrenizi tekrar kontrol edin.'
            });
        </script>
    <?php } elseif ($alert == "user_not_found") { ?>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Kullanıcı Bulunamadı!',
                text: 'Kullanıcı adı yanlış.'
            });
        </script>
    <?php } ?>
   </body>
   </html>
