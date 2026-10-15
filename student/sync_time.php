<?php
/**
 * File: student/sync_time.php
 * Deskripsi: Endpoint AJAX untuk menyinkronkan sisa waktu ujian siswa ke database
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Validasi autentikasi siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Dukung penerimaan via JSON payload maupun POST form
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input && !empty($_POST)) {
    $input = $_POST;
}

$sessionId = isset($input['session_id']) ? (int)$input['session_id'] : (isset($_REQUEST['session_id']) ? (int)$_REQUEST['session_id'] : null);
$remainingSeconds = null;
if (isset($input['remaining_seconds']) && is_numeric($input['remaining_seconds'])) {
    $remainingSeconds = (int)$input['remaining_seconds'];
} elseif (isset($_REQUEST['remaining_seconds']) && is_numeric($_REQUEST['remaining_seconds'])) {
    $remainingSeconds = (int)$_REQUEST['remaining_seconds'];
}

if (!$sessionId || $remainingSeconds === null || $remainingSeconds < 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

$studentId = (int)$_SESSION['user_id'];

try {
    // Update sisa detik dan sesuaikan end_time di database
    $stmt = $pdo->prepare("
        UPDATE exam_sessions 
        SET remaining_seconds = ?, 
            end_time = DATE_ADD(NOW(), INTERVAL ? SECOND) 
        WHERE id = ? AND student_id = ? AND status = 'ongoing'
    ");
    $stmt->execute([$remainingSeconds, $remainingSeconds, $sessionId, $studentId]);

    echo json_encode([
        'status' => 'success',
        'session_id' => $sessionId,
        'remaining_seconds' => $remainingSeconds
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
