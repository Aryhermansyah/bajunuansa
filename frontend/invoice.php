<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash'] = [
        'message' => 'ID pesanan tidak valid. Silakan periksa kembali link yang Anda kunjungi.',
        'type' => 'error'
    ];
    header('Location: index.php');
    exit;
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil data pesanan
$rental = $db->fetchOne(
    "SELECT r.*, i.nama_baju, i.kategori, i.foto, iv.ukuran
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.id = ?",
    [$rentalId]
);
if (!$rental) {
    die('Pesanan tidak ditemukan.');
}

// Format detail untuk WhatsApp
$waText = "Halo Admin, saya ingin konfirmasi pesanan:\n";
$waText .= "Kode Pesanan: " . $rental['id'] . "\n";
$waText .= "Nama: " . $rental['customer_nama'] . "\n";
$waText .= "No HP: " . $rental['customer_hp'] . "\n";
$waText .= "Alamat: " . $rental['customer_lokasi'] . "\n";
$waText .= "Baju: " . $rental['nama_baju'] . " (" . $rental['kategori'] . ")\n";
$waText .= "Ukuran: " . $rental['ukuran'] . "\n";
$waText .= "Tanggal Sewa: " . $rental['tanggal_sewa'] . "\n";
if (!empty($rental['tanggal_booking'])) {
    $waText .= "Tanggal Booking: " . $rental['tanggal_booking'] . "\n";
}
$waText .= "Tanggal Kembali: " . $rental['tanggal_kembali'] . "\n";
$waText .= "Jumlah: " . $rental['jumlah'] . "\n";
$waText .= "Jaminan: " . $rental['jenis_jaminan'] . "\n";
if (!empty($rental['catatan'])) {
    $waText .= "Catatan: " . $rental['catatan'] . "\n";
}
$waText .= "\nMohon konfirmasi pesanan saya. Terima kasih.";
$waText = urlencode($waText);
$waNumber = '6282228287646'; // Ganti dengan nomor WA admin
$waLink = "https://wa.me/$waNumber?text=$waText";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Pesanan - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <main class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4 text-center">Invoice Pemesanan</h2>
            <div class="mb-6 text-center">
                <?php if ($rental['foto']): ?>
                <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($rental['foto']) ?>" alt="<?= htmlspecialchars($rental['nama_baju']) ?>" class="mx-auto w-40 h-40 object-contain rounded-lg mb-2">
                <?php endif; ?>
                <div class="text-lg font-semibold mb-1"><?= htmlspecialchars($rental['nama_baju']) ?> (<?= htmlspecialchars($rental['kategori']) ?>)</div>
                <div class="text-sm text-gray-500">Ukuran: <?= htmlspecialchars($rental['ukuran']) ?></div>
            </div>
            <div class="mb-4">
                <table class="w-full text-sm">
                    <tr><td class="py-1 font-medium">Kode Pesanan</td><td>: <?= $rental['id'] ?></td></tr>
                    <tr><td class="py-1 font-medium">Nama</td><td>: <?= htmlspecialchars($rental['customer_nama']) ?></td></tr>
                    <tr><td class="py-1 font-medium">No HP</td><td>: <?= htmlspecialchars($rental['customer_hp']) ?></td></tr>
                    <tr><td class="py-1 font-medium">Alamat</td><td>: <?= htmlspecialchars($rental['customer_lokasi']) ?></td></tr>
                    <tr><td class="py-1 font-medium">Tanggal Sewa</td><td>: <?= htmlspecialchars($rental['tanggal_sewa']) ?></td></tr>
                    <?php if (!empty($rental['tanggal_booking'])): ?>
                    <tr><td class="py-1 font-medium">Tanggal Booking</td><td>: <?= htmlspecialchars($rental['tanggal_booking']) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="py-1 font-medium">Tanggal Kembali</td><td>: <?= htmlspecialchars($rental['tanggal_kembali']) ?></td></tr>
                    <tr><td class="py-1 font-medium">Jumlah</td><td>: <?= htmlspecialchars($rental['jumlah']) ?></td></tr>
                    <tr><td class="py-1 font-medium">Jaminan</td><td>: <?= htmlspecialchars($rental['jenis_jaminan']) ?></td></tr>
                    <?php if (!empty($rental['catatan'])): ?>
                    <tr><td class="py-1 font-medium">Catatan</td><td>: <?= nl2br(htmlspecialchars($rental['catatan'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="mb-6 text-center">
                <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">Menunggu Konfirmasi Admin</span>
            </div>
            <div class="flex flex-col gap-3">
                <a href="<?= $waLink ?>" target="_blank" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i class="fab fa-whatsapp mr-2"></i> Konfirmasi ke Admin via WhatsApp
                </a>
                <a href="index.php" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Katalog
                </a>
            </div>
            <script>
                // Fungsi untuk redirect ke WhatsApp
                function redirectToWhatsApp() {
                    var message = "Halo, saya ingin konfirmasi pesanan dengan detail berikut:\n\n" +
                                 "Nama: <?= htmlspecialchars($rental['customer_nama']) ?>\n" +
                                 "No. HP: <?= htmlspecialchars($rental['customer_hp']) ?>\n" +
                                 "Lokasi: <?= htmlspecialchars($rental['customer_lokasi']) ?>\n" +
                                 "Tanggal Sewa: <?= date('d/m/Y', strtotime($rental['tanggal_sewa'])) ?>\n" +
                                 "Tanggal Kembali: <?= date('d/m/Y', strtotime($rental['tanggal_kembali'])) ?>\n" +
                                 "Jumlah: <?= $rental['jumlah'] ?> pcs\n" +
                                 "Total DP: <?= formatRupiah($rental['dp_bayar']) ?>\n" +
                                 "Total Pelunasan: <?= formatRupiah($rental['pelunasan_bayar']) ?>\n\n" +
                                 "Mohon konfirmasi ketersediaan dan proses selanjutnya.";
                    
                    var whatsappUrl = "https://wa.me/6282228287646?text=" + encodeURIComponent(message);
                    window.open(whatsappUrl, '_blank');
                }

                // Fungsi untuk konfirmasi admin
                function konfirmasiAdmin() {
                    // Tampilkan pesan konfirmasi
                    alert('Terima kasih! Admin akan segera menghubungi Anda.');
                    
                    // Set timeout untuk redirect ke home setelah 10 detik
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 10000);
                }
            </script>
            <!-- Tombol Konfirmasi Admin -->
            <button onclick="konfirmasiAdmin()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-check-circle mr-2"></i>
                Konfirmasi Admin
            </button>
        </div>
    </main>
</body>
</html> 