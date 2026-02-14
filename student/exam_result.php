<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId) {
    header("Location: dashboard.php");
    exit;
}

// Get Session Info
$stmt = $pdo->prepare("
    SELECT qs.*, s.name as subject_name, e.exam_name 
    FROM exam_sessions qs
    JOIN subjects s ON qs.subject_id = s.id
    JOIN exams e ON qs.exam_id = e.id
    WHERE qs.id = ? AND qs.student_id = ?
");
$stmt->execute([$sessionId, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    die("Session not found.");
}

// Get Questions and Answers for Review
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ?");
$stmtQ->execute([$session['subject_id']]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

$stmtAns = $pdo->prepare("SELECT * FROM student_answers WHERE session_id = ?");
$stmtAns->execute([$sessionId]);
$answers = $stmtAns->fetchAll(PDO::FETCH_ASSOC);

$answerMap = [];
foreach ($answers as $ans) {
    $answerMap[$ans['question_id']] = json_decode($ans['answer_data'], true);
}

// Check if all subjects are finished
$examId = $session['exam_id'];
$studentId = $_SESSION['user_id'];

// Total subjects
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
$stmtTotal->execute([$examId]);
$totalSubjects = $stmtTotal->fetchColumn();

// Finished sessions
$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND status = 'finished'");
$stmtDone->execute([$examId, $studentId]);
$finishedSubjects = $stmtDone->fetchColumn();

$isAllDone = ($finishedSubjects >= $totalSubjects);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Ujian - <?= htmlspecialchars($session['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .score-card { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 600px; margin: 2rem auto; }
        .score-val { font-size: 3rem; font-weight: 700; color: #0d6efd; }

        @media print {
            .btn, hr, .d-print-none, .card-header { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .score-card { box-shadow: none !important; margin: 0 auto 2rem auto !important; padding: 1rem !important; border: 1px solid #eee !important; }
            .card { border: 1px solid #eee !important; box-shadow: none !important; margin-top: 1rem !important; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .badge { border: 1px solid #ccc !important; color: black !important; background: transparent !important; padding: 2px 5px !important; }
            .bg-success { color: #198754 !important; border-color: #198754 !important; }
            .bg-danger { color: #dc3545 !important; border-color: #dc3545 !important; }
            .text-secondary { color: #6c757d !important; }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Print Only Header -->
        <div class="d-none d-print-block text-center mb-4">
            <h2 class="fw-bold">LAPORAN HASIL UJIAN</h2>
            <hr>
            <div class="row text-start mt-4">
                <div class="col-6">
                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($_SESSION['full_name'] ?? 'Siswa') ?></p>
                    <p class="mb-1"><strong>Ujian:</strong> <?= htmlspecialchars($session['exam_name']) ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Mata Pelajaran:</strong> <?= htmlspecialchars($session['subject_name']) ?></p>
                    <p class="mb-1"><strong>Tanggal:</strong> <?= date('d M Y H:i') ?></p>
                </div>
            </div>
            <hr>
        </div>

        <div class="score-card">
            <h3>Hasil Mata Pelajaran</h3>
            <h4 class="text-secondary"><?= htmlspecialchars($session['subject_name']) ?></h4>
            <hr>
            <p>Nilai Anda:</p>
            <div class="score-val"><?= number_format($session['score'], 2) ?></div>
            
            <div class="mt-4 d-print-none">
            <div class="mt-4">
                <?php if ($isAllDone): ?>
                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-lg flex-grow-1">
                            Dashboard
                        </a>
                        <button onclick="window.print()" class="btn btn-primary btn-lg flex-grow-1">
                            <i class="bi bi-printer me-2"></i> Cetak Hasil
                        </button>
                    </div>
                <?php else: ?>
                    <a href="take_exam.php" class="btn btn-primary btn-lg w-100 mb-2">
                        Lanjut Mata Pelajaran Berikutnya
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-info w-100">
                        <i class="bi bi-printer me-2"></i> Cetak Hasil Sementara
                    </button>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <!-- Optional: Detailed Review -->
        <div class="card mt-4 mx-auto" style="max-width: 800px;">
            <div class="card-header bg-white fw-bold">Review Jawaban</div>
            <div class="card-body">
                <?php foreach($questions as $index => $q): 
                    $userAns = $answerMap[$q['id']] ?? null;
                    $qData = json_decode($q['answer_data'], true);
                    $type = $q['question_type'];
                    $isCorrect = false;
                    $formattedUserAns = "Belum dijawab";
                    $correctLabel = "";
                    
                    if ($type == 'pg') {
                        $formattedUserAns = $userAns ?? '-';
                        $correctLabel = $qData['correct'] ?? '-';
                        if (isset($qData['correct']) && $userAns === $qData['correct']) {
                            $isCorrect = true;
                        }
                    } elseif ($type == 'bs') {
                        $isCorrect = true;
                        $items = isset($qData['items']) ? $qData['items'] : (is_array($qData) ? $qData : []);
                        $labels = $qData['labels'] ?? ['pos' => 'Benar', 'neg' => 'Salah'];
                        
                        $userBits = [];
                        $correctBits = [];
                        
                        if (!empty($items)) {
                             foreach ($items as $idx => $item) {
                                 $flag = $item['is_correct'] ?? $item['correct'] ?? 0;
                                 $correctChar = ($flag == 1) ? 'B' : 'S';
                                 $correctBits[] = ($idx+1) . ":" . (($flag == 1) ? $labels['pos'] : $labels['neg']);
                                 
                                 $uVal = $userAns[$idx] ?? null;
                                 if ($uVal !== null) {
                                     $userBits[] = ($idx+1) . ":" . (($uVal == 'B') ? $labels['pos'] : $labels['neg']);
                                 } else {
                                     $userBits[] = ($idx+1) . ":-";
                                 }

                                 if ($uVal !== $correctChar) {
                                     $isCorrect = false;
                                 }
                             }
                        } else { $isCorrect = false; }
                        $formattedUserAns = implode(", ", $userBits);
                        $correctLabel = implode(", ", $correctBits);

                    } elseif ($type == 'multi') {
                        $isCorrect = true;
                        $uIndices = is_array($userAns) ? array_map('intval', $userAns) : [];
                        
                        $userSelected = [];
                        $correctSelected = [];
                        
                        if (is_array($qData)) {
                            foreach ($qData as $idx => $item) {
                                $shouldBeSelected = (isset($item['correct']) && $item['correct'] == 1) 
                                                    || (isset($item['is_correct']) && $item['is_correct']);
                                $isSelected = in_array((int)$idx, $uIndices);
                                
                                if ($isSelected) $userSelected[] = strip_tags($item['text']);
                                if ($shouldBeSelected) $correctSelected[] = strip_tags($item['text']);
                                
                                if ($shouldBeSelected !== $isSelected) {
                                    $isCorrect = false;
                                }
                            }
                        }
                        $formattedUserAns = empty($userSelected) ? "-" : implode(", ", $userSelected);
                        $correctLabel = implode(", ", $correctSelected);

                    } elseif ($type == 'isian') {
                        $correctVal = $qData['correct'] ?? '';
                        $userVal = is_string($userAns) ? trim($userAns) : '';
                        $formattedUserAns = $userVal ?: "-";
                        $correctLabel = $correctVal;
                        
                        if (strtolower($userVal) == strtolower(trim($correctVal))) {
                            $isCorrect = true;
                        }
                    }
                ?>
                    <div class="mb-3 border-bottom pb-3">
                        <p class="fw-bold mb-1">No. <?= $index + 1 ?></p>
                        <div class="mb-2"> <?=$q['question_text']?></div>
                        <div class="small">
                            <div class="mb-1">
                                <strong>Jawaban Kamu:</strong> 
                                <span class="text-<?= $isCorrect ? 'success' : 'danger' ?> fw-bold"><?= htmlspecialchars($formattedUserAns) ?></span>
                                <span class="badge bg-<?= $isCorrect ? 'success' : 'danger' ?> ms-2"><?= $isCorrect ? 'Benar' : 'Salah' ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
