<?php
/**
 * File: admin/modules/questions/upload_image.php
 * Deskripsi: Endpoint AJAX untuk mengunggah gambar soal/opsi secara terpisah
 * Menggunakan Base64 x-www-form-urlencoded untuk bypass OpenResty 403 WAF
 */

require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);

$dataUri = $_POST['image_base64'] ?? ($jsonInput['image_base64'] ?? '');

if (empty($dataUri)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tidak ada gambar yang dikirim']);
    exit;
}

if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format gambar Base64 tidak valid']);
    exit;
}

$encodedData = substr($dataUri, strpos($dataUri, ',') + 1);
$decodedData = base64_decode($encodedData);

if ($decodedData === false || strlen($decodedData) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data gambar rusak atau kosong']);
    exit;
}

// Batasi ukuran maksimal 5MB sebelum kompresi
if (strlen($decodedData) > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ukuran gambar melebihi 5MB']);
    exit;
}

$targetDir = "../../../uploads/questions/";
if (!file_exists($targetDir)) {
    @mkdir($targetDir, 0755, true);
}

$fileName = 'q_' . time() . '_' . uniqid() . '.jpg';
$targetFile = $targetDir . $fileName;

$src = @imagecreatefromstring($decodedData);
if (!$src) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Gagal memproses data gambar']);
    exit;
}

// Tangani transparansi PNG/WebP menjadi latar putih
$width = imagesx($src);
$height = imagesy($src);
$bg = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($bg, 255, 255, 255);
imagefill($bg, 0, 0, $white);
imagecopy($bg, $src, 0, 0, 0, 0, $width, $height);
imagedestroy($src);
$src = $bg;

// Resize jika dimensi melebihi 1200px
$maxWidth = 1200;
if ($width > $maxWidth) {
    $newWidth = $maxWidth;
    $newHeight = (int)floor($height * ($maxWidth / $width));
    $tmp = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($src);
    $src = $tmp;
}

// Simpan dengan kompresi JPG kualitas 80
$saved = imagejpeg($src, $targetFile, 80);
imagedestroy($src);

if ($saved) {
    // Return path relatif untuk disimpan ke HTML soal
    echo json_encode([
        'success' => true,
        'url' => 'uploads/questions/' . $fileName,
        'preview_url' => '../uploads/questions/' . $fileName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file gambar ke disk']);
}
