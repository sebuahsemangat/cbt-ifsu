<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['exam_name']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $allow_review = isset($_POST['allow_review']) ? 1 : 0;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $show_score = isset($_POST['show_score']) ? 1 : 0;

    $selected_classes = $_POST['classes'] ?? [];

    if (empty($selected_classes) || !is_array($selected_classes)) {
        header("Location: ../../dashboard.php?page=exams&status=error_no_class");
        exit;
    }

    if (!empty($name) && !empty($start) && !empty($end)) {
        // Validasi: Waktu selesai tidak boleh lebih kecil dari waktu mulai
        if ($end > $start) {
            $stmt = $pdo->prepare("INSERT INTO exams (exam_name, start_date, end_date, allow_review, randomize_questions, show_score) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $start, $end, $allow_review, $randomize_questions, $show_score]);
            $exam_id = $pdo->lastInsertId();

            // Simpan relasi kelas
            $stmtClass = $pdo->prepare("INSERT INTO exam_classes (exam_id, class_name) VALUES (?, ?)");
            foreach ($selected_classes as $cls) {
                $cls = trim($cls);
                if ($cls !== '') {
                    $stmtClass->execute([$exam_id, $cls]);
                }
            }

            header("Location: ../../dashboard.php?page=exams&status=success");
        } else {
            header("Location: ../../dashboard.php?page=exams&status=error_time");
        }
    }
    exit;
}

if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['exam_name']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $allow_review = isset($_POST['allow_review']) ? 1 : 0;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $show_score = isset($_POST['show_score']) ? 1 : 0;
    $selected_classes = $_POST['classes'] ?? [];

    if (empty($selected_classes) || !is_array($selected_classes)) {
        header("Location: ../../dashboard.php?page=exams&status=error_no_class");
        exit;
    }

    if (!empty($name) && !empty($start) && !empty($end)) {
        if ($end > $start) {
            $stmt = $pdo->prepare("UPDATE exams SET exam_name = ?, start_date = ?, end_date = ?, allow_review = ?, randomize_questions = ?, show_score = ? WHERE id = ?");
            $stmt->execute([$name, $start, $end, $allow_review, $randomize_questions, $show_score, $id]);

            // Perbarui relasi kelas: hapus yang lama lalu masukkan yang baru
            $pdo->prepare("DELETE FROM exam_classes WHERE exam_id = ?")->execute([$id]);
            $stmtClass = $pdo->prepare("INSERT INTO exam_classes (exam_id, class_name) VALUES (?, ?)");
            foreach ($selected_classes as $cls) {
                $cls = trim($cls);
                if ($cls !== '') {
                    $stmtClass->execute([$id, $cls]);
                }
            }

            header("Location: ../../dashboard.php?page=exams&status=success");
        } else {
            header("Location: ../../dashboard.php?page=exams&status=error_time");
        }
    }
    exit;
}

if ($action == 'toggle_review' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE exams SET allow_review = IF(allow_review = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../../dashboard.php?page=exams&status=success");
    exit;
}

if ($action == 'toggle_random' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE exams SET randomize_questions = IF(randomize_questions = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../../dashboard.php?page=exams&status=success");
    exit;
}

if ($action == 'toggle_score' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE exams SET show_score = IF(show_score = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../../dashboard.php?page=exams&status=success");
    exit;
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: ../../dashboard.php?page=exams&status=deleted");
    exit;
}