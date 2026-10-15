<?php
// admin/ajax_monitor.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Statistik
    $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_subjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $total_exams = (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
    
    // 2. Monitoring Siswa & Sesi
    $stmtMonitor = $pdo->query("
        SELECT 
            s.id AS student_id,
            s.nis,
            s.full_name,
            s.class,
            s.is_logged_in,
            s.last_login,
            es.id AS session_id,
            es.exam_id,
            es.subject_id,
            es.start_time,
            es.end_time,
            COALESCE(es.remaining_seconds, TIMESTAMPDIFF(SECOND, NOW(), es.end_time)) AS remaining_seconds,
            e.exam_name,
            sb.name AS subject_name,
            (SELECT COUNT(DISTINCT question_id) FROM student_answers WHERE session_id = es.id AND answer_data IS NOT NULL AND answer_data != '' AND answer_data != 'null') AS answered_count,
            (SELECT COUNT(*) FROM questions WHERE subject_id = es.subject_id) AS total_questions
        FROM students s
        LEFT JOIN (
            SELECT es1.*
            FROM exam_sessions es1
            INNER JOIN (
                SELECT student_id, MAX(id) AS max_id
                FROM exam_sessions
                WHERE status = 'ongoing'
                GROUP BY student_id
            ) es2 ON es1.id = es2.max_id
        ) es ON es.student_id = s.id
        LEFT JOIN exams e ON es.exam_id = e.id
        LEFT JOIN subjects sb ON es.subject_id = sb.id
        ORDER BY 
            CASE 
                WHEN es.id IS NOT NULL THEN 1
                WHEN s.is_logged_in = 1 THEN 2
                ELSE 3
            END ASC,
            s.class ASC,
            s.full_name ASC
    ");
    $raw_students = $stmtMonitor->fetchAll(PDO::FETCH_ASSOC);

    $count_working = 0;
    $count_login_only = 0;
    $students = [];

    foreach ($raw_students as $st) {
        $is_working = !empty($st['session_id']);
        $is_login = ($st['is_logged_in'] == 1);

        if ($is_working) {
            $count_working++;
            $category = 'working';
        } elseif ($is_login) {
            $count_login_only++;
            $category = 'login';
        } else {
            $category = 'offline';
        }

        $students[] = [
            'student_id'        => (int)$st['student_id'],
            'nis'               => $st['nis'] ?? '',
            'full_name'         => $st['full_name'] ?? '',
            'class'             => $st['class'] ?? '-',
            'is_logged_in'      => (int)$st['is_logged_in'],
            'session_id'        => $st['session_id'] ? (int)$st['session_id'] : null,
            'exam_id'           => $st['exam_id'] ? (int)$st['exam_id'] : null,
            'exam_name'         => $st['exam_name'] ?? null,
            'subject_id'        => $st['subject_id'] ? (int)$st['subject_id'] : null,
            'subject_name'      => $st['subject_name'] ?? null,
            'start_time'        => $st['start_time'] ? date('H:i', strtotime($st['start_time'])) : null,
            'remaining_seconds' => $st['remaining_seconds'] !== null ? (int)$st['remaining_seconds'] : null,
            'answered_count'    => $is_working ? (int)($st['answered_count'] ?? 0) : 0,
            'total_questions'   => $is_working ? (int)($st['total_questions'] ?? 0) : 0,
            'is_working'        => $is_working,
            'is_login'          => $is_login,
            'category'          => $category
        ];
    }

    $count_active = $count_working + $count_login_only;

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_students'   => $total_students,
            'total_subjects'   => $total_subjects,
            'total_exams'      => $total_exams,
            'count_working'    => $count_working,
            'count_login_only' => $count_login_only,
            'count_active'     => $count_active,
            'count_all'        => count($students)
        ],
        'students' => $students,
        'server_time' => date('H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
