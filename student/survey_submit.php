<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../config/database.php';

// Validasi otentikasi siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$studentId   = (int)$_SESSION['user_id'];
$sessionId   = (int)($_POST['session_id'] ?? 0);
$surveyCode  = trim($_POST['survey_code'] ?? '');
$teacherName = trim($_POST['teacher_name'] ?? '');
$isAnonim    = !empty($_POST['is_anonim']) ? 1 : 0;
$rawJawaban  = $_POST['jawaban'] ?? [];

if ($sessionId <= 0 || empty($surveyCode)) {
    header("Location: dashboard.php");
    exit;
}

// Ambil info session ujian dan siswa
$stmt = $pdo->prepare("
    SELECT qs.*, s.name as subject_name, e.exam_name, e.show_score, stu.full_name, stu.class, stu.nis
    FROM exam_sessions qs
    JOIN subjects s ON qs.subject_id = s.id
    JOIN exams e ON qs.exam_id = e.id
    JOIN students stu ON qs.student_id = stu.id
    WHERE qs.id = ? AND qs.student_id = ?
");
$stmt->execute([$sessionId, $studentId]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: dashboard.php");
    exit;
}

$subjectId = (int)$session['subject_id'];
$examId    = (int)$session['exam_id'];

// Cek apakah sudah pernah isi survei untuk subject ini
$stmtCheck = $pdo->prepare("SELECT id FROM cbt_survey_responden_local WHERE student_id = ? AND subject_id = ? LIMIT 1");
$stmtCheck->execute([$studentId, $subjectId]);
if (!$stmtCheck->fetch()) {
    // Ambil data kuesioner dari cbt_surveys_local untuk mencocokkan tipe dan skor angka
    $stmtSrv = $pdo->prepare("SELECT guru_nama, payload_json FROM cbt_surveys_local WHERE kode_akses = ? LIMIT 1");
    $stmtSrv->execute([$surveyCode]);
    $srvData = $stmtSrv->fetch();

    if ($srvData && empty($teacherName)) {
        $teacherName = $srvData['guru_nama'];
    }

    $questionsMap = [];
    if ($srvData && !empty($srvData['payload_json'])) {
        $payload = json_decode($srvData['payload_json'], true) ?: [];
        foreach (($payload['pertanyaan'] ?? []) as $q) {
            $questionsMap[(int)$q['id']] = $q;
        }
    }

    // Susun jawaban dalam format standar API
    $formattedAnswers = [];
    if (is_array($rawJawaban)) {
        foreach ($rawJawaban as $qId => $val) {
            $qId = (int)$qId;
            $valStr = trim((string)$val);
            $qInfo = $questionsMap[$qId] ?? null;

            $score = null;
            if ($qInfo && $qInfo['tipe'] === 'skala' && is_numeric($valStr)) {
                $score = (float)$valStr;
            } elseif (is_numeric($valStr)) {
                $score = (float)$valStr;
            }

            $formattedAnswers[] = [
                'pertanyaan_id' => $qId,
                'jawaban'       => $valStr,
                'skor_angka'    => $score
            ];
        }
    }

    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CBT-Client';

    $savedNamaSiswa = $isAnonim ? 'Anonim' : $session['full_name'];
    $savedNis       = $isAnonim ? null : $session['nis'];

    // 1. Simpan ke cbt_survey_responden_local
    $stmtInsert = $pdo->prepare("
        INSERT INTO `cbt_survey_responden_local` 
        (`student_id`, `exam_id`, `subject_id`, `kode_akses`, `teacher_name`, `nama_siswa`, `kelas_siswa`, `nisn`, `is_anonim`, `jawaban_json`, `waktu_isi`, `synced`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)
    ");
    $stmtInsert->execute([
        $studentId,
        $examId,
        $subjectId,
        $surveyCode,
        $teacherName,
        $savedNamaSiswa,
        $session['class'],
        $savedNis,
        $isAnonim,
        json_encode($formattedAnswers, JSON_UNESCAPED_UNICODE)
    ]);

    // 2. Catat juga ke tabel log student_surveys
    $stmtLog = $pdo->prepare("
        INSERT INTO `student_surveys` 
        (`student_id`, `exam_id`, `subject_id`, `survey_code`, `teacher_name`, `status`, `submitted_at`)
        VALUES (?, ?, ?, ?, ?, 'submitted', NOW())
    ");
    $stmtLog->execute([
        $studentId,
        $examId,
        $subjectId,
        $surveyCode,
        $teacherName
    ]);
}

// Cek apakah seluruh mata pelajaran dalam ujian ini sudah selesai
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
$stmtTotal->execute([$examId]);
$totalSubjects = (int)$stmtTotal->fetchColumn();

$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND status = 'finished'");
$stmtDone->execute([$examId, $studentId]);
$finishedSubjects = (int)$stmtDone->fetchColumn();
$isAllDone = ($finishedSubjects >= $totalSubjects);

// Aturan Pengalihan Halaman:
// 1. Jika nilai disembunyikan (show_score = 0):
//    - Jika seluruh mapel tuntas atau hanya 1 mapel: Arahkan ke dashboard
//    - Jika masih ada mapel berikutnya: Langsung arahkan ke mapel selanjutnya (take_exam.php)
// 2. Jika nilai ditampilkan (show_score = 1):
//    - Arahkan ke exam_result.php
if (empty($session['show_score'])) {
    if ($totalSubjects <= 1 || $isAllDone) {
        unset($_SESSION['active_exam']);
        header("Location: dashboard.php?msg=exam_finished");
        exit;
    } else {
        // Langsung arahkan ke mata pelajaran berikutnya
        header("Location: take_exam.php");
        exit;
    }
} else {
    if ($isAllDone) {
        unset($_SESSION['active_exam']);
    }
    header("Location: exam_result.php?session_id=" . $sessionId);
    exit;
}
