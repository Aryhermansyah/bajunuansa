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

try {
    $db->execute("DELETE FROM rentals WHERE id = ?", [$rentalId]);
    redirect('fixed_rentals.php', 'Pesanan berhasil dihapus', 'success');
} catch (Exception $e) {
    redirect('fixed_rentals.php', 'Gagal menghapus pesanan: ' . $e->getMessage(), 'error');
} 