<?php
/**
 * Script untuk membuat database MySQL lokal dan migrasi data dari SQLite
 * 
 * LANGKAH-LANGKAH:
 * 1. Pastikan XAMPP sudah berjalan (Apache dan MySQL)
 * 2. Buka http://localhost/ewa%20baju/php-simple-redirection-app/tools/create_mysql_db.php
 * 3. Ikuti petunjuk yang muncul
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
    <title>Setup Database MySQL Lokal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        .step { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setup Database MySQL Lokal</h1>';

// Cek apakah konfigurasi sudah disetel ke MySQL
if (!defined('DB_TYPE') || DB_TYPE !== 'mysql') {
    showMessage('Konfigurasi database bukan MySQL. Script ini hanya berfungsi untuk MySQL.', true);
    exit;
}

// Langkah 1: Koneksi ke server MySQL
echo '<div class="step">';
echo '<h2>Langkah 1: Koneksi ke Server MySQL</h2>';

try {
    // Koneksi ke MySQL tanpa database (untuk membuat database)
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    showMessage('Berhasil terhubung ke server MySQL.');
    
    // Langkah 2: Buat Database
    echo '</div><div class="step">';
    echo '<h2>Langkah 2: Membuat Database</h2>';
    
    try {
        // Buat database jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        showMessage("Database '" . DB_NAME . "' berhasil dibuat atau sudah ada sebelumnya.");
        
        // Pilih database
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // Langkah 3: Membuat Tabel
        echo '</div><div class="step">';
        echo '<h2>Langkah 3: Membuat Tabel</h2>';
        
        // Definisi tabel
        $tables = [
            'items' => "CREATE TABLE IF NOT EXISTS `items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `nama_baju` VARCHAR(255) NOT NULL,
                `kategori` VARCHAR(100) NOT NULL,
                `deskripsi` TEXT,
                `foto` VARCHAR(255),
                `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            'item_variants' => "CREATE TABLE IF NOT EXISTS `item_variants` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `item_id` INT(11) NOT NULL,
                `ukuran` ENUM('S', 'M', 'L', 'XL') NOT NULL,
                `stok_total` INT(11) NOT NULL DEFAULT 0,
                `kode_unik` VARCHAR(50) NOT NULL,
                `barcode` VARCHAR(100) NOT NULL,
                `harga` DECIMAL(10,2) NOT NULL,
                `dp` DECIMAL(10,2) NOT NULL,
                `pelunasan` DECIMAL(10,2) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY (`kode_unik`),
                CONSTRAINT `fk_item_variants_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            'item_availability' => "CREATE TABLE IF NOT EXISTS `item_availability` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `variant_id` INT(11) NOT NULL,
                `tanggal` DATE NOT NULL,
                `stok_terpakai` INT(11) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY (`variant_id`, `tanggal`),
                CONSTRAINT `fk_availability_variant` FOREIGN KEY (`variant_id`) REFERENCES `item_variants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            'rentals' => "CREATE TABLE IF NOT EXISTS `rentals` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `customer_nama` VARCHAR(255) NOT NULL,
                `customer_hp` VARCHAR(50) NOT NULL,
                `customer_lokasi` VARCHAR(255) NOT NULL,
                `variant_id` INT(11) NOT NULL,
                `tanggal_sewa` DATE NOT NULL,
                `tanggal_kembali` DATE NOT NULL,
                `tanggal_booking` DATE DEFAULT NULL,
                `jumlah` INT(11) NOT NULL,
                `dp_bayar` DECIMAL(10,2) NOT NULL,
                `pelunasan_bayar` DECIMAL(10,2) NOT NULL,
                `status` ENUM('pending', 'approved', 'returned', 'canceled') NOT NULL DEFAULT 'pending',
                `catatan` TEXT,
                `jenis_jaminan` VARCHAR(50) DEFAULT NULL,
                `info_pelunasan` TEXT,
                `info_pengembalian` TEXT,
                `tanggal_pelunasan` DATE DEFAULT NULL,
                `tanggal_pengembalian` DATE DEFAULT NULL,
                `denda` DECIMAL(10,2) DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_rentals_variant` FOREIGN KEY (`variant_id`) REFERENCES `item_variants` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
        
        // Buat tabel
        foreach ($tables as $table => $query) {
            try {
                $pdo->exec($query);
                showMessage("Tabel '$table' berhasil dibuat.");
            } catch (PDOException $e) {
                showMessage("Error saat membuat tabel '$table': " . $e->getMessage(), true);
            }
        }
        
        // Buat indeks
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_rental_status` ON `rentals`(`status`);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_rental_dates` ON `rentals`(`tanggal_sewa`, `tanggal_kembali`);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_item_variants_item` ON `item_variants`(`item_id`);");
            showMessage("Indeks untuk optimasi query berhasil dibuat.");
        } catch (PDOException $e) {
            showMessage("Error saat membuat indeks: " . $e->getMessage(), true);
        }
        
        // Langkah 4: Migrasi Data dari SQLite (opsional)
        echo '</div><div class="step">';
        echo '<h2>Langkah 4: Migrasi Data dari SQLite</h2>';
        
        if (defined('DB_SQLITE_PATH') && file_exists(DB_SQLITE_PATH)) {
            echo '<p>Database SQLite ditemukan di: <code>' . DB_SQLITE_PATH . '</code></p>';
            echo '<p>Untuk migrasi data dari SQLite ke MySQL, silakan gunakan script <a href="migrate_to_mysql.php">migrate_to_mysql.php</a> setelah mengubah konfigurasi di file tersebut.</p>';
        } else {
            showMessage('Database SQLite tidak ditemukan. Migrasi data tidak diperlukan atau gunakan file backup lain.', true);
        }
        
    } catch (PDOException $e) {
        showMessage("Error saat membuat database: " . $e->getMessage(), true);
    }
    
} catch (PDOException $e) {
    showMessage("Koneksi ke server MySQL gagal: " . $e->getMessage(), true);
    
    echo '<p>Pastikan:</p>';
    echo '<ol>';
    echo '<li>MySQL server sudah berjalan (XAMPP Control Panel → MySQL → Start)</li>';
    echo '<li>Kredensial database di <code>includes/config.php</code> sudah benar</li>';
    echo '</ol>';
}

// Langkah 5: Petunjuk Selanjutnya
echo '</div><div class="step">';
echo '<h2>Langkah 5: Petunjuk Selanjutnya</h2>';

echo '<p>Setelah database berhasil dibuat:</p>';
echo '<ol>';
echo '<li>Pastikan file <code>includes/config.php</code> sudah dikonfigurasi dengan benar untuk MySQL lokal</li>';
echo '<li>Pastikan file <code>includes/db.php</code> sudah diupdate untuk mendukung MySQL</li>';
echo '<li>Akses aplikasi Anda di <a href="../frontend/index.php">frontend/index.php</a></li>';
echo '</ol>';

echo '</div>';

// Footer HTML
echo '
    </div>
</body>
</html>';
?>
