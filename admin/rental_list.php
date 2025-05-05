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

// Debug untuk memeriksa koneksi database
$db_connection_status = !empty($rentals) || !empty($rentals_pending) ? 'Connected' : 'Issue with database or empty results';

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
    <main class="max-w-7xl mx-auto py-3 px-3 sm:py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm px-4 py-4 sm:px-4 sm:py-5 mb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">
                Daftar Semua Pemesanan
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Menampilkan semua pemesanan termasuk yang status Pending, Disetujui, dan Dikembalikan
            </p>
            <div class="mt-4">
                <a href="add_rental.php" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus mr-2"></i> Tambah Penyewaan Baru
                </a>
            </div>
        </div>

        <!-- Flash Message -->
        <?= showFlashMessage() ?>

        <!-- Form Pencarian -->
        <form method="get" class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex flex-col gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Cari nama, kode pesanan, baju, atau HP..." 
                    class="border border-gray-300 rounded-md px-3 py-2 w-full text-sm"
                >
                <div class="flex items-center gap-2">
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm flex-grow sm:flex-grow-0"
                    >
                        <i class="fas fa-search mr-1"></i> Cari
                    </button>
                    <?php if ($search): ?>
                    <a href="rental_list.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm text-center">
                        <i class="fas fa-times mr-1"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Tabel Pending -->
        <h3 class="text-lg font-bold mb-2 mt-2 bg-yellow-50 px-4 py-2 rounded-t-lg border-l-4 border-yellow-500">Pesanan Pending (<?= count($rentals_pending) ?>)</h3>
        <div class="overflow-x-auto">
        <?php 
        // Debug data
        echo "<!-- Debug: " . count($rentals_pending) . " pesanan pending -->";
        
        // Pastikan fungsi tersedia
        if(!function_exists('show_rental_table')) { 
            include_once __DIR__.'/table_rental_status.php'; 
        }
        
        // Tampilkan tabel
        show_rental_table($rentals_pending, 'pending'); 
        ?>
        </div>
        <!-- Tabel Disetujui -->
        <h3 class="text-lg font-bold mb-2 mt-6 bg-green-50 px-4 py-2 rounded-t-lg border-l-4 border-green-500">Pesanan Disetujui (<?= count($rentals_approved) ?>)</h3>
        <div class="overflow-x-auto">
        <?php 
        // Debug data
        echo "<!-- Debug: " . count($rentals_approved) . " pesanan disetujui -->";
        
        // Pastikan fungsi tersedia dan file ada
        if(!function_exists('show_rental_table')) { 
            $table_file = __DIR__.'/table_rental_status.php'; 
            if(file_exists($table_file)) {
                include_once $table_file;
                echo "<!-- Table file included successfully -->";
            } else {
                echo "<!-- ERROR: Table file not found: $table_file -->";
            }
        }
        
        // Cek apakah fungsi tersedia setelah include
        if(function_exists('show_rental_table')) {
            show_rental_table($rentals_approved, 'approved');
        } else {
            echo "<div class='p-4 text-red-700 bg-red-100 rounded'>Error: Fungsi show_rental_table tidak tersedia</div>";
        }
        ?>
        </div>
        <!-- Tabel Dikembalikan -->
        <h3 class="text-lg font-bold mb-2 mt-6 bg-blue-50 px-4 py-2 rounded-t-lg border-l-4 border-blue-500">Pesanan Dikembalikan (<?= count($rentals_returned) ?>)</h3>
        <div class="overflow-x-auto">
        <?php 
        // Debug data
        echo "<!-- Debug: " . count($rentals_returned) . " pesanan dikembalikan -->";
        
        // Pastikan fungsi tersedia dan file ada
        if(!function_exists('show_rental_table')) { 
            $table_file = __DIR__.'/table_rental_status.php'; 
            if(file_exists($table_file)) {
                include_once $table_file;
                echo "<!-- Table file included successfully -->";
            } else {
                echo "<!-- ERROR: Table file not found: $table_file -->";
            }
        }
        
        // Cek apakah fungsi tersedia setelah include
        if(function_exists('show_rental_table')) {
            show_rental_table($rentals_returned, 'returned');
        } else {
            echo "<div class='p-4 text-red-700 bg-red-100 rounded'>Error: Fungsi show_rental_table tidak tersedia</div>";
        }
        ?>
        </div>
        <!-- Tabel Dibatalkan -->
        <h3 class="text-lg font-bold mb-2 mt-6 bg-red-50 px-4 py-2 rounded-t-lg border-l-4 border-red-500">Pesanan Dibatalkan (<?= count($rentals_canceled) ?>)</h3>
        <div class="overflow-x-auto">
        <?php 
        // Debug data
        echo "<!-- Debug: " . count($rentals_canceled) . " pesanan dibatalkan -->";
        
        // Pastikan fungsi tersedia dan file ada
        if(!function_exists('show_rental_table')) { 
            $table_file = __DIR__.'/table_rental_status.php'; 
            if(file_exists($table_file)) {
                include_once $table_file;
                echo "<!-- Table file included successfully -->";
            } else {
                echo "<!-- ERROR: Table file not found: $table_file -->";
            }
        }
        
        // Cek apakah fungsi tersedia setelah include
        if(function_exists('show_rental_table')) {
            show_rental_table($rentals_canceled, 'canceled');
        } else {
            echo "<div class='p-4 text-red-700 bg-red-100 rounded'>Error: Fungsi show_rental_table tidak tersedia</div>";
        }
        ?>
        </div>
    </main>
</body>
</html> 