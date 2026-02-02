<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    // Jika yang logout adalah siswa, ubah status is_logged_in di database
    if ($_SESSION['role'] === 'student') {
        $stmt = $pdo->prepare("UPDATE students SET is_logged_in = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }

    // Hapus semua data session
    $_SESSION = array();

    // Hapus cookie session jika ada
    if (ini_get("session_use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Hancurkan session
    session_destroy();
}

// Redirect ke halaman login
header("Location: index.php");
exit;