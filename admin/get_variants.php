<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Memastikan request memiliki item_id
if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$itemId = (int)$_GET['item_id'];
$db = Database::getInstance();

// Mengambil varian berdasarkan item_id
$variants = $db->fetchAll(
    "SELECT id, ukuran, stok_total, harga, dp, pelunasan, kode_unik 
     FROM item_variants 
     WHERE item_id = ? 
     ORDER BY ukuran ASC",
    [$itemId]
);

// Mengembalikan hasil dalam format JSON
header('Content-Type: application/json');
echo json_encode($variants); 