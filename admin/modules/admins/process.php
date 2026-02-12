<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

$action = $_GET['action'] ?? '';

if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($fullName) && !empty($username) && !empty($password)) {
        // Cek username duplikat
        $check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) {
            header("Location: ../../dashboard.php?page=admins&status=username_exists");
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $fullName]);
        header("Location: ../../dashboard.php?page=admins&status=success");
    } else {
        header("Location: ../../dashboard.php?page=admins&status=error");
    }
    exit;
}

if ($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($id) && !empty($fullName) && !empty($username)) {
        // Cek username duplikat (untuk admin lain)
        $check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);
        if ($check->fetchColumn() > 0) {
            header("Location: ../../dashboard.php?page=admins&status=username_exists");
            exit;
        }

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ?, full_name = ? WHERE id = ?");
            $stmt->execute([$username, $hashedPassword, $fullName, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET username = ?, full_name = ? WHERE id = ?");
            $stmt->execute([$username, $fullName, $id]);
        }
        header("Location: ../../dashboard.php?page=admins&status=updated");
    } else {
        header("Location: ../../dashboard.php?page=admins&status=error");
    }
    exit;
}

if ($action == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: ../../dashboard.php?page=admins&status=deleted");
    } else {
        header("Location: ../../dashboard.php?page=admins&status=error");
    }
    exit;
}
