<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

// --- FUNGSI HELPER: UPLOAD & KOMPRESI ---
function uploadAndCompressImage($file) {
    $targetDir = "../../../assets/uploads/references/";
    
    // Pastikan folder ada
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . '_' . uniqid() . '.jpg'; // Kita konversi semua ke JPG agar seragam & kecil
    $targetFile = $targetDir . $fileName;
    
    // Cek info gambar
    $imageInfo = getimagesize($file["tmp_name"]);
    if (!$imageInfo) return false; // Bukan gambar

    $mime = $imageInfo['mime'];
    $src = null;

    // 1. Buat resource gambar dari file asal
    switch ($mime) {
        case 'image/jpeg': 
            $src = imagecreatefromjpeg($file["tmp_name"]); 
            break;
        case 'image/png': 
            $src = imagecreatefrompng($file["tmp_name"]); 
            // Handle transparansi PNG sebelum convert ke JPG (background jadi putih)
            $bg = imagecreatetruecolor(imagesx($src), imagesy($src));
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));
            $src = $bg;
            break;
        default: 
            return false; // Format tidak didukung
    }

    // 2. Logika Resize (Jika lebar > 1200px)
    $maxWidth = 1200;
    $width = imagesx($src);
    $height = imagesy($src);

    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));
        
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($src);
        $src = $tmp;
    }

    // 3. Simpan dengan Kompresi (Quality: 75)
    // Hasilnya akan jauh lebih kecil dari aslinya
    if (imagejpeg($src, $targetFile, 75)) {
        imagedestroy($src);
        return $fileName;
    }

    return false;
}

// --- FUNGSI HELPER: SIMPAN & KOMPRESI BASE64 (BYPASS OPENRESTY 403) ---
function saveAndCompressBase64Image($dataUri) {
    if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri)) {
        return false;
    }

    $encodedData = substr($dataUri, strpos($dataUri, ',') + 1);
    $decodedData = base64_decode($encodedData);
    if ($decodedData === false) {
        return false;
    }

    $targetDir = "../../../assets/uploads/references/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . '_' . uniqid() . '.jpg';
    $targetFile = $targetDir . $fileName;

    $src = @imagecreatefromstring($decodedData);
    if (!$src) {
        return false;
    }

    // Tangani transparansi PNG agar latar belakang tetap putih rapi saat dijadikan JPG
    $width = imagesx($src);
    $height = imagesy($src);
    $bg = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($bg, 255, 255, 255);
    imagefill($bg, 0, 0, $white);
    imagecopy($bg, $src, 0, 0, 0, 0, $width, $height);
    imagedestroy($src);
    $src = $bg;

    // Resize jika lebar > 1200px
    $maxWidth = 1200;
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)floor($height * ($maxWidth / $width));
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($src);
        $src = $tmp;
    }

    if (imagejpeg($src, $targetFile, 75)) {
        imagedestroy($src);
        return $fileName;
    }
    imagedestroy($src);
    return false;
}

// --- AKSI SIMPAN (TAMBAH / EDIT) ---
if ($action == 'save' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $subject_id = $_POST['subject_id'];
    $type = $_POST['type'];
    $content = $_POST['content'] ?? null;
    
    // 1. Validasi Nama Unik
    $checkSql = "SELECT COUNT(*) FROM `references` WHERE title = ? AND id != ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$title, $id ?? 0]);
    
    if ($checkStmt->fetchColumn() > 0) {
        $redirectUrl = "../../dashboard.php?page=references_form" . ($id ? "&id=$id" : "");
        header("Location: $redirectUrl&status=error_unique");
        exit;
    }

    // 2. Logika Upload Gambar dengan Kompresi (Mendukung Base64 untuk Bypass OpenResty 403)
    $imagePath = null;
    
    // Prioritas 1: Base64 Upload (Aman dari blokir multipart OpenResty)
    if ($type == 'image' && !empty($_POST['image_base64'])) {
        $uploaded = saveAndCompressBase64Image($_POST['image_base64']);
        if ($uploaded) {
            $imagePath = $uploaded;
            // Hapus gambar lama jika ini mode Edit
            if ($id) {
                $oldStmt = $pdo->prepare("SELECT image_path FROM `references` WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldImg = $oldStmt->fetchColumn();
                if ($oldImg && file_exists("../../../assets/uploads/references/$oldImg")) {
                    @unlink("../../../assets/uploads/references/$oldImg");
                }
            }
        } else {
            header("Location: ../../dashboard.php?page=references_form&status=error_upload" . ($id ? "&id=$id" : ""));
            exit;
        }
    } 
    // Prioritas 2: Fallback $_FILES jika ada
    elseif ($type == 'image' && !empty($_FILES['image']['name'])) {
        $uploaded = uploadAndCompressImage($_FILES['image']);
        if ($uploaded) {
            $imagePath = $uploaded;
            if ($id) {
                $oldStmt = $pdo->prepare("SELECT image_path FROM `references` WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldImg = $oldStmt->fetchColumn();
                if ($oldImg && file_exists("../../../assets/uploads/references/$oldImg")) {
                    @unlink("../../../assets/uploads/references/$oldImg");
                }
            }
        } else {
            header("Location: ../../dashboard.php?page=references_form&status=error_upload" . ($id ? "&id=$id" : ""));
            exit;
        }
    } elseif ($type == 'image' && $id) {
        // Jika mode edit tapi tidak ganti gambar, pakai gambar lama
        $stmt = $pdo->prepare("SELECT image_path FROM `references` WHERE id = ?");
        $stmt->execute([$id]);
        $imagePath = $stmt->fetchColumn();
    }

    // 3. Simpan ke Database
    if ($id) {
        // UPDATE
        $sql = "UPDATE `references` SET title=?, subject_id=?, type=?, content=?, image_path=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $subject_id, $type, $content, $imagePath, $id]);
    } else {
        // INSERT
        $sql = "INSERT INTO `references` (title, subject_id, type, content, image_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $subject_id, $type, $content, $imagePath]);
    }

    header("Location: ../../dashboard.php?page=references&status=success");
    exit;
}

// --- AKSI HAPUS ---
if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Hapus file fisik
    $stmt = $pdo->prepare("SELECT image_path FROM `references` WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    
    if ($img && file_exists("../../../assets/uploads/references/$img")) {
        unlink("../../../assets/uploads/references/$img");
    }

    // Hapus data DB
    $pdo->prepare("DELETE FROM `references` WHERE id = ?")->execute([$id]);
    
    header("Location: ../../dashboard.php?page=references&status=deleted");
    exit;
}