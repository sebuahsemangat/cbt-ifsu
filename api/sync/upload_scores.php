<?php
/**
 * CBT Cloud Endpoint: Upload & Merge Nilai Siswa dari Multi-Lab Offline
 * File: api/sync/upload_scores.php
 * Digunakan pada server Cloud (cbt.page.gd) dan dapat diuji pada instalasi lokal
 */

declare(strict_types=1);

// Pastikan header respon selalu JSON
header('Content-Type: application/json; charset=utf-8');

// Cegah caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . '/../../config/database.php';

// Ambil token sinkronisasi dari settings database cloud
$syncToken = '';
try {
    $stmtToken = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cbt_cloud_sync_token' LIMIT 1");
    $syncToken = (string)$stmtToken->fetchColumn();
} catch (Exception $e) {
    // Abaikan jika tabel settings belum memiliki key tersebut
}
if (empty($syncToken)) {
    $syncToken = 'cbt-cloud-secret-key-2026'; // Default fallback
}

// 1. Validasi Header Autentikasi
$incomingToken = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';
if (empty($incomingToken) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $incomingToken = trim($matches[1]);
    }
}

// Mode Uji Koneksi (GET ?test=1)
if (isset($_GET['test'])) {
    if ($incomingToken !== $syncToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Autentikasi gagal: Token sinkronisasi tidak cocok.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Koneksi ke CBT Cloud berhasil. Server siap menerima data nilai.',
        'server_time' => date('Y-m-d H:i:s'),
        'app' => 'CBT Simulation Cloud Endpoint'
    ]);
    exit;
}

// Hanya menerima metode POST untuk pengunggahan nilai
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

// Validasi Token untuk POST
if ($incomingToken !== $syncToken) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Autentikasi gagal: Token sinkronisasi tidak valid.'
    ]);
    exit;
}

// 2. Baca Payload JSON
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    $rawInput = file_get_contents('php://stdin');
}
$payload = json_decode((string)$rawInput, true);

if (!is_array($payload) || empty($payload['exam']) || !isset($payload['sessions']) || !is_array($payload['sessions'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format payload tidak valid. Membutuhkan objek exam dan daftar sessions.'
    ]);
    exit;
}

$examInfo = $payload['exam'];
$sessionsData = $payload['sessions'];
$labName = $payload['lab_name'] ?? 'Lab CBT';

if (empty($sessionsData)) {
    echo json_encode([
        'success' => true,
        'message' => 'Tidak ada data nilai yang dikirim.',
        'processed' => 0
    ]);
    exit;
}

