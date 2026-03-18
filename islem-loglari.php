<?php
require "config.php";
require "auth.php";
require "log_helpers.php";

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$role = $stmt->fetchColumn();

if (strtolower((string)$role) === 'user') {
    header("Location: 403.php");
    exit;
}

ensure_islem_loglari_table($db);

$logs = $db->query("SELECT * FROM islem_loglari ORDER BY id DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="tr" data-layout="vertical" data-topbar="dark" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="https://www.nextario.com/eski/assets/images/sidebar/img-4.jpg" data-preloader="enable" data-theme="material" data-theme-colors="#themeColor-02">
<head>
    <meta charset="utf-8" />
    <title>Islem Loglari | Nextario Muhasebe Programi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Islem loglari" name="description" />
    <meta content="Nextario" name="author" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <script src="assets/js/layout.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="layout-wrapper">
<?php require "head.php"; ?>
<?php require "sidebar.php"; ?>
<div class="vertical-overlay"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                        <h4 class="mb-sm-0">Islem Loglari</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="panel.php">Nextario</a></li>
                                <li class="breadcrumb-item active">Islem Loglari</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Son 500 Islem</h5>
                            <span class="badge bg-primary-subtle text-primary"><?= count($logs) ?> kayit</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="islem-loglari" class="display table table-bordered dt-responsive nowrap align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Kullanici</th>
                                            <th>Modul</th>
                                            <th>Islem</th>
                                            <th>Kayit ID</th>
                                            <th>Aciklama</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                                            <td><?= htmlspecialchars($log['user_name']) ?></td>
                                            <td><span class="badge bg-info-subtle text-info text-uppercase"><?= htmlspecialchars($log['modul']) ?></span></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary text-uppercase"><?= htmlspecialchars($log['islem']) ?></span></td>
                                            <td><?= $log['kayit_id'] !== null ? (int)$log['kayit_id'] : '-' ?></td>
                                            <td style="white-space: normal; min-width: 280px;"><?= htmlspecialchars((string)$log['aciklama']) ?></td>
                                            <td><?= htmlspecialchars((string)$log['ip_adresi']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
$(function () {
    $('#islem-loglari').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'print'],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        }
    });
});
</script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/libs/feather-icons/feather.min.js"></script>
<script src="assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="assets/js/plugins.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>