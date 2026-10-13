<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['exam_name']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $allow_review = isset($_POST['allow_review']) ? 1 : 0;

    if (!empty($name) && !empty($start) && !empty($end)) {
        // Validasi: Waktu selesai tidak boleh lebih kecil dari waktu mulai
        if ($end > $start) {
            $stmt = $pdo->prepare("INSERT INTO exams (exam_name, start_date, end_date, allow_review) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $start, $end, $allow_review]);
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

    if (!empty($name) && !empty($start) && !empty($end)) {
        if ($end > $start) {
            $stmt = $pdo->prepare("UPDATE exams SET exam_name = ?, start_date = ?, end_date = ?, allow_review = ? WHERE id = ?");
            $stmt->execute([$name, $start, $end, $allow_review, $id]);
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

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: ../../dashboard.php?page=exams&status=deleted");
    exit;
}