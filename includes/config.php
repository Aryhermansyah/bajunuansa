<?php
// Konfigurasi Database SQLite
define('DB_PATH', __DIR__ . '/../database/rental_baju.sqlite');

// Konfigurasi Path
define('BASE_URL', 'http://localhost:8000');
define('UPLOAD_PATH', __DIR__ . '/../assets/images/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Konfigurasi Aplikasi
define('APP_NAME', 'Sistem Penyewaan Baju');
define('ITEMS_PER_PAGE', 12);

// Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
