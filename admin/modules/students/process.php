<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

// --- FITUR BARU: TAMBAH 1 SISWA ---
if ($action == 'add_single' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['full_name']);
    $kelas = trim($_POST['class']);
    
    if (!empty($nama)) {
        // Generate Username (nama kecil, tanpa spasi + angka acak)
        $namadepan = explode(" ", $nama)[0];
        $usernameBase = strtolower(str_replace(' ', '', $namadepan));
        $username = $usernameBase . rand(10, 99);

        // Proteksi duplikat username
        $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) {
            $username = $usernameBase . rand(100, 999);
        }

        // Password Default: 123456
        $password = password_hash('123456', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO students (username, password, full_name, class) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama, $kelas]);
        
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// --- FITUR BARU: EDIT DATA SISWA ---
if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $nama = trim($_POST['full_name']);
    $kelas = trim($_POST['class']);
    
    if (!empty($id) && !empty($nama)) {
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, class = ? WHERE id = ?");
        $stmt->execute([$nama, $kelas, $id]);
        header("Location: ../../dashboard.php?page=students&status=updated");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// Logika Import Excel
if ($action == 'import' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $file = $_FILES['file_excel']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Baca baris pertama untuk deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle); // Kembali ke awal file

        fgetcsv($handle, 1000, $delimiter); // Skip header

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $nama = trim($data[0] ?? '');
            $kelas = trim($data[1] ?? ''); // Kolom kedua untuk kelas
            if (empty($nama)) continue;

            $usernameBase = strtolower(str_replace(' ', '', $nama));
            $username = $usernameBase . rand(10, 99);
            
            // Cek duplikat
            $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) $username = $usernameBase . rand(100, 999);

            $password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (username, password, full_name, class) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $nama, $kelas]);
        }
        fclose($handle);
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// Fitur Reset Login
if ($action == 'reset_login' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE students SET is_logged_in = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../../dashboard.php?page=students&status=resetted");
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