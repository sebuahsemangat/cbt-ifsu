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
                $error = "Akun sedang digunakan. Silakan hubungi admin untuk reset login.";
            } else {
                // Set Session
                $_SESSION['user_id']   = $user['id'];
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
        $error = "Harap isi semua bidang!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Simulasi TKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; margin: auto; border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #0d6efd; border: none; padding: 10px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <h4 class="fw-bold">SIMULASI TKA</h4>
            <p class="text-muted">Silakan login untuk memulai ujian</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Masuk</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">&copy; 2026 TKA Simulation System</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>