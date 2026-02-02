<?php
require_once '../../../includes/auth_check.php';

// Nama file yang akan diunduh
$filename = "template_import_siswa.csv";

// Header agar browser mendownload sebagai file CSV
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Baris pertama (Header kolom)
$header = ["Nama Lengkap"];

// Buka output stream
$output = fopen("php://output", "w");

// Tulis header ke file
fputcsv($output, $header);

// Contoh data (Opsional)
fputcsv($output, ["Apep Wahyudin"]);
fputcsv($output, ["Budi Santoso"]);

fclose($output);
exit;