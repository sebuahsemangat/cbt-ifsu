<?php
// admin/modules/scores/process.php

session_start();
require_once '../../../config/database.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$action = $_GET['action'] ?? '';

// === HAPUS DATA NILAI ===
if ($action === 'delete') {
    $id = $_GET['id'] ?? null;
    $exam_id = $_GET['exam_id'] ?? null;

    if (!$id) {
        $_SESSION['error'] = "ID nilai tidak valid.";
        header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
        exit;
    }

    try {
        // Hapus data dari exam_sessions berdasarkan id
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Data nilai berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Data nilai tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus data: " . $e->getMessage();
    }

    // Redirect
    $redirect = $_GET['redirect'] ?? 'scores';
    if ($redirect === 'dashboard') {
        header("Location: ../../dashboard.php?status=session_deleted");
    } else {
        header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
    }
    exit;
}

// === HAPUS SEMUA DATA NILAI BERDASARKAN EXAM ===
if ($action === 'delete_all') {
    $exam_id = $_GET['exam_id'] ?? null;

    if (!$exam_id) {
        $_SESSION['error'] = "ID ujian tidak valid.";
        header("Location: ../../dashboard.php?page=scores");
        exit;
    }

    try {
        // Hapus semua data dari exam_sessions berdasarkan exam_id
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);

        $_SESSION['success'] = "Semua data nilai untuk ujian ini berhasil dihapus.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus semua data: " . $e->getMessage();
    }

    // Redirect kembali ke halaman nilai
    header("Location: ../../dashboard.php?page=scores&exam_id=" . $exam_id);
    exit;
}

// === SELESAIKAN PAKSA UJIAN ===
if ($action === 'finish') {
    $id = $_GET['id'] ?? null;
    $exam_id = $_GET['exam_id'] ?? null;

    $redirect = $_GET['redirect'] ?? 'scores';

    if (!$id) {
        $_SESSION['error'] = "ID sesi tidak valid.";
        if ($redirect === 'dashboard') {
            header("Location: ../../dashboard.php");
        } else {
            header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
        }
        exit;
    }

    try {
        // 1. Ambil info sesi & subject
        $stmt_session = $pdo->prepare("SELECT es.*, s.max_score FROM exam_sessions es JOIN subjects s ON es.subject_id = s.id WHERE es.id = ?");
        $stmt_session->execute([$id]);
        $session = $stmt_session->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $_SESSION['error'] = "Sesi ujian tidak ditemukan.";
            if ($redirect === 'dashboard') {
                header("Location: ../../dashboard.php");
            } else {
                header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
            }
            exit;
        }

        $subjectId = $session['subject_id'];

        // 2. Ambil semua pertanyaan untuk mapel ini
        $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ?");
        $stmtQ->execute([$subjectId]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        // 3. Ambil semua jawaban siswa untuk sesi ini
        $stmtAns = $pdo->prepare("SELECT * FROM student_answers WHERE session_id = ?");
        $stmtAns->execute([$id]);
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

            if ($userAns === null) continue;

            $isCorrect = false;
            $type = $q['question_type'];

            if ($type == 'pg') {
                $correctKey = $qData['correct'] ?? null;
                if ($userAns == $correctKey) $isCorrect = true;
            } elseif ($type == 'bs') {
                $isCorrect = true;
                if (is_array($qData)) {
                    foreach ($qData as $idx => $item) {
                        $flag = $item['is_correct'] ?? $item['correct'] ?? 0;
                        $correctChar = ($flag == 1) ? 'B' : 'S';
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
                $isCorrect = true;
                $uIndices = [];
                if (is_array($userAns)) {
                    foreach($userAns as $v) $uIndices[] = (int)$v;
                }
                sort($uIndices);
                
                $correctIndices = [];
                if (is_array($qData)) {
                    foreach ($qData as $idx => $item) {
                        $flag = $item['is_correct'] ?? $item['correct'] ?? (isset($item['is_correct']) && $item['is_correct'] === true ? 1 : 0);
                        if ($flag == 1) $correctIndices[] = $idx;
                    }
                }
                sort($correctIndices);
                if ($uIndices != $correctIndices) $isCorrect = false;
            }

            if ($isCorrect) $correctCount++;
        }

        $score = 0;
        if ($totalQuestions > 0) {
            $maxScore = $session['max_score'] ?? 100;
            $score = ($correctCount / $totalQuestions) * $maxScore;
        }

        // 4. Update status sesi
        $stmtUpd = $pdo->prepare("UPDATE exam_sessions SET score = ?, status = 'finished', remaining_seconds = 0, end_time = NOW() WHERE id = ?");
        $stmtUpd->execute([$score, $id]);

        $_SESSION['success'] = "Ujian siswa berhasil diselesaikan dan nilai telah dihitung.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menyelesaikan ujian: " . $e->getMessage();
    }

    // Redirect
    if ($redirect === 'dashboard') {
        header("Location: ../../dashboard.php?status=exam_finished");
    } else {
        header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
    }
    exit;
}

// Jika action tidak valid
$_SESSION['error'] = "Aksi tidak valid.";
header("Location: ../../dashboard.php?page=scores");
exit;
