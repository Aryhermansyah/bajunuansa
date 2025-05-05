<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('fixed_rentals.php', 'ID pesanan tidak valid', 'error');
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Cek apakah pesanan ada
$rental = $db->fetchOne("SELECT * FROM rentals WHERE id = ?", [$rentalId]);
if (!$rental) {
    redirect('fixed_rentals.php', 'Pesanan tidak ditemukan', 'error');
}

error_log('[cancel_rental] Masuk proses cancel, rentalId=' . $rentalId);

// Kurangi stok_terpakai jika status bukan returned/canceled
if ($rental['status'] !== 'returned' && $rental['status'] !== 'canceled') {
    error_log('[cancel_rental] Panggil reduceStockRange, variant_id=' . $rental['variant_id'] . ', tanggal_sewa=' . $rental['tanggal_sewa'] . ', tanggal_kembali=' . $rental['tanggal_kembali'] . ', jumlah=' . $rental['jumlah']);
    reduceStockRange($rental['variant_id'], $rental['tanggal_sewa'], $rental['tanggal_kembali'], $rental['jumlah']);
    error_log('[cancel_rental] Selesai reduceStockRange');
}

try {
    $db->execute("UPDATE rentals SET status = 'canceled', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$rentalId]);
    redirect('fixed_rentals.php', 'Pesanan berhasil dibatalkan', 'success');
} catch (Exception $e) {
    redirect('fixed_rentals.php', 'Gagal membatalkan pesanan: ' . $e->getMessage(), 'error');
} 