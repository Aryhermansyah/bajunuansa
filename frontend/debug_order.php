<?php
// Script diagnostik untuk order.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load semua yang dibutuhkan
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Aktifkan output buffering agar kita bisa melihat error meskipun ada header redirect
ob_start();

echo "<h1>Diagnostik Order.php</h1>";
echo "<pre>". date('Y-m-d H:i:s') . " - Script started</pre>";

// Dapatkan parameter
$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 2;
$tanggal_sewa = isset($_GET['tanggal_sewa']) ? $_GET['tanggal_sewa'] : date('Y-m-d');

echo "<h2>Parameter</h2>";
echo "<pre>";
echo "item_id: $itemId\n";
echo "tanggal_sewa: $tanggal_sewa\n";
echo "</pre>";

// Validasi parameter
echo "<h2>Validasi Parameter</h2>";
try {
    // Validasi tanggal
    if (!validateDate($tanggal_sewa)) {
        echo "<p style='color:red'>ERROR: Tanggal sewa tidak valid</p>";
    } else {
        echo "<p style='color:green'>Tanggal sewa valid</p>";
    }

    // Dapatkan koneksi database
    $db = Database::getInstance();
    echo "<p style='color:green'>Koneksi database berhasil</p>";

    // Ambil data item
    echo "<h2>Data Item</h2>";
    try {
        $item = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
        if (!$item) {
            echo "<p style='color:red'>ERROR: Item dengan ID $itemId tidak ditemukan</p>";
            
            // Tampilkan semua item yang tersedia
            echo "<h3>Items yang Tersedia:</h3>";
            $items = $db->fetchAll("SELECT id, nama_baju, kategori FROM items LIMIT 10");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nama Baju</th><th>Kategori</th></tr>";
            foreach ($items as $itm) {
                echo "<tr>";
                echo "<td>" . $itm['id'] . "</td>";
                echo "<td>" . htmlspecialchars($itm['nama_baju']) . "</td>";
                echo "<td>" . htmlspecialchars($itm['kategori']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:green'>Item ditemukan: " . htmlspecialchars($item['nama_baju']) . "</p>";
            echo "<pre>";
            print_r($item);
            echo "</pre>";
            
            // Ambil data varian
            echo "<h2>Data Varian</h2>";
            try {
                $variants = $db->fetchAll("SELECT * FROM item_variants WHERE item_id = ?", [$itemId]);
                if (empty($variants)) {
                    echo "<p style='color:red'>ERROR: Tidak ada varian untuk item ini</p>";
                } else {
                    echo "<p style='color:green'>Jumlah varian: " . count($variants) . "</p>";
                    foreach ($variants as $index => $variant) {
                        echo "<h3>Varian #" . ($index + 1) . " (ID: " . $variant['id'] . ")</h3>";
                        echo "<pre>";
                        print_r($variant);
                        echo "</pre>";
                        
                        // Cek ketersediaan stok
                        $available = getAvailableStock($variant['id'], $tanggal_sewa);
                        echo "<p>Stok tersedia pada $tanggal_sewa: $available</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>ERROR saat mengambil varian: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>ERROR saat mengambil item: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Selesai
echo "<h2>Debug Selesai</h2>";
echo "<p><a href='index.php'>Kembali ke Halaman Utama</a></p>";

// Tampilkan output buffering
$output = ob_get_clean();
echo $output;
?>
