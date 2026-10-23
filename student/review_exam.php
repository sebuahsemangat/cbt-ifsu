<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$examId = $_GET['exam_id'] ?? null;
if (!$examId) {
    header("Location: dashboard.php");
    exit;
}

$studentId = $_SESSION['user_id'];

// Ambil info ujian
$stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmtExam->execute([$examId]);
$exam = $stmtExam->fetch();
if (!$exam) {
    header("Location: dashboard.php");
    exit;
}

// Cek apakah fitur review diizinkan untuk ujian ini
if (empty($exam['allow_review'])) {
    header("Location: dashboard.php?error=review_disabled");
    exit;
}

// Pastikan ujian sudah selesai semua untuk siswa ini
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
$stmtTotal->execute([$examId]);
$totalSubjects = $stmtTotal->fetchColumn();

$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND status = 'finished'");
$stmtDone->execute([$examId, $studentId]);
$finishedSubjects = $stmtDone->fetchColumn();

if ($finishedSubjects < $totalSubjects) {
    header("Location: dashboard.php");
    exit;
}

function resolveStudentImg($html) {
    if (!is_string($html) || empty($html)) return $html;
    return preg_replace('/src=["\']uploads\/questions\//i', 'src="../uploads/questions/', $html);
}

function cleanOptionReviewHtml($html) {
    if (!is_string($html) || empty($html)) return '';
    $s = trim($html);
    $s = preg_replace('/^(<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>\s*)+/i', '', $s);
    $s = preg_replace('/^(\s*<br\s*\/?>\s*)+/i', '', $s);
    $s = preg_replace('/^(<p[^>]*>)\s*<br\s*\/?>\s*/i', '$1', $s);
    $s = preg_replace('/(\s*<br\s*\/?>\s*)+$/i', '', $s);
    $s = preg_replace('/(\s*<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>)+$/i', '', $s);
    return $s;
}

// Ambil semua subject beserta session, soal, dan jawaban siswa
$stmtSubs = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
$stmtSubs->execute([$examId]);
$subjects = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);

$subjectData = [];
$totalScoreSum = 0;

