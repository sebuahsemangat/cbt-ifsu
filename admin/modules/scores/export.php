<?php
// admin/modules/scores/export.php
require_once '../../../config/database.php';
session_start();

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$kelompok_filter = isset($_GET['kelompok']) ? $_GET['kelompok'] : (isset($_GET['class']) ? $_GET['class'] : 'all');

if (!$exam_id) {
    die("ID Ujian tidak ditemukan.");
}

// Ambil info ujian
$stmt_exam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt_exam->execute([$exam_id]);
$exam = $stmt_exam->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Ujian tidak ditemukan.");
}

// Query data nilai dengan filter kelompok
$query = "
    SELECT 
        s.nis,
        s.full_name,
        s.class,
        s.kelompok,
        e.exam_name,
        sb.name AS subject_name,
        es.score,
        es.status,
        es.start_time,
        es.end_time
    FROM exam_sessions es
    JOIN students s ON es.student_id = s.id
    JOIN exams e ON es.exam_id = e.id
    JOIN subjects sb ON es.subject_id = sb.id
    WHERE es.exam_id = :exam_id AND es.status = 'finished'
";

$params = ['exam_id' => $exam_id];

if ($kelompok_filter === 'none') {
    $query .= " AND (s.kelompok IS NULL OR s.kelompok = '') ";
} elseif ($kelompok_filter !== 'all') {
    $query .= " AND s.kelompok = :kelompok ";
    $params['kelompok'] = $kelompok_filter;
}

$query .= " ORDER BY s.kelompok ASC, s.class ASC, s.full_name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Set Header untuk Download Excel
$kel_label = 'Semua_Kelompok';
if ($kelompok_filter === 'none') {
    $kel_label = 'Tanpa_Kelompok';
} elseif ($kelompok_filter !== 'all') {
    $kel_label = preg_replace('/[^A-Za-z0-9]/', '_', $kelompok_filter);
}

$filename = "Nilai_" . preg_replace('/[^A-Za-z0-9]/', '_', $exam['exam_name']) . "_Kelompok_" . $kel_label . "_" . date('Ymd') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Nilai per Kelompok</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h3>Laporan Nilai Ujian: <?= htmlspecialchars($exam['exam_name']) ?></h3>
    <?php if ($kelompok_filter === 'none'): ?>
        <p>Kelompok: Tanpa Kelompok (Belum Ditentukan)</p>
    <?php elseif ($kelompok_filter !== 'all'): ?>
        <p>Kelompok: <?= htmlspecialchars($kelompok_filter) ?></p>
    <?php else: ?>
        <p>Kelompok: Semua Kelompok</p>
    <?php endif; ?>
    <p>Periode: <?= date('d M Y', strtotime($exam['start_date'])) ?> - <?= date('d M Y', strtotime($exam['end_date'])) ?></p>
    
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Kelompok</th>
                <th>Ujian</th>
                <th>Mata Pelajaran</th>
                <th class="text-center">Nilai</th>
                <th class="text-center">Status</th>
                <th class="text-center">Waktu Mulai</th>
                <th class="text-center">Waktu Selesai</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scores as $index => $row): ?>
            <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($row['nis'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['class'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['kelompok'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['exam_name']) ?></td>
                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                <td class="text-center"><?= number_format($row['score'], 2) ?></td>
                <td class="text-center">
                    <?= $row['status'] == 'finished' ? 'Selesai' : 'Sedang Mengerjakan' ?>
                </td>
                <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['start_time'])) ?></td>
                <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['end_time'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
