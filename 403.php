<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">

<head>

    <meta charset="utf-8" />
    <title>Yasak Erişim | Nextario Muhasebe Programı</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Müşteri takip, stok ,ödeme takip muhasebe programı" name="description" />
    <meta content="Nextario" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Layout config Js -->
    <script src="assets/js/layout.js"></script>
    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
        .brand-panel{background:linear-gradient(135deg,#0e2a47,#1c4e80);position:relative;border-radius:16px;color:#fff;padding:32px;box-shadow:0 12px 32px rgba(13,30,70,.18)}
        .brand-panel .badge{background:#fff;color:#0e2a47}
        .card.corporate{box-shadow:0 12px 32px rgba(13,30,70,.12);border:0;border-radius:16px}
        .btn-primary{background:#1c4e80;border-color:#1c4e80}
        .btn-primary:hover{background:#173f69;border-color:#173f69}
        .form-help{color:#6b7280}
        .meta{color:#6b7280}
    </style>

</head>

<body>

    <!-- auth-page wrapper -->
    <div class="auth-page-wrapper py-5 min-vh-100">

        <!-- auth-page content -->
        <div class="auth-page-content overflow-hidden">
            <div class="container">
                <div class="row gy-4 align-items-center">
                    <div class="col-12 col-lg-6">
                        <div class="brand-panel">
                            <img src="assets/Nextario-b.png" alt="Nextario" height="64">
                            <h2 class="mt-3 mb-2">403 Yasak Erişim</h2>
                            <p class="mb-3">Bu bölümü görüntülemek için gerekli yetkiye sahip değilsiniz.</p>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="ri-shield-check-line me-2"></i> Rol tabanlı erişim denetimi</li>
                                <li class="mb-2"><i class="ri-lock-2-line me-2"></i> Duyarlı veri koruması</li>
                                <li class="mb-2"><i class="ri-customer-service-2-line me-2"></i> Yardım için bizimle iletişime geçin</li>
                            </ul>
                            <div class="mt-3">
                                <span class="badge">Kurumsal</span>
                                <span class="badge ms-2">Güvenli</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card corporate">
                            <div class="card-body p-4">
                                <h5 class="text-primary mb-2">Erişiminiz Engellendi</h5>
                                <p class="form-help mb-3">Olası nedenler:</p>
                                <ul class="text-muted">
                                    <li>Yönetici yetkisi gerektiren bir sayfaya erişmeye çalıştınız.</li>
                                    <li>Oturum süreniz doldu; yeniden giriş yapmanız gerekebilir.</li>
                                    <li>Hesabınızın rolü bu işlemi yapmaya izin vermiyor.</li>
                                </ul>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <a href="index.php" class="btn btn-primary"><i class="ri-login-box-line me-2"></i>Giriş Yap</a>
                                    <a href="panel.php" class="btn btn-outline-secondary"><i class="mdi mdi-home me-2"></i>Panoya Dön</a>
                                    <a href="tel:+905391063431" class="btn btn-outline-secondary"><i class="ri-customer-service-2-line me-2"></i>Destek</a>
                                </div>
                                <div class="meta mt-3">
                                    <span id="meta-id"></span>
                                    <span class="ms-3" id="meta-time"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end auth-page content -->
    </div>
    <!-- end auth-page-wrapper -->

    <script>
        var rid="REQ-"+Math.random().toString(36).slice(2,8).toUpperCase();
        var d=new Date();
        document.getElementById("meta-id").textContent="İstek Kimliği: "+rid;
        document.getElementById("meta-time").textContent="Zaman: "+d.toLocaleString("tr-TR",{hour12:false});
    </script>
</body>

</html>
