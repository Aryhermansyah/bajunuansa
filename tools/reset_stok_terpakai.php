<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

// Ambil semua data item_availability
$rows = $db->fetchAll("SELECT id, variant_id, tanggal FROM item_availability");
$resetCount = 0;
foreach ($rows as $row) {
    $variantId = $row['variant_id'];
    $tanggal = $row['tanggal'];
    // Cek apakah ada pesanan aktif pada variant dan tanggal ini
    $rental = $db->fetchOne(
        "SELECT 1 FROM rentals WHERE variant_id = ? AND status IN ('pending','approved') AND tanggal_sewa <= ? AND tanggal_kembali >= ?",
        [$variantId, $tanggal, $tanggal]
    );
    if (!$rental) {
        // Tidak ada pesanan aktif, reset stok_terpakai ke 0
        $db->execute("UPDATE item_availability SET stok_terpakai = 0 WHERE id = ?", [$row['id']]);
        $resetCount++;
    }
}
echo "Reset selesai. $resetCount baris stok_terpakai telah di-set ke 0."; 