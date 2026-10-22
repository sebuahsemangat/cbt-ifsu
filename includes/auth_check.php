<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika request merupakan AJAX / Fetch, kembalikan JSON agar tidak memicu SyntaxError di frontend
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Sesi admin Anda telah habis atau tidak valid. Silakan refresh halaman dan login kembali.'
        ]);
        exit;
    }

    // Tentukan path relatif ke root index.php sesuai kedalaman script
    $redirectUrl = '../index.php';
    if (file_exists('../../../index.php')) {
        $redirectUrl = '../../../index.php';
    } elseif (file_exists('../../index.php')) {
        $redirectUrl = '../../index.php';
    } elseif (file_exists('../index.php')) {
        $redirectUrl = '../index.php';
    } elseif (file_exists('index.php')) {
        $redirectUrl = 'index.php';
    }

    header("Location: " . $redirectUrl);
    exit;
}

?>