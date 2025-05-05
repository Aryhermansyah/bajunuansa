<?php
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/config.php';
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/db.php';

// Mendapatkan instance database
$db = Database::getInstance();

try {
    // Menambahkan kolom kondisi_pengembalian dan catatan_pengembalian ke tabel rentals
    $db->execute("ALTER TABLE rentals ADD COLUMN kondisi_pengembalian VARCHAR(20) DEFAULT NULL");
    $db->execute("ALTER TABLE rentals ADD COLUMN catatan_pengembalian TEXT DEFAULT NULL");
    
    echo "Kolom kondisi_pengembalian dan catatan_pengembalian berhasil ditambahkan ke tabel rentals.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 