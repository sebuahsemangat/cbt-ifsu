<?php
// admin/modules/students/print_card.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth_check.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Ambil Pengaturan Aplikasi (Logo & Nama Sekolah)
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$bimbel_logo = $settings['bimbel_logo'] ?? 'logo-bimbel.png';
$bimbel_name = $settings['bimbel_name'] ?? 'SMK INFORMATIKA SUMEDANG';

// 2. Encode Logo Sekolah & Logo YPS ke Base64 (Reliabel & 100% didukung Dompdf)
$logoPath = realpath(__DIR__ . '/../../../assets/img/' . $bimbel_logo);
$logoBase64 = '';
if ($logoPath && file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$logoYpsPath = realpath(__DIR__ . '/../../../assets/img/logo-yps.png');
$logoYpsBase64 = '';
if ($logoYpsPath && file_exists($logoYpsPath)) {
    $typeYps = pathinfo($logoYpsPath, PATHINFO_EXTENSION);
    $dataYps = file_get_contents($logoYpsPath);
    $logoYpsBase64 = 'data:image/' . $typeYps . ';base64,' . base64_encode($dataYps);
}

// 3. Ambil Daftar Mata Pelajaran Terurut dari Tanggal Terdekat (Dikelompokkan per Kelas)
$stmtClassSubjects = $pdo->query("
    SELECT ec.class_name, s.name AS subject_name, e.start_date
    FROM subjects s
    JOIN exams e ON s.exam_id = e.id
    JOIN exam_classes ec ON ec.exam_id = e.id
    WHERE e.is_active = 1 AND s.is_active = 1
    ORDER BY e.start_date ASC, s.name ASC
");
$allClassSubjects = [];
while ($row = $stmtClassSubjects->fetch(PDO::FETCH_ASSOC)) {
    $allClassSubjects[$row['class_name']][] = [
        'name' => $row['subject_name'],
        'date' => date('d-m-Y', strtotime($row['start_date']))
    ];
}

// Fallback: Seluruh mata pelajaran aktif jika siswa belum memiliki mapping kelas spesifik
$stmtGlobalSubjects = $pdo->query("
    SELECT s.name AS subject_name, e.start_date
    FROM subjects s
    JOIN exams e ON s.exam_id = e.id
    WHERE e.is_active = 1 AND s.is_active = 1
    ORDER BY e.start_date ASC, s.name ASC
");
$globalSubjects = [];
while ($row = $stmtGlobalSubjects->fetch(PDO::FETCH_ASSOC)) {
    $globalSubjects[] = [
        'name' => $row['subject_name'],
        'date' => date('d-m-Y', strtotime($row['start_date']))
    ];
}

// 4. Filter Siswa yang Akan Dicetak
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$class = isset($_GET['class']) ? trim($_GET['class']) : 'all';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdfTitle = "Kartu_Ujian_" . ($students[0]['nis'] ?? 'Siswa');
} elseif ($class !== 'all' && $class !== '') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? ORDER BY full_name ASC");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdfTitle = "Kartu_Ujian_Kelas_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $class);
} else {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY class ASC, full_name ASC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdfTitle = "Kartu_Ujian_Semua_Siswa";
}

if (empty($students)) {
    die("Tidak ada data siswa yang dapat dicetak.");
}

// 5. Bagi siswa ke dalam grup 6 kartu per lembar A4 (Grid 2 Kolom x 3 Baris)
$studentChunks = array_chunk($students, 6);

