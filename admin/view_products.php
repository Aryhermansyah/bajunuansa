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

// Filter kategori
$kategoriFilter = isset($_GET['kategori']) ? sanitizeInput($_GET['kategori']) : '';

// Base query
$baseQuery = "FROM items i";
$countParams = [];
$itemParams = [];

// Apply filter if needed
if (!empty($kategoriFilter)) {
    $baseQuery .= " WHERE i.kategori = ?";
    $countParams[] = $kategoriFilter;
    $itemParams[] = $kategoriFilter;
}

// Get total items count
$totalQuery = $db->fetchOne(
    "SELECT COUNT(*) as total " . $baseQuery,
    $countParams
);
$total = $totalQuery['total'];
$totalPages = ceil($total / $limit);

// Get items with variant count
$items = $db->fetchAll(
    "SELECT i.*, 
            (SELECT COUNT(*) FROM item_variants iv WHERE iv.item_id = i.id) as variant_count,
            (SELECT SUM(stok_total) FROM item_variants iv WHERE iv.item_id = i.id) as total_stock,
            i.is_featured as unggulan
     " . $baseQuery . "
     ORDER BY i.nama_baju ASC
     LIMIT ? OFFSET ?",
    array_merge($itemParams, [$limit, $offset])
);

// Get all categories for filter
$categories = $db->fetchAll(
    "SELECT DISTINCT kategori FROM items ORDER BY kategori ASC"
);

// Proses DELETE jika ada parameter delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $itemIdToDelete = (int)$_GET['delete'];
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Hapus varian terlebih dahulu
        $db->execute(
            "DELETE FROM item_variants WHERE item_id = ?",
            [$itemIdToDelete]
        );
        
        // Hapus ketersediaan item
        $db->execute(
            "DELETE FROM item_availability 
             WHERE variant_id IN (SELECT id FROM item_variants WHERE item_id = ?)",
            [$itemIdToDelete]
        );
        
        // Hapus item
        $db->execute(
            "DELETE FROM items WHERE id = ?",
            [$itemIdToDelete]
        );
        
        $db->getConnection()->commit();
        redirect("view_products.php", "Produk berhasil dihapus", "success");
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $errors[] = "Gagal menghapus produk: " . $e->getMessage();
    }
}

// Proses toggle featured
if (isset($_GET['toggle_featured']) && !empty($_GET['toggle_featured'])) {
    $itemId = (int)$_GET['toggle_featured'];
    
    try {
        // Dapatkan status featured saat ini
        $currentFeatured = $db->fetchOne(
            "SELECT is_featured FROM items WHERE id = ?",
            [$itemId]
        );
        
        if ($currentFeatured) {
            // Toggle status featured
            $newStatus = $currentFeatured['is_featured'] ? 0 : 1;
            
            $db->execute(
                "UPDATE items SET is_featured = ? WHERE id = ?",
                [$newStatus, $itemId]
            );
            
            $statusText = $newStatus ? "ditandai sebagai unggulan" : "tidak lagi ditandai sebagai unggulan";
            redirect("view_products.php", "Produk berhasil $statusText", "success");
        }
    } catch (Exception $e) {
        $errors[] = "Gagal mengubah status unggulan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk - <?= APP_NAME ?></title>
    
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
                <div class="sm:hidden flex items-center">
                    <button id="menu-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
                <!-- Menu Desktop -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="rental_list.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Daftar Semua Pemesanan</a>
                    <a href="fixed_rentals.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Daftar Pemesanan Fix</a>
                    <a href="add_rental.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Tambah Penyewaan</a>
                    <a href="view_products.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Daftar Produk</a>
                    <a href="add_product.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Tambah Produk</a>
                    <a href="manage_categories.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Kelola Kategori</a>
                    <a href="manage_sizes.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Kelola Ukuran</a>
                </div>
            </div>
            <!-- Mobile Menu, hidden by default -->
            <div id="mobile-menu" class="sm:hidden hidden mt-2">
                <a href="rental_list.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Daftar Semua Pemesanan</a>
                <a href="fixed_rentals.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Daftar Pemesanan Fix</a>
                <a href="add_rental.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Tambah Penyewaan</a>
                <a href="view_products.php" class="block px-3 py-2 rounded-md text-base font-medium text-indigo-700 bg-indigo-50">Daftar Produk</a>
                <a href="add_product.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Tambah Produk</a>
                <a href="manage_categories.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Kelola Kategori</a>
                <a href="manage_sizes.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Kelola Ukuran</a>
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
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Daftar Produk
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Menampilkan semua produk baju yang tersedia untuk disewa
                    </p>
                </div>
                <div>
                    <a href="add_product.php" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i> Tambah Produk Baru
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?= showFlashMessage() ?>

        <!-- Filter Section -->
        <div class="mt-4 px-4 sm:px-6">
            <form action="view_products.php" method="get" class="flex items-center space-x-4">
                <div>
                    <label for="kategori" class="block text-sm font-medium text-gray-700">Filter Kategori</label>
                    <select name="kategori" id="kategori" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['kategori'] ?>" <?= $kategoriFilter === $cat['kategori'] ? 'selected' : '' ?>>
                            <?= $cat['kategori'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-6">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                    <?php if (!empty($kategoriFilter)): ?>
                    <a href="view_products.php" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-times mr-2"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Baju
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kategori
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah Varian
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Stok
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unggulan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Tidak ada data produk
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $item['id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($item['nama_baju']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($item['kategori']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $item['variant_count'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $item['total_stock'] ?? 0 ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $item['unggulan'] ? '<span class="text-green-600 font-semibold">Ya</span>' : 'Tidak' ?>
                                    <a href="view_products.php?toggle_featured=<?= $item['id'] ?>" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-exchange-alt"></i> 
                                        <?= $item['unggulan'] ? 'Batalkan' : 'Jadikan Unggulan' ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="view_product_detail.php?id=<?= $item['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="edit_product.php?id=<?= $item['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="view_products.php?delete=<?= $item['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($kategoriFilter) ? '&kategori=' . urlencode($kategoriFilter) : '' ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($kategoriFilter) ? '&kategori=' . urlencode($kategoriFilter) : '' ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Menampilkan
                            <span class="font-medium"><?= ($offset + 1) ?></span>
                            sampai
                            <span class="font-medium"><?= min($offset + $limit, $total) ?></span>
                            dari
                            <span class="font-medium"><?= $total ?></span>
                            hasil
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?><?= !empty($kategoriFilter) ? '&kategori=' . urlencode($kategoriFilter) : '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                                      <?= $i === $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : '' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 