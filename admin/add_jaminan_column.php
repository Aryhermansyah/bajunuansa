<?php
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/config.php';
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/db.php';

// Mendapatkan instance database
$db = Database::getInstance();

try {
    // Menambahkan kolom jaminan ke tabel rentals
    $db->execute("ALTER TABLE rentals ADD COLUMN jenis_jaminan VARCHAR(50) DEFAULT NULL");
    $db->execute("ALTER TABLE rentals ADD COLUMN nomor_jaminan VARCHAR(100) DEFAULT NULL");
    
    echo "Kolom jenis_jaminan dan nomor_jaminan berhasil ditambahkan ke tabel rentals.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 