// 6. Generate HTML untuk Dompdf
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pdfTitle) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3.5mm 4.5mm;
        }
        * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            margin: 0;
            padding: 0;
            font-size: 6pt;
        }
        .page {
            width: 100%;
        }
        .page-break {
            page-break-before: always;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        .grid-td {
            width: 50%;
            vertical-align: top;
            padding: 1.2mm 3.5mm;
            text-align: center;
        }
        .card-box {
            width: 85mm;
            height: 86mm;
            border: 1px solid #000;
            border-radius: 0;
            padding: 2mm 2.5mm;
            background-color: #ffffff;
            overflow: hidden;
            margin: 0 auto;
            text-align: left;
        }

        /* KOP HEADER (3 KOLOM: LOGO KIRI, TEKS TENGAH, LOGO KANAN) */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .kop-logo-left {
            width: 20px;
            vertical-align: middle;
            text-align: center;
        }
        .kop-logo-left img {
            max-width: 19px;
            max-height: 21px;
            display: block;
            margin: 0 auto;
        }
        .kop-logo-right {
            width: 20px;
            vertical-align: middle;
            text-align: center;
        }
        .kop-logo-right img {
            max-width: 19px;
            max-height: 21px;
            display: block;
            margin: 0 auto;
        }
        .kop-text {
            vertical-align: middle;
            text-align: center;
            padding: 0 1px;
        }
        .kop-line-1 {
            font-size: 6.5pt;
            font-weight: bold;
            color: #000;
            line-height: 1.1;
        }
        .kop-line-2 {
            font-size: 5.2pt;
            font-weight: bold;
            color: #000;
            line-height: 1.1;
            margin-top: 0.5px;
        }
        .kop-line-3 {
            font-size: 5.5pt;
            font-weight: bold;
            color: #000;
            line-height: 1.1;
            margin-top: 0.5px;
        }
        .kop-divider {
            border-bottom: 1px solid #000;
            margin: 1.5mm 0 1.5mm 0;
            height: 0;
            font-size: 0;
            line-height: 0;
        }

        /* IDENTITAS SISWA */
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 5.3pt;
            margin-bottom: 1.5mm;
        }
        .bio-table td {
            padding: 0.4px 0;
            vertical-align: top;
            border: none;
            line-height: 1.15;
        }
        .bio-label {
            width: 56px;
            color: #222;
        }
        .bio-colon {
            width: 5px;
            text-align: center;
        }
        .bio-value {
            color: #000;
        }

        /* TABEL MATA PELAJARAN */
        .mapel-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 5pt;
        }
        .mapel-table th, .mapel-table td {
            border: 0.8px solid #000;
            padding: 0.8px 1.5px;
            line-height: 1.1;
        }
        .mapel-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .mapel-col-date {
            width: 22mm;
            text-align: center;
        }
        .mapel-col-name {
            text-align: left;
        }
    </style>
</head>
<body>

<?php foreach ($studentChunks as $pageIndex => $chunk): ?>
    <?php if ($pageIndex > 0): ?>
        <div class="page-break"></div>
    <?php endif; ?>

    <div class="page">
        <table class="grid-table">
            <?php for ($row = 0; $row < 3; $row++): ?>
                <tr>
                    <?php for ($col = 0; $col < 2; $col++): 
                        $cardIndex = $row * 2 + $col;
                    ?>
                        <td class="grid-td">
                            <?php if (isset($chunk[$cardIndex])): 
                                $s = $chunk[$cardIndex]; 
                                $studentClass = $s['class'] ?? '';
                                $subjects = $allClassSubjects[$studentClass] ?? $globalSubjects;
                            ?>
                                <div class="card-box">
                                    <!-- KOP HEADER -->
                                    <table class="kop-table">
                                        <tr>
                                            <td class="kop-logo-left">
                                                <?php if (!empty($logoBase64)): ?>
                                                    <img src="<?= $logoBase64 ?>" alt="Logo Sekolah">
                                                <?php endif; ?>
                                            </td>
                                            <td class="kop-text">
                                                <div class="kop-line-1">KARTU PESERTA &amp; LOGIN</div>
                                                <div class="kop-line-2">PENILAIAN SUMATIF AKHIR JENJANG (PSAJ)</div>
                                                <div class="kop-line-3">TAHUN <?= date('Y') ?></div>
                                            </td>
                                            <td class="kop-logo-right">
                                                <?php if (!empty($logoYpsBase64)): ?>
                                                    <img src="<?= $logoYpsBase64 ?>" alt="Logo YPS">
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="kop-divider"></div>

                                    <!-- IDENTITAS SISWA -->
                                    <table class="bio-table">
                                        <tr>
                                            <td class="bio-label">Nama Peserta</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value"><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="bio-label">NIS</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value"><strong><?= htmlspecialchars($s['nis'] ?? '') ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="bio-label">Kelas</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value"><?= htmlspecialchars($s['class'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="bio-label">Nama Kelompok</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value"><?= htmlspecialchars($s['kelompok'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="bio-label">Password</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value">123456</td>
                                        </tr>
                                        <tr>
                                            <td class="bio-label">Ruang</td>
                                            <td class="bio-colon">:</td>
                                            <td class="bio-value"><?= (!empty($s['room']) && $s['room'] !== '0') ? htmlspecialchars($s['room']) : '-' ?></td>
                                        </tr>
                                    </table>

                                    <!-- TABEL MATA PELAJARAN -->
                                    <table class="mapel-table">
                                        <thead>
                                            <tr>
                                                <th class="mapel-col-date">Tanggal</th>
                                                <th class="mapel-col-name">Mata Pelajaran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($subjects)): ?>
                                                <?php foreach ($subjects as $sub): ?>
                                                    <tr>
                                                        <td class="mapel-col-date"><?= htmlspecialchars($sub['date']) ?></td>
                                                        <td class="mapel-col-name"><?= htmlspecialchars($sub['name']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="2" style="text-align: center; color: #888; font-style: italic;">Jadwal belum ditentukan</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// 7. Konfigurasi dan Render Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 8. Output PDF langsung ke browser (inline preview)
$dompdf->stream($pdfTitle . ".pdf", ["Attachment" => false]);
