<?php
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/config.php';
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/db.php';

// Mendapatkan instance database
$db = Database::getInstance();

try {
    // Menambahkan kolom tanggal_booking ke tabel rentals
    $db->execute("ALTER TABLE rentals ADD COLUMN tanggal_booking DATE DEFAULT NULL");
    
    echo "Kolom tanggal_booking berhasil ditambahkan ke tabel rentals.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 