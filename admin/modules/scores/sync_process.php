<?php
/**
 * AJAX Handler: Validasi & Proses Upload Nilai ke CBT Cloud (cbt.page.gd)
 * File: admin/modules/scores/sync_process.php
 */

declare(strict_types=1);

ob_start();
session_start();

function sendJsonResponse(array $data, int $httpCode = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    echo json_encode($data);
    exit;
}

// Validasi otentikasi admin
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Sesi admin berakhir. Silakan refresh dan login kembali.'
    ], 401);
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/CbtCloudSync.php';

// Ambil pengaturan konfigurasi Cloud CBT
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'cbt_cloud_%'");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$cloudUrl   = rtrim($settings['cbt_cloud_sync_url'] ?? 'http://cbt.page.gd', '/');
$syncToken  = $settings['cbt_cloud_sync_token'] ?? 'cbt-cloud-secret-key-2026';
$lastUpload = $settings['cbt_cloud_last_upload'] ?? '';

$action  = $_POST['action'] ?? ($_GET['action'] ?? '');
$examId  = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : (isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0);

try {
    // ==========================================
    // ACTION 1: CEK KELAYAKAN UPLOAD (ELIGIBILITY)
    // ==========================================
    if ($action === 'check_eligibility') {
        if (!$examId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Parameter ID Ujian tidak valid.'
            ]);
        }

        // 1. Cek jumlah siswa yang masih login
        $stmtLogged = $pdo->query("SELECT COUNT(*) FROM students WHERE is_logged_in = 1");
        $loggedInCount = (int)$stmtLogged->fetchColumn();

        // 2. Cek jumlah sesi yang masih ongoing pada ujian ini
        $stmtOngoing = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND status = 'ongoing'");
        $stmtOngoing->execute([$examId]);
        $ongoingCount = (int)$stmtOngoing->fetchColumn();

        // 3. Cek jumlah sesi yang sudah selesai
        $stmtFinished = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND status = 'finished'");
        $stmtFinished->execute([$examId]);
        $finishedCount = (int)$stmtFinished->fetchColumn();

        // 4. Cek jumlah sesi yang belum diunggah ke cloud (synced = 0)
        $stmtPendingSync = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND status = 'finished' AND (synced = 0 OR synced IS NULL)");
        $stmtPendingSync->execute([$examId]);
        $pendingSyncCount = (int)$stmtPendingSync->fetchColumn();

        $canUpload = ($loggedInCount === 0 && $ongoingCount === 0 && $finishedCount > 0);

        // Siapkan pesan keterangan jika tidak bisa upload
        $reasons = [];
        if ($loggedInCount > 0) {
            $reasons[] = "Terdapat {$loggedInCount} siswa yang masih dalam status login.";
        }
        if ($ongoingCount > 0) {
            $reasons[] = "Terdapat {$ongoingCount} sesi siswa yang masih berlangsung (belum selesai).";
        }
        if ($finishedCount === 0) {
            $reasons[] = "Belum ada siswa yang menyelesaikan ujian ini.";
        }

        sendJsonResponse([
            'success' => true,
            'can_upload' => $canUpload,
            'logged_in_count' => $loggedInCount,
            'ongoing_count' => $ongoingCount,
            'finished_count' => $finishedCount,
            'pending_sync_count' => $pendingSyncCount,
            'reasons' => $reasons,
            'cloud_url' => $cloudUrl,
            'last_upload' => $lastUpload
        ]);
    }

    // ==========================================
    // ACTION 2: TES KONEKSI KE SERVER CLOUD
    // ==========================================
    if ($action === 'test_connection') {
        $targetUrl = !empty($_POST['url']) ? trim($_POST['url']) : $cloudUrl;
        $targetToken = !empty($_POST['token']) ? trim($_POST['token']) : $syncToken;

        if (empty($targetUrl)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'URL Server Cloud tidak boleh kosong.'
            ]);
        }

        $testResult = CbtCloudSync::testConnection($targetUrl, $targetToken);
        sendJsonResponse($testResult);
    }

    // ==========================================
    // ACTION 3: EKSEKUSI UPLOAD NILAI & JAWABAN
    // ==========================================
    if ($action === 'upload_scores') {
        if (!$examId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Parameter ID Ujian tidak valid.'
            ]);
        }

        // VALIDASI KETAT 1: Pastikan tidak ada siswa yang masih login
        $stmtLogged = $pdo->query("SELECT COUNT(*) FROM students WHERE is_logged_in = 1");
        $loggedInCount = (int)$stmtLogged->fetchColumn();
        if ($loggedInCount > 0) {
            sendJsonResponse([
                'success' => false,
                'message' => "Upload dibatalkan! Masih ada {$loggedInCount} siswa yang sedang login. Silakan minta siswa logout atau gunakan fitur Reset Login Siswa terlebih dahulu."
            ]);
        }

        // VALIDASI KETAT 2: Pastikan tidak ada sesi yang masih ongoing
        $stmtOngoing = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND status = 'ongoing'");
        $stmtOngoing->execute([$examId]);
        $ongoingCount = (int)$stmtOngoing->fetchColumn();
        if ($ongoingCount > 0) {
            sendJsonResponse([
                'success' => false,
                'message' => "Upload dibatalkan! Masih ada {$ongoingCount} siswa yang sedang mengerjakan ujian. Silakan selesaikan ujian terlebih dahulu di halaman Dashboard."
            ]);
        }

        // Ambil data Ujian
        $stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmtExam->execute([$examId]);
        $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Data ujian tidak ditemukan.'
            ]);
        }

        // Ambil sesi ujian yang sudah selesai
        $stmtSessions = $pdo->prepare("
            SELECT 
                es.*,
                s.nis,
                s.full_name,
                s.class,
                s.kelompok,
                sb.name AS subject_name,
                sb.duration AS subject_duration,
                sb.max_score AS subject_max_score,
                sb.sort_order AS subject_sort_order
            FROM exam_sessions es
            JOIN students s ON es.student_id = s.id
            JOIN subjects sb ON es.subject_id = sb.id
            WHERE es.exam_id = ? AND es.status = 'finished'
            ORDER BY es.id ASC
        ");
        $stmtSessions->execute([$examId]);
        $sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sessions)) {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada data nilai ujian yang berstatus selesai untuk diupload.'
            ]);
            exit;
        }

        // Ambil rincian jawaban siswa untuk sesi-sesi tersebut
        $sessionIds = array_column($sessions, 'id');
        $inClause = implode(',', array_fill(0, count($sessionIds), '?'));

        $stmtAnswers = $pdo->prepare("
            SELECT 
                sa.session_id,
                sa.question_id,
                sa.answer_data,
                sa.is_doubt,
                sa.time_spent,
                sa.answered_at,
                q.question_text
            FROM student_answers sa
            LEFT JOIN questions q ON sa.question_id = q.id
            WHERE sa.session_id IN ($inClause)
            ORDER BY sa.id ASC
        ");
        $stmtAnswers->execute($sessionIds);
        $answersGrouped = [];
        while ($ansRow = $stmtAnswers->fetch(PDO::FETCH_ASSOC)) {
            $sessId = (int)$ansRow['session_id'];
            $ansRow['question_text_hash'] = !empty($ansRow['question_text']) ? md5(trim(strip_tags($ansRow['question_text']))) : null;
            unset($ansRow['question_text']); // Ringankan ukuran payload
            $answersGrouped[$sessId][] = $ansRow;
        }

        // Susun payload JSON
        $payloadSessions = [];
        foreach ($sessions as $s) {
            $sessId = (int)$s['id'];
            $payloadSessions[] = [
                'local_session_id'   => $sessId,
                'subject_name'       => $s['subject_name'],
                'subject_duration'   => (int)$s['subject_duration'],
                'subject_max_score'  => (float)$s['subject_max_score'],
                'subject_sort_order' => (int)$s['subject_sort_order'],
                'start_time'         => $s['start_time'],
                'end_time'           => $s['end_time'],
                'remaining_seconds'  => (int)$s['remaining_seconds'],
                'score'              => (float)$s['score'],
                'violation_count'    => (int)$s['violation_count'],
                'is_locked'          => (int)$s['is_locked'],
                'question_order'     => $s['question_order'],
                'student' => [
                    'nis'       => $s['nis'],
                    'full_name' => $s['full_name'],
                    'class'     => $s['class'],
                    'kelompok'  => $s['kelompok']
                ],
                'answers' => $answersGrouped[$sessId] ?? []
            ];
        }

        // Ambil nama instansi / lab dari settings
        $stmtBimbel = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'bimbel_name' LIMIT 1");
        $labName = $stmtBimbel->fetchColumn() ?: 'Server Lab CBT Offline';

        $payload = [
            'lab_name' => $labName,
            'exam' => [
                'local_exam_id'       => (int)$exam['id'],
                'exam_name'           => $exam['exam_name'],
                'start_date'          => $exam['start_date'],
                'end_date'            => $exam['end_date'],
                'allow_review'        => (int)$exam['allow_review'],
                'randomize_questions' => (int)$exam['randomize_questions'],
                'show_score'          => (int)$exam['show_score']
            ],
            'sessions' => $payloadSessions
        ];

        // Kirim ke server Cloud via cURL
        $uploadResult = CbtCloudSync::uploadScores($cloudUrl, $syncToken, $payload);

        if (!$uploadResult['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengunggah nilai ke cloud: ' . ($uploadResult['message'] ?? 'Kesalahan tidak diketahui'),
                'details' => $uploadResult
            ]);
        }

        // Update status lokal: tandai seluruh sesi ujian ini sebagai synced = 1
        $now = date('Y-m-d H:i:s');
        $stmtMark = $pdo->prepare("UPDATE exam_sessions SET synced = 1, synced_at = ? WHERE exam_id = ? AND status = 'finished'");
        $stmtMark->execute([$now, $examId]);

        // Simpan waktu upload terakhir ke settings
        $stmtSaveLast = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES ('cbt_cloud_last_upload', ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmtSaveLast->execute([$now]);

        sendJsonResponse([
            'success' => true,
            'message' => 'Seluruh data nilai dan rincian jawaban berhasil diupload ke server cbt.page.gd!',
            'data' => [
                'total_uploaded' => count($payloadSessions),
                'cloud_response' => $uploadResult['data'] ?? null,
                'synced_at'      => $now
            ]
        ]);
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Aksi tidak dikenal.'
    ]);
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], 500);
}
