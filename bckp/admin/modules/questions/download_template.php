<?php
require_once '../../../includes/auth_check.php';

// Create a simple Word template using PHPWord
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

$phpWord = new PhpWord();

// Title style
$phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16, 'color' => '1a365d']);
$phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14, 'color' => '2d3748']);
$phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12, 'color' => '4a5568']);

$section = $phpWord->addSection();

// Header
$section->addTitle('TEMPLATE IMPORT SOAL', 1);
$section->addText('Gunakan format di bawah ini untuk mengimport soal dari Word', ['italic' => true, 'color' => '666666']);
$section->addTextBreak(2);

// ==================== PILIHAN GANDA ====================
$section->addTitle('1. PILIHAN GANDA (PG)', 2);
$section->addText('Gunakan tag [SOAL] untuk memulai soal pilihan ganda.', ['size' => 10, 'color' => '4a5568']);
$section->addTextBreak();

// Example PG 1
$section->addText('[SOAL]', ['bold' => true, 'color' => '2563eb']);
$section->addText('Berapakah hasil dari 15 + 27?');
$section->addText('[A] 40');
$section->addText('[B] 41');
$section->addText('[C] 42', ['bold' => true]); // Correct answer
$section->addText('[D] 43');
$section->addText('[E] 44');
$section->addText('[JAWABAN] C', ['bold' => true, 'color' => '16a34a']);
$section->addTextBreak(2);

// Example PG 2
$section->addText('[SOAL]', ['bold' => true, 'color' => '2563eb']);
$section->addText('Ibu kota Indonesia adalah...');
$section->addText('[A] Surabaya');
$section->addText('[B] Bandung');
$section->addText('[C] Semarang');
$section->addText('[D] Jakarta', ['bold' => true]);
$section->addText('[E] Yogyakarta');
$section->addText('[JAWABAN] D', ['bold' => true, 'color' => '16a34a']);
$section->addTextBreak(2);

// ==================== BENAR SALAH ====================
$section->addTitle('2. BENAR / SALAH (BS)', 2);
$section->addText('Gunakan tag [SOAL-BS] untuk soal benar/salah dengan beberapa pernyataan.', ['size' => 10, 'color' => '4a5568']);
$section->addTextBreak();

// Example BS
$section->addText('[SOAL-BS]', ['bold' => true, 'color' => '9333ea']);
$section->addText('Tentukan benar atau salah untuk pernyataan berikut tentang Tata Surya:');
$section->addText('[1] Matahari adalah bintang terdekat dengan Bumi [BENAR]');
$section->addText('[2] Bulan adalah planet terbesar di Tata Surya [SALAH]');
$section->addText('[3] Jupiter memiliki lebih dari 70 satelit alami [BENAR]');
$section->addText('[4] Mars disebut planet merah [BENAR]');
$section->addTextBreak(2);

// ==================== MULTI CHECKBOX ====================
$section->addTitle('3. MULTI CHECKBOX', 2);
$section->addText('Gunakan tag [SOAL-MULTI] untuk soal dengan beberapa jawaban benar.', ['size' => 10, 'color' => '4a5568']);
$section->addText('[V] = jawaban benar, [] = jawaban salah', ['size' => 10, 'italic' => true]);
$section->addTextBreak();

// Example Multi
$section->addText('[SOAL-MULTI]', ['bold' => true, 'color' => 'dc2626']);
$section->addText('Pilih semua provinsi yang berada di Pulau Jawa:');
$section->addText('[1] DKI Jakarta [V]');
$section->addText('[2] Jawa Barat [V]');
$section->addText('[3] Sumatera Utara []');
$section->addText('[4] Jawa Tengah [V]');
$section->addText('[5] Kalimantan Timur []');
$section->addTextBreak(2);

// ==================== JAWABAN SINGKAT ====================
$section->addTitle('4. JAWABAN SINGKAT (ISIAN)', 2);
$section->addText('Gunakan tag [SOAL-ISIAN] untuk soal yang memerlukan jawaban teks singkat.', ['size' => 10, 'color' => '4a5568']);
$section->addTextBreak();

// Example Isian
$section->addText('[SOAL-ISIAN]', ['bold' => true, 'color' => 'd97706']);
$section->addText('Siapa nama proklamator kemerdekaan Indonesia?');
$section->addText('[JAWABAN] Soekarno dan Hatta', ['bold' => true, 'color' => '16a34a']);
$section->addTextBreak(2);

// ==================== NOTES ====================
$section->addTitle('CATATAN PENTING:', 3);
$section->addListItem('Setiap soal HARUS diawali dengan tag [SOAL], [SOAL-BS], [SOAL-MULTI], atau [SOAL-ISIAN]', 0, ['size' => 10]);
$section->addListItem('Untuk soal PG, kunci jawaban WAJIB dicantumkan dengan [JAWABAN] A/B/C/D/E', 0, ['size' => 10]);
$section->addListItem('Gambar bisa disisipkan di dalam soal (harus inline, bukan floating)', 0, ['size' => 10]);
$section->addListItem('Jangan sisipkan baris kosong di antara opsi jawaban', 0, ['size' => 10]);

// Generate and download
$filename = 'template_import_soal.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
