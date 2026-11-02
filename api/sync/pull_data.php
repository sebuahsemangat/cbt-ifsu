<?php
/**
 * CBT Cloud Endpoint: Penyedia Data Ujian (Pull Sync) untuk Server Lab Offline
 * File: api/sync/pull_data.php
 * Digunakan pada server Cloud (cbt.page.gd) dan dapat diuji pada instalasi lokal
 */

declare(strict_types=1);

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
if (empty($incomingToken) && isset($_GET['token'])) {
    $incomingToken = trim((string)$_GET['token']);
}

// Helper untuk mengirim respon JSON
function sendJsonResponse(array $data, int $httpCode = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Validasi Token
if ($incomingToken !== $syncToken) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Autentikasi gagal: Token sinkronisasi tidak valid atau tidak cocok.'
    ], 401);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ==========================================
// 1. TEST KONEKSI & HANDSHAKE
// ==========================================
if ($action === 'test' || empty($action)) {
    sendJsonResponse([
        'success' => true,
        'message' => 'Koneksi ke CBT Cloud berhasil. Server siap melayani sinkronisasi data.',
        'server_time' => date('Y-m-d H:i:s'),
        'app' => 'CBT Simulation Cloud Endpoint (Pull Data)'
    ]);
}

// ==========================================
// 2. DAFTAR UJIAN YANG TERSEDIA DI CLOUD
// ==========================================
if ($action === 'get_exams') {
    try {
        $stmt = $pdo->query("SELECT * FROM exams ORDER BY id DESC");
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil mapping kelas per ujian
        $stmtClasses = $pdo->query("SELECT exam_id, class_name FROM exam_classes ORDER BY class_name ASC");
        $classesMap = [];
        while ($row = $stmtClasses->fetch(PDO::FETCH_ASSOC)) {
            $classesMap[$row['exam_id']][] = $row['class_name'];
        }

        // Ambil jumlah mapel dan jumlah soal per ujian
        $examList = [];
        foreach ($exams as $e) {
            $examId = (int)$e['id'];

            // Hitung mapel
            $stmtSubj = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
            $stmtSubj->execute([$examId]);
            $subjectCount = (int)$stmtSubj->fetchColumn();

            // Hitung soal
            $stmtQ = $pdo->prepare("SELECT COUNT(*) FROM questions q JOIN subjects s ON q.subject_id = s.id WHERE s.exam_id = ?");
            $stmtQ->execute([$examId]);
            $questionCount = (int)$stmtQ->fetchColumn();

            $assignedClasses = $classesMap[$examId] ?? [];

            // Hitung siswa terkait
            $studentCount = 0;
            if (!empty($assignedClasses)) {
                $placeholders = implode(',', array_fill(0, count($assignedClasses), '?'));
                $stmtStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class IN ($placeholders)");
                $stmtStudents->execute($assignedClasses);
                $studentCount = (int)$stmtStudents->fetchColumn();
            } else {
                $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            }

            $examList[] = [
                'id' => $examId,
                'exam_name' => $e['exam_name'],
                'start_date' => $e['start_date'],
                'end_date' => $e['end_date'],
                'is_active' => (int)$e['is_active'],
                'allow_review' => (int)($e['allow_review'] ?? 0),
                'randomize_questions' => (int)($e['randomize_questions'] ?? 1),
                'show_score' => (int)($e['show_score'] ?? 0),
                'subject_count' => $subjectCount,
                'question_count' => $questionCount,
                'student_count' => $studentCount,
                'classes' => $assignedClasses
            ];
        }

        sendJsonResponse([
            'success' => true,
            'exams' => $examList
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil daftar ujian dari cloud: ' . $e->getMessage()
        ], 500);
    }
}

// ==========================================
// 3. METADATA UJIAN (EXAM, SUBJECTS, CLASSES, TOKENS)
// ==========================================
if ($action === 'get_exam_meta') {
    $examId = (int)($_GET['exam_id'] ?? 0);
    if ($examId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Parameter exam_id tidak valid.'], 400);
    }

    try {
        $stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmtExam->execute([$examId]);
        $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            sendJsonResponse(['success' => false, 'message' => 'Ujian tidak ditemukan di cloud.'], 404);
        }

        // Ambil subjects
        $stmtSubj = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
        $stmtSubj->execute([$examId]);
        $subjects = $stmtSubj->fetchAll(PDO::FETCH_ASSOC);

        // Ambil exam_classes
        $stmtClasses = $pdo->prepare("SELECT * FROM exam_classes WHERE exam_id = ?");
        $stmtClasses->execute([$examId]);
        $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

        // Ambil tokens
        $stmtTokens = $pdo->prepare("SELECT * FROM tokens WHERE exam_id = ?");
        $stmtTokens->execute([$examId]);
        $tokens = $stmtTokens->fetchAll(PDO::FETCH_ASSOC);

        // Ambil subject_surveys jika ada
        $subjectSurveys = [];
        $subjectIds = array_column($subjects, 'id');
        if (!empty($subjectIds)) {
            $inClause = implode(',', array_map('intval', $subjectIds));
            try {
                $stmtSurveys = $pdo->query("SELECT * FROM subject_surveys WHERE subject_id IN ($inClause)");
                $subjectSurveys = $stmtSurveys->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Table might not exist yet, ignore
            }
        }

        sendJsonResponse([
            'success' => true,
            'exam' => $exam,
            'subjects' => $subjects,
            'classes' => $classes,
            'tokens' => $tokens,
            'subject_surveys' => $subjectSurveys
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil metadata ujian: ' . $e->getMessage()
        ], 500);
    }
}

