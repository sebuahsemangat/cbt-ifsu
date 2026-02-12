<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, langsung lempar ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else header("Location: student/dashboard.php");
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

        // 2. Jika tidak ada di admin, cek di tabel Student
        if (!$user) {
            $stmt = $pdo->prepare("SELECT id, password, full_name, is_logged_in, 'student' AS role FROM students WHERE username = ?");
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
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $username;
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Update status login jika siswa
                if ($user['role'] == 'student') {
                    $upd = $pdo->prepare("UPDATE students SET is_logged_in = 1, last_login = NOW() WHERE id = ?");
                    $upd->execute([$user['id']]);
                }

                // Redirect
                if ($user['role'] == 'admin') header("Location: admin/dashboard.php");
                else header("Location: student/dashboard.php");
                exit;
            }
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Harap isi username dan password!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bimbel Belajar Lita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/img/logo-bimbel.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); /* Gradasi Biru */
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .login-card { 
            max-width: 420px; 
            width: 100%; 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
            overflow: hidden;
            background-color: #fff;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header-custom {
            background-color: #fff;
            padding: 2rem 2rem 1rem 2rem;
            text-align: center;
        }
        
        .logo-placeholder {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .logo-placeholder:hover {
            transform: scale(1.05);
        }
        
        .bimbel-name {
            color: #0d6efd;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
            padding-left: 10px;
        }
        
        .btn-primary { 
            background: linear-gradient(to right, #0d6efd, #0b5ed7);
            border: none; 
            padding: 12px; 
            border-radius: 10px; 
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        
        .copyright {
            font-size: 0.8rem;
            color: #aaa;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card">
        <div class="card-header-custom">
            <img src="assets/img/logo-bimbel.png" alt="Logo Bimbel" class="logo-placeholder rounded-circle shadow-sm">
            
            <h4 class="bimbel-name mb-0">BIMBINGAN BELAJAR LITA</h4>
            <p class="text-muted small mt-2">Sistem Ujian Computer Based Test (CBT)</p>
        </div>

        <div class="card-body p-4 pt-2">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= $error ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person text-primary"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock text-primary"></i></span>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Masukkan password" required>
                        <button class="btn btn-outline-secondary border-start-0 border-secondary-subtle bg-white" type="button" onclick="togglePassword()">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">LOGIN SEKARANG</button>
                </div>
            </form>

            <div class="text-center mt-4 mb-2">
                <small class="copyright">&copy; <?= date('Y') ?> Bimbingan Belajar Lita. All rights reserved.</small>
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