<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

session_start();

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Ambil data dari POST
$variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$tanggalSewa = $_POST['tanggal_sewa'] ?? '';
$tanggalKembali = $_POST['tanggal_kembali'] ?? '';

// Simpan informasi pelanggan di session jika tersedia
if (isset($_POST['nama']) && isset($_POST['hp']) && isset($_POST['lokasi'])) {
    $_SESSION['customer_info'] = [
        'nama' => $_POST['nama'],
        'hp' => $_POST['hp'],
        'lokasi' => $_POST['lokasi'],
        'jenis_jaminan' => $_POST['jenis_jaminan'] ?? 'KTP'
    ];
}

// Validasi input
if ($variantId <= 0 || $quantity <= 0 || empty($tanggalSewa) || empty($tanggalKembali)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

try {
    // Proses tambah ke keranjang
    $result = addToCart($variantId, $quantity, $tanggalSewa, $tanggalKembali);
    
    // Kirim response
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    // Tangkap error dan kirim informasi yang lebih detail
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'error_detail' => $e->getTraceAsString()
    ]);
}
?>
