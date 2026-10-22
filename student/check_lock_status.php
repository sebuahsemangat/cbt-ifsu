<?php
/**
 * File: student/check_lock_status.php
 * Deskripsi: Memeriksa apakah sesi ujian siswa terkunci atau sudah dibuka oleh pengawas
 */

declare(strict_types=1);

session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$studentId = (int)$_SESSION['user_id'];

if ($sessionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

$stmtSess = $pdo->prepare("SELECT id, violation_count, is_locked, status FROM exam_sessions WHERE id = ? AND student_id = ?");
$stmtSess->execute([$sessionId, $studentId]);
$session = $stmtSess->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan']);
    exit;
}

echo json_encode([
    'success' => true,
    'is_locked' => ((int)$session['is_locked'] === 1),
    'violation_count' => (int)$session['violation_count'],
    'status' => $session['status']
]);
