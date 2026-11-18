<?php
// admin/modules/scores/export.php
// Export nilai ujian ke file .xlsx asli (PhpSpreadsheet) dengan format
// yang sama seperti file contoh "Nilai Gabungan UAS PKN".
require_once '../../../config/database.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$export_by = isset($_GET['export_by']) ? $_GET['export_by'] : (isset($_GET['class']) && !isset($_GET['kelompok']) ? 'class' : 'kelompok');

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

$clean_exam = preg_replace('/[^A-Za-z0-9]/', '_', $exam['exam_name']);

// ---------------------------------------------------------------------------
// Style (meniru file contoh)
// ---------------------------------------------------------------------------
$headerFont = ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']];
$headerFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']];
$headerAlignment = ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true];
$thinBorder = ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BBBBBB']]];
$dataFont = ['name' => 'Calibri', 'size' => 11];

// Lebar kolom (meniru file contoh)
$widthsGabungan = [5, 13, 41, 12, 10, 8, 9, 7, 9, 18, 18];
$widthsPerKelas = [4, 30, 12, 10, 8, 9, 7, 9, 18, 18];
$widthsBelum    = [4, 13, 22, 12, 10, 7, 48];
$widthsRingkasan = [16, 15, 16, 16];

$headersGabungan = ['No', 'Kelas', 'Nama Siswa', 'NIS', 'Kelompok', 'Ruang', 'Ujian', 'Nilai', 'Status', 'Waktu Mulai', 'Waktu Selesai'];
$headersPerKelas = ['No', 'Nama Siswa', 'NIS', 'Kelompok', 'Ruang', 'Ujian', 'Nilai', 'Status', 'Waktu Mulai', 'Waktu Selesai'];
$headersBelum    = ['No', 'Kelas', 'Nama Siswa', 'NIS (SQL)', 'Kelompok', 'Ruang', 'Catatan'];
$headersRingkasan = ['Kelas', 'Jumlah di SQL', 'Sudah ada nilai', 'Belum ada nilai'];

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------
function fmtWaktu($dt) {
    if (empty($dt) || $dt === '0000-00-00 00:00:00') return '-';
    $ts = strtotime($dt);
    return $ts === false ? '-' : date('d/m/Y H:i', $ts);
}

function nilaiAtauStrip($v) {
    return ($v === null || $v === '') ? '-' : $v;
}

/** Nama sheet yang aman untuk Excel (maks. 31 karakter, tanpa \ / ? * [ ] :). */
function namaSheetKelas($class) {
    $name = str_replace(' - ', ' ', trim((string)$class));
    $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '_', $name);
    $name = trim($name);
    if ($name === '') $name = 'Tanpa Kelas';
    return mb_substr($name, 0, 31);
}

/** Terapkan style header + data ke satu sheet. */
function styleSheet($sheet, $headers, $widths, $lastRow, $freezeAndFilter = true, $boldRows = []) {
    global $headerFont, $headerFill, $headerAlignment, $thinBorder, $dataFont;

    $colCount = count($headers);
    $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

    foreach ($widths as $i => $w) {
        $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
    }

    // Header
    $sheet->fromArray($headers, null, 'A1');
    $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
        'font' => $headerFont,
        'fill' => $headerFill,
        'alignment' => $headerAlignment,
        'borders' => $thinBorder,
    ]);

    // Data
    if ($lastRow >= 2) {
        $sheet->getStyle("A2:{$lastColLetter}{$lastRow}")->applyFromArray([
            'font' => $dataFont,
            'borders' => $thinBorder,
        ]);
    }

    foreach ($boldRows as $r) {
        $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->getFont()->setBold(true);
    }

    if ($freezeAndFilter) {
        $sheet->freezePane('A2');
        if ($lastRow >= 1) {
            $sheet->setAutoFilter("A1:{$lastColLetter}{$lastRow}");
        }
    }
}

