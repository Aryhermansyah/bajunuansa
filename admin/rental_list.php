<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = Database::getInstance();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Ambil keyword pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSql = '';
$searchParams = [];
if ($search) {
    $searchSql = "AND (r.customer_nama LIKE ? OR r.customer_hp LIKE ? OR iv.kode_unik LIKE ? OR i.nama_baju LIKE ?)";
    $searchParams = array_fill(0, 4, "%$search%");
}

// Get total rentals (perbaiki agar JOIN ke tabel lain jika ada pencarian)
if ($search) {
    $totalQuery = $db->fetchOne(
        "SELECT COUNT(*) as total FROM rentals r JOIN item_variants iv ON r.variant_id = iv.id JOIN items i ON iv.item_id = i.id WHERE 1=1 $searchSql",
        $searchParams
    );
} else {
    $totalQuery = $db->fetchOne(
        "SELECT COUNT(*) as total FROM rentals",
    );
}
$total = $totalQuery['total'];
$totalPages = ceil($total / $limit);

// Get all rentals with item details
$rentals = $db->fetchAll(
    "SELECT r.*, 
            i.nama_baju,
            iv.ukuran,
            iv.kode_unik,
            iv.barcode
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     ORDER BY r.created_at DESC
     LIMIT ? OFFSET ?",
    [$limit, $offset]
);

// Query per status
$rentals_pending = $db->fetchAll(
    "SELECT r.*, i.nama_baju, iv.ukuran, iv.kode_unik, iv.barcode
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.status = 'pending' $searchSql
     ORDER BY r.created_at DESC",
    $searchParams
);
$rentals_approved = $db->fetchAll(
    "SELECT r.*, i.nama_baju, iv.ukuran, iv.kode_unik, iv.barcode
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.status = 'approved' $searchSql
     ORDER BY r.created_at DESC",
    $searchParams
);
$rentals_returned = $db->fetchAll(
    "SELECT r.*, i.nama_baju, iv.ukuran, iv.kode_unik, iv.barcode
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.status = 'returned' $searchSql
     ORDER BY r.created_at DESC",
    $searchParams
);
$rentals_canceled = $db->fetchAll(
    "SELECT r.*, i.nama_baju, iv.ukuran, iv.kode_unik, iv.barcode
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.status = 'canceled' $searchSql
     ORDER BY r.created_at DESC",
    $searchParams
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Semua Pemesanan - <?= APP_NAME ?></title>
    
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
        <div class="max-w-7xl mx-auto px-2 sm:px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <div class="flex items-center">
                    <h1 class="text-base sm:text-xl font-bold text-gray-800 whitespace-nowrap">Pearls Bridal</h1>
                </div>
                <!-- Hamburger menu for mobile -->
                <div class="sm:hidden flex items-center">
                    <button id="menu-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none text-lg sm:text-xl">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <!-- Menu Desktop -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="rental_list.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-xs sm:text-sm font-medium">Daftar Semua Pemesanan</a>
                    <a href="fixed_rentals.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs sm:text-sm font-medium">Daftar Pemesanan Fix</a>
                    <a href="add_rental.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs sm:text-sm font-medium">Tambah Penyewaan</a>
                    <a href="view_products.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs sm:text-sm font-medium">Daftar Produk</a>
                    <a href="add_product.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-xs sm:text-sm font-medium">Tambah Produk</a>
                </div>
            </div>
            <!-- Mobile Menu, hidden by default -->
            <div id="mobile-menu" class="sm:hidden hidden mt-2">
                <a href="rental_list.php" class="block px-2 py-1 rounded-md text-xs font-medium text-indigo-700 bg-indigo-50">Daftar Semua Pemesanan</a>
                <a href="fixed_rentals.php" class="block px-2 py-1 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-100">Daftar Pemesanan Fix</a>
                <a href="add_rental.php" class="block px-2 py-1 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-100">Tambah Penyewaan</a>
                <a href="view_products.php" class="block px-2 py-1 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-100">Daftar Produk</a>
                <a href="add_product.php" class="block px-2 py-1 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-100">Tambah Produk</a>
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
    <main class="max-w-7xl mx-auto py-4 sm:py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-2 py-3 sm:px-4 sm:py-5">
            <h2 class="text-lg sm:text-2xl font-bold text-gray-900">
                Daftar Semua Pemesanan
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-gray-600">
                Menampilkan semua pemesanan termasuk yang status Pending, Disetujui, dan Dikembalikan
            </p>
            <div class="mt-2 sm:mt-4">
                <a href="add_rental.php" class="inline-flex justify-center py-1.5 sm:py-2 px-3 sm:px-4 border border-transparent shadow-sm text-xs sm:text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus mr-2"></i> Tambah Penyewaan Baru
                </a>
            </div>
        </div>

        <!-- Flash Message -->
        <?= showFlashMessage() ?>

        <!-- Form Pencarian -->
        <form method="get" class="mb-2 sm:mb-4 flex flex-col sm:flex-row gap-1 sm:gap-2 items-start sm:items-center">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, kode pesanan, baju, atau HP..." class="border border-gray-300 rounded-md px-2 sm:px-3 py-1.5 sm:py-2 w-full sm:w-64 text-xs sm:text-sm">
            <button type="submit" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-xs sm:text-sm"><i class="fas fa-search mr-1"></i> Cari</button>
            <?php if ($search): ?>
            <a href="rental_list.php" class="ml-1 sm:ml-2 text-xs sm:text-sm text-gray-500 hover:underline">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Tabel Pending -->
        <h3 class="text-base sm:text-lg font-bold mb-1 sm:mb-2 mt-4 sm:mt-8">Pesanan Pending</h3>
        <div class="overflow-x-auto">
        <?php include __DIR__.'/table_rental_status.php'; show_rental_table($rentals_pending, 'pending'); ?>
        </div>
        <!-- Tabel Disetujui -->
        <h3 class="text-base sm:text-lg font-bold mb-1 sm:mb-2 mt-4 sm:mt-8">Pesanan Disetujui</h3>
        <div class="overflow-x-auto">
        <?php show_rental_table($rentals_approved, 'approved'); ?>
        </div>
        <!-- Tabel Dikembalikan -->
        <h3 class="text-base sm:text-lg font-bold mb-1 sm:mb-2 mt-4 sm:mt-8">Pesanan Dikembalikan</h3>
        <div class="overflow-x-auto">
        <?php show_rental_table($rentals_returned, 'returned'); ?>
        </div>
        <!-- Tabel Dibatalkan -->
        <h3 class="text-base sm:text-lg font-bold mb-1 sm:mb-2 mt-4 sm:mt-8">Pesanan Dibatalkan</h3>
        <div class="overflow-x-auto">
        <?php show_rental_table($rentals_canceled, 'canceled'); ?>
        </div>
    </main>
</body>
</html> 