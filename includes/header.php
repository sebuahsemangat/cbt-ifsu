<?php
// Fetch settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $app_settings = [];
    while ($row = $stmt->fetch()) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Fail silently or use defaults if table doesn't exist yet
    $app_settings = [];
}

$site_app_name = $app_settings['app_name'] ?? 'CBT Simulation';
$site_bimbel_name = $app_settings['bimbel_name'] ?? 'Bimbel Lita Sumedang';
$site_bimbel_logo = $app_settings['bimbel_logo'] ?? 'logo-bimbel.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= $site_app_name ?> - <?= $site_bimbel_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --sidebar-width: 250px; 
            --primary-teal: #3a797a;
            --mint-green: #a2d9c1;
            --mint-light: #f0fdf4;
        }
        body { background-color: #f0f4f4; font-family: 'Poppins', sans-serif; }
        .sidebar { 
            width: var(--sidebar-width); 
            height: 100vh; 
            position: fixed; 
            background: var(--primary-teal); 
            color: white; 
            transition: 0.3s; 
            z-index: 1000; 
            box-shadow: 4px 0 15px rgba(58, 121, 122, 0.1);
        }
        .main-content { margin-left: var(--sidebar-width); transition: 0.3s; padding: 20px; }
        .nav-link { 
            color: rgba(255,255,255,0.8); 
            font-weight: 500;
            padding: 12px 15px;
            margin-bottom: 5px;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active { 
            color: white; 
            background: rgba(255,255,255,0.15); 
            transform: translateX(5px);
        }
        .nav-link.active {
            border-left: 4px solid var(--mint-green);
            background: rgba(255,255,255,0.2);
        }
        .card { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .btn-success { background-color: #3fb27f; border: none; }
        .btn-success:hover { background-color: #36a374; }
        .btn-primary { background-color: var(--primary-teal); border: none; }
        .btn-primary:hover { background-color: #2d6161; }
        
        @media (max-width: 991.98px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>
    
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="p-4 text-center">
        <img src="../assets/img/<?= $site_bimbel_logo ?>" alt="Logo" class="rounded-circle bg-white p-1 mb-3 shadow-sm" style="width: 70px; height: 70px; object-fit: contain;">
        <h6 class="fw-bold mb-0" style="letter-spacing: 1px;"><?= strtoupper($site_app_name) ?></h6>
        <small class="opacity-75"><?= $site_bimbel_name ?></small>
        <hr class="bg-white opacity-25 mt-3">
    </div>
    <ul class="nav flex-column px-2">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link mb-2 rounded <?= !isset($_GET['page']) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=exams" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'exams' ? 'active' : '' ?>">
                <i class="bi bi-book me-2"></i> Ujian
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=references" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'references' ? 'active' : '' ?>">
                <i class="bi bi-journal-text me-2"></i> Referensi
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=students" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'students' ? 'active' : '' ?>">
                <i class="bi bi-people me-2"></i> Data Siswa
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=scores" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'scores' ? 'active' : '' ?>">
                <i class="bi bi-graph-up me-2"></i> Data Nilai
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=admins" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'admins' ? 'active' : '' ?>">
                <i class="bi bi-person-lock me-2"></i> Manajemen Admin
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=settings" class="nav-link mb-2 rounded <?= ($_GET['page'] ?? '') == 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear me-2"></i> Pengaturan
            </a>
        </li>
        <li class="nav-item mt-4">
            <a href="../logout.php" class="nav-link mb-2 rounded text-white bg-danger bg-opacity-75 mx-2 text-center shadow-sm">
                <i class="bi bi-box-arrow-left me-2"></i> Keluar
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4 d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-outline-dark" id="btn-toggle"><i class="bi bi-list"></i> Menu</button>
            <span class="navbar-text fw-bold"><?= strtoupper($site_app_name) ?></span>
        </div>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>