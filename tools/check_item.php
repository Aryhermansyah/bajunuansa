<?php
// Script untuk mengecek item langsung
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

$itemId = 2; // Hardcode item ID
$tanggal_sewa = '2025-05-05'; // Hardcode tanggal

$db = Database::getInstance();

// Cek item
echo "<h2>Checking Item ID: $itemId</h2>";
try {
    $item = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$itemId]);
    if ($item) {
        echo "<p style='color:green'>Item Found: " . htmlspecialchars($item['nama_baju']) . "</p>";
        echo "<pre>" . print_r($item, true) . "</pre>";
        
        // Cek varian
        $variants = $db->fetchAll("SELECT * FROM item_variants WHERE item_id = ?", [$itemId]);
        if (!empty($variants)) {
            echo "<p style='color:green'>Variants Found: " . count($variants) . "</p>";
            echo "<pre>" . print_r($variants, true) . "</pre>";
            
            // Cek ketersediaan
            foreach ($variants as $variant) {
                $variantId = $variant['id'];
                $stokTotal = $variant['stok_total'];
                $available = getAvailableStock($variantId, $tanggal_sewa);
                
                echo "<p>Variant #" . $variantId . " (Size: " . $variant['ukuran'] . "):<br>";
                echo "Total Stock: " . $stokTotal . "<br>";
                echo "Available on $tanggal_sewa: " . $available . "</p>";
            }
        } else {
            echo "<p style='color:red'>No variants found for this item!</p>";
        }
    } else {
        echo "<p style='color:red'>Item not found!</p>";
        
        // Tampilkan semua item yang tersedia
        echo "<h3>Available Items:</h3>";
        $allItems = $db->fetchAll("SELECT id, nama_baju, kategori FROM items LIMIT 10");
        if (!empty($allItems)) {
            echo "<ul>";
            foreach ($allItems as $item) {
                echo "<li>ID: " . $item['id'] . " - " . htmlspecialchars($item['nama_baju']) . 
                     " (" . htmlspecialchars($item['kategori']) . ")</li>";
            }
            echo "</ul>";
            
            echo "<p>Use one of these IDs in the URL instead.</p>";
        } else {
            echo "<p>No items found in database. Database might be empty.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Link kembali
echo "<p><a href='../frontend/index.php'>Back to Frontend</a></p>";
?>
