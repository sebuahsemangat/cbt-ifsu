<?php
/**
 * AJAX Handler: Sinkronisasi Data Ujian dari Cloud ke Server Lab Offline (Pull Sync)
 * File: admin/modules/exams/sync_pull_process.php
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
        'message' => 'Sesi admin berakhir. Silakan login kembali.'
    ], 401);
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/CbtCloudSync.php';

// Ambil pengaturan konfigurasi Cloud CBT
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'cbt_cloud_%'");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$cloudUrl   = rtrim($settings['cbt_cloud_sync_url'] ?? 'http://cbt.page.gd', '/');
$syncToken  = $settings['cbt_cloud_sync_token'] ?? 'cbt-cloud-secret-key-2026';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$examId = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : (isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0);

try {
    // ==========================================
    // 1. TEST KONEKSI KE CLOUD
    // ==========================================
    if ($action === 'test_connection') {
        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=test';
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => $res['message'] ?? 'Gagal menghubungkan ke server cloud.',
                'cloud_url' => $cloudUrl
            ]);
        }

        sendJsonResponse([
            'success' => true,
            'message' => $res['message'] ?? 'Koneksi ke CBT Cloud berhasil!',
            'cloud_url' => $cloudUrl,
            'server_time' => $res['server_time'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // ==========================================
    // 2. AMBIL DAFTAR UJIAN DARI CLOUD
    // ==========================================
    if ($action === 'fetch_cloud_exams') {
        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=get_exams';
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengambil daftar ujian dari cloud: ' . ($res['message'] ?? 'Respon tidak valid.')
            ]);
        }

        sendJsonResponse([
            'success' => true,
            'exams' => $res['exams'] ?? []
        ]);
    }

    // ==========================================
    // 3. SINKRONISASI METADATA UJIAN (EXAM, SUBJECTS, CLASSES, TOKENS)
    // ==========================================
    if ($action === 'sync_exam_meta') {
        if ($examId <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'ID Ujian tidak valid.'], 400);
        }

        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=get_exam_meta&exam_id=' . $examId;
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success'] || empty($res['exam'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengunduh metadata ujian dari cloud: ' . ($res['message'] ?? 'Data kosong.')
            ]);
        }

        $exam = $res['exam'];
        $subjects = $res['subjects'] ?? [];
        $classes = $res['classes'] ?? [];
        $tokens = $res['tokens'] ?? [];
        $subjectSurveys = $res['subject_surveys'] ?? [];

        $pdo->beginTransaction();

        // 1. Simpan Ujian
        $stmtEx = $pdo->prepare("
            INSERT INTO exams (id, exam_name, start_date, end_date, is_active, allow_review, randomize_questions, show_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                exam_name = VALUES(exam_name),
                start_date = VALUES(start_date),
                end_date = VALUES(end_date),
                is_active = VALUES(is_active),
                allow_review = VALUES(allow_review),
                randomize_questions = VALUES(randomize_questions),
                show_score = VALUES(show_score)
        ");
        $stmtEx->execute([
            $exam['id'],
            $exam['exam_name'],
            $exam['start_date'],
            $exam['end_date'],
            $exam['is_active'],
            $exam['allow_review'] ?? 0,
            $exam['randomize_questions'] ?? 1,
            $exam['show_score'] ?? 0
        ]);

        // 2. Simpan Pemetaan Kelas (exam_classes)
        $pdo->prepare("DELETE FROM exam_classes WHERE exam_id = ?")->execute([$examId]);
        if (!empty($classes)) {
            $stmtCls = $pdo->prepare("INSERT INTO exam_classes (exam_id, class_name) VALUES (?, ?)");
            foreach ($classes as $c) {
                if (!empty($c['class_name'])) {
                    $stmtCls->execute([$examId, $c['class_name']]);
                }
            }
        }

        // 3. Simpan Mata Pelajaran (subjects)
        if (!empty($subjects)) {
            $stmtSub = $pdo->prepare("
                INSERT INTO subjects (id, exam_id, name, duration, max_score, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    exam_id = VALUES(exam_id),
                    name = VALUES(name),
                    duration = VALUES(duration),
                    max_score = VALUES(max_score),
                    sort_order = VALUES(sort_order),
                    is_active = VALUES(is_active)
            ");
            foreach ($subjects as $s) {
                $stmtSub->execute([
                    $s['id'],
                    $s['exam_id'],
                    $s['name'],
                    $s['duration'],
                    $s['max_score'] ?? 100.00,
                    $s['sort_order'] ?? 0,
                    $s['is_active'] ?? 1
                ]);
            }
        }

        // 4. Simpan Tokens Ujian
        $pdo->prepare("DELETE FROM tokens WHERE exam_id = ?")->execute([$examId]);
        if (!empty($tokens)) {
            $stmtTok = $pdo->prepare("INSERT INTO tokens (id, exam_id, token_code, created_at, expires_at) VALUES (?, ?, ?, ?, ?)");
            foreach ($tokens as $t) {
                $stmtTok->execute([
                    $t['id'],
                    $t['exam_id'],
                    $t['token_code'],
                    $t['created_at'] ?? date('Y-m-d H:i:s'),
                    $t['expires_at'] ?? null
                ]);
            }
        }

        // 5. Simpan Subject Surveys jika ada dan tabel lokal tersedia
        if (!empty($subjectSurveys)) {
            try {
                $stmtSurv = $pdo->prepare("
                    INSERT INTO subject_surveys (id, subject_id, teacher_name, survey_code)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        teacher_name = VALUES(teacher_name),
                        survey_code = VALUES(survey_code)
                ");
                foreach ($subjectSurveys as $ss) {
                    $stmtSurv->execute([
                        $ss['id'],
                        $ss['subject_id'],
                        $ss['teacher_name'],
                        $ss['survey_code']
                    ]);
                }
            } catch (Exception $e) {
                // Abaikan jika tabel tidak aktif
            }
        }

        $pdo->commit();

        sendJsonResponse([
            'success' => true,
            'message' => 'Metadata ujian, kelas, dan mata pelajaran berhasil disinkronkan.',
            'exam_name' => $exam['exam_name'],
            'total_subjects' => count($subjects),
            'total_classes' => count($classes),
            'total_tokens' => count($tokens)
        ]);
    }

    // ==========================================
    // 4. SINKRONISASI SOAL, OPSI, & REFERENSI
    // ==========================================
    if ($action === 'sync_questions') {
        if ($examId <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'ID Ujian tidak valid.'], 400);
        }

        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=get_questions&exam_id=' . $examId;
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengunduh data soal: ' . ($res['message'] ?? 'Koneksi error.')
            ]);
        }

        $references = $res['references'] ?? [];
        $questions  = $res['questions'] ?? [];
        $options    = $res['options'] ?? [];

        $pdo->beginTransaction();

        // 1. Sinkronkan References lebih dahulu (karena questions me-referensi reference_id)
        if (!empty($references)) {
            $stmtRef = $pdo->prepare("
                INSERT INTO `references` (id, subject_id, title, type, content, image_path, sound_path)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    subject_id = VALUES(subject_id),
                    title = VALUES(title),
                    type = VALUES(type),
                    content = VALUES(content),
                    image_path = VALUES(image_path),
                    sound_path = VALUES(sound_path)
            ");
            foreach ($references as $r) {
                $stmtRef->execute([
                    $r['id'],
                    $r['subject_id'],
                    $r['title'],
                    $r['type'] ?? 'text',
                    $r['content'] ?? null,
                    $r['image_path'] ?? null,
                    $r['sound_path'] ?? null
                ]);
            }
        }

        // 2. Sinkronkan Questions
        if (!empty($questions)) {
            $stmtQ = $pdo->prepare("
                INSERT INTO `questions` (id, subject_id, reference_id, question_text, question_type, answer_data)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    subject_id = VALUES(subject_id),
                    reference_id = VALUES(reference_id),
                    question_text = VALUES(question_text),
                    question_type = VALUES(question_type),
                    answer_data = VALUES(answer_data)
            ");
            foreach ($questions as $q) {
                $ansData = is_array($q['answer_data']) ? json_encode($q['answer_data']) : ($q['answer_data'] ?? '{}');
                $refId = !empty($q['reference_id']) ? (int)$q['reference_id'] : null;
                $stmtQ->execute([
                    $q['id'],
                    $q['subject_id'],
                    $refId,
                    $q['question_text'],
                    $q['question_type'],
                    $ansData
                ]);
            }
        }

        // 3. Sinkronkan Options
        if (!empty($options)) {
            $stmtOpt = $pdo->prepare("
                INSERT INTO `options` (id, question_id, option_text, is_correct)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    question_id = VALUES(question_id),
                    option_text = VALUES(option_text),
                    is_correct = VALUES(is_correct)
            ");
            foreach ($options as $o) {
                $stmtOpt->execute([
                    $o['id'],
                    $o['question_id'],
                    $o['option_text'],
                    (int)($o['is_correct'] ?? 0)
                ]);
            }
        }

        $pdo->commit();

        sendJsonResponse([
            'success' => true,
            'message' => 'Soal, opsi, dan referensi stimulus berhasil disinkronkan.',
            'total_references' => count($references),
            'total_questions' => count($questions),
            'total_options' => count($options)
        ]);
    }

    // ==========================================
    // 5. SINKRONISASI DATA SISWA TERKAIT
    // ==========================================
    if ($action === 'sync_students') {
        if ($examId <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'ID Ujian tidak valid.'], 400);
        }

        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=get_students&exam_id=' . $examId;
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengunduh data siswa: ' . ($res['message'] ?? 'Koneksi error.')
            ]);
        }

        $students = $res['students'] ?? [];
        if (empty($students)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Tidak ada data siswa baru yang perlu disinkronkan.',
                'total_students' => 0
            ]);
        }

        $pdo->beginTransaction();

        // Cek kolom kelompok pada tabel students lokal
        $hasKelompok = false;
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM students LIKE 'kelompok'");
            $hasKelompok = ($checkCol && $checkCol->rowCount() > 0);
        } catch (Exception $e) {
            $hasKelompok = false;
        }

        if ($hasKelompok) {
            $stmtStu = $pdo->prepare("
                INSERT INTO students (id, nis, password, full_name, class, kelompok)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    password = VALUES(password),
                    full_name = VALUES(full_name),
                    class = VALUES(class),
                    kelompok = VALUES(kelompok)
            ");
            foreach ($students as $s) {
                $stmtStu->execute([
                    $s['id'],
                    $s['nis'],
                    $s['password'],
                    $s['full_name'],
                    $s['class'] ?? '',
                    $s['kelompok'] ?? null
                ]);
            }
        } else {
            $stmtStu = $pdo->prepare("
                INSERT INTO students (id, nis, password, full_name, class)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    password = VALUES(password),
                    full_name = VALUES(full_name),
                    class = VALUES(class)
            ");
            foreach ($students as $s) {
                $stmtStu->execute([
                    $s['id'],
                    $s['nis'],
                    $s['password'],
                    $s['full_name'],
                    $s['class'] ?? ''
                ]);
            }
        }

        $pdo->commit();

        sendJsonResponse([
            'success' => true,
            'message' => 'Data siswa peserta ujian berhasil disinkronkan.',
            'total_students' => count($students)
        ]);
    }

    // ==========================================
    // 6. PERIKSA DAFTAR MEDIA & BUAT ANTREAN DOWNLOAD
    // ==========================================
    if ($action === 'get_media_queue') {
        if ($examId <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'ID Ujian tidak valid.'], 400);
        }

        $endpoint = $cloudUrl . '/api/sync/pull_data.php?action=get_media_list&exam_id=' . $examId;
        $res = CbtCloudSync::request($endpoint, 'GET', null, $syncToken);

        if (!$res['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Gagal mengambil daftar aset media dari cloud: ' . ($res['message'] ?? 'Koneksi error.')
            ]);
        }

        $mediaList = $res['media'] ?? [];
        $queue = [];
        $alreadyCached = 0;

        foreach ($mediaList as $item) {
            $type = $item['type'] ?? 'questions';
            $fn = basename($item['file'] ?? '');

            if (empty($fn)) continue;

            $localPath = ($type === 'references')
                ? __DIR__ . '/../../../assets/uploads/references/' . $fn
                : __DIR__ . '/../../../uploads/questions/' . $fn;

            // Jika file belum ada atau ukurannya 0, masukkan ke antrean download
            if (!file_exists($localPath) || filesize($localPath) === 0) {
                $queue[] = [
                    'type' => $type,
                    'file' => $fn,
                    'size' => $item['size'] ?? 0
                ];
            } else {
                $alreadyCached++;
            }
        }

        sendJsonResponse([
            'success' => true,
            'total_cloud_files' => count($mediaList),
            'already_cached' => $alreadyCached,
            'queue_count' => count($queue),
            'queue' => $queue
        ]);
    }

    // ==========================================
    // 7. DOWNLOAD FILE MEDIA TUNGGAL DARI CLOUD
    // ==========================================
    if ($action === 'download_single_media') {
        $type = $_POST['type'] ?? ($_GET['type'] ?? 'questions');
        $fileName = basename((string)($_POST['file'] ?? ($_GET['file'] ?? '')));

        if (empty($fileName)) {
            sendJsonResponse(['success' => false, 'message' => 'Nama file tidak boleh kosong.'], 400);
        }

        $destFolder = ($type === 'references')
            ? __DIR__ . '/../../../assets/uploads/references/'
            : __DIR__ . '/../../../uploads/questions/';

        if (!is_dir($destFolder)) {
            @mkdir($destFolder, 0777, true);
        }

        $destPath = $destFolder . $fileName;

        // URL download dari Cloud
        $downloadUrl = $cloudUrl . '/api/sync/pull_data.php?action=download_media&type=' . urlencode($type) . '&file=' . urlencode($fileName);

        $downloadRes = CbtCloudSync::downloadFile($downloadUrl, $destPath, $syncToken);

        if (!$downloadRes['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => "Gagal mengunduh file {$fileName}: " . ($downloadRes['message'] ?? 'Error tidak diketahui.'),
                'file' => $fileName
            ]);
        }

        sendJsonResponse([
            'success' => true,
            'message' => "File {$fileName} berhasil diunduh.",
            'file' => $fileName,
            'bytes' => $downloadRes['bytes'] ?? 0
        ]);
    }

    sendJsonResponse([
        'success' => false,
        'message' => 'Aksi sinkronisasi tidak valid.'
    ], 400);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], 500);
}
