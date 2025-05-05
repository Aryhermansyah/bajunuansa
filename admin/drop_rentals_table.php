<?php
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/config.php';
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/db.php';

$db = Database::getInstance();

try {
    $db->execute("DROP TABLE IF EXISTS rentals");
    echo "Tabel rentals berhasil dihapus. Silakan jalankan ulang migrasi.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 