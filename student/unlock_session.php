<?php
/**
 * File: student/unlock_session.php
 * Deskripsi: Membuka kunci ujian menggunakan autentikasi pengawas/admin di layar siswa
 */

declare(strict_types=1);

session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
$adminPassword = trim($_POST['admin_password'] ?? '');

if ($sessionId <= 0 || $adminPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Password pengawas wajib diisi']);
    exit;
}

// Cek apakah password cocok dengan salah satu admin yang terdaftar
$stmtAdmins = $pdo->query("SELECT id, password FROM admins");
$admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

$isAuthorized = false;
foreach ($admins as $adm) {
    if (password_verify($adminPassword, $adm['password'])) {
        $isAuthorized = true;
        break;
    }
}

if (!$isAuthorized) {
    echo json_encode(['success' => false, 'message' => 'Password pengawas salah!']);
    exit;
}

// Buka kunci sesi ujian dan reset hitungan pelanggaran
$stmtUpd = $pdo->prepare("UPDATE exam_sessions SET is_locked = 0, violation_count = 0 WHERE id = ? AND student_id = ?");
$stmtUpd->execute([$sessionId, $_SESSION['user_id']]);

echo json_encode([
    'success' => true,
    'message' => 'Ujian berhasil dibuka kembali. Silakan lanjutkan ujian dengan tertib.'
]);
