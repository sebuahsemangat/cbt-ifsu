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
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="score-card">
            <h3>Hasil Mata Pelajaran</h3>
            <h4 class="text-secondary"><?= htmlspecialchars($session['subject_name']) ?></h4>
            <hr>
            <p>Nilai Anda:</p>
            <div class="score-val"><?= number_format($session['score'], 2) ?></div>
            
            <div class="mt-4">
            <div class="mt-4">
                <?php if ($isAllDone): ?>
                    <a href="dashboard.php" class="btn btn-success btn-lg w-100">
                        Kembali ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="take_exam.php" class="btn btn-primary btn-lg w-100">
                        Lanjut Mata Pelajaran Berikutnya
                    </a>
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
                    
                    // Simple check for display color
                    if ($type == 'pg') {
                        if (isset($qData['correct']) && $userAns === $qData['correct']) {
                            $isCorrect = true;
                        }
                    } elseif ($type == 'bs') {
                        // BS: $userAns is {0:'B', 1:'S'}, $qData is [{text:.., is_correct:1}, ..]
                        $isCorrect = true;
                        if (is_array($qData)) {
                             foreach ($qData as $idx => $item) {
                                 // Determine correct char: Support 'is_correct' or 'correct'
                                 $flag = 0;
                                 if (isset($item['is_correct'])) $flag = $item['is_correct'];
                                 elseif (isset($item['correct'])) $flag = $item['correct'];
                                 
                                 $correctChar = ($flag == 1) ? 'B' : 'S';
                                 
                                 // User answer for this index
                                 $uVal = $userAns[$idx] ?? null;
                                 if ($uVal !== $correctChar) {
                                     $isCorrect = false;
                                     break; // One wrong makes the whole question wrong/partially wrong
                                 }
                             }
                        } else {
                            $isCorrect = false; 
                        }
                    } elseif ($type == 'multi') {
                        // Multi: $userAns is [0, 2], $qData is [{text:.., correct:1}, ..] OR {options:[], correct:[]}
                        // Based on form.php, structure likely array of objects with correct flag OR separate list.
                        // Let's assume most robust: Check if 'correct' key exists in items.
                        // If $qData has 'multi_correct' array (indices)? No, likely stored in items.
                        
                        $isCorrect = true;
                        // Normalize user ans to array of integers
                        $uIndices = [];
                        if (is_array($userAns)) {
                            foreach($userAns as $v) $uIndices[] = (int)$v;
                        }
                        
                        if (is_array($qData)) {
                            foreach ($qData as $idx => $item) {
                                $shouldBeSelected = (isset($item['correct']) && $item['correct'] == 1) 
                                                    || (isset($item['is_correct']) && $item['is_correct']);
                                $isSelected = in_array((int)$idx, $uIndices);
                                
                                if ($shouldBeSelected !== $isSelected) {
                                    $isCorrect = false;
                                    break;
                                }
                            }
                        }
                    }
                ?>
                    <div class="mb-3 border-bottom pb-3">
                        <p class="fw-bold">No. <?= $index + 1 ?></p>
                        <p> <?=$q['question_text']?></p>
                        <p class="mb-1">
                            Jawaban Kamu: <span class="badge bg-secondary"><?= htmlspecialchars(is_string($userAns) ? $userAns : json_encode($userAns)) ?></span>
                            <?php if ($isCorrect): ?>
                                <span class="badge bg-success ms-2">Benar</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">Salah</span>
                            <?php endif; ?>
                        </p>
                        <!-- Show correct answer logic here if desired -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
