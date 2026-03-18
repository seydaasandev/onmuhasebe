<?php
session_start();
require "config.php";
require "log_helpers.php";

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

            log_islem(
                $db,
                'oturum',
                'giris',
                (int)$user['id'],
                log_format_pairs([
                    'Kullanici' => $user['namesurname'] ?: $user['username'],
                    'Username' => $user['username'],
                ]),
                [
                    'user_id' => (int)$user['id'],
                    'username' => $user['username'],
                    'namesurname' => $user['namesurname'],
                    'redirect' => $redirect,
                ]
            );

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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --login-ink: #10233a;
            --login-muted: #6a7788;
            --login-accent: #0f7b6c;
            --login-accent-dark: #0b5f54;
            --login-gold: #d6b36d;
            --login-shell: #f3efe7;
            --login-card: #fffdf8;
            --login-line: rgba(16, 35, 58, 0.12);
            --login-shadow: 0 24px 60px rgba(16, 35, 58, 0.14);
        }
        body {
            font-family: 'Manrope', system-ui, sans-serif;
            min-height: 100vh;
            color: var(--login-ink);
            background:
                radial-gradient(circle at top left, rgba(214, 179, 109, 0.18), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 123, 108, 0.18), transparent 26%),
                linear-gradient(135deg, #f7f2e8 0%, #f2f6f8 48%, #eef2f3 100%);
        }
        .login-shell {
            min-height: 100vh;
            padding: 24px;
        }
        .login-frame {
            min-height: calc(100vh - 48px);
            border: 1px solid rgba(255,255,255,0.55);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--login-shadow);
            background: rgba(255,255,255,0.42);
            backdrop-filter: blur(10px);
        }
        .brand-panel {
            position: relative;
            padding: 56px;
            background:
                linear-gradient(160deg, rgba(9, 27, 48, 0.94) 0%, rgba(16, 48, 78, 0.96) 48%, rgba(12, 80, 73, 0.94) 100%),
                url('assets/images/sidebar/img-4.jpg') center/cover;
            color: #f5f1e8;
        }
        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(214, 179, 109, 0.28), transparent 24%),
                linear-gradient(180deg, transparent 0%, rgba(9, 27, 48, 0.18) 100%);
            pointer-events: none;
        }
        .brand-overlay {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        .animate-rise {
            opacity: 0;
            transform: translateY(16px);
            animation: riseIn .8s ease forwards;
        }
        .delay-1 { animation-delay: .08s; }
        .delay-2 { animation-delay: .16s; }
        .delay-3 { animation-delay: .24s; }
        .delay-4 { animation-delay: .32s; }
        .delay-5 { animation-delay: .4s; }
        .brand-logo-wrap {
            position: relative;
            width: fit-content;
            margin-top: 24px;
            padding: 12px;
            border-radius: 22px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.16);
            backdrop-filter: blur(6px);
            overflow: hidden;
        }
        .brand-logo-wrap::after {
            content: "";
            position: absolute;
            inset: -42%;
            background: conic-gradient(from 180deg, transparent, rgba(214, 179, 109, 0.35), transparent 55%);
            animation: spinSlow 6s linear infinite;
            pointer-events: none;
        }
        .brand-logo-card {
            position: relative;
            z-index: 1;
            padding: 16px 20px;
            border-radius: 16px;
            background: rgba(10, 28, 48, 0.84);
            border: 1px solid rgba(255,255,255,0.16);
        }
        .brand-logo-sub {
            margin-top: 8px;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(245, 241, 232, 0.74);
        }
        .brand-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            width: fit-content;
            font-size: 13px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .brand-title {
            font-family: 'Fraunces', serif;
            font-size: clamp(2.4rem, 4vw, 2.3rem);
            line-height: 1.02;
            letter-spacing: -0.03em;
            max-width: 560px;
            margin: 26px 0 18px;
        }
        .brand-copy {
            max-width: 520px;
            color: rgba(245, 241, 232, 0.78);
            font-size: 1.05rem;
            line-height: 1.75;
        }
        .brand-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 36px;
        }
        .metric-card {
            padding: 18px 18px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .metric-label {
            display: block;
            color: rgba(245, 241, 232, 0.64);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }
        .metric-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
        }
        .brand-foot {
            margin-top: auto;
            padding-top: 38px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .brand-pill {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.14);
            color: #f6f1e7;
            font-size: 13px;
            font-weight: 600;
        }
        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px;
            background: linear-gradient(180deg, rgba(255,255,255,0.76) 0%, rgba(255,255,255,0.96) 100%);
        }
        .login-stack {
            width: 100%;
            max-width: 500px;
        }
        .mobile-brand {
            display: none;
            margin-bottom: 18px;
            padding: 22px;
            border-radius: 22px;
            background: linear-gradient(145deg, #123252 0%, #0f7b6c 100%);
            color: #fff8ef;
            box-shadow: 0 18px 40px rgba(16, 35, 58, 0.18);
        }
        .login-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }
        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(15, 123, 108, 0.1);
            color: var(--login-accent-dark);
            font-size: 13px;
            font-weight: 700;
        }
        .login-card {
            border: 1px solid rgba(16, 35, 58, 0.08);
            border-radius: 28px;
            background: var(--login-card);
            box-shadow: 0 18px 44px rgba(16, 35, 58, 0.09);
            overflow: hidden;
        }
        .login-card-body {
            padding: 34px;
        }
        .login-title {
            font-family: 'Fraunces', serif;
            font-size: 2rem;
            line-height: 1.1;
            margin-bottom: 10px;
            color: var(--login-ink);
        }
        .login-subtitle {
            color: var(--login-muted);
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #536273;
            margin-bottom: 10px;
        }
        .form-control {
            height: 56px;
            border-radius: 16px;
            border: 1px solid var(--login-line);
            background: #ffffff;
            box-shadow: none;
            padding-left: 16px;
            font-size: 15px;
        }
        .form-control:focus {
            border-color: rgba(15, 123, 108, 0.5);
            box-shadow: 0 0 0 4px rgba(15, 123, 108, 0.12);
        }
        .password-addon {
            height: 56px;
            width: 56px;
            color: #718093;
        }
        .login-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 8px;
        }
        .login-meta a {
            color: var(--login-accent-dark);
            text-decoration: none;
            font-weight: 700;
        }
        .login-meta a:hover {
            color: var(--login-accent);
        }
        .btn-login {
            height: 58px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--login-accent) 0%, #136f89 100%);
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.02em;
            box-shadow: 0 18px 28px rgba(15, 123, 108, 0.22);
        }
        .btn-login:hover {
            color: #fff;
            background: linear-gradient(135deg, var(--login-accent-dark) 0%, #0f6077 100%);
        }
        .trust-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }
        .trust-box {
            padding: 14px 12px;
            border-radius: 18px;
            border: 1px solid rgba(16, 35, 58, 0.08);
            background: #fff;
            text-align: center;
        }
        .trust-box i {
            font-size: 20px;
            color: var(--login-accent-dark);
        }
        .trust-box span {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #536273;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .footer-links {
            margin-top: 22px;
            text-align: center;
            color: var(--login-muted);
        }
        .footer-links a {
            color: var(--login-accent-dark);
            text-decoration: none;
            font-weight: 700;
        }
        @keyframes riseIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes spinSlow {
            to {
                transform: rotate(360deg);
            }
        }
        @media (max-width: 991.98px) {
            .login-shell {
                padding: 16px;
            }
            .login-frame {
                min-height: calc(100vh - 32px);
            }
            .brand-panel {
                display: none;
            }
            .login-panel {
                padding: 20px;
            }
            .mobile-brand {
                display: block;
            }
            .login-card-body {
                padding: 26px;
            }
        }
        @media (max-width: 575.98px) {
            .login-panel {
                padding: 14px;
            }
            .login-card-body {
                padding: 22px;
            }
            .trust-strip,
            .brand-metrics {
                grid-template-columns: 1fr;
            }
            .login-topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid login-shell">
        <div class="row g-0 login-frame">
            <div class="col-lg-7 brand-panel d-none d-lg-block">
                <div class="brand-overlay">
                    <div class="brand-kicker animate-rise delay-1">
                        <i class="ri-building-line"></i>
                        Nextario Kurumsal Platform
                    </div>
                    <a href="index.php" class="brand-logo-wrap animate-rise delay-2" aria-label="Nextario">
                        <div class="brand-logo-card">
                            <img src="assets/Nextario-b.png" alt="Nextario" height="78">
                            <div class="brand-logo-sub">Enterprise Finance Suite</div>
                        </div>
                    </a>
                    <h1 class="brand-title animate-rise delay-3">Finansal operasyonlarınızı tek merkezden yönetin.</h1>
                    <p class="brand-copy animate-rise delay-4">Satış, stok, tahsilat ve raporlama süreçlerini daha kontrollü, daha izlenebilir ve daha profesyonel bir deneyimle yönetin. Nextario, günlük muhasebe akışını kurumsal standartta sadeleştirir.</p>

                    <div class="brand-metrics animate-rise delay-4">
                        <div class="metric-card">
                            <span class="metric-label">Operasyon</span>
                            <div class="metric-value">Tek panelde stok, satış ve ödeme</div>
                        </div>
                        <div class="metric-card">
                            <span class="metric-label">Kontrol</span>
                            <div class="metric-value">Canlı kur, hesap ve rapor bütünlüğü</div>
                        </div>
                        <div class="metric-card">
                            <span class="metric-label">Güven</span>
                            <div class="metric-value">Yetki, iz kaydı ve kalıcı erişim</div>
                        </div>
                    </div>

                    <div class="brand-foot animate-rise delay-5">
                        <span class="brand-pill">Kurumsal görünüm</span>
                        <span class="brand-pill">Hızlı tahsilat akışı</span>
                        <span class="brand-pill">Profesyonel raporlama</span>
                        <span class="brand-pill">Seyda AŞAN | Web Tasarım ve Yazılım</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5 login-panel">
                <div class="login-stack">
                    <div class="mobile-brand animate-rise delay-1">
                        <img src="assets/Nextario-b.png" alt="Nextario" height="52">
                        <div class="mt-2 small text-uppercase" style="letter-spacing:.08em;opacity:.78;">Enterprise Finance Suite</div>
                        <div class="mt-2 fw-semibold">Kurumsal muhasebe ve stok takibi tek noktada.</div>
                    </div>

                    <div class="login-topbar animate-rise delay-2">
                        <div>
                            <div class="login-badge">
                                <i class="ri-shield-check-line"></i>
                                Guvenli Giris
                            </div>
                        </div>
                        <div class="text-muted small">Yetkili kullanici erisimi</div>
                    </div>

                    <div class="login-card animate-rise delay-3">
                        <div class="login-card-body">
                            <h2 class="login-title">Hesabınıza giriş yapın</h2>
                            <p class="login-subtitle">Kurumsal çalışma alanınıza erişmek için kullanıcı bilgilerinizle oturum açın. Güvenli giriş sonrası panelinize yönlendirilirsiniz.</p>

                            <form method="post" action="index.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı adınızı yazın" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label" for="password-input">Şifre</label>
                                    <div class="position-relative auth-pass-inputgroup">
                                        <input type="password" class="form-control pe-5 password-input" placeholder="Şifrenizi yazın" id="password-input" name="password" required>
                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none password-addon" type="button" id="password-addon">
                                            <i class="ri-eye-fill align-middle"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="login-meta">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" id="auth-remember-check" name="remember" value="1">
                                        <label class="form-check-label" for="auth-remember-check">Beni hatırla</label>
                                    </div>
                                    <a href="tel:+905391063431">Şifre desteği</a>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-login w-100" type="submit">
                                        <i class="ri-login-box-line me-1"></i> Giriş Yap
                                    </button>
                                </div>
                            </form>

                            <div class="trust-strip">
                                <div class="trust-box">
                                    <i class="ri-lock-2-line"></i>
                                    <span>Güvenli Oturum</span>
                                </div>
                                <div class="trust-box">
                                    <i class="ri-line-chart-line"></i>
                                    <span>Rapor Odaklı</span>
                                </div>
                                <div class="trust-box">
                                    <i class="ri-time-line"></i>
                                    <span>Hızlı Erişim</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="footer-links animate-rise delay-4">
                        <p class="mb-1">Destek için <a href="tel:+905391063431">Nextario ile iletişime geçin</a></p>
                        <p class="mb-0">&copy; <script>document.write(new Date().getFullYear())</script> Nextario</p>
                    </div>
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
