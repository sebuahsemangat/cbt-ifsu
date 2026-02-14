<?php
session_start();
require_once '../config/database.php';

// 1. Keamanan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// 1. Cek apakah ada input POST (Attempt Masuk Ujian Baru)
if (isset($_POST['exam_id'])) {
    $exam_id = $_POST['exam_id'];
    $token_input = strtoupper(trim($_POST['token'] ?? ''));
    
    // Verifikasi Token
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE exam_id = ? AND token_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$exam_id, $token_input]);
    if (!$stmt->fetch()) {
        $_SESSION['error_token'] = "Token tidak valid/kadaluwarsa.";
        header("Location: dashboard.php");
        exit;
    }
    // Jika valid, set session
    $_SESSION['active_exam'] = $exam_id;
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

// 3. Tentukan Mata Pelajaran Saat Ini
// Ambil semua subject urut ID Desc
$stmtSub = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ? ORDER BY id DESC");
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
    $duration = $currentSubject['duration'];
    $stmtIns = $pdo->prepare("INSERT INTO exam_sessions (student_id, exam_id, subject_id, start_time, end_time, status) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), 'ongoing')");
    $stmtIns->execute([$student_id, $exam_id, $currentSubject['id'], $duration]);
    $sessionId = $pdo->lastInsertId();
    $currentSession = ['id' => $sessionId, 'start_time' => date('Y-m-d H:i:s')];
} else {
    $sessionId = $currentSession['id'];
}

