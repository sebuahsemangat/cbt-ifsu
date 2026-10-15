<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

// --- FITUR: TAMBAH 1 SISWA DENGAN NIS ---
if ($action == 'add_single' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['full_name'] ?? '');
    $kelas = trim($_POST['class'] ?? '');
    
    if (!empty($nis) && !empty($nama)) {
        // Proteksi duplikat NIS
        $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE nis = ?");
        $check->execute([$nis]);
        if ($check->fetchColumn() > 0) {
            header("Location: ../../dashboard.php?page=students&status=nis_exists");
            exit;
        }

        // Password Default: 123456
        $password = password_hash('123456', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO students (nis, password, full_name, class) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nis, $password, $nama, $kelas]);
        
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// --- FITUR: EDIT DATA SISWA & NIS ---
if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['full_name'] ?? '');
    $kelas = trim($_POST['class'] ?? '');
    
    if (!empty($id) && !empty($nis) && !empty($nama)) {
        // Cek duplikasi NIS untuk siswa lain
        $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE nis = ? AND id != ?");
        $check->execute([$nis, $id]);
        if ($check->fetchColumn() > 0) {
            header("Location: ../../dashboard.php?page=students&status=nis_exists");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE students SET nis = ?, full_name = ?, class = ? WHERE id = ?");
        $stmt->execute([$nis, $nama, $kelas, $id]);
        header("Location: ../../dashboard.php?page=students&status=updated");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// Logika Import CSV dengan NIS
if ($action == 'import' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $file = $_FILES['file_excel']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Baca baris pertama untuk deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle); // Kembali ke awal file

        fgetcsv($handle, 1000, $delimiter); // Skip baris header

        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO students (nis, password, full_name, class) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), class = VALUES(class)");

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $nis = trim($data[0] ?? '');
            $nama = trim($data[1] ?? '');
            $kelas = trim($data[2] ?? '');
            if (empty($nis) || empty($nama)) continue;

            $stmt->execute([$nis, $password, $nama, $kelas]);
        }
        fclose($handle);
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// Fitur Reset Login Semua Siswa
if ($action == 'reset_all_login') {
    $stmt = $pdo->query("UPDATE students SET is_logged_in = 0");
    $redirect = $_GET['redirect'] ?? 'students';
    if ($redirect === 'dashboard') {
        $_SESSION['success'] = "Status login semua siswa berhasil direset!";
        header("Location: ../../dashboard.php?status=all_resetted");
    } else {
        header("Location: ../../dashboard.php?page=students&status=all_resetted");
    }
    exit;
}

// Fitur Reset Login
if ($action == 'reset_login' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE students SET is_logged_in = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $redirect = $_GET['redirect'] ?? 'students';
    if ($redirect === 'dashboard') {
        $_SESSION['success'] = "Status login siswa berhasil direset!";
        header("Location: ../../dashboard.php?status=resetted");
    } else {
        header("Location: ../../dashboard.php?page=students&status=resetted");
    }
    exit;
}

// Fitur Reset Password
if ($action == 'reset_password' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
    $stmt->execute([$password, $id]);
    header("Location: ../../dashboard.php?page=students&status=pw_resetted");
    exit;
}

// Fitur Hapus Siswa
if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../../dashboard.php?page=students&status=deleted");
    exit;
}