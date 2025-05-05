<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../frontend/index.php', 'Metode tidak diizinkan', 'danger');
    exit;
}

// Ambil data dari POST
$rentalId = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
$status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
$catatanAdmin = isset($_POST['catatan_admin']) ? sanitizeInput($_POST['catatan_admin']) : '';

// Validasi input
if ($rentalId <= 0 || empty($status)) {
    redirect('../frontend/index.php', 'Data tidak valid', 'danger');
    exit;
}

// Update status pesanan
$db = Database::getInstance();
try {
    // Cek apakah rental ID valid
    $rental = $db->fetchOne(
        "SELECT * FROM rentals WHERE id = ?",
        [$rentalId]
    );
    
    if (!$rental) {
        redirect('../frontend/index.php', 'ID pesanan tidak valid', 'danger');
        exit;
    }
    
    // Update status dan catatan admin
    $db->query(
        "UPDATE rentals SET status = ?, catatan_admin = ?, updated_at = NOW() WHERE id = ?",
        [$status, $catatanAdmin, $rentalId]
    );
    
    // Redirect ke halaman order success dengan pesan sukses
    redirect("../frontend/order_success.php?id={$rentalId}", 'Status pesanan berhasil diperbarui', 'success');
} catch (Exception $e) {
    // Log error
    file_put_contents('../logs/admin_errors.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    redirect('../frontend/index.php', 'Terjadi kesalahan: ' . $e->getMessage(), 'danger');
}
?>
