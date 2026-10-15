<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized");
}

$sessionId = $_POST['session_id'] ?? null;
if (!$sessionId) {
    die("Invalid Session");
}

// Get Session & Subject Info
$stmtSess = $pdo->prepare("SELECT es.*, s.max_score FROM exam_sessions es JOIN subjects s ON es.subject_id = s.id WHERE es.id = ?");
$stmtSess->execute([$sessionId]);
$session = $stmtSess->fetch();

if (!$session || $session['student_id'] != $_SESSION['user_id']) {
    die("Session not found or forbidden");
}

$subjectId = $session['subject_id'];
$examId = $session['exam_id'];

// Get Questions
$stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ?");
$stmtQ->execute([$subjectId]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Get Answers - ambil jawaban terbaru per soal (hindari duplikat dari bug lama)
$stmtAns = $pdo->prepare("
    SELECT sa.* FROM student_answers sa
    INNER JOIN (
        SELECT question_id, MAX(answered_at) as latest
        FROM student_answers WHERE session_id = ?
        GROUP BY question_id
    ) latest_ans ON sa.question_id = latest_ans.question_id AND sa.answered_at = latest_ans.latest
    WHERE sa.session_id = ?
");
$stmtAns->execute([$sessionId, $sessionId]);
$answers = $stmtAns->fetchAll(PDO::FETCH_ASSOC);

$answerMap = [];
foreach ($answers as $ans) {
    $answerMap[$ans['question_id']] = json_decode($ans['answer_data'], true);
}

$correctCount = 0;
$totalQuestions = count($questions);

foreach ($questions as $q) {
    $qId = $q['id'];
    $qData = json_decode($q['answer_data'], true);
    $userAns = $answerMap[$qId] ?? null;

    if ($userAns === null) continue; // Unanswered is wrong

    // Scoring Logic
    $isCorrect = false;

    // Check type based on JSON structure or DB field
    // DB field: question_type enum('pg','bs','multi')
    $type = $q['question_type'];

    if ($type == 'pg') {
        // Single Choice
        // structure: {"type": "single_choice", "correct": "C", ...}
        $correctKey = $qData['correct'] ?? null;
        if ($userAns == $correctKey) {
            $isCorrect = true;
        }
    } elseif ($type == 'bs') {
        // BS: $userAns is {0:'B', 1:'S'}, $qData is [{"text":..., "is_correct":1}, ...] OR {labels:..., items:[...]}
        $isCorrect = true;
        $items = isset($qData['items']) ? $qData['items'] : (is_array($qData) ? $qData : []);
        
        if (!empty($items)) {
             foreach ($items as $idx => $item) {
                 // Determine correct char: Support 'is_correct' or 'correct'
                 $flag = 0;
                 if (isset($item['is_correct'])) $flag = $item['is_correct'];
                 elseif (isset($item['correct'])) $flag = $item['correct'];
                 
                 $correctChar = ($flag == 1) ? 'B' : 'S';
                 
                 // User answer for this index
                 $uVal = $userAns[$idx] ?? null;
                 if ($uVal !== $correctChar) {
                     $isCorrect = false;
                     break; 
                 }
             }
        } else {
            $isCorrect = false; 
        }
    } elseif ($type == 'multi') {
        // Multi: $userAns is [0, 2], $qData is [{"text":..., "is_correct":1}, ...]
        $isCorrect = true;
        
        // Normalize user ans to array of integers
        $uIndices = [];
        if (is_array($userAns)) {
            foreach($userAns as $v) $uIndices[] = (int)$v;
        }
        sort($uIndices);
        
        // Identify proper correct indices
        $correctIndices = [];
        if (is_array($qData)) {
            foreach ($qData as $idx => $item) {
                 $flag = 0;
                 if (isset($item['is_correct'])) $flag = $item['is_correct'];
                 elseif (isset($item['correct'])) $flag = $item['correct'];
                 elseif (isset($item['is_correct']) && $item['is_correct'] === true) $flag = 1;

                 if ($flag == 1) {
                     $correctIndices[] = $idx;
                 }
            }
        }
        sort($correctIndices);
        
        if ($uIndices != $correctIndices) {
            $isCorrect = false;
        }
    } elseif ($type == 'isian') {
        // Isian: Case-insensitive comparison
        $correctVal = $qData['correct'] ?? '';
        $userVal = is_string($userAns) ? trim($userAns) : '';
        
        if (strtolower($userVal) == strtolower(trim($correctVal))) {
            $isCorrect = true;
        }
    }

    if ($isCorrect) {
        $correctCount++;
    }
}

// Calculate Score
$score = 0;
if ($totalQuestions > 0) {
    $maxScore = $session['max_score'] ?? 100;
    $score = ($correctCount / $totalQuestions) * $maxScore;
}

// Update Session
$stmtUpd = $pdo->prepare("UPDATE exam_sessions SET score = ?, status = 'finished', remaining_seconds = 0, end_time = NOW() WHERE id = ?");
$stmtUpd->execute([$score, $sessionId]);

// Cek pengaturan tampilkan nilai pada ujian
$stmtExam = $pdo->prepare("SELECT show_score FROM exams WHERE id = ?");
$stmtExam->execute([$examId]);
$showScore = $stmtExam->fetchColumn();

// Total mata pelajaran dalam ujian ini
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
$stmtTotal->execute([$examId]);
$totalSubjects = (int)$stmtTotal->fetchColumn();

// Jumlah mata pelajaran yang sudah selesai untuk siswa ini
$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND status = 'finished'");
$stmtDone->execute([$examId, $_SESSION['user_id']]);
$finishedSubjects = (int)$stmtDone->fetchColumn();

$isAllDone = ($finishedSubjects >= $totalSubjects);

// Jika semua mata pelajaran selesai, simpan total skor ke tabel scores
if ($isAllDone) {
    $stmtAgg = $pdo->prepare("SELECT SUM(score) as total, COUNT(*) as cnt FROM exam_sessions WHERE exam_id = ? AND student_id = ?");
    $stmtAgg->execute([$examId, $_SESSION['user_id']]);
    $agg = $stmtAgg->fetch();
    $avgScore = $agg['cnt'] > 0 ? $agg['total'] / $agg['cnt'] : 0;

    $chkScore = $pdo->prepare("SELECT id FROM scores WHERE student_id = ? AND exam_id = ?");
    $chkScore->execute([$_SESSION['user_id'], $examId]);
    if (!$chkScore->fetch()) {
        $pdo->prepare("INSERT INTO scores (student_id, exam_id, total_score) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], $examId, $avgScore]);
    }
}

// Logika Pengalihan Berdasarkan Pengaturan show_score:
if (empty($showScore)) {
    // Jika nonaktif:
    // Jika hanya terdapat 1 mata pelajaran ATAU seluruh mata pelajaran sudah selesai:
    // Langsung arahkan siswa ke halaman dashboard
    if ($totalSubjects <= 1 || $isAllDone) {
        unset($_SESSION['active_exam']);
        header("Location: dashboard.php?msg=exam_finished");
        exit;
    } else {
        // Jika ada lebih dari 1 mata pelajaran dan masih ada mapel berikutnya:
        // Arahkan ke exam_result.php (hanya tombol menuju mapel berikutnya)
        header("Location: exam_result.php?session_id=" . $sessionId);
        exit;
    }
} else {
    if ($isAllDone) {
        unset($_SESSION['active_exam']);
    }
    header("Location: exam_result.php?session_id=" . $sessionId);
    exit;
}
?>
