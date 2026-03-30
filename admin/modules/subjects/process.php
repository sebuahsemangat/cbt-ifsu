<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $exam_id = (int)$_POST['exam_id'];
    $duration = (int)$_POST['duration'];

    if (!empty($name) && $duration > 0) {
        $max_score = (float)$_POST['max_score'];
        
        // Hitung sort_order berikutnya untuk exam ini
        $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM subjects WHERE exam_id = ?");
        $stmtMax->execute([$exam_id]);
        $nextOrder = $stmtMax->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO subjects (name, exam_id, duration, max_score, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $exam_id, $duration, $max_score, $nextOrder]);
    }
    header("Location: ../../dashboard.php?page=exams");
    exit;
}

if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $duration = (int)$_POST['duration'];

    if (!empty($name) && $duration > 0 && $id > 0) {
        $max_score = (float)$_POST['max_score'];
        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, duration = ?, max_score = ? WHERE id = ?");
        $stmt->execute([$name, $duration, $max_score, $id]);
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

// Action reorder: menerima JSON via AJAX
if ($action == 'reorder' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    if (!empty($order) && is_array($order)) {
        // Proteksi: Cek apakah ada siswa yang sedang mengerjakan ujian
        $placeholders = implode(',', array_fill(0, count($order), '?'));
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) FROM exam_sessions 
            WHERE subject_id IN ($placeholders) AND status = 'ongoing'
        ");
        $stmtCheck->execute(array_map('intval', $order));
        $ongoingCount = $stmtCheck->fetchColumn();
        
        if ($ongoingCount > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => "Tidak dapat mengubah urutan! Ada $ongoingCount siswa yang sedang mengerjakan ujian.",
                'locked' => true
            ]);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE subjects SET sort_order = ? WHERE id = ?");
            foreach ($order as $index => $subjectId) {
                $stmt->execute([$index + 1, (int)$subjectId]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    }
    exit;
}

// Jika akses langsung ke file ini tanpa action yang jelas
header("Location: ../../dashboard.php?page=exams");
exit;