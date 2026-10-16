<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, langsung lempar ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin')
        header("Location: admin/dashboard.php");
    else
        header("Location: student/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // 1. Cek di tabel Admin
        $stmt = $pdo->prepare("SELECT id, password, full_name, 'admin' AS role FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 2. Jika tidak ada di admin, cek di tabel Student berdasarkan NIS
        if (!$user) {
            $stmt = $pdo->prepare("SELECT id, password, full_name, is_logged_in, 'student' AS role FROM students WHERE nis = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }

        // 3. Verifikasi Password
        if ($user && password_verify($password, $user['password'])) {

            // Cek jika siswa sudah login di device lain (Fitur Reset Login)
            if ($user['role'] == 'student' && $user['is_logged_in'] == 1) {
                $error = "Akun sedang digunakan di perangkat lain. Hubungi admin untuk reset login.";
            } else {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['nis'] = ($user['role'] == 'student') ? $username : null;
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Update status login jika siswa
                if ($user['role'] == 'student') {
                    $upd = $pdo->prepare("UPDATE students SET is_logged_in = 1, last_login = NOW() WHERE id = ?");
                    $upd->execute([$user['id']]);
                }

                // Redirect
                if ($user['role'] == 'admin')
                    header("Location: admin/dashboard.php");
                else
                    header("Location: student/dashboard.php");
                exit;
            }
        } else {
            $error = "NIS / Username atau Password salah!";
        }
    } else {
        $error = "Harap isi NIS / Username dan Password!";
    }
}

// === AMBIL SETTING DARI DATABASE ===
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallback values jika di database tidak ada
$app_name = $settings['app_name'] ?? 'Sistem Ujian Computer Based Test (CBT)';
$bimbel_name = $settings['bimbel_name'] ?? 'BIMBINGAN BELAJAR LITA';
$bimbel_logo = $settings['bimbel_logo'] ?? 'logo-bimbel.png';
$login_logo_path = 'assets/img/' . $bimbel_logo;
$login_logo_version = (file_exists($login_logo_path) && is_file($login_logo_path)) ? '?v=' . filemtime($login_logo_path) : '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($bimbel_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/img/<?= htmlspecialchars($bimbel_logo) . $login_logo_version ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brand-blue: #1e40af;
            --brand-blue-hover: #1d4ed8;
            --brand-accent: #f59e0b;
            --brand-accent-glow: rgba(245, 158, 11, 0.35);
        }

        body {
            /* Background Deep Navy dengan sentuhan Glow Biru & Emas */
            background: 
                radial-gradient(circle at 15% 20%, rgba(29, 78, 216, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(245, 158, 11, 0.16) 0%, transparent 45%),
                linear-gradient(135deg, #0b1f3a 0%, #08152e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle Background Grid Pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .login-card {
            max-width: 440px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08);
            overflow: hidden;
            background-color: #ffffff;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-header-custom {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 2.5rem 2rem 1.25rem 2rem;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }

        /* Top accent border on card header */
        .card-header-custom::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1d4ed8 0%, #f59e0b 50%, #1d4ed8 100%);
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 14px;
            transition: all 0.35s ease;
            border: 3px solid #f59e0b;
            box-shadow: 0 0 18px var(--brand-accent-glow), 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 6px;
            background: #ffffff;
        }

        .logo-placeholder:hover {
            transform: scale(1.06) rotate(2deg);
            box-shadow: 0 0 24px rgba(245, 158, 11, 0.5);
        }

        .bimbel-name {
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0.3px;
            font-size: 1.25rem;
            line-height: 1.35;
        }

        .app-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #dbeafe;
            font-size: 0.725rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.4px;
            margin-top: 8px;
        }

        .form-label {
            color: #475569;
            font-size: 0.775rem;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.12);
        }

        .input-group-text {
            background-color: #f8fafc;
            border: none;
            color: #1e40af;
            font-size: 1.1rem;
            padding-left: 14px;
            padding-right: 10px;
        }

        .form-control {
            border: none;
            padding: 12px 14px 12px 6px;
            font-size: 0.925rem;
            font-weight: 500;
            color: #0f172a;
        }

        .form-control:focus {
            box-shadow: none;
            border: none;
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .btn-toggle-pw {
            background-color: #ffffff;
            border: none;
            color: #64748b;
            padding-right: 14px;
            transition: color 0.2s;
        }

        .btn-toggle-pw:hover {
            color: #1e40af;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.925rem;
            letter-spacing: 0.8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.32);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(29, 78, 216, 0.42);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .copyright {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 400;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card login-card">
            <div class="card-header-custom">
                <img src="assets/img/<?= htmlspecialchars($bimbel_logo) . $login_logo_version ?>" alt="Logo Bimbel"
                    class="logo-placeholder rounded-circle shadow-sm">

                <h4 class="bimbel-name mb-0"><?= htmlspecialchars($bimbel_name) ?></h4>
                <div class="app-badge"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($app_name) ?></div>
            </div>

            <div class="card-body p-4 pt-3">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= $error ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">NIS / USERNAME</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan NIS atau Username" required
                                autocomplete="off" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">PASSWORD</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="passwordInput" class="form-control"
                                placeholder="Masukkan password" required>
                            <button class="btn btn-toggle-pw" type="button" onclick="togglePassword()">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">MASUK KE SISTEM</button>
                    </div>
                </form>

                <div class="text-center mt-4 mb-2">
                    <small class="copyright">&copy; <?= date('Y') ?> <?= htmlspecialchars($bimbel_name) ?>. All rights
                        reserved.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fitur Show/Hide Password
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }
    </script>
</body>

</html>