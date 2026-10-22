<?php
session_start();
// Cegah caching di iOS/Safari yang menyebabkan bug waktu habis atau skip mapel (Bfcache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Past date

require_once '../config/database.php';

// 1. Keamanan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// === AMBIL SETTING DARI DATABASE ===
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

$bimbel_name = $settings['bimbel_name'] ?? 'BIMBINGAN BELAJAR LITA';
$app_name    = $settings['app_name'] ?? 'CBT Simulation';
$bimbel_logo = $settings['bimbel_logo'] ?? 'logo-bimbel.png';
$logo_path   = '../assets/img/' . $bimbel_logo;
$logo_version = (file_exists($logo_path) && is_file($logo_path)) ? '?v=' . filemtime($logo_path) : '';

// 1. Cek apakah ada input POST (Attempt Masuk Ujian Baru)
if (isset($_POST['exam_id'])) {
    $exam_id = $_POST['exam_id'];
    $token_input = strtoupper(trim($_POST['token'] ?? ''));
    
    // 1a. Validasi Jadwal Ujian Terlebih Dahulu (Anti Inspect Element / Bypass)
    $stmtCheck = $pdo->prepare("SELECT start_date, end_date FROM exams WHERE id = ?");
    $stmtCheck->execute([$exam_id]);
    $examSchedule = $stmtCheck->fetch();
    
    $now = date('Y-m-d H:i:s');
    if (!$examSchedule) {
        $_SESSION['error_token'] = "Ujian tidak ditemukan.";
        header("Location: dashboard.php");
        exit;
    }
    
    if ($now < $examSchedule['start_date']) {
        $_SESSION['error_token'] = "Ujian belum dimulai! Akses ditolak.";
        header("Location: dashboard.php");
        exit;
    }
    
    if ($now > $examSchedule['end_date']) {
        $_SESSION['error_token'] = "Jadwal ujian telah berakhir. Akses ditolak.";
        header("Location: dashboard.php");
        exit;
    }

    // 1b. Validasi Kelas Siswa (Akses hanya untuk kelas yang diizinkan)
    $stmtClassCheck = $pdo->prepare("
        SELECT COUNT(*) FROM exam_classes ec 
        JOIN students s ON s.class = ec.class_name 
        WHERE ec.exam_id = ? AND s.id = ?
    ");
    $stmtClassCheck->execute([$exam_id, $student_id]);
    if ($stmtClassCheck->fetchColumn() == 0) {
        $_SESSION['error_token'] = "Akses ditolak. Ujian ini tidak dibuka untuk kelas Anda.";
        header("Location: dashboard.php");
        exit;
    }

    // 1c. Verifikasi Token
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE exam_id = ? AND token_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$exam_id, $token_input]);
    if (!$stmt->fetch()) {
        $_SESSION['error_token'] = "Token tidak valid/kadaluwarsa.";
        header("Location: dashboard.php");
        exit;
    }
    // Jika valid, set session
    $_SESSION['active_exam'] = $exam_id;
    
    // PRG Pattern: Redirect ke GET request (HTTP 303) dengan query param unik
    // untuk memaksa browser benar-benar membuang riwayat POST
    header("Location: take_exam.php?ref=" . time(), true, 303);
    exit;
}
    
// Redirect kembali jika ada POST dummy (pencegahan ekstra)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['exam_id']) && !isset($_POST['session_id'])) {
    header("Location: take_exam.php", true, 303);
    exit;
}

// 2. Ambil dari session jika tidak ada POST
$exam_id = $_SESSION['active_exam'] ?? null;

if (!$exam_id) {
    header("Location: dashboard.php");
    exit;
}

// 2. Ambil Info Ujian
$stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmtExam->execute([$exam_id]);
$exam = $stmtExam->fetch();

if (!$exam) die("Exam not found");

