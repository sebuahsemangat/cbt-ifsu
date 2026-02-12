<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CBT Simmulation - Bimbel Lita Sumedang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 250px; }
        body { background-color: #f4f7f6; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #212529; color: white; transition: 0.3s; z-index: 1000; }
        .main-content { margin-left: var(--sidebar-width); transition: 0.3s; padding: 20px; }
        .nav-link { color: rgba(255,255,255,0.7); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        
        @media (max-width: 991.98px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>
    
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="p-3 text-center">
        <h5 class="fw-bold">CBT SIMULATION</h5>
        <hr>
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
                <i class="bi bi-book me-2"></i> Referensi
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
        <li class="nav-item mt-4">
            <a href="../logout.php" class="nav-link mb-2 rounded text-danger">
                <i class="bi bi-box-arrow-left me-2"></i> Keluar
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4 d-lg-none">
        <div class="container-fluid">
            <button class="btn btn-outline-dark" id="btn-toggle"><i class="bi bi-list"></i> Menu</button>
            <span class="navbar-text fw-bold">CBT SIMULATION</span>
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