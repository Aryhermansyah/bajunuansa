<?php
$base_dir = __DIR__ . '/../';
require_once $base_dir . 'includes/config.php';
require_once $base_dir . 'includes/db.php';

$db = Database::getInstance();

// Periksa apakah tabel custom_sizes sudah ada
$dbType = $db->getDbType();
$tableExists = false;

if ($dbType === 'mysql') {
    // Kueri untuk MySQL
    $result = $db->fetchOne("SHOW TABLES LIKE 'custom_sizes'");
    $tableExists = !empty($result);
} else {
    // Kueri untuk SQLite
    $result = $db->fetchOne(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'"
    );
    $tableExists = !empty($result);
}

// Buat tabel jika belum ada
if (!$tableExists) {
    try {
        $db->execute("
            CREATE TABLE custom_sizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                size_name TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "Tabel custom_sizes berhasil dibuat!";
    } catch (Exception $e) {
        echo "Error saat membuat tabel: " . $e->getMessage();
    }
} else {
    echo "Tabel custom_sizes sudah ada.";
}

// Tambahkan kolom is_featured ke tabel items jika belum ada
try {
    // SQLite tidak memiliki "ALTER TABLE IF COLUMN NOT EXISTS", jadi kita perlu cek secara terprogram
    $columnInfos = $db->fetchAll(
        "PRAGMA table_info(items)" // Mendapatkan informasi kolom tabel
    );
    
    $hasIsFeatured = false;
    foreach ($columnInfos as $column) {
        if ($column['name'] === 'is_featured') {
            $hasIsFeatured = true;
            break;
        }
    }
    
    if (!$hasIsFeatured) {
        $db->execute("ALTER TABLE items ADD COLUMN is_featured INTEGER DEFAULT 0");
        echo "\nKolom is_featured berhasil ditambahkan ke tabel items!";
    } else {
        echo "\nKolom is_featured sudah ada pada tabel items.";
    }
} catch (Exception $e) {
    echo "\nError saat menambah kolom is_featured: " . $e->getMessage();
}

// Tampilkan semua tabel yang ada untuk debugging
echo "\n\nDaftar tabel dalam database:\n";

if ($dbType === 'mysql') {
    // Kueri untuk MySQL
    $tables = $db->fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
        echo "- " . current($table) . "\n";
    }
} else {
    // Kueri untuk SQLite
    $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
    foreach ($tables as $table) {
        echo "- " . $table['name'] . "\n";
    }
}
?> 