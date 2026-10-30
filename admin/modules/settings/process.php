<?php
/**
 * File: admin/modules/settings/process.php
 * Deskripsi: Memproses pembaruan pengaturan aplikasi
 */

session_start();
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

/**
 * Fungsi kompres dan resize gambar dengan dukungan transparansi PNG/WebP/GIF
 */
function compressImage($source, $destination, $quality = 80, $maxWidth = 500) {
    $info = @getimagesize($source);
    if (!$info) {
        return false;
    }

    $mime = $info['mime'];
    $image = null;

    if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
        $image = @imagecreatefromjpeg($source);
    } elseif ($mime === 'image/gif') {
        $image = @imagecreatefromgif($source);
    } elseif ($mime === 'image/png' || $mime === 'image/x-png') {
        $image = @imagecreatefrompng($source);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($source);
    }

    if (!$image) {
        return false;
    }

    $width = $info[0];
    $height = $info[1];

    // Hitung dimensi baru jika melebihi maxWidth
    if ($width > $maxWidth || $height > $maxWidth) {
        $ratio = $width / $height;
        if ($ratio > 1) {
            $newWidth = (int) $maxWidth;
            $newHeight = (int) round($maxWidth / $ratio);
        } else {
            $newHeight = (int) $maxWidth;
            $newWidth = (int) round($maxWidth * $ratio);
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparansi untuk PNG, WebP, dan GIF
        if ($mime === 'image/png' || $mime === 'image/x-png' || $mime === 'image/gif' || $mime === 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $newImage;
    } else {
        // Pertahankan transparansi PNG asli meski tidak di-resize
        if ($mime === 'image/png' || $mime === 'image/x-png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
    }

    $saved = false;
    if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
        $saved = imagejpeg($image, $destination, $quality);
    } elseif ($mime === 'image/gif') {
        $saved = imagegif($image, $destination);
    } elseif ($mime === 'image/png' || $mime === 'image/x-png') {
        // Skala kualitas PNG 0-9 (0 = uncompressed, 9 = max compression)
        $pngQuality = 9 - (int) round(($quality / 100) * 9);
        $pngQuality = max(0, min(9, $pngQuality));
        $saved = imagepng($image, $destination, $pngQuality);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        $saved = imagewebp($image, $destination, $quality);
    }

    imagedestroy($image);
    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_settings']) || isset($_POST['app_name']))) {
    $app_name = trim($_POST['app_name'] ?? '');
    $bimbel_name = trim($_POST['bimbel_name'] ?? '');

    try {
        $pdo->beginTransaction();

        // Update App Name
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('app_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$app_name, $app_name]);

        // Update Bimbel Name
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('bimbel_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bimbel_name, $bimbel_name]);

        // Update Fitur Ganti Password Siswa
        $allow_student_change_password = isset($_POST['allow_student_change_password']) ? '1' : '0';
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('allow_student_change_password', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$allow_student_change_password, $allow_student_change_password]);

        // Update Batas Minimal Durasi Pengerjaan Ujian (%)
        $min_percent = isset($_POST['min_exam_duration_percent']) ? (int)$_POST['min_exam_duration_percent'] : 50;
        $min_percent = max(0, min(100, $min_percent));
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('min_exam_duration_percent', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([(string)$min_percent, (string)$min_percent]);

        // Update Fitur Anti-Cheating
        $enable_anti_cheating = isset($_POST['enable_anti_cheating']) ? '1' : '0';
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('enable_anti_cheating', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$enable_anti_cheating, $enable_anti_cheating]);

        // Update Pengaturan Cloud Sync CBT
        if (isset($_POST['cbt_cloud_sync_url'])) {
            $cloud_url = trim($_POST['cbt_cloud_sync_url']);
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cbt_cloud_sync_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$cloud_url, $cloud_url]);
        }
        if (isset($_POST['cbt_cloud_sync_token'])) {
            $cloud_token = trim($_POST['cbt_cloud_sync_token']);
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cbt_cloud_sync_token', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$cloud_token, $cloud_token]);
        }

        // Handle Logo Upload (Mendukung Base64 untuk bypass OpenResty 403 pada multipart/form-data)
        $logo_processed = false;

        // 1. Upload via Base64 Data URL (Format application/x-www-form-urlencoded untuk bypass OpenResty 403)
        if (!empty($_POST['bimbel_logo_base64'])) {
            $dataUri = $_POST['bimbel_logo_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
                $rawExt = strtolower($matches[1]);
                $file_extension = ($rawExt === 'jpeg') ? 'jpg' : $rawExt;
                $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Format logo tidak didukung. Gunakan PNG, JPG, JPEG, atau WebP.");
                }

                $base64Data = substr($dataUri, strpos($dataUri, ',') + 1);
                $binaryData = base64_decode($base64Data);

                if ($binaryData === false) {
                    throw new Exception("Format gambar base64 rusak.");
                }

                if (strlen($binaryData) > 2 * 1024 * 1024) {
                    throw new Exception("Ukuran file logo terlalu besar. Maksimal 2MB.");
                }

                $target_dir = '../../../assets/img/';
                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0755, true);
                }

                // Dapatkan logo lama untuk dibersihkan
                $stmt_old = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'bimbel_logo'");
                $stmt_old->execute();
                $old_filename = $stmt_old->fetchColumn();

                $new_filename = 'logo-bimbel-' . time() . '.' . $file_extension;
                $target_path = $target_dir . $new_filename;

                $tmp_file = tempnam(sys_get_temp_dir(), 'logo_');
                file_put_contents($tmp_file, $binaryData);

                $processed = compressImage($tmp_file, $target_path, 80, 400);
                if (!$processed) {
                    if (!copy($tmp_file, $target_path)) {
                        @unlink($tmp_file);
                        throw new Exception("Gagal memproses dan menyimpan file logo.");
                    }
                }
                @unlink($tmp_file);

                // Update nama file logo di database
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('bimbel_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$new_filename, $new_filename]);

                // Hapus file logo lama jika ada dan berbeda
                if ($old_filename && $old_filename !== $new_filename) {
                    $old_path = $target_dir . $old_filename;
                    if (file_exists($old_path) && is_file($old_path)) {
                        @unlink($old_path);
                    }
                }

                $logo_processed = true;
            }
        }

        // 2. Fallback Upload normal via $_FILES jika tersedia
        if (!$logo_processed && isset($_FILES['bimbel_logo']) && $_FILES['bimbel_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['bimbel_logo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Gagal mengunggah logo. Kode error: " . $_FILES['bimbel_logo']['error']);
            }

            // Validasi ukuran (max 2MB)
            if ($_FILES['bimbel_logo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran file logo terlalu besar. Maksimal 2MB.");
            }

            // Validasi MIME type secara aman di server
            $tmp_file = $_FILES['bimbel_logo']['tmp_name'];
            $detected_mime = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detected_mime = finfo_file($finfo, $tmp_file);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $detected_mime = mime_content_type($tmp_file);
            } else {
                $info = @getimagesize($tmp_file);
                $detected_mime = $info['mime'] ?? '';
            }

            $allowed_mimes = [
                'image/png',
                'image/x-png',
                'image/jpeg',
                'image/pjpeg',
                'image/jpg',
                'image/webp'
            ];

            $file_extension = strtolower(pathinfo($_FILES['bimbel_logo']['name'], PATHINFO_EXTENSION));
            if ($file_extension === 'jpeg') {
                $file_extension = 'jpg';
            }

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp'];

            if (!in_array($detected_mime, $allowed_mimes) || !in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Format logo tidak didukung. Gunakan PNG, JPG, JPEG, atau WebP.");
            }

            // Dapatkan logo lama untuk dibersihkan
            $stmt_old = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'bimbel_logo'");
            $stmt_old->execute();
            $old_filename = $stmt_old->fetchColumn();

            // Beri nama unik berbasis timestamp agar browser TIDAK menggunakan cache lama
            $new_filename = 'logo-bimbel-' . time() . '.' . $file_extension;
            $target_dir = '../../../assets/img/';
            $target_path = $target_dir . $new_filename;

            // Proses kompresi gambar, fallback ke move_uploaded_file jika GD gagal
            $processed = compressImage($tmp_file, $target_path, 80, 400);
            if (!$processed) {
                if (!move_uploaded_file($tmp_file, $target_path)) {
                    throw new Exception("Gagal memproses dan menyimpan file logo.");
                }
            }

            // Update nama file logo di database
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('bimbel_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$new_filename, $new_filename]);

            // Hapus file logo lama jika ada dan berbeda
            if ($old_filename && $old_filename !== $new_filename) {
                $old_path = $target_dir . $old_filename;
                if (file_exists($old_path) && is_file($old_path)) {
                    @unlink($old_path);
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Pengaturan berhasil diperbarui.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal memperbarui pengaturan: " . $e->getMessage();
    }

    header("Location: ../../dashboard.php?page=settings");
    exit();
} else {
    header("Location: ../../dashboard.php");
    exit();
}
