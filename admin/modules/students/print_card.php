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

// 2. Encode Logo & Tanda Tangan ke Base64 (Reliabel & 100% didukung Dompdf)
$logoPath = realpath(__DIR__ . '/../../../assets/img/' . $bimbel_logo);
$logoBase64 = '';
if ($logoPath && file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$ttdPath = realpath(__DIR__ . '/../../../assets/img/ttd.png');
$ttdBase64 = '';
if ($ttdPath && file_exists($ttdPath)) {
    $type = pathinfo($ttdPath, PATHINFO_EXTENSION);
    $data = file_get_contents($ttdPath);
    $ttdBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// 3. Filter Siswa yang Akan Dicetak
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

// 4. Bagi siswa ke dalam grup 4 kartu per lembar A4 (Grid 2 Kolom x 2 Baris)
$studentChunks = array_chunk($students, 4);

// 5. Generate HTML untuk Dompdf
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
            margin: 8mm 6mm;
        }
        * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #111;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
        }
        .page {
            width: 100%;
            height: 100%;
        }
        .page-break {
            page-break-before: always;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .grid-td {
            width: 50%;
            vertical-align: top;
            padding: 3.5mm 3.5mm;
        }
        .card-box {
            border: 1.5px solid #222;
            border-radius: 8px;
            padding: 7px 9px 6px 9px;
            background-color: #ffffff;
            height: 130mm;
            max-height: 130mm;
            overflow: hidden;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo {
            width: 48px;
            vertical-align: middle;
            text-align: center;
        }
        .header-logo img {
            max-width: 44px;
            max-height: 44px;
            object-fit: contain;
        }
        .header-text {
            vertical-align: middle;
            text-align: center;
            padding-left: 4px;
        }
        .school-name {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
            color: #0f172a;
        }
        .school-address {
            font-size: 6.8pt;
            color: #334155;
            margin-top: 1px;
            line-height: 1.15;
        }
        .divider {
            border-top: 1.8px solid #000;
            border-bottom: 0.5px solid #000;
            height: 2px;
            margin: 4px 0 5px 0;
        }
        .card-title {
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            color: #0f172a;
        }
        .card-subtitle {
            text-align: center;
            font-size: 7.2pt;
            color: #475569;
            margin-top: 1px;
            margin-bottom: 6px;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 3px;
            margin-bottom: 4px;
        }
        .bio-table td {
            padding: 2px 1px;
            vertical-align: top;
            border: none;
        }
        .bio-label {
            width: 50px;
            color: #334155;
        }
        .bio-colon {
            width: 8px;
            text-align: center;
        }
        .bio-value {
            font-weight: 600;
            color: #0f172a;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .sig-table td {
            border: none;
            vertical-align: top;
        }
        .sig-date {
            font-size: 7.2pt;
            margin-bottom: 1px;
        }
        .sig-title {
            font-size: 7.2pt;
            margin-bottom: 1px;
        }
        .sig-image-container {
            height: 38px;
            text-align: center;
        }
        .sig-image {
            max-height: 36px;
            max-width: 110px;
            object-fit: contain;
        }
        .sig-name {
            font-size: 7.5pt;
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
            margin-top: 1px;
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
            <tr>
                <?php for ($col = 0; $col < 2; $col++): ?>
                    <td class="grid-td">
                        <?php if (isset($chunk[$col])): $s = $chunk[$col]; ?>
                            <div class="card-box">
                                <!-- Bagian Atas: Header Logo & Nama Sekolah -->
                                <table class="header-table">
                                    <tr>
                                        <?php if (!empty($logoBase64)): ?>
                                            <td class="header-logo">
                                                <img src="<?= $logoBase64 ?>" alt="Logo">
                                            </td>
                                        <?php endif; ?>
                                        <td class="header-text">
                                            <div class="school-name">SMK INFORMATIKA SUMEDANG</div>
                                            <div class="school-address">Jln. Angkrek Situ No. 19 Sumedang</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Garis Pemisah -->
                                <div class="divider"></div>

                                <!-- Judul Kartu -->
                                <div class="card-title">KARTU UJIAN SEKOLAH</div>
                                <div class="card-subtitle">Tahun Pelajaran 2026/2027</div>

                                <!-- Identitas Siswa (Tabel tanpa border) -->
                                <table class="bio-table">
                                    <tr>
                                        <td class="bio-label">NIS</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['nis']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bio-label">Nama</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['full_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bio-label">Kelas</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['class'] ?? '-') ?></td>
                                    </tr>
                                </table>

                                <!-- Tanda Tangan Kepala Sekolah (Kanan Bawah) -->
                                <table class="sig-table">
                                    <tr>
                                        <td style="width: 45%;"></td>
                                        <td style="width: 55%; text-align: center;">
                                            <div class="sig-date">Sumedang, 20 Oktober 2026</div>
                                            <div class="sig-title">Kepala Sekolah,</div>
                                            <div class="sig-image-container">
                                                <?php if (!empty($ttdBase64)): ?>
                                                    <img src="<?= $ttdBase64 ?>" class="sig-image" alt="TTD">
                                                <?php else: ?>
                                                    <div style="height: 36px;"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="sig-name">Tatang Suryana, S.Ag., M.Pd.</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
            <tr>
                <?php for ($col = 2; $col < 4; $col++): ?>
                    <td class="grid-td">
                        <?php if (isset($chunk[$col])): $s = $chunk[$col]; ?>
                            <div class="card-box">
                                <!-- Bagian Atas: Header Logo & Nama Sekolah -->
                                <table class="header-table">
                                    <tr>
                                        <?php if (!empty($logoBase64)): ?>
                                            <td class="header-logo">
                                                <img src="<?= $logoBase64 ?>" alt="Logo">
                                            </td>
                                        <?php endif; ?>
                                        <td class="header-text">
                                            <div class="school-name">SMK INFORMATIKA SUMEDANG</div>
                                            <div class="school-address">Jln. Angkrek Situ No. 19 Sumedang</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Garis Pemisah -->
                                <div class="divider"></div>

                                <!-- Judul Kartu -->
                                <div class="card-title">KARTU UJIAN SEKOLAH</div>
                                <div class="card-subtitle">Tahun Pelajaran 2026/2027</div>

                                <!-- Identitas Siswa (Tabel tanpa border) -->
                                <table class="bio-table">
                                    <tr>
                                        <td class="bio-label">NIS</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['nis']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bio-label">Nama</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['full_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="bio-label">Kelas</td>
                                        <td class="bio-colon">:</td>
                                        <td class="bio-value"><?= htmlspecialchars($s['class'] ?? '-') ?></td>
                                    </tr>
                                </table>

                                <!-- Tanda Tangan Kepala Sekolah (Kanan Bawah) -->
                                <table class="sig-table">
                                    <tr>
                                        <td style="width: 45%;"></td>
                                        <td style="width: 55%; text-align: center;">
                                            <div class="sig-date">Sumedang, 20 Oktober 2026</div>
                                            <div class="sig-title">Kepala Sekolah,</div>
                                            <div class="sig-image-container">
                                                <?php if (!empty($ttdBase64)): ?>
                                                    <img src="<?= $ttdBase64 ?>" class="sig-image" alt="TTD">
                                                <?php else: ?>
                                                    <div style="height: 36px;"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="sig-name">Tatang Suryana, S.Ag., M.Pd.</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// 6. Konfigurasi dan Render Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 7. Output PDF langsung ke browser (inline preview)
$dompdf->stream($pdfTitle . ".pdf", ["Attachment" => false]);