// ==========================================
// 4. DATA SOAL, OPSI, & REFERENSI
// ==========================================
if ($action === 'get_questions') {
    $examId = (int)($_GET['exam_id'] ?? 0);
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

    if ($examId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Parameter exam_id tidak valid.'], 400);
    }

    try {
        // Cari subjek yang terkait
        if ($subjectId > 0) {
            $subjectIds = [$subjectId];
        } else {
            $stmtSubj = $pdo->prepare("SELECT id FROM subjects WHERE exam_id = ?");
            $stmtSubj->execute([$examId]);
            $subjectIds = $stmtSubj->fetchAll(PDO::FETCH_COLUMN);
        }

        if (empty($subjectIds)) {
            sendJsonResponse([
                'success' => true,
                'references' => [],
                'questions' => [],
                'options' => []
            ]);
        }

        $inSubjects = implode(',', array_map('intval', $subjectIds));

        // 1. References
        $stmtRef = $pdo->query("SELECT * FROM `references` WHERE subject_id IN ($inSubjects) ORDER BY id ASC");
        $references = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

        // 2. Questions
        $stmtQ = $pdo->query("SELECT * FROM `questions` WHERE subject_id IN ($inSubjects) ORDER BY id ASC");
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        // 3. Options
        $questionIds = array_column($questions, 'id');
        $options = [];
        if (!empty($questionIds)) {
            // Chunk query jika jumlah soal sangat banyak
            $chunks = array_chunk($questionIds, 200);
            foreach ($chunks as $chunk) {
                $inQ = implode(',', array_map('intval', $chunk));
                $stmtOpt = $pdo->query("SELECT * FROM `options` WHERE question_id IN ($inQ) ORDER BY id ASC");
                $optRows = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
                $options = array_merge($options, $optRows);
            }
        }

        sendJsonResponse([
            'success' => true,
            'references' => $references,
            'questions' => $questions,
            'options' => $options
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil data soal: ' . $e->getMessage()
        ], 500);
    }
}

// ==========================================
// 5. DATA SISWA TERKAIT UJIAN
// ==========================================
if ($action === 'get_students') {
    $examId = (int)($_GET['exam_id'] ?? 0);
    if ($examId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Parameter exam_id tidak valid.'], 400);
    }

    try {
        // Ambil kelas yang terdaftar pada ujian ini
        $stmtClasses = $pdo->prepare("SELECT class_name FROM exam_classes WHERE exam_id = ?");
        $stmtClasses->execute([$examId]);
        $classes = $stmtClasses->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($classes)) {
            $placeholders = implode(',', array_fill(0, count($classes), '?'));
            $stmtStudents = $pdo->prepare("SELECT id, nis, password, full_name, class, kelompok FROM students WHERE class IN ($placeholders) ORDER BY class ASC, full_name ASC");
            $stmtStudents->execute($classes);
        } else {
            // Jika tidak ada batasan kelas khusus, tarik seluruh siswa
            $stmtStudents = $pdo->query("SELECT id, nis, password, full_name, class, kelompok FROM students ORDER BY class ASC, full_name ASC");
        }
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse([
            'success' => true,
            'students' => $students
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil data siswa: ' . $e->getMessage()
        ], 500);
    }
}