$nowCheck = date('Y-m-d H:i:s');
if ($nowCheck < $exam['start_date']) {
    unset($_SESSION['active_exam']);
    $_SESSION['error_token'] = "Ujian belum dimulai! Akses ditolak.";
    header("Location: dashboard.php");
    exit;
}
if ($nowCheck > $exam['end_date']) {
    unset($_SESSION['active_exam']);
    $_SESSION['error_token'] = "Jadwal ujian telah berakhir.";
    header("Location: dashboard.php");
    exit;
}

// Validasi Kelas Siswa untuk sesi aktif
$stmtClassCheck = $pdo->prepare("
    SELECT COUNT(*) FROM exam_classes ec 
    JOIN students s ON s.class = ec.class_name 
    WHERE ec.exam_id = ? AND s.id = ?
");
$stmtClassCheck->execute([$exam_id, $student_id]);
if ($stmtClassCheck->fetchColumn() == 0) {
    unset($_SESSION['active_exam']);
    $_SESSION['error_token'] = "Akses ditolak. Ujian ini tidak dibuka untuk kelas Anda.";
    header("Location: dashboard.php");
    exit;
}

// 3. Tentukan Mata Pelajaran Saat Ini
// Ambil semua subject urut sort_order ASC (sesuai urutan yang diatur admin)
$stmtSub = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
$stmtSub->execute([$exam_id]);
$subjects = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

$currentSubject = null;
$currentSession = null;
$isAllFinished = true;

foreach ($subjects as $sub) {
    $stmtSess = $pdo->prepare("SELECT * FROM exam_sessions WHERE student_id = ? AND subject_id = ?");
    $stmtSess->execute([$student_id, $sub['id']]);
    $sess = $stmtSess->fetch();
    
    if ($sess && $sess['status'] === 'finished') {
        continue;
    }
    
    // Found ongoing or not started
    $currentSubject = $sub;
    $currentSession = $sess; 
    $isAllFinished = false;
    break; 
}

if ($isAllFinished) {
    // Hitung total skor jika perlu, lalu redirect ke dashboard
    // Optional: Insert ke tabel scores (aggregation)
    $stmtAgg = $pdo->prepare("SELECT SUM(score) as total, COUNT(*) as cnt FROM exam_sessions WHERE exam_id = ? AND student_id = ?");
    $stmtAgg->execute([$exam_id, $student_id]);
    $agg = $stmtAgg->fetch();
    $avgScore = $agg['cnt'] > 0 ? $agg['total'] / $agg['cnt'] : 0; 
    
    // Check if score exists first
    $chkScore = $pdo->prepare("SELECT id FROM scores WHERE student_id = ? AND exam_id = ?");
    $chkScore->execute([$student_id, $exam_id]);
    if (!$chkScore->fetch()) {
        $pdo->prepare("INSERT INTO scores (student_id, exam_id, total_score) VALUES (?, ?, ?)")
            ->execute([$student_id, $exam_id, $avgScore]);
    }
        
    unset($_SESSION['active_exam']);
    header("Location: dashboard.php?msg=all_done");
    exit;
}

// 4. Start Session
if (!$currentSession) {
    $duration = (int)$currentSubject['duration'];
    $durationSec = $duration * 60;
    $stmtIns = $pdo->prepare("INSERT INTO exam_sessions (student_id, exam_id, subject_id, start_time, end_time, remaining_seconds, status) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), ?, 'ongoing')");
    $stmtIns->execute([$student_id, $exam_id, $currentSubject['id'], $duration, $durationSec]);
    $sessionId = $pdo->lastInsertId();
    $currentSession = ['id' => $sessionId, 'start_time' => date('Y-m-d H:i:s'), 'remaining_seconds' => $durationSec, 'question_order' => null];
} else {
    $sessionId = $currentSession['id'];
}

// 5. Fetch Data (Questions, References, Answers)
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ? ORDER BY id ASC");
$stmtQ->execute([$currentSubject['id']]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$stmtRef = $pdo->prepare("SELECT * FROM `references` WHERE subject_id = ?");
$stmtRef->execute([$currentSubject['id']]);
$references = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
$refMap = [];
foreach ($references as $r) $refMap[$r['id']] = $r;

// Pengaturan Acak / Berurutan (Default: Random / Acak)
$isRandom = !isset($exam['randomize_questions']) || $exam['randomize_questions'] == 1;
$savedOrder = !empty($currentSession['question_order']) ? json_decode($currentSession['question_order'], true) : null;

$finalQuestions = [];

if (is_array($savedOrder) && !empty($savedOrder)) {
    // Gunakan urutan yang sudah tersimpan untuk sesi ini (konsisten saat reload/refresh)
    $qMap = [];
    foreach ($questions as $q) {
        $qMap[$q['id']] = $q;
    }
    foreach ($savedOrder as $qid) {
        if (isset($qMap[$qid])) {
            $q = $qMap[$qid];
            $rid = $q['reference_id'];
            $q['ref_data'] = ($rid && isset($refMap[$rid])) ? $refMap[$rid] : null;
            $finalQuestions[] = $q;
            unset($qMap[$qid]);
        }
    }
    // Jika ada soal baru yang belum terdaftar di urutan sesi sebelumnya
    foreach ($qMap as $q) {
        $rid = $q['reference_id'];
        $q['ref_data'] = ($rid && isset($refMap[$rid])) ? $refMap[$rid] : null;
        $finalQuestions[] = $q;
    }
} else {
    // Buat urutan baru untuk sesi ini
    $blocks = [];
    foreach ($questions as $q) {
        $rid = $q['reference_id'];
        if ($rid) {
            if (!isset($blocks["ref_$rid"])) {
                $blocks["ref_$rid"] = [
                    'type' => 'ref',
                    'data' => $refMap[$rid] ?? null,
                    'questions' => []
                ];
            }
            $blocks["ref_$rid"]['questions'][] = $q;
        } else {
            // Orphan
            $blocks["q_" . $q['id']] = [
                'type' => 'single',
                'data' => null,
                'questions' => [$q]
            ];
        }
    }

    $blockKeys = array_keys($blocks);
    if ($isRandom) {
        shuffle($blockKeys); // Acak blok referensi & soal tunggal
    }

    foreach ($blockKeys as $k) {
        $blk = $blocks[$k];
        $qs = $blk['questions'];
        if ($isRandom) {
            shuffle($qs); // Acak soal di dalam referensi yang sama
        }
        foreach ($qs as $q) {
            $q['ref_data'] = $blk['data'];
            $finalQuestions[] = $q;
        }
    }

    // Simpan urutan soal ke exam_sessions agar tidak berubah jika halaman di-reload
    if (!empty($finalQuestions)) {
        $orderedIds = array_column($finalQuestions, 'id');
        $stmtSaveOrder = $pdo->prepare("UPDATE exam_sessions SET question_order = ? WHERE id = ?");
        $stmtSaveOrder->execute([json_encode($orderedIds), $sessionId]);
    }
}

// Fetch Saved Answers
$stmtAns = $pdo->prepare("SELECT * FROM student_answers WHERE session_id = ?");
$stmtAns->execute([$sessionId]);
$savedAnswers = $stmtAns->fetchAll(PDO::FETCH_ASSOC);
$ansMap = [];
foreach ($savedAnswers as $sa) {
    $ansMap[$sa['question_id']] = [
        'data' => json_decode($sa['answer_data'] ?? '', true),
        'doubt' => $sa['is_doubt']
    ];
}

// Timer Calculation (Diselesaikan lewat DB agar kebal dari beda Timezone PHP vs MySQL)
$stmtTime = $pdo->prepare("SELECT remaining_seconds, TIMESTAMPDIFF(SECOND, start_time, NOW()) as elapsed FROM exam_sessions WHERE id = ?");
$stmtTime->execute([$sessionId]);
$timeData = $stmtTime->fetch();

$durationSec = (int)$currentSubject['duration'] * 60;

// Jika remaining_seconds sudah pernah tercatat (termasuk resume setelah komputer mati/restart)
if ($timeData && $timeData['remaining_seconds'] !== null) {
    $remainingSec = max(0, (int)$timeData['remaining_seconds']);
} else {
    // Fallback jika remaining_seconds belum tersimpan
    $elapsed = $timeData ? (int)$timeData['elapsed'] : 0;
    if ($elapsed < 0) $elapsed = 0;
    $remainingSec = max(0, $durationSec - $elapsed);
}

// Perbarui end_time dan remaining_seconds saat resume/buka halaman agar sisa waktu terjaga
$stmtSync = $pdo->prepare("UPDATE exam_sessions SET end_time = DATE_ADD(NOW(), INTERVAL ? SECOND), remaining_seconds = ? WHERE id = ?");
$stmtSync->execute([$remainingSec, $remainingSec, $sessionId]);

// Ambil pengaturan batas minimal durasi pengerjaan ujian (%)
$stmtMinPct = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'min_exam_duration_percent'");
$minDurationPercent = (int)($stmtMinPct->fetchColumn() ?: 50);

$subjectDuration = (int)$currentSubject['duration'];
$minDurationMinutes = (int)ceil($subjectDuration * ($minDurationPercent / 100));
$minDurationSeconds = $minDurationMinutes * 60;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian: <?= htmlspecialchars($currentSubject['name']) ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Local Fonts: Plus Jakarta Sans & Poppins -->
    <link href="../assets/vendor/fonts/fonts.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/<?= htmlspecialchars($bimbel_logo) . $logo_version ?>">
    
    <style>
        :root {
            /* Identitas Brand Sekolah: SMK Informatika Sumedang */
            --brand-primary: #1e40af;        /* Royal Blue */
            --brand-primary-hover: #1d4ed8;  /* Vibrant Blue */
            --brand-primary-dark: #0f274e;   /* Deep Blue */
            --brand-primary-subtle: #eff6ff; /* Soft Ice Blue */
            --brand-accent: #f59e0b;         /* Golden Amber dari Logo */
            --brand-accent-hover: #d97706;
            --ans-hover: #eff6ff;
            --canvas-bg: #f8fafc;
            --surface-card: #ffffff;
            --border-subtle: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background-color: var(--canvas-bg); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
            overflow-x: hidden; 
            color: #1e293b; 
            -webkit-font-smoothing: antialiased; 
        }
        .navbar-exam { 
            background: linear-gradient(135deg, #0d2347 0%, #1e3a8a 100%); 
            box-shadow: 0 4px 20px rgba(13, 35, 71, 0.25); 
            padding: 0.75rem 0; 
            border-bottom: 2px solid var(--brand-accent);
        }
        .navbar-brand { 
            color: #fff !important; 
            white-space: normal; 
            max-width: 80%; 
            font-size: 1.05rem; 
            display: flex;
            align-items: center;
        }
        .navbar-logo { 
            width: 36px; 
            height: 36px; 
            object-fit: contain; 
            background: #ffffff;
            border-radius: 50%;
            padding: 2.5px;
            border: 2px solid var(--brand-accent);
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.35);
        }
        .student-name { color: rgba(255,255,255,0.92); font-weight: 500; font-size: 0.925rem; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .status-text { font-size: 0.85rem; color: rgba(255,255,255,0.8); }
        
        .question-info-bar { 
            background-color: white; 
            border-radius: 16px; 
            padding: 1.25rem 1.5rem; 
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04); 
            margin-bottom: 1.5rem; 
            border-top: 4px solid var(--brand-accent); 
            border-left: 1px solid var(--border-subtle);
            border-right: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }
        .question-number { font-size: 1.15rem; font-weight: 700; color: var(--brand-primary); }
        
        .font-size-controls { display: flex; gap: 0.45rem; margin-top: 0.7rem; }
        .font-size-btn { padding: 0.4rem 1rem; border: 1.5px solid #e2e8f0; background-color: white; border-radius: 10px; cursor: pointer; transition: all 0.25s; font-weight: 600; color: #64748b; font-size: 0.88rem; }
        .font-size-btn.active, .font-size-btn:hover { background-color: var(--brand-primary); color: white; border-color: var(--brand-primary); }
        
        .timer-display { background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; padding: 0.55rem 1.2rem; border-radius: 12px; font-size: 1.15rem; font-weight: 700; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); letter-spacing: 1px; }
        .subject-name { color: #64748b; font-size: 0.9rem; margin-top: 0.5rem; font-weight: 500; }
        
        .content-card { 
            background-color: white; 
            border-radius: 18px; 
            padding: 2rem; 
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); 
            height: 100%; 
            min-height: 450px; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid var(--border-subtle);
        }
        .reference-section { border-right: 1.5px solid #f1f5f9; padding-right: 1.5rem; height: 100%; overflow-y: auto; scrollbar-width: thin; }
        .reference-title { font-weight: 700; color: #0f172a; margin-bottom: 1.2rem; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .reference-content { color: #334155; font-size: 0.98rem; line-height: 1.8; }
        
        .question-section { padding-left: 1.5rem; }
        .question-text { font-size: 1.05rem; line-height: 1.8; color: #0f172a; margin-bottom: 2rem; font-weight: 500; }
        
        .font-small .question-text, .font-small .answer-option label, .font-small .reference-content { font-size: 0.95rem; }
        .font-medium .question-text, .font-medium .answer-option label, .font-medium .reference-content { font-size: 1.05rem; }
        .font-large .question-text, .font-large .answer-option label, .font-large .reference-content { font-size: 1.2rem; }
        
        .answer-option { padding: 1.15rem; border: 2px solid #f1f5f9; border-radius: 14px; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: start; background: #ffffff; }
        .answer-option:hover { border-color: #93c5fd; background-color: var(--ans-hover); transform: translateX(4px); }
        .answer-option.selected { border-color: var(--brand-primary); background-color: var(--ans-hover); box-shadow: 0 4px 14px rgba(30, 64, 175, 0.12); }
        .answer-option input { width: 22px; height: 22px; margin-right: 1rem; cursor: pointer; margin-top: 3px; accent-color: var(--brand-primary); }
        .answer-option label { cursor: pointer; margin: 0; width: 100%; color: #1e293b; font-weight: 500; }
        
        .bottom-navigation { 
            background-color: white; 
            border-radius: 16px; 
            padding: 1.25rem 1.5rem; 
            box-shadow: 0 -4px 15px rgba(15, 23, 42, 0.04); 
            margin-top: 2rem; 
            border-bottom: 4px solid var(--brand-primary); 
            border-left: 1px solid var(--border-subtle);
            border-right: 1px solid var(--border-subtle);
            border-top: 1px solid var(--border-subtle);
        }
        .nav-container { display: flex; align-items: center; justify-content: center; gap: 1rem; }
        .nav-btn { padding: 0.75rem 1.5rem; font-weight: 700; border-radius: 12px; transition: all 0.25s ease; font-size: 0.95rem; width: 180px; }
        
        .btn-outline-secondary { border: 2px solid #e2e8f0; color: #475569; background: #ffffff; }
        .btn-outline-secondary:hover { background-color: #f1f5f9; color: #0f172a; border-color: #cbd5e1; transform: translateY(-1px); }
        .btn-primary { 
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); 
            border: none; 
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
            color: #ffffff;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { 
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
            transform: translateY(-2px); 
            box-shadow: 0 6px 18px rgba(29, 78, 216, 0.35); 
            color: #ffffff;
        }
        
        .btn-doubt { 
            background-color: #fbbf24; 
            color: #451a03; 
            border: none; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.6rem; 
            transition: all 0.25s ease; 
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.25);
        }
        .btn-doubt input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--brand-primary); }
        .btn-doubt:hover { 
            background-color: #f59e0b; 
            transform: translateY(-2px); 
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35); 
        }
        
        .btn-outline-primary { 
            border: 2px solid var(--brand-primary); 
            color: var(--brand-primary); 
            border-radius: 12px; 
            font-weight: 600;
        }
        .btn-outline-primary:hover { 
            background-color: var(--brand-primary); 
            border-color: var(--brand-primary);
            color: white; 
        }
        
        .modal-question-list .question-number-btn { 
            width: 48px; 
            height: 48px; 
            border-radius: 12px; 
            font-weight: 600; 
            transition: all 0.2s ease; 
            border: 2px solid #e2e8f0; 
            background: white; 
            color: #64748b; 
        }
        .question-number-btn:hover { 
            border-color: #93c5fd; 
            background: #eff6ff; 
        }
        .question-number-btn.answered { 
            background-color: #10b981 !important; 
            color: #ffffff !important; 
            border-color: #10b981 !important; 
        }
        .question-number-btn.doubt { 
            background-color: #fbbf24 !important; 
            color: #451a03 !important; 
            border-color: #fbbf24 !important; 
        }
        .question-number-btn.current { 
            border: 3px solid var(--brand-primary) !important; 
            color: var(--brand-primary); 
            z-index: 10; 
            transform: scale(1.1); 
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25); 
        }
        .question-number-btn.current:not(.answered):not(.doubt) { 
            background-color: var(--brand-primary) !important; 
            color: white !important; 
        }
        
        .modal-header { font-weight: 700; border: none; }
        .modal-content { border-radius: 20px; border: none; }
        
        @media (max-width: 768px) {
            .navbar-exam .navbar-brand { display: none !important; }
            .navbar-exam .exam-user-info { width: 100%; justify-content: space-between; }
            .nav-container { flex-direction: column; width: 100%; }
            .nav-btn { width: 100%; }
            .reference-section { border-right: none; border-bottom: 2px solid #f1f5f9; padding-right: 0; padding-bottom: 1.5rem; margin-bottom: 1.8rem; }
            .question-section { padding-left: 0; }

            /* Perkecil kontrol info bar di mobile */
            .font-size-btn { padding: 0.3rem 0.75rem; font-size: 0.82rem; border-radius: 8px; }
            .font-size-controls { gap: 0.35rem; margin-top: 0.5rem; }
            .timer-display { padding: 0.35rem 0.75rem; font-size: 0.9rem; border-radius: 8px; }
            .btn-daftar-soal { padding: 0.35rem 0.85rem !important; font-size: 0.82rem !important; }
            .question-info-bar { padding: 1rem; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-exam">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-none d-md-flex align-items-center" href="#">
            <img src="../assets/img/<?= htmlspecialchars($bimbel_logo) . $logo_version ?>" alt="Logo" class="navbar-logo me-2">
            <span>CBT - <?= htmlspecialchars($bimbel_name) ?></span>
        </a>
        <div class="d-flex align-items-center gap-3 exam-user-info">
            <div id="connection-indicator" class="student-name d-flex align-items-center gap-2">
                <span class="status-dot bg-success"></span>
                <span class="status-text">Terhubung</span>
            </div>
            <div class="student-name">
                <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($_SESSION['full_name']) ?>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">
    <!-- Question Info Bar -->
    <div class="question-info-bar">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="question-number">
                    <i class="bi bi-journal-text me-2"></i>Soal Nomor <span id="current-question-number">1</span>
                </div>
                <div class="font-size-controls">
                    <button class="font-size-btn" onclick="setFontSize('small')">A</button>
                    <button class="font-size-btn active" onclick="setFontSize('medium')"><strong>A</strong></button>
                    <button class="font-size-btn" onclick="setFontSize('large')"><strong style="font-size: 1.2em;">A</strong></button>
                </div>
            </div>
            <div class="col-md-8 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column align-items-md-end">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="timer-display">
                            <i class="bi bi-clock-fill me-2"></i><span id="exam-timer">--:--:--</span>
                        </div>
                        <button class="btn btn-outline-primary btn-daftar-soal" data-bs-toggle="modal" data-bs-target="#questionListModal">
                            <i class="bi bi-list-ol me-2"></i>Daftar Soal
                        </button>
                    </div>
                    <div class="subject-name">
                        <i class="bi bi-book me-2"></i>Mapel: <strong><?= htmlspecialchars($currentSubject['name']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-card font-medium" id="main-content">
        <div class="row h-100">
            <!-- Reference Section (Left) -->
            <div class="col-md-5" id="ref-col" style="display: none;">
                <div class="reference-section">
                    <h6 class="reference-title">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>Referensi
                    </h6>
                    <div id="reference-content-body" class="reference-content">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>

            <!-- Question Section (Right) -->
            <div class="col-md-12" id="q-col">
                <div class="question-section">
                    <div class="question-text" id="question-text">
                        <!-- Filled by JS -->
                    </div>
                    <div class="answers" id="answers-container">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-navigation">
        <div class="nav-container">
            <div class="nav-item-side">
                <button class="btn btn-outline-secondary nav-btn w-100" id="btn-prev" onclick="navStep(-1)">
                    <i class="bi bi-chevron-left me-2"></i>Sebelumnya
                </button>
            </div>
            <div class="nav-item-center">
                <button class="btn btn-doubt nav-btn w-100" type="button" onclick="toggleDoubt(event)">
                    <input type="checkbox" id="doubt-checkbox" pointer-events="none">
                    <i class="bi bi-question-circle me-1"></i>Ragu
                </button>
            </div>
            <div class="nav-item-side">
                <button class="btn btn-primary nav-btn w-100" id="btn-next" onclick="navStep(1)">
                    Berikutnya<i class="bi bi-chevron-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Batas Waktu Minimal Belum Terpenuhi -->
<div class="modal fade" id="minDurationWarningModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #b45309 0%, #d97706 100%); color: #ffffff;">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Belum Dapat Mengumpulkan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width: 70px; height: 70px; font-size: 2.2rem;">
                        <i class="bi bi-hourglass-split"></i>
                    </span>
                </div>
                <h5 class="fw-bold text-dark mb-2">Batas Minimal Pengerjaan Belum Tercapai</h5>
                <p class="text-secondary mb-3 small" id="min-duration-msg">
                    Siswa belum diizinkan mengumpulkan jawaban sebelum batas minimal waktu terlewati.
                </p>
                <div class="p-3 rounded-3 bg-light border text-start small mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Durasi Mapel:</span>
                        <strong id="md-total-dur">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Batas Minimal (<?= $minDurationPercent ?>%):</span>
                        <strong id="md-min-dur" class="text-warning">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Waktu Pengerjaan Anda:</span>
                        <strong id="md-elapsed-dur">-</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-semibold text-danger">Tersisa Waktu Tunggu:</span>
                        <strong id="md-remaining-wait" class="text-danger fs-6">-</strong>
                    </div>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-lightbulb text-warning me-1"></i>Silakan manfaatkan sisa waktu ini untuk memeriksa kembali jawaban Anda.
                </div>
            </div>
            <div class="modal-footer border-0 p-3 pt-0 justify-content-center">
                <button type="button" class="btn btn-warning px-4 py-2 fw-bold text-white shadow-sm" data-bs-dismiss="modal" style="border-radius: 10px;">
                    <i class="bi bi-arrow-left me-1"></i>Kembali Periksa Jawaban
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Konfirmasi Selesai -->
<div class="modal fade" id="confirmFinishModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #0d2347 0%, #1e3a8a 100%); color: #ffffff; border-bottom: 3px solid var(--brand-accent);">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Konfirmasi Selesai</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="confirm-message" class="fs-5 mb-0 fw-semibold text-secondary">
                    Apakah Anda yakin ingin menyelesaikan mata pelajaran ini?
                </div>
                <div id="doubt-warning" class="alert alert-warning mt-3 mb-0 d-none text-start" style="border-radius: 12px; border-left: 4px solid #f59e0b;">
                    <i class="bi bi-info-circle-fill me-2 text-warning"></i>
                    Masih ada <strong id="doubt-count">0</strong> soal yang ditandai <strong>ragu-ragu</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 p-3 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-light px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid #cbd5e1;">BATAL</button>
                <button type="button" class="btn px-4 py-2 fw-bold text-white" onclick="submitExamForce()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 10px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">YA, SELESAI</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Question List -->
<div class="modal fade" id="questionListModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #0d2347 0%, #1e3a8a 100%); color: #ffffff; border-bottom: 3px solid var(--brand-accent);">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-grid-3x3-gap-fill text-warning me-2"></i>Daftar Nomor Soal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 d-flex flex-wrap gap-3 align-items-center bg-light p-3 rounded-3" style="border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center font-monospace small">
                        <span class="badge rounded-pill me-2" style="background-color: #10b981; width: 14px; height: 14px; padding: 0;">&nbsp;</span> <span class="fw-semibold text-secondary">Sudah Dijawab</span>
                    </div>
                    <div class="d-flex align-items-center font-monospace small">
                        <span class="badge rounded-pill me-2" style="background-color: #fbbf24; width: 14px; height: 14px; padding: 0;">&nbsp;</span> <span class="fw-semibold text-secondary">Ragu-ragu</span>
                    </div>
                    <div class="d-flex align-items-center font-monospace small">
                        <span class="badge rounded-pill me-2" style="background-color: var(--brand-primary); width: 14px; height: 14px; padding: 0;">&nbsp;</span> <span class="fw-semibold text-secondary">Sedang Dikerjakan</span>
                    </div>
                    <div class="d-flex align-items-center font-monospace small">
                        <span class="badge rounded-pill border me-2" style="background-color: #ffffff; border-color: #cbd5e1 !important; width: 14px; height: 14px; padding: 0;">&nbsp;</span> <span class="fw-semibold text-secondary">Belum Dijawab</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 modal-question-list my-3" id="modal-grid">
                    <!-- Filled by JS -->
                </div>
                <hr style="border-color: #e2e8f0;">
                <div class="d-grid mt-3">
                    <button class="btn btn-lg fw-bold text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 12px; padding: 0.85rem; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);" onclick="finishExam()">
                        <i class="bi bi-check2-circle me-2"></i>SELESAI & KUMPULKAN UJIAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form to Submit -->
<form id="submit-form" action="submit_subject.php" method="POST">
    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
</form>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    const EXAM_DATA = {
        questions: <?= json_encode($finalQuestions) ?>,
        ansMap: <?= json_encode($ansMap) ?>,
        remainingSeconds: <?= $remainingSec ?>,
        totalDurationSeconds: <?= $durationSec ?>,
        minDurationSeconds: <?= $minDurationSeconds ?>,
        minDurationMinutes: <?= $minDurationMinutes ?>,
        minDurationPercent: <?= $minDurationPercent ?>,
        sessionId: <?= $sessionId ?>
    };

    <?php if (isset($_GET['err']) && $_GET['err'] === 'min_duration'): ?>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof finishExam === 'function') {
            finishExam();
        }
    });
    <?php endif; ?>
</script>

<script src="../assets/js/exam.js?v=<?= filemtime(__DIR__ . '/../assets/js/exam.js') ?>"></script>
</body>
</html>