<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $exam_id = (int)$_POST['exam_id'];
    $duration = (int)$_POST['duration'];

    if (!empty($name) && $duration > 0) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, exam_id, duration) VALUES (?, ?, ?)");
        $stmt->execute([$name, $exam_id, $duration]);
    }
    header("Location: ../../dashboard.php?page=exams");
    exit;
}

if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $duration = (int)$_POST['duration'];

    if (!empty($name) && $duration > 0 && $id > 0) {
        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, duration = ? WHERE id = ?");
        $stmt->execute([$name, $duration, $id]);
    }
    header("Location: ../../dashboard.php?page=exams");
    exit;
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: ../../dashboard.php?page=exams");  
    exit;
}

// Jika akses langsung ke file ini tanpa action yang jelas
header("Location: ../../dashboard.php?page=exams");
exit;