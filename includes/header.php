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
    <!-- Local Fonts: Plus Jakarta Sans & Poppins -->
    <link href="../assets/vendor/fonts/fonts.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    
    <!-- MathJax 3 (Formula & Fractions Renderer) - Works Offline on LAN -->
    <script>
    window.MathJax = {
        chtml: {
            fontURL: 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/output/chtml/fonts/woff-v2'
        },
        tex: {
            inlineMath: [['\\(', '\\)'], ['$', '$']],
            displayMath: [['\\[', '\\]'], ['$$', '$$']],
            processEscapes: true
        },
        options: {
            skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
            ignoreHtmlClass: 'tex2jax_ignore|ql-container|ql-editor',
            processHtmlClass: 'tex2jax_process'
        },
        startup: {
            pageReady: () => {
                return MathJax.startup.defaultPageReady();
            }
        }
    };
    </script>
    <script src="../assets/vendor/mathjax/tex-mml-chtml.js" id="MathJax-script" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';"></script>
    
    <style>
        :root { 
            --sidebar-width: 260px; 
            
            /* Identitas Brand Sekolah: SMK Informatika Sumedang */
            --brand-primary: #1e40af;        /* Royal Blue */
            --brand-primary-hover: #1d4ed8;  /* Vibrant Blue */
            --brand-primary-dark: #172554;   /* Deep Blue */
            --brand-primary-subtle: #eff6ff; /* Soft Ice Blue */
            
            /* Aksen Kuning Emas dari Logo Sekolah */
            --brand-accent: #f59e0b;         /* Golden Amber */
            --brand-accent-hover: #d97706;   /* Deep Gold */
            --brand-accent-light: #fef3c7;   /* Warm Pale Gold */
            --brand-accent-glow: rgba(245, 158, 11, 0.28);
            
            /* Tema Sidebar Deep Navy */
            --sidebar-bg: linear-gradient(180deg, #0d2347 0%, #08152e 100%);
            --sidebar-border: rgba(255, 255, 255, 0.08);
            
            /* Canvas & Surface */
            --canvas-bg: #f8fafc;
            --surface-card: #ffffff;
            --border-subtle: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
        }

        body { 
            background-color: var(--canvas-bg); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-body);
            -webkit-font-smoothing: antialiased;
        }

        /* MathJax Styling: Rata tengah & sejajar teks */
        mjx-container { display: inline-block !important; margin: 0 3px !important; vertical-align: middle !important; }
        mjx-container[jax="CHTML"][display="true"] { display: block !important; margin: 0.75rem 0 !important; }

        /* Styling khusus tabel stimulus/referensi dari soal (tidak mempengaruhi tabel admin) */
        table.table-stimulus {
            width: auto !important;
            min-width: 45% !important;
            max-width: 100% !important;
            margin: 8px auto !important;
            border-collapse: collapse !important;
            background-color: #ffffff;
        }
        table.table-stimulus th, table.table-stimulus td {
            border: 1px solid #cbd5e1 !important;
            padding: 6px 12px !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        table.table-stimulus th {
            background-color: #f1f5f9 !important;
            font-weight: 600 !important;
            color: #1e293b;
        }

        /* Sidebar Styling */
        .sidebar { 
            width: var(--sidebar-width); 
            height: 100vh; 
            position: fixed; 
            background: var(--sidebar-bg); 
            color: white; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            z-index: 1000; 
            box-shadow: 4px 0 25px rgba(13, 35, 71, 0.16);
            border-right: 1px solid var(--sidebar-border);
            overflow-y: auto;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .sidebar-brand-box {
            padding: 24px 16px 18px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: radial-gradient(circle at 50% 20%, rgba(30, 64, 175, 0.25) 0%, transparent 70%);
        }

        .sidebar-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            background: #ffffff;
            border-radius: 50%;
            padding: 5px;
            border: 2.5px solid var(--brand-accent);
            box-shadow: 0 0 16px var(--brand-accent-glow), 0 4px 10px rgba(0, 0, 0, 0.25);
            transition: transform 0.25s ease;
        }
        .sidebar-logo:hover {
            transform: scale(1.05);
        }

        .sidebar-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #ffffff;
            margin-top: 12px;
            margin-bottom: 2px;
        }

        .sidebar-subtitle {
            font-size: 0.78rem;
            color: #94a3b8;
            display: block;
        }

        .sidebar-badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }

        .main-content { 
            margin-left: var(--sidebar-width); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            padding: 28px 28px 0 28px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content > .container-fluid {
            flex: 1 0 auto;
            padding-left: 0;
            padding-right: 0;
        }

        /* Nav Links */
        .sidebar .nav-link { 
            color: rgba(255, 255, 255, 0.72); 
            font-weight: 500;
            font-size: 0.875rem;
            padding: 10px 14px;
            margin: 3px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link i {
            font-size: 1.15rem;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover { 
            color: #ffffff; 
            background: rgba(255, 255, 255, 0.08); 
            transform: translateX(4px);
        }
        .sidebar .nav-link.active {
            color: #ffffff; 
            background: linear-gradient(90deg, rgba(30, 64, 175, 0.45) 0%, rgba(30, 64, 175, 0.1) 100%);
            border-left: 4px solid var(--brand-accent);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .sidebar .nav-link.active i {
            color: var(--brand-accent) !important;
        }

        .sidebar .nav-logout {
            margin-top: 24px;
            background: rgba(239, 68, 68, 0.12) !important;
            border: 1px solid rgba(239, 68, 68, 0.25) !important;
            color: #fca5a5 !important;
            justify-content: center;
        }
        .nav-logout:hover {
            background: rgba(239, 68, 68, 0.85) !important;
            color: #ffffff !important;
            transform: none !important;
        }

        /* Cards & Buttons */
        .card { 
            border-radius: 14px; 
            border: 1px solid var(--border-subtle); 
            background: var(--surface-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 4px 12px rgba(15, 23, 42, 0.03); 
        }

        .btn-primary { 
            background-color: var(--brand-primary); 
            border-color: var(--brand-primary); 
            color: #ffffff;
            font-weight: 500;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { 
            background-color: var(--brand-primary-hover); 
            border-color: var(--brand-primary-hover); 
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.3);
        }

        .btn-outline-primary {
            color: var(--brand-primary);
            border-color: #bfdbfe;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-outline-primary:hover {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            color: #ffffff;
        }

        .btn-warning {
            background-color: var(--brand-accent);
            border-color: var(--brand-accent);
            color: #0f172a;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-warning:hover {
            background-color: var(--brand-accent-hover);
            border-color: var(--brand-accent-hover);
            color: #ffffff;
        }

        .btn-success { 
            background-color: #10b981; 
            border-color: #10b981; 
            color: #ffffff;
            border-radius: 8px;
        }
        .btn-success:hover { 
            background-color: #059669; 
            border-color: #059669; 
        }

        /* Typography & Utilities */
        .text-primary {
            color: var(--brand-primary) !important;
        }
        .badge.bg-primary {
            background-color: var(--brand-primary) !important;
        }
        .badge.bg-primary.bg-opacity-10 {
            background-color: var(--brand-primary-subtle) !important;
            color: var(--brand-primary) !important;
            border: 1px solid #dbeafe;
        }

        .table-light, thead.table-light th {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            font-weight: 600;
        }
        
        @media (max-width: 991.98px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; padding: 16px 16px 0 16px; }
        }
    </style>
    
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand-box">
        <?php
        $site_logo_path = '../assets/img/' . $site_bimbel_logo;
        $site_logo_version = (file_exists($site_logo_path) && is_file($site_logo_path)) ? '?v=' . filemtime($site_logo_path) : '';
        ?>
        <img src="../assets/img/<?= htmlspecialchars($site_bimbel_logo) . $site_logo_version ?>" alt="Logo" class="sidebar-logo">
        <h6 class="sidebar-title"><?= strtoupper($site_app_name) ?></h6>
        <small class="sidebar-subtitle"><?= $site_bimbel_name ?></small>
        <span class="sidebar-badge"><i class="bi bi-shield-check me-1"></i>ADMIN PANEL</span>
    </div>
    <ul class="nav flex-column px-1 pt-3">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= !isset($_GET['page']) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=exams" class="nav-link <?= ($_GET['page'] ?? '') == 'exams' ? 'active' : '' ?>">
                <i class="bi bi-book me-2"></i> Ujian
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=references" class="nav-link <?= ($_GET['page'] ?? '') == 'references' ? 'active' : '' ?>">
                <i class="bi bi-journal-text me-2"></i> Referensi
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=students" class="nav-link <?= ($_GET['page'] ?? '') == 'students' ? 'active' : '' ?>">
                <i class="bi bi-people me-2"></i> Data Siswa
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=scores" class="nav-link <?= ($_GET['page'] ?? '') == 'scores' ? 'active' : '' ?>">
                <i class="bi bi-graph-up me-2"></i> Data Nilai
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=admins" class="nav-link <?= ($_GET['page'] ?? '') == 'admins' ? 'active' : '' ?>">
                <i class="bi bi-person-lock me-2"></i> Manajemen Admin
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=surveys" class="nav-link <?= ($_GET['page'] ?? '') == 'surveys' ? 'active' : '' ?>">
                <i class="bi bi-clipboard2-check me-2"></i> Survei Guru
            </a>
        </li>
        <li class="nav-item">
            <a href="dashboard.php?page=settings" class="nav-link <?= ($_GET['page'] ?? '') == 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear me-2"></i> Pengaturan
            </a>
        </li>
        <li class="nav-item">
            <a href="../logout.php" class="nav-link nav-logout">
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