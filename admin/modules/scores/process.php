<?php
// admin/modules/scores/process.php

session_start();
require_once '../../../config/database.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$action = $_GET['action'] ?? '';

// === HAPUS DATA NILAI ===
if ($action === 'delete') {
    $id = $_GET['id'] ?? null;
    $exam_id = $_GET['exam_id'] ?? null;

    if (!$id) {
        $_SESSION['error'] = "ID nilai tidak valid.";
        header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
        exit;
    }

    try {
        // Hapus data dari exam_sessions berdasarkan id
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Data nilai berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Data nilai tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus data: " . $e->getMessage();
    }

    // Redirect kembali ke halaman nilai
    header("Location: ../../dashboard.php?page=scores" . ($exam_id ? "&exam_id=" . $exam_id : ""));
    exit;
}

// Jika action tidak valid
$_SESSION['error'] = "Aksi tidak valid.";
header("Location: ../../dashboard.php?page=scores");
exit;
