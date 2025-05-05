<?php
/**
 * Setup Categories dan Sizes
 * 
 * Script ini memastikan tabel custom_sizes dan custom_categories sudah ada di database
 * dan membuat tabel tersebut jika belum ada, baik untuk MySQL maupun SQLite
 */

// Load semua yang diperlukan
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Mulai session
session_start();

$db = Database::getInstance();
$dbType = $db->getDbType();

// Tampilkan informasi
echo '<h1>Setup Kategori dan Ukuran</h1>';
echo '<p>Jenis Database: <strong>' . strtoupper($dbType) . '</strong></p>';

// 1. Cek dan buat tabel custom_sizes
echo '<h2>1. Tabel Custom Sizes</h2>';

$tableExists = false;
if ($dbType === 'mysql') {
    $result = $db->fetchOne("SHOW TABLES LIKE 'custom_sizes'");
    $tableExists = !empty($result);
} else {
    $result = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'");
    $tableExists = !empty($result);
}

if (!$tableExists) {
    echo '<p>Tabel custom_sizes belum ada. Membuat tabel...</p>';
    
    try {
        if ($dbType === 'mysql') {
            // Create table for MySQL
            $db->execute("
                CREATE TABLE custom_sizes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    size_name VARCHAR(50) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // Create table for SQLite
            $db->execute("
                CREATE TABLE custom_sizes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    size_name TEXT NOT NULL UNIQUE,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        }
        echo '<p style="color: green">Tabel custom_sizes berhasil dibuat!</p>';
    } catch (Exception $e) {
        echo '<p style="color: red">Error saat membuat tabel custom_sizes: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p style="color: green">Tabel custom_sizes sudah ada.</p>';
    
    // Tampilkan daftar ukuran yang sudah ada
    try {
        $sizes = $db->fetchAll("SELECT id, size_name FROM custom_sizes ORDER BY size_name");
        if (!empty($sizes)) {
            echo '<p>Daftar ukuran yang tersedia:</p>';
            echo '<ul>';
            foreach ($sizes as $size) {
                echo '<li>' . htmlspecialchars($size['size_name']) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Belum ada ukuran yang ditambahkan.</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red">Error saat mengambil data ukuran: ' . $e->getMessage() . '</p>';
    }
}

// 2. Cek dan buat tabel custom_categories
echo '<h2>2. Tabel Custom Categories</h2>';

$tableExists = false;
if ($dbType === 'mysql') {
    $result = $db->fetchOne("SHOW TABLES LIKE 'custom_categories'");
    $tableExists = !empty($result);
} else {
    $result = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_categories'");
    $tableExists = !empty($result);
}

if (!$tableExists) {
    echo '<p>Tabel custom_categories belum ada. Membuat tabel...</p>';
    
    try {
        if ($dbType === 'mysql') {
            // Create table for MySQL
            $db->execute("
                CREATE TABLE custom_categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    category_name VARCHAR(100) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // Create table for SQLite
            $db->execute("
                CREATE TABLE custom_categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    category_name TEXT NOT NULL UNIQUE,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        }
        echo '<p style="color: green">Tabel custom_categories berhasil dibuat!</p>';
    } catch (Exception $e) {
        echo '<p style="color: red">Error saat membuat tabel custom_categories: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p style="color: green">Tabel custom_categories sudah ada.</p>';
    
    // Tampilkan daftar kategori yang sudah ada
    try {
        $categories = $db->fetchAll("SELECT id, category_name FROM custom_categories ORDER BY category_name");
        if (!empty($categories)) {
            echo '<p>Daftar kategori yang tersedia:</p>';
            echo '<ul>';
            foreach ($categories as $category) {
                echo '<li>' . htmlspecialchars($category['category_name']) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Belum ada kategori yang ditambahkan.</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red">Error saat mengambil data kategori: ' . $e->getMessage() . '</p>';
    }
}

// 3. Link ke halaman admin
echo '<h2>3. Link ke Halaman Admin</h2>';
echo '<p>Anda dapat mengakses halaman admin melalui link berikut:</p>';
echo '<ul>';
echo '<li><a href="../admin/manage_sizes.php">Kelola Ukuran</a></li>';
echo '<li><a href="../admin/manage_categories.php">Kelola Kategori</a></li>';
echo '<li><a href="../admin/view_products.php">Kelola Produk</a></li>';
echo '</ul>';

// 4. Perbaiki file manage_sizes.php dan manage_categories.php
echo '<h2>4. Check Compatibility</h2>';
echo '<p>Script ini telah memeriksa dan memastikan tabel untuk ukuran dan kategori tersedia.</p>';
echo '<p>Jika Anda mengalami masalah dengan halaman manage_sizes.php atau manage_categories.php, pastikan:</p>';
echo '<ol>';
echo '<li>File-file tersebut telah diperbarui untuk mendukung MySQL</li>';
echo '<li>File-file tersebut tidak menggunakan query SQLite seperti "SELECT FROM sqlite_master"</li>';
echo '</ol>';
?>
