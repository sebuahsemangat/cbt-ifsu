<?php
require_once '../../../includes/auth_check.php';

// Nama file yang akan diunduh
$filename = "template_import_siswa.csv";

// Header agar browser mendownload sebagai file CSV
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Output UTF-8 BOM agar Excel mengenali karakter khusus dan format kolom
echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

// Baris pertama (Header kolom) menggunakan pemisah titik koma (standard tabel di Excel Indonesia)
// Kami menulis manual fputcsv dengan delimiter ;
fputcsv($output, ["Nama Lengkap", "Kelas"], ";");

// Contoh data (Opsional)
fputcsv($output, ["Apep Wahyudin", "XII IPA 1"], ";");
fputcsv($output, ["Budi Santoso", "XII IPA 2"], ";");

fclose($output);
exit;