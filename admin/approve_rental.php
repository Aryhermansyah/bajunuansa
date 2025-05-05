<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Validasi ID pesanan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('rental_list.php', 'ID pesanan tidak valid', 'error');
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil data pesanan
$rental = $db->fetchOne(
    "SELECT r.*, 
            i.nama_baju,
            iv.ukuran,
            iv.kode_unik
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.id = ? AND r.status = 'pending'",
    [$rentalId]
);

// Jika pesanan tidak ditemukan atau bukan status pending
if (!$rental) {
    redirect('rental_list.php', 'Pesanan tidak ditemukan atau sudah disetujui', 'error');
}

// Proses persetujuan pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update status pesanan menjadi approved
        $db->execute(
            "UPDATE rentals SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$rentalId]
        );
        
        redirect('rental_list.php', 'Pesanan berhasil disetujui', 'success');
    } catch (Exception $e) {
        redirect('rental_list.php', 'Gagal menyetujui pesanan: ' . $e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setujui Pesanan - <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 whitespace-nowrap">Pearls Bridal</h1>
                </div>
                <!-- Hamburger menu for mobile -->
                <div class="sm:hidden flex items-center space-x-3">
                    <a href="rental_list.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <button id="menu-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
                <!-- Menu Desktop -->
                <div class="hidden sm:flex sm:items-center">
                    <div class="sm:ml-6 sm:flex sm:space-x-8">
                        <a href="rental_list.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Semua Pemesanan
                        </a>
                        <a href="fixed_rentals.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Pemesanan Fix
                        </a>
                        <a href="add_rental.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Tambah Penyewaan
                        </a>
                        <a href="view_products.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Produk
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu, hidden by default -->
            <div id="mobile-menu" class="sm:hidden hidden mt-2 shadow-lg rounded-lg overflow-hidden">
                <!-- Pesanan Section -->
                <div class="bg-indigo-50 px-4 py-2">
                    <span class="text-xs uppercase tracking-wider font-semibold text-indigo-800">Pesanan</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <a href="rental_list.php" class="flex items-center px-4 py-3 bg-indigo-50">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-2">
                            <i class="fas fa-list text-white"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-indigo-900">Daftar Semua Pemesanan</p>
                            <p class="text-xs text-indigo-700">Lihat semua status pesanan</p>
                        </div>
                    </a>
                    <a href="fixed_rentals.php" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <div class="flex-shrink-0 bg-indigo-100 rounded-md p-2">
                            <i class="fas fa-clipboard-check text-indigo-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Daftar Pemesanan Fix</p>
                            <p class="text-xs text-gray-500">Pesanan disetujui & dikembalikan</p>
                        </div>
                    </a>
                    <a href="add_rental.php" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
                            <i class="fas fa-plus text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Tambah Penyewaan</p>
                            <p class="text-xs text-gray-500">Buat pesanan baru</p>
                        </div>
                    </a>
                </div>
                
                <!-- Produk Section -->
                <div class="bg-indigo-50 px-4 py-2">
                    <span class="text-xs uppercase tracking-wider font-semibold text-indigo-800">Produk</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <a href="view_products.php" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-2">
                            <i class="fas fa-tshirt text-purple-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Daftar Produk</p>
                            <p class="text-xs text-gray-500">Lihat semua produk</p>
                        </div>
                    </a>
                    <a href="add_product.php" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <div class="flex-shrink-0 bg-pink-100 rounded-md p-2">
                            <i class="fas fa-plus text-pink-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Tambah Produk</p>
                            <p class="text-xs text-gray-500">Tambah produk baru</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            menuToggle && menuToggle.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        });
    </script>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-3 px-4 sm:py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-3 py-3 sm:px-6 sm:py-5">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">
                Konfirmasi Persetujuan Pesanan
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-gray-600">
                Tinjau detail pesanan sebelum menyetujuinya
            </p>
        </div>

        <!-- Rental Detail Card -->
        <div class="bg-white shadow overflow-hidden rounded-lg">
            <div class="px-3 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900">
                    Detail Pesanan
                </h3>
            </div>
            <div class="border-t border-gray-200 px-3 py-2 sm:px-4 sm:py-5 sm:p-0">
                <dl class="divide-y divide-gray-200">
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Nama Penyewa
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= htmlspecialchars($rental['customer_nama']) ?>
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Nomor HP
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= htmlspecialchars($rental['customer_hp']) ?>
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Alamat
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= nl2br(htmlspecialchars($rental['customer_lokasi'])) ?>
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Baju
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= htmlspecialchars($rental['nama_baju']) ?> (Ukuran: <?= htmlspecialchars($rental['ukuran']) ?>)
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Periode Sewa
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= formatTanggalIndo($rental['tanggal_sewa']) ?> s/d <?= formatTanggalIndo($rental['tanggal_kembali']) ?>
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Jumlah
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= htmlspecialchars($rental['jumlah']) ?> unit
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Pembayaran
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <p>DP: <?= formatRupiah($rental['dp_bayar']) ?></p>
                            <p>Pelunasan: <?= formatRupiah($rental['pelunasan_bayar']) ?></p>
                            <p class="font-medium">Total: <?= formatRupiah($rental['dp_bayar'] + $rental['pelunasan_bayar']) ?></p>
                        </dd>
                    </div>
                    <div class="py-3 sm:py-4 md:py-5 md:grid md:grid-cols-3 md:gap-4 md:px-6">
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">
                            Catatan
                        </dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900 md:mt-0 md:col-span-2">
                            <?= !empty($rental['catatan']) ? nl2br(htmlspecialchars($rental['catatan'])) : '-' ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Confirmation Buttons -->
        <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row sm:justify-center gap-2 sm:gap-4">
            <form method="post" class="sm:inline-block w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-check mr-2"></i> Setujui Pesanan
                </button>
            </form>
            <a href="rental_list.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </main>
</body>
</html>