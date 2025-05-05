<?php
/**
 * Script untuk menambahkan kolom tanggal_booking ke tabel rentals
 * Gunakan script ini untuk memperbaiki error: Unknown column 'tanggal_booking' in 'INSERT INTO'
 */

// Jalur relatif ke direktori induk
$basePath = dirname(__DIR__);

// Load konfigurasi database
require_once $basePath . '/includes/config.php';

// Fungsi untuk menampilkan pesan
function showMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; background-color: ' . 
        ($isError ? '#ffeeee' : '#eeffee') . '; color: ' . ($isError ? '#990000' : '#009900') . ';">' . 
        $message . '</div>';
}

// Header HTML
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kolom tanggal_booking ke Tabel rentals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        h1 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .step { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tambah Kolom tanggal_booking ke Tabel rentals</h1>';

// Koneksi ke MySQL
try {
    // Pastikan kita menggunakan MySQL
    if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $db = new PDO($dsn, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        showMessage('Berhasil terhubung ke database MySQL.');
        
        // Cek apakah kolom sudah ada
        $stmt = $db->query("SHOW COLUMNS FROM rentals LIKE 'tanggal_booking'");
        if ($stmt->rowCount() > 0) {
            showMessage('Kolom tanggal_booking sudah ada di tabel rentals.');
        } else {
            // Tambahkan kolom tanggal_booking
            $db->exec("ALTER TABLE rentals ADD COLUMN tanggal_booking DATE DEFAULT NULL AFTER tanggal_kembali");
            showMessage('Kolom tanggal_booking berhasil ditambahkan ke tabel rentals.');
        }
    } else {
        showMessage('Konfigurasi database bukan MySQL. Script ini hanya berfungsi untuk MySQL.', true);
    }
} catch (PDOException $e) {
    showMessage('Koneksi atau query gagal: ' . $e->getMessage(), true);
}

// Footer HTML
echo '
    </div>
</body>
</html>';
?>
