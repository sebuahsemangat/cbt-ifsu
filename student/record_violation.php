<?php
/**
 * File: student/record_violation.php
 * Deskripsi: Mencatat pelanggaran anti-cheating (pindah tab, minimize, keluar fullscreen)
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
$studentId = (int)$_SESSION['user_id'];

if ($sessionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

// Cek apakah fitur anti-cheating aktif di pengaturan
$stmtSetting = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'enable_anti_cheating'");
$enableAntiCheating = ($stmtSetting->fetchColumn() ?? '1') === '1';

if (!$enableAntiCheating) {
    echo json_encode(['success' => true, 'anti_cheating' => false, 'is_locked' => false, 'violation_count' => 0]);
    exit;
}

// Cek sesi milik siswa ini dan masih berlangsung
$stmtSess = $pdo->prepare("SELECT id, violation_count, is_locked, status FROM exam_sessions WHERE id = ? AND student_id = ?");
$stmtSess->execute([$sessionId, $studentId]);
$session = $stmtSess->fetch(PDO::FETCH_ASSOC);

if (!$session || $session['status'] !== 'ongoing') {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak aktif']);
    exit;
}

// Tambah hitungan pelanggaran
$newCount = (int)$session['violation_count'] + 1;
$isLocked = ($newCount >= 3) ? 1 : (int)$session['is_locked'];

$stmtUpd = $pdo->prepare("UPDATE exam_sessions SET violation_count = ?, is_locked = ? WHERE id = ?");
$stmtUpd->execute([$newCount, $isLocked, $sessionId]);

echo json_encode([
    'success' => true,
    'anti_cheating' => true,
    'violation_count' => $newCount,
    'is_locked' => ($isLocked === 1),
    'max_violations' => 3
]);
