<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Mendapatkan instance database
$db = Database::getInstance();

try {
    // Menambahkan kolom catatan ke tabel rentals
    $db->execute("ALTER TABLE rentals ADD COLUMN catatan TEXT");
    echo "Kolom 'catatan' berhasil ditambahkan ke tabel rentals.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 