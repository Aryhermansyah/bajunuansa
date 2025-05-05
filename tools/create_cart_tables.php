<?php
/**
 * Create Cart Tables
 * Script untuk membuat tabel-tabel yang diperlukan untuk fitur keranjang
 */

// Load semua yang diperlukan
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Mulai session
session_start();

$db = Database::getInstance();
$dbType = $db->getDbType();

echo '<h1>Pembuatan Tabel untuk Fitur Keranjang</h1>';
echo '<p>Jenis Database: <strong>' . strtoupper($dbType) . '</strong></p>';

try {
    // Cek apakah tabel cart sudah ada
    $tableExists = false;
    
    if ($dbType === 'mysql') {
        $result = $db->fetchOne("SHOW TABLES LIKE 'cart'");
        $tableExists = !empty($result);
    } else {
        $result = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='cart'");
        $tableExists = !empty($result);
    }
    
    // Buat tabel cart jika belum ada
    if (!$tableExists) {
        if ($dbType === 'mysql') {
            $db->execute("
                CREATE TABLE cart (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(255) NOT NULL,
                    variant_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    tanggal_sewa DATE NOT NULL,
                    tanggal_kembali DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $db->execute("
                CREATE TABLE cart (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id TEXT NOT NULL,
                    variant_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL DEFAULT 1,
                    tanggal_sewa DATE NOT NULL,
                    tanggal_kembali DATE NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE
                );
            ");
        }
        echo '<p style="color: green;">Tabel cart berhasil dibuat!</p>';
    } else {
        echo '<p>Tabel cart sudah ada.</p>';
    }
    
    // Cek apakah tabel rental_items sudah ada
    $tableExists = false;
    
    if ($dbType === 'mysql') {
        $result = $db->fetchOne("SHOW TABLES LIKE 'rental_items'");
        $tableExists = !empty($result);
    } else {
        $result = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='rental_items'");
        $tableExists = !empty($result);
    }
    
    // Buat tabel rental_items jika belum ada
    if (!$tableExists) {
        if ($dbType === 'mysql') {
            $db->execute("
                CREATE TABLE rental_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    rental_id INT NOT NULL,
                    variant_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    price DECIMAL(10,2) NOT NULL,
                    dp DECIMAL(10,2) NOT NULL,
                    pelunasan DECIMAL(10,2) NOT NULL,
                    tanggal_sewa DATE NOT NULL,
                    tanggal_kembali DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
                    FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            $db->execute("
                CREATE TABLE rental_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    rental_id INTEGER NOT NULL,
                    variant_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL DEFAULT 1,
                    price DECIMAL(10,2) NOT NULL,
                    dp DECIMAL(10,2) NOT NULL,
                    pelunasan DECIMAL(10,2) NOT NULL,
                    tanggal_sewa DATE NOT NULL,
                    tanggal_kembali DATE NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
                    FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE
                );
            ");
        }
        echo '<p style="color: green;">Tabel rental_items berhasil dibuat!</p>';
    } else {
        echo '<p>Tabel rental_items sudah ada.</p>';
    }
    
    // Modifikasi tabel rentals untuk menangani multiple items
    if ($dbType === 'mysql') {
        // Cek apakah kolom total_items sudah ada di tabel rentals
        $result = $db->fetchAll("SHOW COLUMNS FROM rentals LIKE 'total_items'");
        $columnExists = !empty($result);
        
        if (!$columnExists) {
            $db->execute("ALTER TABLE rentals ADD COLUMN total_items INT NOT NULL DEFAULT 1");
            echo '<p style="color: green;">Kolom total_items berhasil ditambahkan ke tabel rentals!</p>';
        }
    } else {
        // Untuk SQLite, cek kolom total_items
        $result = $db->fetchAll("PRAGMA table_info(rentals)");
        $columnExists = false;
        foreach ($result as $column) {
            if ($column['name'] === 'total_items') {
                $columnExists = true;
                break;
            }
        }
        
        if (!$columnExists) {
            $db->execute("ALTER TABLE rentals ADD COLUMN total_items INTEGER NOT NULL DEFAULT 1");
            echo '<p style="color: green;">Kolom total_items berhasil ditambahkan ke tabel rentals!</p>';
        }
    }
    
    echo '<p style="margin-top: 20px; font-weight: bold; color: green;">Setup database untuk fitur keranjang selesai!</p>';
    echo '<p><a href="../frontend/index.php">Kembali ke Halaman Utama</a></p>';
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}
?>
