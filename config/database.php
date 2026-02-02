<?php
/**
 * File: config/database.php
 * Deskripsi: Koneksi database menggunakan PDO dengan Best Practice Keamanan
 * Author: Apep Wahyudin
 */

// Konfigurasi Database
date_default_timezone_set('Asia/Jakarta');
$host    = 'localhost';
$db_name = 'tka_simulation';
$username = 'root';
$password = ''; // Kosongkan jika menggunakan XAMPP default
$charset  = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// Opsi Konfigurasi PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Melempar error sebagai Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil fetch berupa array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Menggunakan prepared statement asli (Keamanan!)
];

try {
    // Inisialisasi Koneksi
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Jika gagal, tampilkan pesan tanpa membocorkan detail sensitif
    // Di tahap produksi, sebaiknya simpan error ke log file
    die("Koneksi database gagal. Silakan hubungi administrator.");
}