// 5. Fetch Data (Questions, References, Answers)
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ?");
$stmtQ->execute([$currentSubject['id']]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$stmtRef = $pdo->prepare("SELECT * FROM `references` WHERE subject_id = ?");
$stmtRef->execute([$currentSubject['id']]);
$references = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
$refMap = [];
foreach ($references as $r) $refMap[$r['id']] = $r;

// Group & Shuffle
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

// Shuffle blocks
$blockKeys = array_keys($blocks);
shuffle($blockKeys);

$finalQuestions = [];
foreach ($blockKeys as $k) {
    $blk = $blocks[$k];
    $qs = $blk['questions'];
    shuffle($qs); // Shuffle questions inside the block
    foreach ($qs as $q) {
        $q['ref_data'] = $blk['data']; // Attach ref data to question for easy access
        $finalQuestions[] = $q;
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

// Timer Calculation
$durationSec = $currentSubject['duration'] * 60;
$startTime = strtotime($currentSession['start_time']);
$now = time();
$elapsed = $now - $startTime;
$remainingSec = max(0, $durationSec - $elapsed);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian: <?= htmlspecialchars($currentSubject['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-teal: #3a797a;
            --mint-green: #a2d9c1;
            --soft-mint: #f9fdfd;
            --ans-hover: #f1faf6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--soft-mint); font-family: 'Poppins', sans-serif; overflow-x: hidden; color: #334155; }
        .navbar-exam { background: linear-gradient(135deg, var(--primary-teal) 0%, #4b8b8c 100%); box-shadow: 0 4px 12px rgba(58, 121, 122, 0.2); padding: 0.8rem 0; }
        .navbar-brand { color: #fff !important; }
        .logo-bimbel { width: 45px; height: 45px; object-fit: contain; }
        .bimbel-name { font-size: 1.25rem; font-weight: 700; color: white; margin: 0; letter-spacing: 0.5px; }
        .student-name { color: rgba(255,255,255,0.9); font-weight: 500; font-size: 0.95rem; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .status-text { font-size: 0.85rem; color: rgba(255,255,255,0.8); }
        
        .question-info-bar { background-color: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(58, 121, 122, 0.05); margin-bottom: 2rem; border-top: 4px solid var(--mint-green); }
        .question-number { font-size: 1.15rem; font-weight: 700; color: var(--primary-teal); }
        
        .font-size-controls { display: flex; gap: 0.5rem; margin-top: 0.8rem; }
        .font-size-btn { padding: 0.5rem 1.2rem; border: 1.5px solid #e2e8f0; background-color: white; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; color: #64748b; }
        .font-size-btn.active, .font-size-btn:hover { background-color: var(--primary-teal); color: white; border-color: var(--primary-teal); }
        
        .timer-display { background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; padding: 0.6rem 1.2rem; border-radius: 12px; font-size: 1.2rem; font-weight: 700; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); letter-spacing: 1px; }
        .subject-name { color: #64748b; font-size: 0.9rem; margin-top: 0.6rem; font-weight: 500; }
        
        .content-card { background-color: white; border-radius: 20px; padding: 2rem; box-shadow: 0 4px 20px rgba(58, 121, 122, 0.06); height: 100%; min-height: 450px; display: flex; flex-direction: column; }
        .reference-section { border-right: 1.5px solid #f1f5f9; padding-right: 1.5rem; height: 100%; overflow-y: auto; scrollbar-width: thin; }
        .reference-title { font-weight: 700; color: #1e293b; margin-bottom: 1.2rem; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .reference-content { color: #334155; font-size: 0.98rem; line-height: 1.8; }
        
        .question-section { padding-left: 1.5rem; }
        .question-text { font-size: 1.05rem; line-height: 1.8; color: #1e293b; margin-bottom: 2rem; font-weight: 500; }
        
        .font-small .question-text, .font-small .answer-option label, .font-small .reference-content { font-size: 0.95rem; }
        .font-medium .question-text, .font-medium .answer-option label, .font-medium .reference-content { font-size: 1.05rem; }
        .font-large .question-text, .font-large .answer-option label, .font-large .reference-content { font-size: 1.2rem; }
        
        .answer-option { padding: 1.2rem; border: 2px solid #f1f5f9; border-radius: 15px; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: start; background: #fdfdfd; }
        .answer-option:hover { border-color: var(--mint-green); background-color: var(--ans-hover); transform: translateX(5px); }
        .answer-option.selected { border-color: var(--primary-teal); background-color: var(--ans-hover); box-shadow: 0 4px 12px rgba(58, 121, 122, 0.1); }
        .answer-option input { width: 22px; height: 22px; margin-right: 1rem; cursor: pointer; margin-top: 3px; accent-color: var(--primary-teal); }
        .answer-option label { cursor: pointer; margin: 0; width: 100%; color: #334155; }
        
        .bottom-navigation { background-color: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 -4px 15px rgba(58, 121, 122, 0.05); margin-top: 2rem; border-bottom: 4px solid var(--primary-teal); }
        .nav-container { display: flex; align-items: center; justify-content: center; gap: 1rem; }
        .nav-btn { padding: 0.75rem 1.5rem; font-weight: 600; border-radius: 12px; transition: all 0.3s; font-size: 0.95rem; width: 180px; }
        
        .btn-outline-secondary { border: 2px solid #e2e8f0; color: #64748b; }
        .btn-outline-secondary:hover { background-color: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }
        .btn-primary { background-color: var(--primary-teal); border: none; }
        .btn-primary:hover { background-color: #2d6161; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(58, 121, 122, 0.2); }
        
        .btn-doubt { background-color: #fbbf24; color: #1e293b; border: none; display: flex; align-items: center; justify-content: center; gap: 0.6rem; transition: all 0.3s; }
        .btn-doubt input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-teal); }
        .btn-doubt:hover { background-color: #f59e0b; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2); }
        
        .btn-outline-primary { border: 2px solid var(--primary-teal); color: var(--primary-teal); border-radius: 12px; }
        .btn-outline-primary:hover { background-color: var(--primary-teal); color: white; }
        
        .modal-question-list .question-number-btn { width: 48px; height: 48px; border-radius: 12px; font-weight: 600; transition: all 0.3s; border: 2px solid #e2e8f0; background: white; color: #64748b; }
        .question-number-btn:hover { border-color: var(--mint-green); background: var(--ans-hover); }
        .question-number-btn.answered { background-color: var(--mint-green) !important; color: #064e3b; border-color: var(--mint-green); }
        .question-number-btn.doubt { background-color: #fbbf24 !important; color: #451a03; border-color: #fbbf24; }
        .question-number-btn.current { border: 3px solid var(--primary-teal) !important; color: var(--primary-teal); z-index: 10; transform: scale(1.1); box-shadow: 0 4px 10px rgba(58, 121, 122, 0.2); }
        .question-number-btn.current:not(.answered):not(.doubt) { background-color: var(--primary-teal); color: white; }
        
        .modal-header { font-weight: 700; border: none; }
        .modal-content { border-radius: 20px; border: none; }
        
        @media (max-width: 768px) {
            .nav-container { flex-direction: column; width: 100%; }
            .nav-btn { width: 100%; }
            .reference-section { border-right: none; border-bottom: 2px solid #f1f5f9; padding-right: 0; padding-bottom: 1.5rem; margin-bottom: 1.8rem; }
            .question-section { padding-left: 0; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-exam">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            <i class="bi bi-mortarboard-fill me-2"></i>CBT - BIMBEL LITA
        </a>
        <div class="d-flex align-items-center gap-3">
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
                        <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#questionListModal">
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

<!-- Modal: Konfirmasi Selesai -->
<div class="modal fade" id="confirmFinishModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Selesai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="confirm-message" class="fs-5 mb-0">
                    Apakah Anda yakin ingin menyelesaikan mata pelajaran ini?
                </div>
                <div id="doubt-warning" class="alert alert-warning mt-3 mb-0 d-none">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Masih ada <strong id="doubt-count">0</strong> soal yang ditandai <strong>ragu-ragu</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 p-3 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">BATAL</button>
                <button type="button" class="btn btn-success px-4" onclick="submitExamForce()">YA, SELESAI</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Question List -->
<div class="modal fade" id="questionListModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-list-ol me-2"></i>Daftar Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <span class="badge bg-success me-2">&#9632;</span> Dijawab
                    <span class="badge bg-warning text-dark ms-3 me-2">&#9632;</span> Ragu
                    <span class="badge bg-primary ms-3 me-2">&#9632;</span> Aktif
                    <span class="badge bg-secondary ms-3 me-2">&#9632;</span> Kosong
                </div>
                <hr>
                <div class="d-flex flex-wrap gap-2 modal-question-list" id="modal-grid">
                    <!-- Filled by JS -->
                </div>
                <hr>
                <div class="d-grid mt-4">
                    <button class="btn btn-success btn-lg fw-bold" onclick="finishExam()">
                        <i class="bi bi-check-circle me-2"></i>SELESAI & KUMPULKAN
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const EXAM_DATA = {
        questions: <?= json_encode($finalQuestions) ?>,
        ansMap: <?= json_encode($ansMap) ?>,
        remainingSeconds: <?= $remainingSec ?>,
        sessionId: <?= $sessionId ?>
    };
</script>
<script src="../assets/js/exam.js"></script>
</body>
</html>