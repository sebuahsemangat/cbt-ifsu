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
    // Atomic upsert: INSERT or UPDATE jika sudah ada (cegah race condition)
    // Gunakan option_id = 0 agar UNIQUE KEY (session_id, question_id, option_id) berfungsi benar
    // (NULL dianggap unik berbeda-beda di MySQL, jadi kita fix dengan nilai 0)
    $sql = "INSERT INTO student_answers (session_id, question_id, answer_data, is_doubt, option_id, answered_at)
            VALUES (?, ?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE
                answer_data = VALUES(answer_data),
                is_doubt = VALUES(is_doubt),
                answered_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sessionId, $questionId, $answerData, $isDoubt]);

    // Sinkronisasi sisa waktu jika disertakan
    if (isset($input['remaining_seconds']) && is_numeric($input['remaining_seconds'])) {
        $remSec = max(0, (int)$input['remaining_seconds']);
        $stmtSess = $pdo->prepare("
            UPDATE exam_sessions 
            SET remaining_seconds = ?, 
                end_time = DATE_ADD(NOW(), INTERVAL ? SECOND) 
            WHERE id = ? AND student_id = ? AND status = 'ongoing'
        ");
        $stmtSess->execute([$remSec, $remSec, $sessionId, $_SESSION['user_id']]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