foreach ($subjects as $sub) {
    // Get session
    $stmtSess = $pdo->prepare("SELECT * FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND subject_id = ? AND status = 'finished' LIMIT 1");
    $stmtSess->execute([$examId, $studentId, $sub['id']]);
    $session = $stmtSess->fetch(PDO::FETCH_ASSOC);
    if (!$session) continue;

    // Get questions
    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ? ORDER BY id ASC");
    $stmtQ->execute([$sub['id']]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    // Jika sesi memiliki urutan soal tersimpan, sesuaikan urutan tampilannya
    if (!empty($session['question_order'])) {
        $savedOrder = json_decode($session['question_order'], true);
        if (is_array($savedOrder) && !empty($savedOrder)) {
            $orderMap = array_flip($savedOrder);
            usort($questions, function ($a, $b) use ($orderMap) {
                $posA = $orderMap[$a['id']] ?? 999999;
                $posB = $orderMap[$b['id']] ?? 999999;
                return $posA <=> $posB;
            });
        }
    }

    // Get answers (latest per question)
    $stmtAns = $pdo->prepare("
        SELECT sa.* FROM student_answers sa
        INNER JOIN (
            SELECT question_id, MAX(answered_at) as latest
            FROM student_answers WHERE session_id = ?
            GROUP BY question_id
        ) la ON sa.question_id = la.question_id AND sa.answered_at = la.latest
        WHERE sa.session_id = ?
    ");
    $stmtAns->execute([$session['id'], $session['id']]);
    $answers = $stmtAns->fetchAll(PDO::FETCH_ASSOC);
    $answerMap = [];
    foreach ($answers as $ans) {
        $answerMap[$ans['question_id']] = json_decode($ans['answer_data'], true);
    }

    $totalScoreSum += $session['score'];

    $subjectData[] = [
        'subject' => $sub,
        'session' => $session,
        'questions' => $questions,
        'answerMap' => $answerMap,
    ];
}

$avgScore = count($subjectData) > 0 ? $totalScoreSum / count($subjectData) : 0;

// === AMBIL SETTING DARI DATABASE ===
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

$bimbel_name = $settings['bimbel_name'] ?? 'BIMBINGAN BELAJAR LITA';
$app_name    = $settings['app_name'] ?? 'CBT Simulation';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembahasan Ujian - <?= htmlspecialchars($bimbel_name) ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <!-- Local Fonts: Plus Jakarta Sans & Poppins -->
    <link href="../assets/vendor/fonts/fonts.css" rel="stylesheet">
    
    <!-- MathJax 3 (Formula & Fractions Renderer) - Works Offline on LAN -->
    <script>
    window.MathJax = {
        chtml: {
            fontURL: 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/output/chtml/fonts/woff-v2'
        },
        tex: {
            inlineMath: [['\\(', '\\)'], ['$', '$']],
            displayMath: [['\\[', '\\]'], ['$$', '$$']],
            processEscapes: true
        },
        options: {
            skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
        },
        startup: {
            pageReady: () => {
                return MathJax.startup.defaultPageReady();
            }
        }
    };
    </script>
    <script src="../assets/vendor/mathjax/tex-mml-chtml.js" id="MathJax-script" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';"></script>
    
    <style>
        :root {
            --primary-teal: #3a797a;
            --mint-green: #a2d9c1;
            --soft-bg: #f0fdf9;
            --card-shadow: 0 8px 32px rgba(58, 121, 122, 0.10);
        }

        * { box-sizing: border-box; }

        body {
            background: var(--soft-bg);
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
        }

        /* ===== NAVBAR ===== */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-teal) 0%, #4b8b8c 100%);
            box-shadow: 0 4px 15px rgba(58, 121, 122, 0.25);
            padding: 0.9rem 0;
        }
        .navbar-brand { 
            color: #fff !important; 
            font-weight: 700; 
            font-size: 1.1rem; 
            white-space: normal;
            max-width: 80%;
        }

        /* ===== SUMMARY CARD ===== */
        .summary-hero {
            background: linear-gradient(135deg, var(--primary-teal) 0%, #4c9e9f 50%, #5ab5a0 100%);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            color: white;
            box-shadow: 0 12px 40px rgba(58, 121, 122, 0.30);
            position: relative;
            overflow: hidden;
        }
        .summary-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .summary-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .avg-score-val {
            font-size: 4rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -2px;
        }
        .score-label { font-size: 0.85rem; opacity: 0.8; letter-spacing: 0.5px; text-transform: uppercase; }

        .subject-score-pill {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.88rem;
            backdrop-filter: blur(4px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .subject-score-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--mint-green);
            display: inline-block;
        }

        /* ===== SUBJECT TABS ===== */
        .subject-nav {
            background: white;
            border-radius: 16px;
            padding: 0.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            position: sticky;
            top: 72px;
            z-index: 100;
        }
        .subject-tab-btn {
            border-radius: 12px;
            padding: 0.55rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid transparent;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.25s;
            white-space: nowrap;
        }
        .subject-tab-btn:hover {
            background: var(--soft-bg);
            color: var(--primary-teal);
        }
        .subject-tab-btn.active {
            background: var(--primary-teal);
            color: white;
            box-shadow: 0 4px 14px rgba(58, 121, 122, 0.30);
        }

        /* ===== SUBJECT PANEL ===== */
        .subject-panel { display: none; }
        .subject-panel.active { display: block; }

        .subject-header-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem 2rem;
            box-shadow: var(--card-shadow);
            border-left: 5px solid var(--primary-teal);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .subject-score-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-teal);
        }
        .score-desc { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ===== QUESTION CARD ===== */
        .question-card {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .question-card:hover { transform: translateY(-2px); }

        .question-card-header {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }
        .qnum-badge {
            background: var(--soft-bg);
            color: var(--primary-teal);
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            padding: 0.3rem 0.75rem;
            border: 1.5px solid var(--mint-green);
        }
        .result-badge-correct {
            background: #dcfce7;
            color: #166534;
            border: 1.5px solid #86efac;
            border-radius: 50px;
            padding: 0.3rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .result-badge-wrong {
            background: #fee2e2;
            color: #991b1b;
            border: 1.5px solid #fca5a5;
            border-radius: 50px;
            padding: 0.3rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .result-badge-empty {
            background: #f1f5f9;
            color: #64748b;
            border: 1.5px solid #cbd5e1;
            border-radius: 50px;
            padding: 0.3rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .question-card-body { padding: 1.5rem; }

        .question-text-content {
            font-size: 0.97rem;
            line-height: 1.85;
            color: #1e293b;
            font-weight: 500;
            margin-bottom: 1.2rem;
        }

        /* MathJax Styling: Rata tengah & sejajar teks */
        mjx-container { display: inline-block !important; margin: 0 3px !important; vertical-align: middle !important; }
        mjx-container[jax="CHTML"][display="true"] { display: block !important; margin: 0.75rem 0 !important; }

        /* Styling tabel responsif di stimulus & soal */
        .question-card table.table-stimulus, .reference-box table {
            width: auto !important;
            min-width: 50% !important;
            max-width: 100% !important;
            margin: 12px auto !important;
            border-collapse: collapse !important;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border-radius: 8px;
            overflow: hidden;
        }
        .question-card table.table-stimulus th, .question-card table.table-stimulus td,
        .reference-box th, .reference-box td {
            border: 1px solid #cbd5e1 !important;
            padding: 8px 14px !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        .question-card table.table-stimulus th, .reference-box th {
            background-color: #f1f5f9 !important;
            font-weight: 600 !important;
            color: #1e293b;
        }

        .answer-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            border: 1.5px solid transparent;
            line-height: 1.6;
        }
        .answer-row .ans-icon { font-size: 1rem; flex-shrink: 0; margin-top: 2px; }
        .answer-row .option-wrap {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            flex-grow: 1;
        }
        .answer-row .option-key { font-weight: 700; flex-shrink: 0; min-width: 1.4rem; line-height: 1.5; }
        .answer-row .option-text { flex-grow: 1; word-break: break-word; line-height: 1.5; }
        .answer-row .option-text > p:first-child { margin-top: 0 !important; }
        .answer-row .option-text > p:last-child { margin-bottom: 0 !important; }
        .answer-row .option-text > p:only-child { display: inline; }
        .answer-row .option-text p { margin-bottom: 0.25rem; }
        .answer-row.user-wrong {
            background: #fff5f5;
            border-color: #fca5a5;
            color: #7f1d1d;
        }
        .answer-row.correct-ans {
            background: #f0fdf4;
            border-color: #86efac;
            color: #14532d;
        }
        .answer-row.unanswered {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
            font-style: italic;
        }

        /* ===== STATS BAR ===== */
        .stats-summary {
            background: white;
            border-radius: 16px;
            padding: 1.2rem 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .stat-item { text-align: center; }
        .stat-val { font-size: 1.4rem; font-weight: 700; }
        .stat-lbl { font-size: 0.72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-divider { width: 1px; height: 36px; background: #f1f5f9; }

        /* ===== PRINT ===== */
        @media print {
            .navbar-custom, .subject-nav, .btn, .d-print-none { display: none !important; }
            body { background: white !important; }
            .summary-hero { background: #3a797a !important; -webkit-print-color-adjust: exact; }
            .subject-panel { display: block !important; page-break-before: always; }
            .question-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }

        @media (max-width: 768px) {
            .subject-nav { top: 60px; }
            .avg-score-val { font-size: 3rem; }
            .subject-header-card { padding: 1rem 1.2rem; }
            .question-card-body { padding: 1rem; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="bi bi-mortarboard-fill me-md-2"></i>
            <span class="d-none d-md-inline">CBT - <?= htmlspecialchars($bimbel_name) ?></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light rounded-pill d-print-none">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-light rounded-pill d-print-none">
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </div>
    </div>
</nav>

<div class="container py-4">

    <!-- Print Header -->
    <div class="d-none d-print-block text-center mb-4">
        <h2 class="fw-bold">LAPORAN PEMBAHASAN UJIAN</h2>
        <hr>
        <div class="row text-start mt-3">
            <div class="col-6">
                <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($_SESSION['full_name'] ?? 'Siswa') ?></p>
                <p class="mb-1"><strong>Ujian:</strong> <?= htmlspecialchars($exam['exam_name']) ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>Tanggal:</strong> <?= date('d M Y H:i') ?></p>
                <p class="mb-1"><strong>Rata-rata:</strong> <?= number_format($avgScore, 2) ?></p>
            </div>
        </div>
        <hr>
    </div>

    <!-- Summary Hero -->
    <div class="summary-hero mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-5">
                <div class="score-label mb-1">Nilai Rata-rata</div>
                <div class="avg-score-val"><?= number_format($avgScore, 2) ?></div>
                <div class="mt-1 opacity-75" style="font-size: 0.88rem;">
                    <i class="bi bi-mortarboard-fill me-1"></i><?= htmlspecialchars($exam['exam_name']) ?>
                </div>
                <div class="mt-1 opacity-60" style="font-size: 0.8rem;">
                    <i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
                </div>
            </div>
            <div class="col-md-7">
                <div class="score-label mb-2">Nilai Per Mata Pelajaran</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($subjectData as $sd): 
                        $score = $sd['session']['score'];
                        $total = count($sd['questions']);
                        $correct = 0;
                        // hitung benar
                        foreach ($sd['questions'] as $q) {
                            $uAns = $sd['answerMap'][$q['id']] ?? null;
                            $qData = json_decode($q['answer_data'], true);
                            $type = $q['question_type'];
                            $ok = false;
                            if ($type === 'pg') {
                                $ok = isset($qData['correct']) && $uAns === $qData['correct'];
                            } elseif ($type === 'bs') {
                                $items = $qData['items'] ?? (is_array($qData) ? $qData : []);
                                $ok = true;
                                foreach ($items as $idx => $item) {
                                    $flag = $item['is_correct'] ?? $item['correct'] ?? 0;
                                    $correctChar = ($flag == 1) ? 'B' : 'S';
                                    if (($uAns[$idx] ?? null) !== $correctChar) { $ok = false; break; }
                                }
                            } elseif ($type === 'multi') {
                                $ok = true;
                                $uIdx = is_array($uAns) ? array_map('intval', $uAns) : [];
                                if (is_array($qData)) {
                                    foreach ($qData as $i => $item) {
                                        $should = (isset($item['correct']) && $item['correct'] == 1) || (isset($item['is_correct']) && $item['is_correct']);
                                        if ($should !== in_array((int)$i, $uIdx)) { $ok = false; break; }
                                    }
                                }
                            } elseif ($type === 'isian') {
                                $correctVal = trim($qData['correct'] ?? '');
                                $userVal = is_string($uAns) ? trim($uAns) : '';
                                $ok = strtolower($userVal) === strtolower($correctVal) && $userVal !== '';
                            }
                            if ($ok) $correct++;
                        }
                    ?>
                    <div class="subject-score-pill">
                        <span class="subject-score-dot"></span>
                        <span><?= htmlspecialchars($sd['subject']['name']) ?></span>
                        <strong><?= number_format($score, 2) ?></strong>
                        <span style="opacity:0.7; font-size:0.78rem;">(<?= $correct ?>/<?= $total ?>)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Subject Tabs Navigation -->
    <?php if (count($subjectData) > 1): ?>
    <div class="subject-nav mb-4 d-print-none">
        <?php foreach ($subjectData as $i => $sd): ?>
        <button class="subject-tab-btn <?= $i === 0 ? 'active' : '' ?>"
                onclick="switchTab(<?= $i ?>)"
                id="tab-btn-<?= $i ?>">
            <i class="bi bi-book me-1"></i><?= htmlspecialchars($sd['subject']['name']) ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Subject Panels -->
    <?php foreach ($subjectData as $panelIdx => $sd):
        $sub = $sd['subject'];
        $session = $sd['session'];
        $questions = $sd['questions'];
        $answerMap = $sd['answerMap'];

        $totalQ = count($questions);
        $correctCount = 0;
        $wrongCount = 0;
        $emptyCount = 0;
    ?>
    <div class="subject-panel <?= $panelIdx === 0 ? 'active' : '' ?>" id="panel-<?= $panelIdx ?>">

        <!-- Subject Header -->
        <div class="subject-header-card mb-4">
            <div>
                <div style="font-size:0.8rem; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.3rem;">
                    Mata Pelajaran
                </div>
                <h4 class="fw-bold mb-1" style="color:#1e293b;"><?= htmlspecialchars($sub['name']) ?></h4>
                <span style="font-size:0.82rem; color:#64748b;">
                    <i class="bi bi-clock me-1"></i>Durasi: <?= $sub['duration'] ?> menit &nbsp;|&nbsp;
                    <i class="bi bi-list-ol me-1"></i><?= $totalQ ?> soal
                </span>
            </div>
            <div class="text-center">
                <div class="score-desc">Nilai</div>
                <div class="subject-score-display"><?= number_format($session['score'], 2) ?></div>
            </div>
        </div>

        <!-- Stats Bar (calculated per question below) -->
        <?php
        // Pre-calculate stats
        $qResults = [];
        foreach ($questions as $q) {
            $uAns = $answerMap[$q['id']] ?? null;
            $qData = json_decode($q['answer_data'], true);
            $type = $q['question_type'];
            $isCorrect = false;
            $isEmpty = ($uAns === null);
            $formattedUserAns = "Belum dijawab";
            $correctLabel = "";

            if ($type === 'pg') {
                $formattedUserAns = $uAns ?? 'Belum dijawab';
                $isEmpty = ($uAns === null);
                $correctLabel = $qData['correct'] ?? '-';
                if (isset($qData['correct']) && $uAns === $qData['correct']) $isCorrect = true;
            } elseif ($type === 'bs') {
                $items = $qData['items'] ?? (is_array($qData) ? $qData : []);
                $labels = $qData['labels'] ?? ['pos' => 'Benar', 'neg' => 'Salah'];
                $isCorrect = true;
                $userBits = [];
                $correctBits = [];
                $hasAny = false;
                foreach ($items as $idx => $item) {
                    $flag = $item['is_correct'] ?? $item['correct'] ?? 0;
                    $correctChar = ($flag == 1) ? 'B' : 'S';
                    $correctBits[] = ($idx+1) . ":" . (($flag == 1) ? $labels['pos'] : $labels['neg']);
                    $uVal = $uAns[$idx] ?? null;
                    if ($uVal !== null) {
                        $hasAny = true;
                        $userBits[] = ($idx+1) . ":" . (($uVal === 'B') ? $labels['pos'] : $labels['neg']);
                    } else {
                        $userBits[] = ($idx+1) . ":-";
                    }
                    if ($uVal !== $correctChar) $isCorrect = false;
                }
                $isEmpty = !$hasAny;
                $formattedUserAns = $isEmpty ? "Belum dijawab" : implode(", ", $userBits);
                $correctLabel = implode(", ", $correctBits);
                if ($isEmpty) $isCorrect = false;
            } elseif ($type === 'multi') {
                $isCorrect = true;
                $uIdx = is_array($uAns) ? array_map('intval', $uAns) : [];
                $isEmpty = empty($uIdx);
                $userSelected = [];
                $correctSelected = [];
                if (is_array($qData)) {
                    foreach ($qData as $i => $item) {
                        $should = (isset($item['correct']) && $item['correct'] == 1) || (isset($item['is_correct']) && $item['is_correct']);
                        $isSelected = in_array((int)$i, $uIdx);
                        if ($isSelected) $userSelected[] = strip_tags($item['text']);
                        if ($should) $correctSelected[] = strip_tags($item['text']);
                        if ($should !== $isSelected) $isCorrect = false;
                    }
                }
                $formattedUserAns = $isEmpty ? "Belum dijawab" : implode(", ", $userSelected);
                $correctLabel = implode(", ", $correctSelected);
                if ($isEmpty) $isCorrect = false;
            } elseif ($type === 'isian') {
                $correctVal = trim($qData['correct'] ?? '');
                $userVal = is_string($uAns) ? trim($uAns) : '';
                $isEmpty = ($userVal === '');
                $formattedUserAns = $isEmpty ? "Belum dijawab" : $userVal;
                $correctLabel = $correctVal;
                if (!$isEmpty && strtolower($userVal) === strtolower($correctVal)) $isCorrect = true;
            }

            if ($isEmpty) $emptyCount++;
            elseif ($isCorrect) $correctCount++;
            else $wrongCount++;

            $qResults[] = compact('isCorrect', 'isEmpty', 'formattedUserAns', 'correctLabel', 'type', 'q', 'uAns', 'qData');
        }
        ?>

        <div class="stats-summary mb-4">
            <div class="stat-item">
                <div class="stat-val text-success"><?= $correctCount ?></div>
                <div class="stat-lbl">Benar</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-val text-danger"><?= $wrongCount ?></div>
                <div class="stat-lbl">Salah</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-val text-warning"><?= $emptyCount ?></div>
                <div class="stat-lbl">Kosong</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-val text-secondary"><?= $totalQ ?></div>
                <div class="stat-lbl">Total Soal</div>
            </div>
            <div class="ms-auto">
                <div class="progress" style="height: 10px; width: 180px; border-radius: 8px; background: #e2e8f0;">
                    <div class="progress-bar bg-success" style="width: <?= $totalQ > 0 ? round($correctCount/$totalQ*100) : 0 ?>%"></div>
                </div>
                <div class="text-end mt-1" style="font-size: 0.75rem; color: #94a3b8;">
                    <?= $totalQ > 0 ? round($correctCount/$totalQ*100) : 0 ?>% benar
                </div>
            </div>
        </div>

        <!-- Questions -->
        <div class="d-flex flex-column gap-3 mb-5">
            <?php foreach ($qResults as $qIdx => $qr): 
                $q = $qr['q'];
                $qData = $qr['qData'];
                $type = $qr['type'];
            ?>
            <div class="question-card">
                <div class="question-card-header">
                    <span class="qnum-badge">No. <?= $qIdx + 1 ?></span>
                    <?php if ($qr['isEmpty']): ?>
                        <span class="result-badge-empty"><i class="bi bi-dash-circle"></i> Tidak Dijawab</span>
                    <?php elseif ($qr['isCorrect']): ?>
                        <span class="result-badge-correct"><i class="bi bi-check-circle-fill"></i> Benar</span>
                    <?php else: ?>
                        <span class="result-badge-wrong"><i class="bi bi-x-circle-fill"></i> Salah</span>
                    <?php endif; ?>
                </div>
                <div class="question-card-body">
                    <!-- Question Text -->
                    <div class="question-text-content"><?= resolveStudentImg($q['question_text']) ?></div>

                    <!-- Answer Review by Type -->
                    <?php if ($type === 'pg'): 
                        $options = $qData['options'] ?? [];
                        foreach ($options as $key => $optText):
                            $isUserChoice = ($qr['uAns'] === $key);
                            $isCorrectChoice = ($qr['correctLabel'] === $key);
                            $rowClass = '';
                            $icon = 'bi-circle';
                            if ($isCorrectChoice) { $rowClass = 'correct-ans'; $icon = 'bi-check-circle-fill'; }
                            if ($isUserChoice && !$isCorrectChoice) { $rowClass = 'user-wrong'; $icon = 'bi-x-circle-fill'; }
                    ?>
                        <div class="answer-row <?= $rowClass ?>">
                            <i class="ans-icon bi <?= $icon ?>"></i>
                            <div class="option-wrap">
                                <strong class="option-key"><?= htmlspecialchars($key) ?>.</strong>
                                <div class="option-text"><?= cleanOptionReviewHtml(resolveStudentImg($optText)) ?></div>
                            </div>
                            <?php if ($isUserChoice && !$isCorrectChoice): ?><span class="ms-auto fw-bold" style="font-size:0.78rem; white-space:nowrap;">Jawaban Kamu</span><?php endif; ?>
                            <?php if ($isCorrectChoice): ?><span class="ms-auto fw-bold" style="font-size:0.78rem; white-space:nowrap;">Kunci Jawaban</span><?php endif; ?>
                        </div>
                    <?php endforeach; 
                    elseif ($type === 'bs'):
                        $items = $qData['items'] ?? [];
                        $labels = $qData['labels'] ?? ['pos' => 'Benar', 'neg' => 'Salah'];
                    ?>
                        <?php foreach ($items as $idx => $item): 
                            $flag = $item['is_correct'] ?? $item['correct'] ?? 0;
                            $correctChar = ($flag == 1) ? 'B' : 'S';
                            $userChar = $qr['uAns'][$idx] ?? null;
                            $isItemCorrect = ($userChar === $correctChar);
                            $rowClass = $isItemCorrect ? 'correct-ans' : ($userChar === null ? 'unanswered' : 'user-wrong');
                        ?>
                        <div class="answer-row <?= $rowClass ?>">
                            <i class="ans-icon bi <?= $isItemCorrect ? 'bi-check-circle-fill' : ($userChar === null ? 'bi-dash-circle' : 'bi-x-circle-fill') ?>"></i>
                            <span style="flex:1;"><?= resolveStudentImg($item['text'] ?? 'Item ' . ($idx+1)) ?></span>
                            <span>
                                Kamu: <strong><?= $userChar ? (($userChar === 'B') ? $labels['pos'] : $labels['neg']) : '-' ?></strong>
                                &nbsp;|&nbsp;
                                Kunci: <strong><?= ($flag == 1) ? $labels['pos'] : $labels['neg'] ?></strong>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php elseif ($type === 'multi'):
                        $uIdx = is_array($qr['uAns']) ? array_map('intval', $qr['uAns']) : [];
                        if (is_array($qData)):
                            foreach ($qData as $i => $item):
                                $should = (isset($item['correct']) && $item['correct'] == 1) || (isset($item['is_correct']) && $item['is_correct']);
                                $isSelected = in_array((int)$i, $uIdx);
                                $rowClass = '';
                                $icon = 'bi-circle';
                                if ($should) { $rowClass = 'correct-ans'; $icon = 'bi-check-square-fill'; }
                                if ($isSelected && !$should) { $rowClass = 'user-wrong'; $icon = 'bi-x-square-fill'; }
                    ?>
                            <div class="answer-row <?= $rowClass ?>">
                                <i class="ans-icon bi <?= $icon ?>"></i>
                                <span style="flex:1;"><?= resolveStudentImg($item['text'] ?? '') ?></span>
                                <?php if ($isSelected && !$should): ?><span class="ms-auto fw-bold" style="font-size:0.78rem; white-space:nowrap;">Kamu pilih (Salah)</span><?php endif; ?>
                                <?php if ($should && !$isSelected): ?><span class="ms-auto fw-bold" style="font-size:0.78rem; white-space:nowrap;">Jawaban kunci</span><?php endif; ?>
                                <?php if ($should && $isSelected): ?><span class="ms-auto fw-bold" style="font-size:0.78rem; white-space:nowrap;">Kamu pilih ✓</span><?php endif; ?>
                            </div>
                    <?php       endforeach;
                        endif;
                    elseif ($type === 'isian'):
                        $isOk = $qr['isCorrect'];
                    ?>
                        <div class="answer-row <?= $qr['isEmpty'] ? 'unanswered' : ($isOk ? 'correct-ans' : 'user-wrong') ?>">
                            <i class="ans-icon bi <?= $qr['isEmpty'] ? 'bi-dash-circle' : ($isOk ? 'bi-check-circle-fill' : 'bi-x-circle-fill') ?>"></i>
                            <span>Jawaban kamu: <strong><?= htmlspecialchars($qr['formattedUserAns']) ?></strong></span>
                        </div>
                        <div class="answer-row correct-ans">
                            <i class="ans-icon bi bi-check-circle-fill"></i>
                            <span>Kunci jawaban: <strong><?= htmlspecialchars($qr['correctLabel']) ?></strong></span>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- end subject-panel -->
    <?php endforeach; ?>

    <!-- Back to Dashboard Button -->
    <div class="text-center pb-5 d-print-none">
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4" style="background:var(--primary-teal); border:none;">
            <i class="bi bi-printer me-2"></i>Cetak Pembahasan
        </button>
    </div>

</div><!-- end container -->

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(idx) {
    // Deactivate all
    document.querySelectorAll('.subject-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.subject-tab-btn').forEach(b => b.classList.remove('active'));
    // Activate selected
    document.getElementById('panel-' + idx).classList.add('active');
    document.getElementById('tab-btn-' + idx).classList.add('active');

    if (window.MathJax && typeof window.MathJax.typesetPromise === 'function') {
        window.MathJax.typesetPromise();
    }
}
</script>

</body>
</html>
