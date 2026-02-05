<?php
session_start();
require_once '../config/database.php';

// Check Auth
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$sessionId = $input['session_id'] ?? null;
$questionId = $input['question_id'] ?? null;
$answerData = isset($input['answer_data']) ? json_encode($input['answer_data']) : null;
$isDoubt = isset($input['is_doubt']) ? (int)$input['is_doubt'] : 0;
// We might want to track time spent per question, or overall. 
// If per question, we need logic. For now, let's just save the answer.

if (!$sessionId || !$questionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session or question ID']);
    exit;
}

// Verify Session belongs to Student? (Optional but good for security)
// ...

try {
    // Check if answer exists
    $stmtCheck = $pdo->prepare("SELECT id FROM student_answers WHERE session_id = ? AND question_id = ?");
    $stmtCheck->execute([$sessionId, $questionId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        // Update
        $stmtUpd = $pdo->prepare("UPDATE student_answers SET answer_data = ?, is_doubt = ?, answered_at = NOW() WHERE id = ?");
        $stmtUpd->execute([$answerData, $isDoubt, $existing['id']]);
    } else {
        // Insert
        // We set option_id to 0 to avoid NULL issues if unique index problematic, or just NULL if DB allows.
        // The table schema has `option_id` int DEFAULT NULL.
        // UNIQUE KEY `unique_answer` (`session_id`,`question_id`,`option_id`)
        // If we use NULL, we can have multiple rows with NULL option_id for same session/question.
        // So we should enforce 1 row per question by checking existence (which we did).
        // Safest is to keep option_id NULL but ensure we update if exists.
        
        $stmtIns = $pdo->prepare("INSERT INTO student_answers (session_id, question_id, answer_data, is_doubt, answered_at) VALUES (?, ?, ?, ?, NOW())");
        $stmtIns->execute([$sessionId, $questionId, $answerData, $isDoubt]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
