<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$exam_id = $_POST['exam_id'] ?? 0;

if (!$exam_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Exam ID']);
    exit;
}

// Fungsi generate Random String 4 Karakter Kapital
function generateTokenCode() {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // Tanpa I, 1, O, 0 agar tidak ambigu
    return substr(str_shuffle($chars), 0, 4);
}

if ($action == 'get_or_create') {
    // 1. Cek apakah ada token aktif untuk exam ini
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE exam_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$exam_id]);
    $token = $stmt->fetch(PDO::FETCH_ASSOC);

    $now = time();
    $newDataNeeded = false;

    // 2. Jika tidak ada token atau sudah expired, buat baru
    if (!$token || strtotime($token['expires_at']) <= $now) {
        $code = generateTokenCode();
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes')); // 10 Menit dari sekarang

        $insert = $pdo->prepare("INSERT INTO tokens (exam_id, token_code, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$exam_id, $code, $expires]);

        // Ambil data yang baru saja dibuat
        $token = [
            'token_code' => $code,
            'expires_at' => $expires
        ];
        $newDataNeeded = true;
    }

    // 3. Hitung sisa waktu (detik)
    $remaining = strtotime($token['expires_at']) - $now;

    echo json_encode([
        'status' => 'success',
        'token' => $token['token_code'],
        'remaining' => $remaining > 0 ? $remaining : 0,
        'is_new' => $newDataNeeded
    ]);
    exit;
}
?>