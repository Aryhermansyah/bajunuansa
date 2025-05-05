<?php
/**
 * Script untuk menyinkronkan struktur database MySQL dengan kebutuhan aplikasi
 * Menambahkan kolom-kolom yang mungkin hilang pada database MySQL
 */

// Set ini ke true untuk mengaktifkan script (pengaman)
$enableSync = true;

// Jalur relatif ke direktori induk
$basePath = dirname(__DIR__);

// Load konfigurasi database
require_once $basePath . '/includes/config.php';

// Pastikan DB_PATH selalu didefinisikan untuk analisis SQLite
if (!defined('DB_PATH')) {
    define('DB_PATH', $basePath . '/database/rental_baju.sqlite');
}

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
    <title>Sinkronisasi Struktur Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        .step { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sinkronisasi Struktur Database MySQL</h1>';

// Cek apakah script diaktifkan
if (!$enableSync) {
    showMessage('Script sinkronisasi ini dinonaktifkan secara default untuk alasan keamanan. Untuk mengaktifkannya, buka file ini dan ubah $enableSync menjadi true.', true);
    exit;
}

// Definisi struktur kolom yang diharapkan untuk tabel utama
$expectedStructure = [
    'items' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'nama_baju' => 'VARCHAR(255) NOT NULL',
        'kategori' => 'VARCHAR(100) NOT NULL',
        'deskripsi' => 'TEXT',
        'foto' => 'VARCHAR(255)',
        'is_featured' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ],
    'rentals' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'customer_nama' => 'VARCHAR(255) NOT NULL',
        'customer_hp' => 'VARCHAR(50) NOT NULL',
        'customer_lokasi' => 'VARCHAR(255) NOT NULL',
        'variant_id' => 'INT NOT NULL',
        'tanggal_sewa' => 'DATE NOT NULL',
        'tanggal_kembali' => 'DATE NOT NULL',
        'tanggal_booking' => 'DATE DEFAULT NULL',
        'jumlah' => 'INT NOT NULL',
        'dp_bayar' => 'DECIMAL(10,2) NOT NULL',
        'pelunasan_bayar' => 'DECIMAL(10,2) NOT NULL',
        'status' => "ENUM('pending','approved','returned','canceled') NOT NULL DEFAULT 'pending'",
        'catatan' => 'TEXT',
        'jenis_jaminan' => 'VARCHAR(50) DEFAULT NULL',
        'info_pelunasan' => 'TEXT',
        'info_pengembalian' => 'TEXT',
        'tanggal_pelunasan' => 'DATE DEFAULT NULL',
        'tanggal_pengembalian' => 'DATE DEFAULT NULL',
        'denda' => 'DECIMAL(10,2) DEFAULT 0',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ]
];

try {
    // Koneksi ke MySQL
    if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $mysql = new PDO($dsn, DB_USER, DB_PASS);
        $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        showMessage('Berhasil terhubung ke database MySQL.');
        
        // Sinkronkan setiap tabel
        foreach ($expectedStructure as $table => $columns) {
            echo "<div class='step'>";
            echo "<h2>Sinkronisasi Tabel: $table</h2>";
            
            // Ambil kolom yang ada di tabel
            $existingColumns = [];
            $stmt = $mysql->query("SHOW COLUMNS FROM $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[$row['Field']] = $row['Type'];
                if ($row['Null'] === 'NO') {
                    $existingColumns[$row['Field']] .= ' NOT NULL';
                }
                if ($row['Default'] !== null) {
                    $existingColumns[$row['Field']] .= " DEFAULT '" . $row['Default'] . "'";
                }
            }
            
            echo "<table>";
            echo "<tr><th>Kolom</th><th>Status</th><th>Tindakan</th></tr>";
            
            // Periksa setiap kolom yang diharapkan
            foreach ($columns as $column => $definition) {
                if (!isset($existingColumns[$column])) {
                    // Kolom tidak ada, tambahkan
                    try {
                        $mysql->exec("ALTER TABLE $table ADD COLUMN $column $definition");
                        echo "<tr><td>$column</td><td class='error'>Tidak ada</td><td class='success'>Berhasil ditambahkan</td></tr>";
                    } catch (PDOException $e) {
                        echo "<tr><td>$column</td><td class='error'>Tidak ada</td><td class='error'>Gagal menambahkan: " . $e->getMessage() . "</td></tr>";
                    }
                } else {
                    echo "<tr><td>$column</td><td class='success'>Sudah ada</td><td>-</td></tr>";
                }
            }
            
            echo "</table>";
            echo "</div>";
        }
        
        showMessage("Sinkronisasi struktur database selesai!");
        
    } else {
        showMessage('Konfigurasi database bukan MySQL. Script ini hanya berfungsi untuk MySQL.', true);
    }
} catch (PDOException $e) {
    showMessage('Koneksi database gagal: ' . $e->getMessage(), true);
}

// Footer HTML
echo '
    </div>
</body>
</html>';
?>