// ==========================================
// 6. DAFTAR FILE MEDIA/GAMBAR YANG DIBUTUHKAN
// ==========================================
if ($action === 'get_media_list') {
    $examId = (int)($_GET['exam_id'] ?? 0);
    if ($examId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Parameter exam_id tidak valid.'], 400);
    }

    try {
        $stmtSubj = $pdo->prepare("SELECT id FROM subjects WHERE exam_id = ?");
        $stmtSubj->execute([$examId]);
        $subjectIds = $stmtSubj->fetchAll(PDO::FETCH_COLUMN);

        $mediaList = [];
        $seen = [];

        if (!empty($subjectIds)) {
            $inSubjects = implode(',', array_map('intval', $subjectIds));

            // A. Dari tabel references
            $stmtRef = $pdo->query("SELECT image_path, sound_path, content FROM `references` WHERE subject_id IN ($inSubjects)");
            $refRows = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

            foreach ($refRows as $r) {
                if (!empty($r['image_path'])) {
                    $fn = basename($r['image_path']);
                    $key = 'ref:' . $fn;
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $fullPath = __DIR__ . '/../../assets/uploads/references/' . $fn;
                        $mediaList[] = [
                            'type' => 'references',
                            'file' => $fn,
                            'exists' => file_exists($fullPath),
                            'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                        ];
                    }
                }
                if (!empty($r['sound_path'])) {
                    $fn = basename($r['sound_path']);
                    $key = 'ref:' . $fn;
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $fullPath = __DIR__ . '/../../assets/uploads/references/' . $fn;
                        $mediaList[] = [
                            'type' => 'references',
                            'file' => $fn,
                            'exists' => file_exists($fullPath),
                            'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                        ];
                    }
                }
                // Ekstrak URL gambar dari content HTML
                if (!empty($r['content'])) {
                    preg_match_all('/(?:uploads\/questions\/|assets\/uploads\/references\/)([^"\'\s>]+)/i', $r['content'], $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $rawFile) {
                            $fn = basename($rawFile);
                            $type = (strpos($rawFile, 'references') !== false) ? 'references' : 'questions';
                            $key = $type . ':' . $fn;
                            if (!isset($seen[$key])) {
                                $seen[$key] = true;
                                $subDir = ($type === 'references') ? 'assets/uploads/references/' : 'uploads/questions/';
                                $fullPath = __DIR__ . '/../../' . $subDir . $fn;
                                $mediaList[] = [
                                    'type' => $type,
                                    'file' => $fn,
                                    'exists' => file_exists($fullPath),
                                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                                ];
                            }
                        }
                    }
                }
            }

            // B. Dari tabel questions
            $stmtQ = $pdo->query("SELECT question_text, answer_data FROM `questions` WHERE subject_id IN ($inSubjects)");
            $qRows = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($qRows as $q) {
                // Ekstrak dari question_text HTML
                $combined = $q['question_text'] . ' ' . $q['answer_data'];
                preg_match_all('/(?:uploads\/questions\/|assets\/uploads\/references\/)([^"\'\s>]+)/i', $combined, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $rawFile) {
                        $fn = basename($rawFile);
                        $type = (strpos($rawFile, 'references') !== false) ? 'references' : 'questions';
                        $key = $type . ':' . $fn;
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $subDir = ($type === 'references') ? 'assets/uploads/references/' : 'uploads/questions/';
                            $fullPath = __DIR__ . '/../../' . $subDir . $fn;
                            $mediaList[] = [
                                'type' => $type,
                                'file' => $fn,
                                'exists' => file_exists($fullPath),
                                'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                            ];
                        }
                    }
                }
            }

            // C. Dari tabel options
            $stmtOpt = $pdo->query("SELECT o.option_text FROM `options` o JOIN `questions` q ON o.question_id = q.id WHERE q.subject_id IN ($inSubjects)");
            $optRows = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($optRows as $o) {
                if (!empty($o['option_text'])) {
                    preg_match_all('/(?:uploads\/questions\/|assets\/uploads\/references\/)([^"\'\s>]+)/i', $o['option_text'], $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $rawFile) {
                            $fn = basename($rawFile);
                            $type = (strpos($rawFile, 'references') !== false) ? 'references' : 'questions';
                            $key = $type . ':' . $fn;
                            if (!isset($seen[$key])) {
                                $seen[$key] = true;
                                $subDir = ($type === 'references') ? 'assets/uploads/references/' : 'uploads/questions/';
                                $fullPath = __DIR__ . '/../../' . $subDir . $fn;
                                $mediaList[] = [
                                    'type' => $type,
                                    'file' => $fn,
                                    'exists' => file_exists($fullPath),
                                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                                ];
                            }
                        }
                    }
                }
            }
        }

        sendJsonResponse([
            'success' => true,
            'total_media' => count($mediaList),
            'media' => $mediaList
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil daftar media: ' . $e->getMessage()
        ], 500);
    }
}

// ==========================================
// 7. STREAM / DOWNLOAD FILE MEDIA TUNGGAL
// ==========================================
if ($action === 'download_media') {
    $type = $_GET['type'] ?? 'questions';
    $fileName = basename((string)($_GET['file'] ?? ''));

    if (empty($fileName)) {
        sendJsonResponse(['success' => false, 'message' => 'Nama file tidak boleh kosong.'], 400);
    }

    $baseFolder = ($type === 'references') ? __DIR__ . '/../../assets/uploads/references/' : __DIR__ . '/../../uploads/questions/';
    $filePath = realpath($baseFolder . $fileName);

    // Keamanan: Pastikan path berada dalam folder tujuan (prevent directory traversal)
    if (!$filePath || strpos($filePath, realpath($baseFolder)) !== 0 || !file_exists($filePath)) {
        http_response_code(404);
        echo 'File not found on cloud server.';
        exit;
    }

    $mime = mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    readfile($filePath);
    exit;
}

// Tindakan tidak dikenali
sendJsonResponse([
    'success' => false,
    'message' => 'Aksi tidak valid atau tidak didukung.'
], 400);
