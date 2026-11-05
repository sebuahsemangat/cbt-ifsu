<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

// --- FITUR: TAMBAH 1 SISWA DENGAN NIS, KELOMPOK & RUANGAN ---
if ($action == 'add_single' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['full_name'] ?? '');
    $kelas = trim($_POST['class'] ?? '');
    $kelompok = trim($_POST['kelompok'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $kelompokVal = empty($kelompok) ? null : $kelompok;
    $roomVal = empty($room) ? null : $room;
    
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

        $stmt = $pdo->prepare("INSERT INTO students (nis, password, full_name, class, kelompok, room) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nis, $password, $nama, $kelas, $kelompokVal, $roomVal]);
        
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// --- FITUR: EDIT DATA SISWA, NIS, KELOMPOK & RUANGAN ---
if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['full_name'] ?? '');
    $kelas = trim($_POST['class'] ?? '');
    $kelompok = trim($_POST['kelompok'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $kelompokVal = empty($kelompok) ? null : $kelompok;
    $roomVal = empty($room) ? null : $room;
    
    if (!empty($id) && !empty($nis) && !empty($nama)) {
        // Cek duplikasi NIS untuk siswa lain
        $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE nis = ? AND id != ?");
        $check->execute([$nis, $id]);
        if ($check->fetchColumn() > 0) {
            header("Location: ../../dashboard.php?page=students&status=nis_exists");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE students SET nis = ?, full_name = ?, class = ?, kelompok = ?, room = ? WHERE id = ?");
        $stmt->execute([$nis, $nama, $kelas, $kelompokVal, $roomVal, $id]);
        header("Location: ../../dashboard.php?page=students&status=updated");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// Logika Import CSV dengan NIS, Kelompok & Ruangan (Mendukung Base64 untuk Bypass OpenResty 403)
if ($action == 'import' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $handle = null;
    
    // 1. Dukung Base64 via application/x-www-form-urlencoded (Bypass OpenResty 403)
    if (!empty($_POST['csv_base64'])) {
        $rawCsv = base64_decode($_POST['csv_base64']);
        if ($rawCsv !== false && !empty($rawCsv)) {
            $handle = fopen("php://memory", "r+");
            fwrite($handle, $rawCsv);
            rewind($handle);
        }
    } elseif (!empty($_POST['csv_content'])) {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $_POST['csv_content']);
        rewind($handle);
    } elseif (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        // Fallback: upload normal $_FILES jika diizinkan server
        $handle = fopen($_FILES['file_excel']['tmp_name'], "r");
    }

    if ($handle) {
        // Baca baris pertama untuk deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle); // Kembali ke awal file

        fgetcsv($handle, 1000, $delimiter); // Skip baris header

        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO students (nis, password, full_name, class, kelompok, room) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), class = VALUES(class), kelompok = COALESCE(VALUES(kelompok), kelompok), room = COALESCE(VALUES(room), room)");

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $nis = trim($data[0] ?? '');
            $nama = trim($data[1] ?? '');
            $kelas = trim($data[2] ?? '');
            $kelompok = isset($data[3]) ? trim($data[3]) : null;
            $kelompokVal = empty($kelompok) ? null : $kelompok;
            $room = isset($data[4]) ? trim($data[4]) : null;
            $roomVal = empty($room) ? null : $room;
            if (empty($nis) || empty($nama)) continue;

            $stmt->execute([$nis, $password, $nama, $kelas, $kelompokVal, $roomVal]);
        }
        fclose($handle);
        header("Location: ../../dashboard.php?page=students&status=success");
    } else {
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// --- FITUR UTAMA: UBAH RUANGAN SISWA BERDASARKAN KELOMPOK ---
if ($action == 'set_room_by_group' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $kelompok = trim($_POST['kelompok'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $clearRoom = isset($_POST['clear_room']) && $_POST['clear_room'] == '1';

    if (!empty($kelompok)) {
        $roomVal = ($clearRoom || $room === '') ? null : $room;

        $stmt = $pdo->prepare("UPDATE students SET room = ? WHERE kelompok = ?");
        $stmt->execute([$roomVal, $kelompok]);
        $countUpdated = $stmt->rowCount();

        if ($roomVal !== null) {
            $_SESSION['success'] = "Berhasil menetapkan ruangan \"$roomVal\" untuk seluruh siswa di kelompok \"$kelompok\" ($countUpdated siswa terupdate)!";
        } else {
            $_SESSION['success'] = "Ruangan berhasil dikosongkan untuk seluruh siswa di kelompok \"$kelompok\" ($countUpdated siswa)!";
        }
        header("Location: ../../dashboard.php?page=students&status=room_updated");
    } else {
        $_SESSION['error'] = "Pilih kelompok terlebih dahulu.";
        header("Location: ../../dashboard.php?page=students&status=error");
    }
    exit;
}

// --- FITUR: BULK TERAPKAN KELOMPOK SISWA ---
if ($action == 'bulk_set_kelompok' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $ids = $_POST['student_ids'] ?? [];
    $kelompokInput = trim($_POST['kelompok'] ?? '');
    $clearKelompok = isset($_POST['clear_kelompok']) && $_POST['clear_kelompok'] == '1';

    if (is_array($ids) && count($ids) > 0) {
        $ids = array_map('intval', $ids);
        $kelompokVal = ($clearKelompok || $kelompokInput === '') ? null : $kelompokInput;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE students SET kelompok = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$kelompokVal], $ids));

        $countUpdated = count($ids);
        if ($kelompokVal !== null) {
            $_SESSION['success'] = "Berhasil menetapkan kelompok \"$kelompokVal\" untuk $countUpdated siswa!";
        } else {
            $_SESSION['success'] = "Kelompok berhasil dikosongkan untuk $countUpdated siswa!";
        }
        header("Location: ../../dashboard.php?page=students&status=kelompok_updated");
    } else {
        $_SESSION['error'] = "Pilih minimal satu siswa untuk menerapkan kelompok.";
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
        header("Location: ../../dashboard.php");
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
        header("Location: ../../dashboard.php");
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
