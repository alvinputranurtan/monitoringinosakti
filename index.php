<?php
include 'functions/config.php';
include 'functions/functions.php';

// Proteksi halaman: wajib login
if (!isset($_SESSION['user_id'])) {
    header('Location: functions/login.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$pageFile = "pages/$page.php";
if (!file_exists($pageFile)) {
    $pageFile = 'pages/404.php';
    $page = '404';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($page); ?> - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/chart.umd.min.js"></script>
</head>

<body>

    <div class="topbar p-0">
        <div class="container-fluid d-flex align-items-center">
            <div class="toggle-icon d-flex align-items-center p-0">
                <i class="fa-solid fa-bars toggle-sidebar"></i>
            </div>
            <div class="toggle-title d-flex align-items-center"><?php echo ucfirst($page); ?></div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div id="sidebar" class="sidebar">
        <div class="sidebar-header"></div>
        <ul class="nav flex-column">
            <?php
            $menu = [
                'dashboard' => ['Dashboard', 'fa-house'],
                'kontrol' => ['Kontrol', 'fa-sliders-h'],
                // 'perangkat' => ['Perangkat', 'fa-microchip'],
                'grafik' => ['Grafik', 'fa-chart-line'],
                'tentang' => ['Tentang', 'fa-id-card'],
            ];
foreach ($menu as $slug => [$label, $icon]) {
    $active = ($page === $slug) ? 'active' : '';
    echo "
                <li class='nav-item'>
                    <a href='#' class='nav-link menu-link $active' data-page='{$slug}.php' data-title='{$label}'>
                        <i class='fa-solid {$icon} me-2'></i> {$label}
                    </a>
                </li>";
}
?>
            <li class="nav-item">
                <a href="functions/logout.php" class="nav-link">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT DINAMIS -->
    <div id="main-content" class="main-content">
        <?php include $pageFile; ?>
    </div>

    <div id="overlay"></div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/grafik.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>
