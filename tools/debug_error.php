<?php
// Script diagnostik untuk menampilkan error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Jalur relatif ke direktori induk
$basePath = dirname(__DIR__);

// Load konfigurasi yang diperlukan
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/db.php';
require_once $basePath . '/includes/functions.php';

// Mulai session
session_start();

// Header HTML
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostik Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnostik Error</h1>';

// Cek koneksi database
echo '<div class="section">';
echo '<h2>1. Koneksi Database</h2>';

try {
    // Coba dapatkan instance database
    $db = Database::getInstance();
    echo '<p class="success">Koneksi database berhasil!</p>';
    
    // Tampilkan info koneksi
    echo '<p>Tipe Database: <strong>' . DB_TYPE . '</strong></p>';
    if (DB_TYPE === 'mysql') {
        echo '<p>Host: ' . DB_HOST . '</p>';
        echo '<p>Database: ' . DB_NAME . '</p>';
    }
    
    // Cek tabel
    try {
        if (DB_TYPE === 'mysql') {
            $tables = $db->fetchAll("SHOW TABLES");
            echo '<p>Tabel dalam database:</p>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . current($table) . '</li>';
            }
            echo '</ul>';
        } else {
            if (DB_TYPE === 'mysql') {
                $tables = $db->fetchAll("SHOW TABLES");
                echo '<p>Tabel dalam database MySQL:</p>';
                echo '<ul>';
                foreach ($tables as $table) {
                    echo '<li>' . current($table) . '</li>';
                }
                echo '</ul>';
            } else {
                $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
                echo '<p>Tabel dalam database SQLite:</p>';
                echo '<ul>';
                foreach ($tables as $table) {
                    echo '<li>' . $table['name'] . '</li>';
                }
                echo '</ul>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="error">Error saat memeriksa tabel: ' . $e->getMessage() . '</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">Koneksi database gagal: ' . $e->getMessage() . '</p>';
}

// Cek item yang diminta
echo '</div><div class="section">';
echo '<h2>2. Cek Item yang Diminta</h2>';

if (isset($_GET['item_id'])) {
    $itemId = (int)$_GET['item_id'];
    echo '<p>Item ID yang diminta: ' . $itemId . '</p>';
    
    try {
        $item = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
        if ($item) {
            echo '<p class="success">Item ditemukan: ' . htmlspecialchars($item['nama_baju']) . '</p>';
            echo '<pre>' . print_r($item, true) . '</pre>';
            
            // Cek varian
            try {
                $variants = $db->fetchAll("SELECT * FROM item_variants WHERE item_id = ?", [$itemId]);
                if (count($variants) > 0) {
                    echo '<p class="success">Varian ditemukan: ' . count($variants) . ' varian</p>';
                    echo '<pre>' . print_r($variants, true) . '</pre>';
                } else {
                    echo '<p class="error">Tidak ada varian untuk item ini!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Error saat memeriksa varian: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="error">Item tidak ditemukan!</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">Error saat memeriksa item: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p>Tidak ada item_id yang diberikan.</p>';
}

// Cek tanggal sewa
echo '</div><div class="section">';
echo '<h2>3. Cek Tanggal Sewa</h2>';

if (isset($_GET['tanggal_sewa'])) {
    $tanggalSewa = $_GET['tanggal_sewa'];
    echo '<p>Tanggal sewa yang diminta: ' . $tanggalSewa . '</p>';
    
    if (validateDate($tanggalSewa)) {
        echo '<p class="success">Format tanggal valid!</p>';
    } else {
        echo '<p class="error">Format tanggal tidak valid!</p>';
    }
} else {
    echo '<p>Tidak ada tanggal_sewa yang diberikan.</p>';
}

// Check order.php
echo '</div><div class="section">';
echo '<h2>4. Analisis order.php</h2>';

$orderFile = $basePath . '/frontend/order.php';
if (file_exists($orderFile)) {
    echo '<p class="success">File order.php ditemukan!</p>';
    
    // Tampilkan 20 baris pertama untuk referensi
    $content = file_get_contents($orderFile);
    $lines = explode("\n", $content);
    $first20Lines = array_slice($lines, 0, 20);
    
    echo '<p>20 baris pertama dari order.php:</p>';
    echo '<pre>';
    foreach ($first20Lines as $i => $line) {
        echo ($i + 1) . ": " . htmlspecialchars($line) . "\n";
    }
    echo '</pre>';
    
    // Cek apakah ada koneksi database di file
    if (strpos($content, 'Database::getInstance()') !== false) {
        echo '<p class="success">Koneksi database ditemukan di order.php</p>';
    } else {
        echo '<p class="error">Koneksi database tidak ditemukan di order.php!</p>';
    }
    
} else {
    echo '<p class="error">File order.php tidak ditemukan!</p>';
}

// URL untuk menguji
echo '</div><div class="section">';
echo '<h2>5. Link untuk Menguji</h2>';

echo '<p>Coba akses URL berikut:</p>';
echo '<ul>';
echo '<li><a href="../frontend/index.php" target="_blank">Frontend Index</a></li>';
echo '<li><a href="../frontend/order.php?item_id=2&tanggal_sewa=2025-05-05" target="_blank">Order Item #2</a></li>';
echo '</ul>';

echo '</div>';

// Petunjuk lebih lanjut
echo '<div class="section">';
echo '<h2>6. Petunjuk Lebih Lanjut</h2>';

echo '<p>Jika masih mengalami masalah, coba:</p>';
echo '<ol>';
echo '<li>Periksa log error PHP di server (di Hostinger biasanya di error_log)</li>';
echo '<li>Tambahkan kode berikut di awal order.php:</li>';
echo '<pre>
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);
</pre>';
echo '<li>Pastikan semua kolom database yang dibutuhkan sudah ada dengan menjalankan <a href="sync_database_structure.php" target="_blank">sync_database_structure.php</a></li>';
echo '</ol>';

echo '</div>';

// Footer HTML
echo '
    </div>
</body>
</html>';
?>
