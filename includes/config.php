<?php
// Deteksi lingkungan (lokal atau produksi)
$isProduction = !isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] !== 'localhost';

// Database configuration - Semua menggunakan MySQL
define('DB_TYPE', 'mysql');
define('DB_CHARSET', 'utf8mb4');

if ($isProduction) {
    // Konfigurasi MySQL untuk Hostinger dengan kredensial yang sebenarnya
    define('DB_HOST', 'localhost'); // Server MySQL Hostinger
    define('DB_NAME', 'u240549819_baju'); // Database yang dibuat di Hostinger
    define('DB_USER', 'u240549819_baju'); // Username database di Hostinger
    define('DB_PASS', 'Ary291099.'); // Password database di Hostinger
} else {
    // Konfigurasi MySQL untuk pengembangan lokal dengan XAMPP
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'rental_baju_mysql'); // Buat database ini di phpMyAdmin lokal
    define('DB_USER', 'root'); // Default username XAMPP
    define('DB_PASS', ''); // Default password XAMPP kosong
    
    // Simpan path SQLite untuk keperluan migrasi atau backup
    define('DB_SQLITE_PATH', dirname(__DIR__) . '/database/rental_baju.sqlite');
}

// Application settings
define('APP_NAME', 'Pearls Bridal');

// URL konfigurasi berdasarkan lingkungan
if ($isProduction) {
    // Ganti dengan domain Hostinger Anda
    define('BASE_URL', ''); // Akan otomatis menggunakan path root
} else {
    define('BASE_URL', '/ewa baju/php-simple-redirection-app');
}

define('ITEMS_PER_PAGE', 12);

// Upload settings
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/images/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
?>
