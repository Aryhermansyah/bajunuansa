<?php
/**
 * Script untuk migrasi data dari SQLite ke MySQL
 * Gunakan script ini setelah:
 * 1. Membuat database MySQL di Hostinger
 * 2. Mengupdate kredensial database di includes/config.php
 * 
 * Cara penggunaan:
 * 1. Upload folder aplikasi ke Hostinger
 * 2. Update kredensial MySQL di includes/config.php
 * 3. Akses script ini melalui web browser: http://yourdomain.com/tools/migrate_to_mysql.php
 */

// Set ini ke true untuk mengaktifkan script (pengaman)
$enableMigration = false;

// Jalur relatif ke direktori induk
$basePath = dirname(__DIR__);

// Load konfigurasi database
require_once $basePath . '/includes/config.php';

// Pastikan kita punya path ke SQLite
if (!defined('DB_SQLITE_PATH')) {
    define('DB_SQLITE_PATH', $basePath . '/database/rental_baju.sqlite');
}

// Fungsi untuk menampilkan pesan
function showMessage($message, $isError = false) {
    echo '<div style="padding: 10px; margin: 10px 0; border-radius: 5px; background-color: ' . 
        ($isError ? '#ffeeee' : '#eeffee') . '; color: ' . ($isError ? '#990000' : '#009900') . ';">' . 
        $message . '</div>';
}

// Cek apakah script diaktifkan
if (!$enableMigration) {
    showMessage('Script migrasi ini dinonaktifkan secara default untuk alasan keamanan. Untuk mengaktifkannya, buka file ini dan ubah $enableMigration menjadi true.', true);
    exit;
}

// Cek apakah konfigurasi database MySQL tersedia
if (!defined('DB_TYPE') || DB_TYPE !== 'mysql' || !defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    showMessage('Konfigurasi database MySQL tidak lengkap. Pastikan Anda telah mengatur DB_TYPE, DB_HOST, DB_NAME, DB_USER, dan DB_PASS di file config.php.', true);
    exit;
}

// Cek apakah SQLite path tersedia
if (!defined('DB_SQLITE_PATH') || !file_exists(DB_SQLITE_PATH)) {
    showMessage('File database SQLite tidak ditemukan: ' . (defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : 'DB_SQLITE_PATH tidak didefinisikan'), true);
    exit;
}

// Header HTML
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Data dari SQLite ke MySQL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        h1 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .step { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .step h2 { margin-top: 0; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migrasi Data dari SQLite ke MySQL</h1>';

// Koneksi ke SQLite (sumber)
try {
    $sqlite = new PDO('sqlite:' . DB_SQLITE_PATH);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    showMessage('Berhasil terhubung ke database SQLite.');
} catch (PDOException $e) {
    showMessage('Koneksi ke database SQLite gagal: ' . $e->getMessage(), true);
    exit;
}

// Koneksi ke MySQL (tujuan)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $mysql = new PDO($dsn, DB_USER, DB_PASS);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    showMessage('Berhasil terhubung ke database MySQL.');
} catch (PDOException $e) {
    showMessage('Koneksi ke database MySQL gagal: ' . $e->getMessage(), true);
    exit;
}

// Struktur tabel dan urutan migrasi
$tables = [
    'items' => [
        'columns' => ['id', 'nama_baju', 'kategori', 'deskripsi', 'foto', 'created_at', 'updated_at'],
        'primaryKey' => 'id'
    ],
    'item_variants' => [
        'columns' => ['id', 'item_id', 'ukuran', 'stok_total', 'kode_unik', 'barcode', 'harga', 'dp', 'pelunasan', 'created_at', 'updated_at'],
        'primaryKey' => 'id'
    ],
    'item_availability' => [
        'columns' => ['id', 'variant_id', 'tanggal', 'stok_terpakai', 'created_at', 'updated_at'],
        'primaryKey' => 'id'
    ],
    'rentals' => [
        'columns' => ['id', 'customer_nama', 'customer_hp', 'customer_lokasi', 'variant_id', 'tanggal_sewa', 'tanggal_kembali', 'jumlah', 'dp_bayar', 'pelunasan_bayar', 'status', 'catatan', 'created_at', 'updated_at'],
        'primaryKey' => 'id'
    ]
];

// Nonaktifkan sementara pengecekan foreign key
$mysql->exec("SET FOREIGN_KEY_CHECKS=0;");

// Mulai transaksi MySQL
$mysql->beginTransaction();

try {
    foreach ($tables as $table => $config) {
        echo "<div class='step'>";
        echo "<h2>Migrasi Tabel: $table</h2>";
        
        // Ambil data dari SQLite
        $columns = implode(', ', $config['columns']);
        $sqliteQuery = "SELECT $columns FROM $table ORDER BY " . $config['primaryKey'];
        $sqliteStmt = $sqlite->query($sqliteQuery);
        $rows = $sqliteStmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($rows);
        
        if ($rowCount > 0) {
            // Truncate tabel MySQL sebelum insert
            $mysql->exec("TRUNCATE TABLE $table");
            
            // Persiapkan query insert MySQL
            $placeholders = implode(', ', array_fill(0, count($config['columns']), '?'));
            $mysqlQuery = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $mysqlStmt = $mysql->prepare($mysqlQuery);
            
            // Masukkan data ke MySQL
            $insertedCount = 0;
            foreach ($rows as $row) {
                $values = array_values($row);
                try {
                    $mysqlStmt->execute($values);
                    $insertedCount++;
                } catch (PDOException $e) {
                    showMessage("Error saat insert data ke tabel $table: " . $e->getMessage(), true);
                    throw $e; // Re-throw untuk memicu rollback
                }
            }
            
            showMessage("Berhasil memindahkan $insertedCount dari $rowCount baris data ke tabel $table.");
            
            // Reset auto-increment ke nilai terakhir
            if ($rowCount > 0) {
                $lastId = $rows[$rowCount - 1][$config['primaryKey']];
                $mysql->exec("ALTER TABLE $table AUTO_INCREMENT = " . ($lastId + 1));
                showMessage("Auto increment untuk tabel $table disetel ke " . ($lastId + 1));
            }
        } else {
            showMessage("Tidak ada data di tabel $table untuk dimigrasikan.");
        }
        
        echo "</div>";
    }
    
    // Commit transaksi jika semua berhasil
    $mysql->commit();
    
    // Aktifkan kembali pengecekan foreign key
    $mysql->exec("SET FOREIGN_KEY_CHECKS=1;");
    
    showMessage("Semua data berhasil dimigrasikan!", false);
    
} catch (Exception $e) {
    // Rollback jika terjadi error
    $mysql->rollBack();
    showMessage("Terjadi kesalahan selama migrasi, semua perubahan dibatalkan: " . $e->getMessage(), true);
}

// Footer HTML
echo '
    </div>
</body>
</html>';
?>
