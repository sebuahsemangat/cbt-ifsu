<?php
/**
 * File: admin/modules/exams/token_handler.php
 * Deskripsi: Handler generate dan retrieve token ujian
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../config/database.php';
    require_once '../../../includes/auth_check.php';

    $action = $_GET['action'] ?? '';
    $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

    if ($exam_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID Ujian tidak valid']);
        exit;
    }

    // Pastikan tabel tokens tersedia di database (self-healing jika belum ada di hosting)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tokens` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `exam_id` INT NOT NULL,
      `token_code` CHAR(4) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `expires_at` TIMESTAMP NULL DEFAULT NULL,
      INDEX (`exam_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Fungsi generate Random String 4 Karakter Kapital
    function generateTokenCode(): string {
        $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // Tanpa I, 1, O, 0 agar tidak ambigu
        return substr(str_shuffle($chars), 0, 4);
    }

    if ($action === 'get_or_create') {
        // 1. Cek apakah ada token aktif untuk exam ini
        $stmt = $pdo->prepare("SELECT * FROM tokens WHERE exam_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$exam_id]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = time();
        $newDataNeeded = false;

        // 2. Jika tidak ada token atau sudah expired, buat baru
        if (!$token || strtotime((string)$token['expires_at']) <= $now) {
            $code = generateTokenCode();
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes')); // 10 Menit dari sekarang

            $insert = $pdo->prepare("INSERT INTO tokens (exam_id, token_code, expires_at) VALUES (?, ?, ?)");
            $insert->execute([$exam_id, $code, $expires]);

            $token = [
                'token_code' => $code,
                'expires_at' => $expires
            ];
            $newDataNeeded = true;
        }

        // 3. Hitung sisa waktu (detik)
        $remaining = strtotime((string)$token['expires_at']) - $now;

        echo json_encode([
            'status' => 'success',
            'token' => $token['token_code'],
            'remaining' => $remaining > 0 ? $remaining : 0,
            'is_new' => $newDataNeeded
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan server/database: ' . $e->getMessage()
    ]);
    exit;
}