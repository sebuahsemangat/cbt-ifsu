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
$stmtSess = $pdo->prepare("SELECT * FROM exam_sessions WHERE id = ?");
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

// Get Answers
$stmtAns = $pdo->prepare("SELECT * FROM student_answers WHERE session_id = ?");
$stmtAns->execute([$sessionId]);
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
        // BS: $userAns is {0:'B', 1:'S'}, $qData is [{"text":..., "is_correct":1}, ...]
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
    }

    if ($isCorrect) {
        $correctCount++;
    }
}

// Calculate Score
$score = 0;
if ($totalQuestions > 0) {
    $score = ($correctCount / $totalQuestions) * 100;
}

// Update Session
$stmtUpd = $pdo->prepare("UPDATE exam_sessions SET score = ?, status = 'finished', end_time = NOW() WHERE id = ?");
$stmtUpd->execute([$score, $sessionId]);

// Check if all subjects for this exam are finished
// ... (Logic to determine if we should update 'scores' table now or later)
// For now, let's just finish this subject.

// Redirect to Result/Summary Page
header("Location: exam_result.php?session_id=" . $sessionId);
exit;
?>
