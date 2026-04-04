<?php
// admin/modules/students/export.php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

// Ambil data siswa
$stmt = $pdo->query("SELECT * FROM students ORDER BY class ASC, full_name ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "Data_Siswa_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Siswa</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h3>Daftar Siswa CBT</h3>
    <p>Tanggal Export: <?= date('d M Y H:i') ?></p>
    
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Username</th>
                <th>Password (Default)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $index => $s): ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['class'] ?? '-') ?></td>
                <td><?= htmlspecialchars($s['username']) ?></td>
                <td>123456</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
