<?php
// admin/modules/students/export.php
require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';

// Ambil data siswa
$filter_class = isset($_GET['class']) ? $_GET['class'] : 'all';

if ($filter_class !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? ORDER BY full_name ASC");
    $stmt->execute([$filter_class]);
    $filename = "Data_Siswa_" . str_replace(' ', '_', $filter_class) . "_" . date('Ymd_His') . ".xls";
} else {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY class ASC, full_name ASC");
    $filename = "Data_Siswa_Semua_" . date('Ymd_His') . ".xls";
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <h3>Daftar Siswa CBT <?= $filter_class !== 'all' ? '- Kelas ' . htmlspecialchars($filter_class) : '(Semua Kelas)' ?></h3>
    <p>Tanggal Export: <?= date('d M Y H:i') ?></p>
    
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>NIS</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Kelompok</th>
                <th>Password (Default)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $index => $s): ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($s['nis']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['class'] ?? '-') ?></td>
                <td><?= htmlspecialchars($s['kelompok'] ?? '-') ?></td>
                <td>123456</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
