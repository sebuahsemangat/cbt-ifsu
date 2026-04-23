<?php
/**
 * File: admin/modules/settings/process.php
 * Deskripsi: Memproses pembaruan pengaturan aplikasi
 */

session_start();
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

/**
 * Fungsi sederhana untuk kompres dan resize gambar
 */
function compressImage($source, $destination, $quality = 75, $maxWidth = 500) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        // Pertahankan transparansi PNG
        imagealphablending($image, false);
        imagesavealpha($image, true);
    } else {
        return false;
    }

    // Ambil dimensi asli
    $width = $info[0];
    $height = $info[1];

    // Hitung dimensi baru (jika lebih besar dari maxWidth)
    if ($width > $maxWidth || $height > $maxWidth) {
        $ratio = $width / $height;
        if ($ratio > 1) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } else {
            $newHeight = $maxWidth;
            $newWidth = $maxWidth * $ratio;
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparansi untuk gambar baru
        if ($info['mime'] == 'image/png' || $info['mime'] == 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $image = $newImage;
    }

    // Simpan gambar dengan kompresi
    if ($info['mime'] == 'image/jpeg') {
        return imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/gif') {
        return imagegif($image, $destination);
    } elseif ($info['mime'] == 'image/png') {
        // PNG compression is 0-9, convert quality 0-100 to 0-9
        $pngQuality = 9 - round(($quality / 100) * 9);
        return imagepng($image, $destination, $pngQuality);
    }

    return false;
}

if (isset($_POST['update_settings'])) {
    $app_name = trim($_POST['app_name']);
    $bimbel_name = trim($_POST['bimbel_name']);

    try {
        $pdo->beginTransaction();

        // Update App Name
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('app_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$app_name, $app_name]);

        // Update Bimbel Name
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('bimbel_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bimbel_name, $bimbel_name]);

        // Handle Logo Upload
        if (isset($_FILES['bimbel_logo']) && $_FILES['bimbel_logo']['error'] == 0) {
            $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
            $file_type = $_FILES['bimbel_logo']['type'];

            if (in_array($file_type, $allowed_types)) {
                $file_extension = pathinfo($_FILES['bimbel_logo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'logo-bimbel.' . $file_extension;
                $target_path = '../../../assets/img/' . $new_filename;

                if (compressImage($_FILES['bimbel_logo']['tmp_name'], $target_path, 80, 400)) {
                    // Update logo path in database
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('bimbel_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$new_filename, $new_filename]);
                } else {
                    throw new Exception("Gagal memproses dan mengompres logo.");
                }
            } else {
                throw new Exception("Format logo tidak didukung. Gunakan PNG, JPG, atau JPEG.");
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
