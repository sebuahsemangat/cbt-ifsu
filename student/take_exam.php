<?php
session_start();
require_once '../config/database.php';

// 1. Keamanan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$exam_id = $_SESSION['active_exam'] ?? ($_POST['exam_id'] ?? null);

// Jika baru masuk dari dashboard via POST
if (!$exam_id && isset($_POST['exam_id'])) {
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
    $_SESSION['active_exam'] = $exam_id;
}

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
        'data' => json_decode($sa['answer_data'], true),
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
        /* Existing CSS maintained */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; overflow-x: hidden; }
        .navbar-exam { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 0.8rem 0; }
        .logo-bimbel { width: 45px; height: 45px; object-fit: contain; }
        .bimbel-name { font-size: 1.3rem; font-weight: 700; color: white; margin: 0; }
        .student-name { color: white; font-weight: 500; }
        .question-info-bar { background-color: white; border-radius: 12px; padding: 1.2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .question-number { font-size: 1.1rem; font-weight: 700; color: #0d6efd; }
        .font-size-controls { display: flex; gap: 0.5rem; margin-top: 0.8rem; }
        .font-size-btn { padding: 0.4rem 1rem; border: 2px solid #dee2e6; background-color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-weight: 600; }
        .font-size-btn.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .timer-display { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 0.5rem 1rem; border-radius: 8px; font-size: 1.1rem; font-weight: 700; box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3); }
        .subject-name { color: #6c757d; font-size: 0.95rem; margin-top: 0.5rem; }
        .content-card { background-color: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); height: 100%; min-height: 400px; display: flex; flex-direction: column; }
        .reference-section { border-right: 2px solid #e9ecef; padding-right: 1.5rem; height: 100%; overflow-y: auto;}
        .reference-title { font-weight: 700; color: #495057; margin-bottom: 1rem; font-size: 1rem; }
        .reference-content { color: #6c757d; font-size: 0.95rem; line-height: 1.8; }
        .question-section { padding-left: 1.5rem; }
        .question-text { font-size: 1rem; line-height: 1.8; color: #212529; margin-bottom: 1.5rem; }
        .font-small .question-text, .font-small .answer-option label, .font-small .reference-content { font-size: 0.9rem; }
        .font-medium .question-text, .font-medium .answer-option label, .font-medium .reference-content { font-size: 1rem; }
        .font-large .question-text, .font-large .answer-option label, .font-large .reference-content { font-size: 1.15rem; }
        .answer-option { padding: 1rem; border: 2px solid #e9ecef; border-radius: 10px; margin-bottom: 0.8rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: start;}
        .answer-option:hover { border-color: #0d6efd; background-color: #f8f9ff; }
        .answer-option input { width: 20px; height: 20px; margin-right: 0.8rem; cursor: pointer; margin-top: 3px; }
        .answer-option label { cursor: pointer; margin: 0; width: 100%; }
        .bottom-navigation { background-color: white; border-radius: 12px; padding: 1.2rem; box-shadow: 0 -2px 10px rgba(0,0,0,0.08); margin-top: 1.5rem; }
        .nav-btn { padding: 0.5rem 1rem; font-weight: 600; border-radius: 8px; transition: all 0.3s; font-size: 0.9rem; }
        .btn-doubt { background-color: #ffc107; color: #000; border: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-doubt input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .btn-doubt:hover { background-color: #ffb300; transform: translateY(-2px); }
        .modal-question-list .question-number-btn { width: 50px; height: 50px; border-radius: 10px; font-weight: 600; transition: all 0.3s; }
        .question-number-btn.answered { background-color: #198754; color: white; border-color: #198754; }
        .question-number-btn.doubt { background-color: #ffc107; color: #000; border-color: #ffc107; }
        .question-number-btn.current { background-color: #0d6efd; color: white; border-color: #0d6efd; !important }
        .review-img { max-width: 100%; border-radius: 8px; margin-bottom: 1rem; }
        @media (max-width: 768px) {
            .reference-section { border-right: none; border-bottom: 2px solid #e9ecef; padding-right: 0; padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
            .question-section { padding-left: 0; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-exam">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center">
            <img src="../assets/img/logo-bimbel.png" alt="Logo" class="logo-bimbel me-3">
            <h5 class="bimbel-name">Bimbel Lita</h5>
        </div>
        <div class="student-name">
            <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($_SESSION['full_name']) ?>
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
                <!-- Connection Status Alert -->
                <div id="connection-status" class="alert alert-danger d-none mt-2 py-1 small" role="alert">
                    <i class="bi bi-wifi-off me-2"></i>Koneksi Terputus!
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
        <div class="row align-items-center">
            <div class="col-4">
                <button class="btn btn-outline-secondary nav-btn" id="btn-prev" onclick="navStep(-1)">
                    <i class="bi bi-chevron-left me-2"></i>Sebelumnya
                </button>
            </div>
            <div class="col-4 text-center">
                <button class="btn btn-doubt nav-btn" type="button" onclick="toggleDoubt()">
                    <input type="checkbox" id="doubt-checkbox" onclick="event.stopPropagation()"> <!-- stop propagation fix -->
                    <i class="bi bi-question-circle me-1"></i>Ragu-ragu
                </button>
            </div>
            <div class="col-4 text-end">
                <button class="btn btn-primary nav-btn" id="btn-next" onclick="navStep(1)">
                    Berikutnya<i class="bi bi-chevron-right ms-2"></i>
                </button>
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
    const questions = <?= json_encode($finalQuestions) ?>;
    const ansMap = <?= json_encode($ansMap) ?>; // Initially map question_id -> {data, doubt}
    
    // Ensure ansMap is purely using IDs as keys.
    // We will use a local Map for easier access inside JS? Or just object.
    
    let remainingSeconds = <?= $remainingSec ?>; 
    const sessionId = <?= $sessionId ?>;
    
    let currentIndex = 0;
    
    document.addEventListener('DOMContentLoaded', () => {
        if (!questions || questions.length === 0) {
            document.getElementById('question-text').textContent = "Tidak ada soal untuk mata pelajaran ini.";
            return;
        }
        renderQuestion(0);
        updateGrid();
        startTimer();
        
        window.addEventListener('offline', () => {
            document.getElementById('connection-status').classList.remove('d-none');
        });
        window.addEventListener('online', () => {
            document.getElementById('connection-status').classList.add('d-none');
        });
    });

    function renderQuestion(index) {
        if (index < 0 || index >= questions.length) return;
        currentIndex = index;
        const q = questions[index];
        const ansData = JSON.parse(q.answer_data); // Parse question structure
        
        // 1. Update Number
        document.getElementById('current-question-number').textContent = index + 1;
        
        // 2. Reference Section
        const refCol = document.getElementById('ref-col');
        const qCol = document.getElementById('q-col');
        const refBody = document.getElementById('reference-content-body');
        
        if (q.ref_data) {
            refCol.style.display = 'block';
            qCol.className = 'col-md-7';
            
            let html = '';
            if (q.ref_data.image_path) {
                html += `<img src="../assets/uploads/references/${q.ref_data.image_path}" class="review-img" alt="Ref Image" style="max-height:600px;">`;
            }
            if (q.ref_data.content) {
                html += `<div>${q.ref_data.content}</div>`;
            }
            refBody.innerHTML = html;
        } else {
            refCol.style.display = 'none';
            qCol.className = 'col-md-12';
        }
        
        // 3. Question Text
        let qText = q.question_text;
        if (q.image_path) {
            qText += `<br><img src="../assets/uploads/questions/${q.image_path}" class="review-img mt-3" style="max-height:200px;">`;
        }
        document.getElementById('question-text').innerHTML = qText;
        
        // 4. Answers
        const container = document.getElementById('answers-container');
        container.innerHTML = '';
        
        const stored = ansMap[q.id];
        const myAns = stored ? stored.data : null; 
        
        let type = q.question_type;
        
        if (type === 'pg') {
             const opts = ansData.options || {};
             Object.keys(opts).forEach(key => {
                 const text = opts[key];
                 const checked = myAns === key ? 'checked' : '';
                 
                 container.innerHTML += `
                    <div class="answer-option" onclick="selectOption('${key}', 'pg')">
                        <input type="radio" name="ans_${q.id}" value="${key}" ${checked}>
                        <label><strong>${key}.</strong> ${text}</label>
                    </div>
                 `;
             });
        } else if (type === 'bs') {
             // Table Layout for Benar/Salah
             let html = `
                <div class="table-responsive">
                <table class="table table-bordered table-hover mt-2">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Pernyataan</th>
                            <th style="width: 100px;" class="text-center">Benar</th>
                            <th style="width: 100px;" class="text-center">Salah</th>
                        </tr>
                    </thead>
                    <tbody>`;
             
             // Support both array of strings or objects with 'text'
             const items = Array.isArray(ansData) ? ansData : [];
             items.forEach((item, idx) => {
                 const text = (typeof item === 'object' && item.text) ? item.text : item;
                 const char = String.fromCharCode(65 + idx);
                 
                 // myAns expected to be object {index: value}
                 // Safe access
                 const val = (myAns && typeof myAns === 'object' && myAns[idx]) ? myAns[idx] : null;
                 const bChecked = (val === 'B') ? 'checked' : '';
                 const sChecked = (val === 'S') ? 'checked' : '';

                 html += `
                    <tr>
                        <td class="text-center fw-bold align-middle">${char}</td>
                        <td class="align-middle">${text}</td>
                        <td class="text-center align-middle">
                            <input class="form-check-input" type="radio" name="bs_${q.id}_${idx}" value="B" ${bChecked} onclick="selectBS(${idx}, 'B')">
                        </td>
                        <td class="text-center align-middle">
                            <input class="form-check-input" type="radio" name="bs_${q.id}_${idx}" value="S" ${sChecked} onclick="selectBS(${idx}, 'S')">
                        </td>
                    </tr>
                 `;
             });
             html += `</tbody></table></div>`;
             container.innerHTML = html;

        } else {
            const items = Array.isArray(ansData) ? ansData : [];
            items.forEach((item, idx) => {
                 let isChecked = false;
                 if (Array.isArray(myAns)) {
                     // Need to check loose equality if numbers came back as strings from DB JSON
                     // But json_decode should handle it.
                     isChecked = myAns.includes(idx) || myAns.includes(String(idx));
                 }
                 
                 container.innerHTML += `
                    <div class="answer-option" onclick="toggleMulti(${idx})">
                        <input type="checkbox" name="ans_${q.id}[]" value="${idx}" ${isChecked ? 'checked' : ''}>
                        <label>${item.text}</label>
                    </div>
                 `;
            });
        }
        
        // 5. Doubt Button
        const doubtVal = stored ? stored.doubt : 0;
        document.getElementById('doubt-checkbox').checked = (doubtVal == 1);
        
        // 6. Navigation Buttons
        document.getElementById('btn-prev').disabled = (index === 0);
        document.getElementById('btn-next').innerHTML = (index === questions.length - 1) 
            ? 'Selesai' : 'Berikutnya <i class="bi bi-chevron-right ms-2"></i>';
            
        updateGrid();
    }
    
    function selectBS(idx, val) {
        const q = questions[currentIndex];
        // Ensure data structure
        if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };
        
        let currData = ansMap[q.id].data;
        // If not object (e.g. null or old array format), reset
        if (typeof currData !== 'object' || currData === null || Array.isArray(currData)) {
            currData = {}; 
        }
        
        currData[idx] = val;
        ansMap[q.id].data = currData;
        
        saveAnswer(q.id, currData, ansMap[q.id].doubt);
        updateGrid();
    }

    function selectOption(val, type) {
        const q = questions[currentIndex];
        // Don't trigger if clicked on child input (bubbling)
        if (event.target.tagName === 'INPUT') return; 
        
        // If clicking div, verify invalidation
        // Better: let the input click handle logic?
        // But the design requires clicking the whole div.
        
        // Update local object
        if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0 };
        ansMap[q.id].data = val;
        
        saveAnswer(q.id, val, ansMap[q.id].doubt);
        
        const inputs = document.getElementsByName(`ans_${q.id}`);
        inputs.forEach(i => {
           i.checked = (i.value == val); 
        });
        
        updateGrid();
    }
    
    function toggleMulti(idx) {
        if (event.target.tagName === 'INPUT') {
             // If input clicked directly, just update Model
        } else {
             // If div clicked, toggle input
             const input = document.getElementsByName(`ans_${questions[currentIndex].id}[]`)[idx];
             input.checked = !input.checked;
        }
        
        const q = questions[currentIndex];
        if (!ansMap[q.id]) ansMap[q.id] = { doubt: 0, data: [] };
        let arr = ansMap[q.id].data || [];
        if (!Array.isArray(arr)) arr = [];
        
        // Re-read from DOM to be safe? Or Logic?
        // Let's rely on Logic
        const checkbox = document.getElementsByName(`ans_${q.id}[]`)[idx];
        const isChecked = checkbox.checked;
        
        // Update array
        const iIdx = parseInt(idx);
        if (isChecked) {
            if (!arr.includes(iIdx)) arr.push(iIdx);
        } else {
            arr = arr.filter(i => i !== iIdx);
        }
        
        ansMap[q.id].data = arr;
        saveAnswer(q.id, arr, ansMap[q.id].doubt);
        updateGrid();
    }
    
    function toggleDoubt() {
        const q = questions[currentIndex];
        const cb = document.getElementById('doubt-checkbox');
        // If clicked via button onclick
        // Note: the input onclick event.stopPropagation is good.
        // If button clicked, toggle.
        if (event.target !== cb) {
           cb.checked = !cb.checked;
        }
        
        const isDoubt = cb.checked ? 1 : 0;
        if (!ansMap[q.id]) ansMap[q.id] = { data: null };
        ansMap[q.id].doubt = isDoubt;
        
        saveAnswer(q.id, ansMap[q.id].data, isDoubt);
        updateGrid();
    }
    
    function saveAnswer(qid, data, doubt) {
        fetch('save_answer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionId,
                question_id: qid,
                answer_data: data,
                is_doubt: doubt
            })
        }).catch(e => console.error(e));
    }
    
    function navStep(step) {
        const next = currentIndex + step;
        if (next >= 0 && next < questions.length) {
            renderQuestion(next);
        } else if (next >= questions.length) {
            finishExam();
        }
    }
    
    function updateGrid() {
        const modalGrid = document.getElementById('modal-grid');
        modalGrid.innerHTML = '';
        questions.forEach((q, idx) => {
            const hasAns = ansMap[q.id] && ansMap[q.id].data !== null && 
                           (Array.isArray(ansMap[q.id].data) ? ansMap[q.id].data.length > 0 : true)
                           && ansMap[q.id].data !== ""; // Check empty string
            const isDoubt = ansMap[q.id] && ansMap[q.id].doubt == 1;
            const isCurr = (idx === currentIndex);
            
            let cls = 'btn-outline-secondary';
            if (isCurr) cls = 'current';
            else if (isDoubt) cls = 'doubt';
            else if (hasAns) cls = 'answered';
            
            modalGrid.innerHTML += `
                <button class="btn question-number-btn ${cls}" onclick="goTo(${idx})">${idx+1}</button>
            `;
        });
    }
    
    function goTo(idx) {
        const el = document.getElementById('questionListModal');
        const m = bootstrap.Modal.getInstance(el);
        if (m) m.hide();
        renderQuestion(idx);
    }
    
    function setFontSize(size) {
        const mainContent = document.getElementById('main-content');
        mainContent.classList.remove('font-small', 'font-medium', 'font-large');
        mainContent.classList.add('font-' + size);
        document.querySelectorAll('.font-size-btn').forEach(b => b.classList.remove('active'));
        event.target.closest('button').classList.add('active');
    }

    function startTimer() {
        const display = document.getElementById('exam-timer');
        const interval = setInterval(() => {
            remainingSeconds--;
            if (remainingSeconds <= 0) {
                clearInterval(interval);
                alert("Waktu Habis!");
                submitExamForce();
                return;
            }
            // Sync logic could go here
            
            const h = Math.floor(remainingSeconds / 3600);
            const m = Math.floor((remainingSeconds % 3600) / 60);
            const s = Math.floor(remainingSeconds % 60);
            display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }, 1000);
    }

    function finishExam() {
        // Count doubts properly
        let doubts = 0;
        Object.keys(ansMap).forEach(k => {
             if (ansMap[k].doubt == 1) doubts++;
        });
        
        let msg = "Apakah Anda yakin ingin menyelesaikan mata pelajaran ini?";
        if (doubts > 0) msg = `Anda memiliki ${doubts} soal ragu-ragu. Yakin ingin mengumpulkan?`;
        
        if (confirm(msg)) {
            submitExamForce();
        }
    }
    
    function submitExamForce() {
        document.getElementById('submit-form').submit();
    }
</script>
</body>
</html>