/** Isi satu baris data nilai (layout Gabungan). $withClass = true untuk Gabungan. */
function isiBarisNilai($sheet, $rowNo, $no, $r, $withClass, $examName) {
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, $no);
    if ($withClass) {
        $sheet->setCellValueByColumnAndRow($col++, $rowNo, nilaiAtauStrip($r['class']));
    }
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, $r['full_name']);
    // NIS sebagai TEKS eksplisit supaya awalan 00 tidak hilang di Excel
    $sheet->setCellValueExplicitByColumnAndRow($col++, $rowNo, (string)($r['nis'] ?? '-'), DataType::TYPE_STRING);
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, nilaiAtauStrip($r['kelompok']));
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, nilaiAtauStrip($r['room']));
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, $examName);
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, (float)$r['score']);
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, $r['status'] === 'finished' ? 'Selesai' : 'Sedang Mengerjakan');
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, fmtWaktu($r['start_time']));
    $sheet->setCellValueByColumnAndRow($col++, $rowNo, fmtWaktu($r['end_time']));
}

// ---------------------------------------------------------------------------
// Data: kelas-kelas yang mengikuti ujian ini
// (hanya kelas yang benar-benar ada siswanya; exam_classes yang tidak cocok
//  dengan data students — mis. 'XII-RPL 1' tanpa spasi — otomatis terlewat)
// ---------------------------------------------------------------------------
function daftarKelasUjian($pdo, $exam_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_classes WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $adaExamClasses = (int)$stmt->fetchColumn() > 0;

    if ($adaExamClasses) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.class
            FROM students s
            WHERE s.class IS NOT NULL AND s.class <> ''
              AND s.class IN (SELECT class_name FROM exam_classes WHERE exam_id = ?)
            ORDER BY s.class ASC
        ");
        $stmt->execute([$exam_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT class FROM students
            WHERE class IS NOT NULL AND class <> ''
            ORDER BY class ASC
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Query dasar data nilai (finished sessions ujian ini)
function queryNilai($pdo, $exam_id, $extraWhere = '', $extraParams = [], $orderBy = 's.class ASC, s.full_name ASC') {
    $sql = "
        SELECT
            s.nis,
            s.full_name,
            s.class,
            s.kelompok,
            s.room,
            es.score,
            es.status,
            es.start_time,
            es.end_time
        FROM exam_sessions es
        JOIN students s ON es.student_id = s.id
        WHERE es.exam_id = :exam_id AND es.status = 'finished'
        {$extraWhere}
        ORDER BY {$orderBy}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(['exam_id' => $exam_id], $extraParams));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------------------------
// Tentukan mode export + nama file
// ---------------------------------------------------------------------------
$mode = 'gabungan'; // gabungan | kelas | kelompok
$filename = '';
$kelasTunggal = null;
$kelompokFilter = null;

if ($export_by === 'class') {
    $class_filter = isset($_GET['class']) ? $_GET['class'] : 'all';
    if ($class_filter === 'all') {
        $mode = 'gabungan';
        $filename = "Nilai_{$clean_exam}_Gabungan_" . date('Ymd') . ".xlsx";
    } else {
        $mode = 'kelas';
        $kelasTunggal = $class_filter;
        $class_label = $class_filter === 'none' ? 'Tanpa_Kelas' : preg_replace('/[^A-Za-z0-9]/', '_', $class_filter);
        $filename = "Nilai_{$clean_exam}_Kelas_{$class_label}_" . date('Ymd') . ".xlsx";
    }
} else {
    $kelompok_filter = isset($_GET['kelompok']) ? $_GET['kelompok'] : 'all';
    $mode = 'kelompok';
    $kelompokFilter = $kelompok_filter;
    $kel_label = $kelompok_filter === 'none' ? 'Tanpa_Kelompok'
        : ($kelompok_filter === 'all' ? 'Semua_Kelompok' : preg_replace('/[^A-Za-z0-9]/', '_', $kelompok_filter));
    $filename = "Nilai_{$clean_exam}_Kelompok_{$kel_label}_" . date('Ymd') . ".xlsx";
}

// ---------------------------------------------------------------------------
// Bangun workbook
// ---------------------------------------------------------------------------
try {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
        ->setCreator('CBT IFSU')
        ->setTitle("Nilai {$exam['exam_name']}");

    if ($mode === 'gabungan') {
        $classes = daftarKelasUjian($pdo, $exam_id);

        // --- Ringkasan ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan');
        $rowNo = 2;
        $totalSql = $totalSudah = $totalBelum = 0;
        foreach ($classes as $class) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class = ?");
            $stmt->execute([$class]);
            $jumlah = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT es.student_id)
                FROM exam_sessions es
                JOIN students s ON es.student_id = s.id
                WHERE es.exam_id = ? AND es.status = 'finished' AND s.class = ?
            ");
            $stmt->execute([$exam_id, $class]);
            $sudah = (int)$stmt->fetchColumn();

            $belum = $jumlah - $sudah;
            $totalSql += $jumlah; $totalSudah += $sudah; $totalBelum += $belum;

            $sheet->setCellValueByColumnAndRow(1, $rowNo, $class);
            $sheet->setCellValueByColumnAndRow(2, $rowNo, $jumlah);
            $sheet->setCellValueByColumnAndRow(3, $rowNo, $sudah);
            $sheet->setCellValueByColumnAndRow(4, $rowNo, $belum);
            $rowNo++;
        }
        $totalRow = $rowNo;
        $sheet->setCellValueByColumnAndRow(1, $totalRow, 'TOTAL');
        $sheet->setCellValueByColumnAndRow(2, $totalRow, $totalSql);
        $sheet->setCellValueByColumnAndRow(3, $totalRow, $totalSudah);
        $sheet->setCellValueByColumnAndRow(4, $totalRow, $totalBelum);
        $rowNo++;

        // Satu baris kosong, lalu baris catatan (meniru file contoh)
        $noteRow = $rowNo + 1;
        $sheet->setCellValueByColumnAndRow(1, $noteRow,
            "Nilai {$exam['exam_name']} (" . date('d M Y', strtotime($exam['end_date'])) . ").");
        styleSheet($sheet, $headersRingkasan, $widthsRingkasan, $totalRow, false, [$totalRow]);

        // --- Belum Ujian ---
        $stmt = $pdo->prepare("
            SELECT s.nis, s.full_name, s.class, s.kelompok, s.room
            FROM students s
            WHERE s.class IS NOT NULL AND s.class <> ''
              AND s.class IN (SELECT class_name FROM exam_classes WHERE exam_id = ?)
              AND NOT EXISTS (
                  SELECT 1 FROM exam_sessions es
                  WHERE es.student_id = s.id AND es.exam_id = ? AND es.status = 'finished'
              )
            ORDER BY s.class ASC, s.full_name ASC
        ");
        // Fallback bila exam_classes kosong: semua kelas di students
        $stmt->execute([$exam_id, $exam_id]);
        $belumUjian = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($belumUjian)) {
            $stmt = $pdo->prepare("
                SELECT s.nis, s.full_name, s.class, s.kelompok, s.room
                FROM students s
                WHERE NOT EXISTS (
                    SELECT 1 FROM exam_sessions es
                    WHERE es.student_id = s.id AND es.exam_id = ? AND es.status = 'finished'
                )
                AND NOT EXISTS (SELECT 1 FROM exam_classes WHERE exam_id = ?)
                ORDER BY s.class ASC, s.full_name ASC
            ");
            $stmt->execute([$exam_id, $exam_id]);
            $belumUjian = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Belum Ujian');
        $rowNo = 2;
        foreach ($belumUjian as $i => $r) {
            $sheet->setCellValueByColumnAndRow(1, $rowNo, $i + 1);
            $sheet->setCellValueByColumnAndRow(2, $rowNo, nilaiAtauStrip($r['class']));
            $sheet->setCellValueByColumnAndRow(3, $rowNo, $r['full_name']);
            $sheet->setCellValueExplicitByColumnAndRow(4, $rowNo, (string)($r['nis'] ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueByColumnAndRow(5, $rowNo, nilaiAtauStrip($r['kelompok']));
            $sheet->setCellValueByColumnAndRow(6, $rowNo, nilaiAtauStrip($r['room']));
            $sheet->setCellValueByColumnAndRow(7, $rowNo, 'Belum ujian (tidak ada nilai)');
            $rowNo++;
        }
        styleSheet($sheet, $headersBelum, $widthsBelum, max(1, $rowNo - 1));

        // --- Gabungan ---
        $scores = queryNilai($pdo, $exam_id);
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Gabungan');
        $rowNo = 2;
        foreach ($scores as $i => $r) {
            isiBarisNilai($sheet, $rowNo, $i + 1, $r, true, $exam['exam_name']);
            $rowNo++;
        }
        styleSheet($sheet, $headersGabungan, $widthsGabungan, max(1, $rowNo - 1));

        // --- Satu sheet per kelas ---
        $usedNames = ['Ringkasan' => true, 'Belum Ujian' => true, 'Gabungan' => true];
        foreach ($classes as $class) {
            $sheetName = namaSheetKelas($class);
            $baseName = $sheetName;
            $suffix = 2;
            while (isset($usedNames[$sheetName])) {
                $sheetName = mb_substr($baseName, 0, 29) . '_' . $suffix;
                $suffix++;
            }
            $usedNames[$sheetName] = true;

            $rows = queryNilai($pdo, $exam_id, " AND s.class = :class ", ['class' => $class], 's.full_name ASC');
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);
            $rowNo = 2;
            foreach ($rows as $i => $r) {
                isiBarisNilai($sheet, $rowNo, $i + 1, $r, false, $exam['exam_name']);
                $rowNo++;
            }
            styleSheet($sheet, $headersPerKelas, $widthsPerKelas, max(1, $rowNo - 1));
        }

        $spreadsheet->setActiveSheetIndex(0);
    } elseif ($mode === 'kelas') {
        // Satu sheet saja, layout per-kelas
        if ($kelasTunggal === 'none') {
            $rows = queryNilai($pdo, $exam_id, " AND (s.class IS NULL OR s.class = '') ", [], 's.full_name ASC');
            $sheetName = 'Tanpa Kelas';
        } else {
            $rows = queryNilai($pdo, $exam_id, " AND s.class = :class ", ['class' => $kelasTunggal], 's.full_name ASC');
            $sheetName = namaSheetKelas($kelasTunggal);
        }
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);
        $rowNo = 2;
        foreach ($rows as $i => $r) {
            isiBarisNilai($sheet, $rowNo, $i + 1, $r, false, $exam['exam_name']);
            $rowNo++;
        }
        styleSheet($sheet, $headersPerKelas, $widthsPerKelas, max(1, $rowNo - 1));
    } else {
        // Satu sheet saja, layout Gabungan difilter kelompok
        if ($kelompokFilter === 'none') {
            $rows = queryNilai($pdo, $exam_id, " AND (s.kelompok IS NULL OR s.kelompok = '') ", [], 's.kelompok ASC, s.class ASC, s.full_name ASC');
        } elseif ($kelompokFilter === 'all') {
            $rows = queryNilai($pdo, $exam_id, '', [], 's.kelompok ASC, s.class ASC, s.full_name ASC');
        } else {
            $rows = queryNilai($pdo, $exam_id, " AND s.kelompok = :kelompok ", ['kelompok' => $kelompokFilter], 's.kelompok ASC, s.class ASC, s.full_name ASC');
        }
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gabungan');
        $rowNo = 2;
        foreach ($rows as $i => $r) {
            isiBarisNilai($sheet, $rowNo, $i + 1, $r, true, $exam['exam_name']);
            $rowNo++;
        }
        styleSheet($sheet, $headersGabungan, $widthsGabungan, max(1, $rowNo - 1));
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Kirim file .xlsx
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