// 3. Eksekusi Sinkronisasi & Deduplikasi dalam Database Transaction
try {
    $pdo->beginTransaction();

    // A. Matching / Pemetaan Ujian (Exams)
    // Utamakan pencocokan berdasarkan exam_name
    $examName = trim((string)($examInfo['exam_name'] ?? ''));
    $cloudExamId = null;

    if (!empty($examName)) {
        $stmtFindExam = $pdo->prepare("SELECT id FROM exams WHERE exam_name = ? LIMIT 1");
        $stmtFindExam->execute([$examName]);
        $cloudExamId = $stmtFindExam->fetchColumn();
    }

    if (!$cloudExamId) {
        // Buat record ujian jika belum ada di cloud
        $stmtCreateExam = $pdo->prepare("
            INSERT INTO exams (exam_name, start_date, end_date, is_active, allow_review, randomize_questions, show_score, created_at)
            VALUES (?, ?, ?, 1, ?, ?, ?, NOW())
        ");
        $stmtCreateExam->execute([
            $examName ?: 'Ujian Sinkronisasi',
            $examInfo['start_date'] ?? date('Y-m-d H:i:s'),
            $examInfo['end_date'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
            (int)($examInfo['allow_review'] ?? 0),
            (int)($examInfo['randomize_questions'] ?? 1),
            (int)($examInfo['show_score'] ?? 0)
        ]);
        $cloudExamId = (int)$pdo->lastInsertId();
    } else {
        $cloudExamId = (int)$cloudExamId;
    }

    // Cache pemetaan mata pelajaran: [local_subject_name => cloud_subject_id]
    $subjectCache = [];
    $stmtAllSubjects = $pdo->prepare("SELECT id, name FROM subjects WHERE exam_id = ?");
    $stmtAllSubjects->execute([$cloudExamId]);
    while ($subRow = $stmtAllSubjects->fetch(PDO::FETCH_ASSOC)) {
        $subjectCache[strtolower(trim($subRow['name']))] = (int)$subRow['id'];
    }

    // Cache pemetaan soal: [cloud_subject_id => [local_question_index_or_hash => cloud_question_id]]
    // Untuk menghubungkan rincian jawaban student_answers
    $questionsCache = [];
    $stmtAllQ = $pdo->prepare("SELECT id, subject_id, question_text FROM questions WHERE subject_id IN (SELECT id FROM subjects WHERE exam_id = ?)");
    $stmtAllQ->execute([$cloudExamId]);
    while ($qRow = $stmtAllQ->fetch(PDO::FETCH_ASSOC)) {
        $sId = (int)$qRow['subject_id'];
        $cleanText = trim(strip_tags((string)$qRow['question_text']));
        $hash = md5($cleanText);
        $questionsCache[$sId][$hash] = (int)$qRow['id'];
    }

    $insertedCount = 0;
    $updatedCount = 0;
    $totalAnswersCount = 0;

    // Prepared statements untuk efisiensi
    $stmtFindStudent = $pdo->prepare("SELECT id FROM students WHERE nis = ? LIMIT 1");
    $stmtCreateStudent = $pdo->prepare("
        INSERT INTO students (nis, password, full_name, class, kelompok, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmtUpdateStudent = $pdo->prepare("
        UPDATE students SET full_name = ?, class = ?, kelompok = ? WHERE id = ?
    ");

    $stmtCreateSubject = $pdo->prepare("
        INSERT INTO subjects (exam_id, name, duration, max_score, sort_order, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");

    // Query cek sesi yang sudah ada (DEDUPLIKASI KETAT)
    $stmtFindSession = $pdo->prepare("
        SELECT id FROM exam_sessions 
        WHERE student_id = ? AND exam_id = ? AND subject_id = ? 
        LIMIT 1
    ");

    $stmtInsertSession = $pdo->prepare("
        INSERT INTO exam_sessions (
            student_id, exam_id, subject_id, start_time, end_time, remaining_seconds, 
            score, violation_count, is_locked, question_order, status, synced, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'finished', 1, NOW())
    ");

    $stmtUpdateSession = $pdo->prepare("
        UPDATE exam_sessions SET 
            start_time = ?, end_time = ?, remaining_seconds = ?, score = ?,
            violation_count = ?, is_locked = ?, question_order = ?, status = 'finished',
            synced = 1, synced_at = NOW()
        WHERE id = ?
    ");

    $stmtDeleteAnswers = $pdo->prepare("DELETE FROM student_answers WHERE session_id = ?");
    $stmtInsertAnswer = $pdo->prepare("
        INSERT INTO student_answers (session_id, question_id, answer_data, is_doubt, time_spent, answered_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($sessionsData as $item) {
        $studentInfo = $item['student'] ?? [];
        $nis = trim((string)($studentInfo['nis'] ?? ''));
        if (empty($nis)) {
            continue; // Lewati jika NIS kosong
        }

        // B. Sinkronisasi Data Siswa
        $stmtFindStudent->execute([$nis]);
        $cloudStudentId = $stmtFindStudent->fetchColumn();

        if (!$cloudStudentId) {
            $stmtCreateStudent->execute([
                $nis,
                $studentInfo['password'] ?? password_hash('123456', PASSWORD_BCRYPT),
                $studentInfo['full_name'] ?? 'Siswa ' . $nis,
                $studentInfo['class'] ?? '',
                $studentInfo['kelompok'] ?? ''
            ]);
            $cloudStudentId = (int)$pdo->lastInsertId();
        } else {
            $cloudStudentId = (int)$cloudStudentId;
            // Perbarui data nama/kelas jika berubah
            if (!empty($studentInfo['full_name']) || !empty($studentInfo['class'])) {
                $stmtUpdateStudent->execute([
                    $studentInfo['full_name'] ?? '',
                    $studentInfo['class'] ?? '',
                    $studentInfo['kelompok'] ?? '',
                    $cloudStudentId
                ]);
            }
        }

        // C. Matching Mata Pelajaran (Subject)
        $subjectName = trim((string)($item['subject_name'] ?? ''));
        $subjectKey = strtolower($subjectName);

        if (!isset($subjectCache[$subjectKey])) {
            $stmtCreateSubject->execute([
                $cloudExamId,
                $subjectName ?: 'Mata Pelajaran',
                (int)($item['subject_duration'] ?? 60),
                (float)($item['subject_max_score'] ?? 100),
                (int)($item['subject_sort_order'] ?? 0)
            ]);
            $newSubId = (int)$pdo->lastInsertId();
            $subjectCache[$subjectKey] = $newSubId;
        }
        $cloudSubjectId = $subjectCache[$subjectKey];

        // D. Simpan / Perbarui Sesi Nilai (Deduplikasi Sesi Nilai)
        $stmtFindSession->execute([$cloudStudentId, $cloudExamId, $cloudSubjectId]);
        $existingSessionId = $stmtFindSession->fetchColumn();

        $sessionTargetId = null;
        if ($existingSessionId) {
            // Perbarui nilai jika sesi sudah ada
            $sessionTargetId = (int)$existingSessionId;
            $stmtUpdateSession->execute([
                $item['start_time'] ?? date('Y-m-d H:i:s'),
                $item['end_time'] ?? date('Y-m-d H:i:s'),
                (int)($item['remaining_seconds'] ?? 0),
                (float)($item['score'] ?? 0.0),
                (int)($item['violation_count'] ?? 0),
                (int)($item['is_locked'] ?? 0),
                $item['question_order'] ?? null,
                $sessionTargetId
            ]);
            $updatedCount++;
        } else {
            // Tambahkan sesi nilai baru
            $stmtInsertSession->execute([
                $cloudStudentId,
                $cloudExamId,
                $cloudSubjectId,
                $item['start_time'] ?? date('Y-m-d H:i:s'),
                $item['end_time'] ?? date('Y-m-d H:i:s'),
                (int)($item['remaining_seconds'] ?? 0),
                (float)($item['score'] ?? 0.0),
                (int)($item['violation_count'] ?? 0),
                (int)($item['is_locked'] ?? 0),
                $item['question_order'] ?? null
            ]);
            $sessionTargetId = (int)$pdo->lastInsertId();
            $insertedCount++;
        }

        // E. Sinkronisasi Rincian Butir Jawaban Siswa (student_answers)
        if (!empty($item['answers']) && is_array($item['answers']) && $sessionTargetId) {
            // Hapus jawaban lama sesi ini untuk memastikan TIDAK ADA duplikasi
            $stmtDeleteAnswers->execute([$sessionTargetId]);

            // Ambil seluruh ID soal yang valid untuk subject ini di cloud
            $stmtValidQs = $pdo->prepare("SELECT id FROM questions WHERE subject_id = ?");
            $stmtValidQs->execute([$cloudSubjectId]);
            $validQIds = $stmtValidQs->fetchAll(PDO::FETCH_COLUMN);
            $validQMap = array_flip($validQIds);

            foreach ($item['answers'] as $ans) {
                $targetQId = null;

                // 1. Coba cocokkan via hash teks soal jika ada
                if (!empty($ans['question_text_hash']) && isset($questionsCache[$cloudSubjectId][$ans['question_text_hash']])) {
                    $targetQId = $questionsCache[$cloudSubjectId][$ans['question_text_hash']];
                }
                
                // 2. Fallback: gunakan ID lokal jika ID tersebut ada di mapel ini
                if (!$targetQId && !empty($ans['question_id']) && isset($validQMap[(int)$ans['question_id']])) {
                    $targetQId = (int)$ans['question_id'];
                }

                // Simpan jawaban hanya jika targetQId valid ada di database questions cloud
                if ($targetQId && isset($validQMap[$targetQId])) {
                    $stmtInsertAnswer->execute([
                        $sessionTargetId,
                        $targetQId,
                        is_array($ans['answer_data']) ? json_encode($ans['answer_data']) : (string)($ans['answer_data'] ?? ''),
                        (int)($ans['is_doubt'] ?? 0),
                        (int)($ans['time_spent'] ?? 0),
                        $ans['answered_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $totalAnswersCount++;
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Berhasil memproses nilai dari {$labName}.",
        'data' => [
            'inserted_sessions' => $insertedCount,
            'updated_sessions'  => $updatedCount,
            'total_answers'     => $totalAnswersCount,
            'exam_id'           => $cloudExamId,
            'timestamp'         => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan data nilai di server cloud: ' . $e->getMessage()
    ]);
}
