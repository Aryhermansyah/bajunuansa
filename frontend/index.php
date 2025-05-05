<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

// Fungsi untuk memformat tanggal dalam format Indonesia
function formatTanggalIndonesia($tanggal) {
    if (empty($tanggal)) return '';
    
    $bulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    
    $pecahTanggal = explode('-', $tanggal);
    if (count($pecahTanggal) != 3) return $tanggal;
    
    $tahun = $pecahTanggal[0];
    $namaBulan = isset($bulan[$pecahTanggal[1]]) ? $bulan[$pecahTanggal[1]] : $pecahTanggal[1];
    $hari = (int)$pecahTanggal[2]; // Menghilangkan leading zero
    
    return "$hari $namaBulan $tahun";
}

session_start();

$db = Database::getInstance();

// Filter tanggal
$tanggal_sewa = isset($_GET['tanggal_sewa']) ? $_GET['tanggal_sewa'] : date('Y-m-d');
if (!validateDate($tanggal_sewa)) {
    $tanggal_sewa = date('Y-m-d');
}

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Debug query
$debugQuery = true;

// Query untuk total items
$whereClause = [];
$params = [];

if ($kategori) {
    $whereClause[] = "i.kategori = ?";
    $params[] = $kategori;
}

if ($search) {
    $whereClause[] = "i.nama_baju LIKE ? COLLATE NOCASE";
    $params[] = "%$search%";
}

// Hanya tampilkan produk yang memiliki minimal satu varian dengan stok > 0
$whereClause[] = "EXISTS (SELECT 1 FROM item_variants iv WHERE iv.item_id = i.id AND iv.stok_total > 0)";

// Filter placeholder produk
$whereClause[] = "i.nama_baju NOT LIKE '_kategori_placeholder'";
$whereClause[] = "i.nama_baju IS NOT NULL";

$whereSql = '';
if (!empty($whereClause)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClause);
}

// Urutan tampilan
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
switch ($sort) {
    case 'price_low':
        $orderBy = "MIN(iv.harga) ASC";
        break;
    case 'price_high':
        $orderBy = "MIN(iv.harga) DESC";
        break;
    case 'alphabetical':
        $orderBy = "i.nama_baju ASC";
        break;
    case 'newest':
        $orderBy = "i.id DESC"; // Produk terbaru di atas
        break;
    case 'featured':
    default:
        // Cek apakah kolom is_featured ada
        try {
            $checkColumn = $db->fetchOne("SHOW COLUMNS FROM items LIKE 'is_featured'");
            if ($checkColumn) {
                $orderBy = "i.is_featured DESC, i.id DESC"; // Produk unggulan di atas, kemudian terbaru
            } else {
                $orderBy = "i.id DESC"; // Fallback ke ordering by ID jika is_featured tidak ada
            }
        } catch (Exception $e) {
            // Jika error (misal dengan SQLite yang tidak mendukung SHOW COLUMNS)
            // atau jika terjadi error lain, fallback ke pengurutan berdasarkan ID
            $orderBy = "i.id DESC";
        }
        break;
}

// Build query untuk total items
$totalQuery = "SELECT COUNT(DISTINCT i.id) as total 
               FROM items i 
               $whereSql";

$totalResult = $db->fetchOne($totalQuery, $params);
$total = $totalResult['total'];
$totalPages = ceil($total / $limit);

// Build query untuk items dengan pagination
$itemsQuery = "SELECT i.*, 
        GROUP_CONCAT(DISTINCT iv.ukuran) as ukuran_tersedia,
        MIN(iv.harga) as harga_min,
        MAX(iv.harga) as harga_max,
        MIN(iv.dp) as dp_min,
        MAX(iv.dp) as dp_max
 FROM items i
 LEFT JOIN item_variants iv ON i.id = iv.item_id
 $whereSql
 GROUP BY i.id
 ORDER BY $orderBy
 LIMIT ? OFFSET ?";

// Tambahkan parameter limit dan offset ke array params
$params[] = $limit;
$params[] = $offset;

if ($debugQuery) {
    error_log("Query Items: " . $itemsQuery);
    error_log("Params: " . print_r($params, true));
}

$items = $db->fetchAll($itemsQuery, $params);

// Ambil daftar kategori untuk filter
$categories = $db->fetchAll(
    "SELECT DISTINCT kategori FROM items ORDER BY kategori"
);
?>
<!DOCTYPE html>
<html lang="id" id="katalog">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Baju - <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .font-serif {
            font-family: 'Playfair Display', serif;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-custom-pink">
    <style>
        :root {
            --purple-main: #CDB4DB;
            --pink-main: #FFCBDD;
            --pink-accent: #FFAFCC;
        }
        .bg-custom-purple { background-color: var(--purple-main); }
        .bg-custom-pink { background-color: var(--pink-main); }
        .bg-custom-accent { background-color: var(--pink-accent); }
        .from-custom-purple { --tw-gradient-from: var(--purple-main); }
        .from-custom-pink { --tw-gradient-from: var(--pink-main); }
        .to-custom-accent { --tw-gradient-to: var(--pink-accent); }
        .text-custom-purple { color: var(--purple-main); }
        .text-custom-pink { color: var(--pink-main); }
        .text-custom-accent { color: var(--pink-accent); }
        .border-custom-purple { border-color: var(--purple-main); }
        .border-custom-pink { border-color: var(--pink-main); }
        .border-custom-accent { border-color: var(--pink-accent); }
    </style>
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-custom-purple to-custom-accent shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-custom-purple font-serif">Pearls Bridal</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="cart.php" class="text-custom-purple hover:text-custom-accent flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i> <span>Keranjang</span>
                        <span class="ml-1 text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full"><?= getCartItemCount() ?></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">


        <!-- Filter Section -->
        <div class="bg-white shadow-sm px-4 py-5 sm:rounded-xl sm:p-6 mb-6 border border-[#fde8e8]">
            <form action="" method="GET" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:items-end md:gap-4">
                <!-- Tanggal Sewa -->
                <div class="flex-1 min-w-[200px]">
                    <label for="tanggal_sewa" class="block text-sm font-medium text-[#EBA1A1]">
                        Tanggal Sewa
                    </label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-calendar text-[#EBA1A1]"></i>
                        </div>
                        <input type="hidden" name="tanggal_sewa" 
                               id="tanggal_sewa_hidden" 
                               value="<?= $tanggal_sewa ?>">                              
                        <input type="text" 
                               id="tanggal_sewa" 
                               readonly
                               value="<?= formatTanggalIndonesia($tanggal_sewa) ?>" 
                               class="block w-full pl-10 p-2.5 rounded-full border-[#fde8e8] shadow-sm focus:border-[#EBA1A1] focus:ring-[#EBA1A1] text-sm cursor-pointer">
                        <div id="date-picker-container" class="absolute left-0 mt-1 bg-white shadow-lg rounded-lg p-2 border border-[#fde8e8] z-50 hidden"></div>
                    </div>
                </div>

                <!-- Kategori -->
                <div class="flex-1 min-w-[200px]">
                    <label for="kategori" class="block text-sm font-medium text-[#EBA1A1]">
                        Kategori
                    </label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-tag text-[#EBA1A1]"></i>
                        </div>
                        <select name="kategori" 
                                id="kategori"
                                class="block w-full pl-10 p-2.5 rounded-full border-pink-200 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm appearance-none bg-white pr-8">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['kategori']) ?>"
                                    <?= $kategori === $cat['kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-pink-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Pengurutan -->
                <div class="flex-1 min-w-[200px]">
                    <label for="sort" class="block text-sm font-medium text-[#EBA1A1]">
                        Urutkan
                    </label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-sort text-[#EBA1A1]"></i>
                        </div>
                        <select name="sort" 
                                id="sort"
                                class="block w-full pl-10 p-2.5 rounded-full border-pink-200 shadow-sm focus:border-pink-500 focus:ring-pink-500 text-sm appearance-none bg-white pr-8">
                            <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Terbaru</option>
                            <option value="price_low" <?= ($sort === 'price_low') ? 'selected' : '' ?>>Harga Terendah</option>
                            <option value="price_high" <?= ($sort === 'price_high') ? 'selected' : '' ?>>Harga Tertinggi</option>
                            <option value="alphabetical" <?= ($sort === 'alphabetical') ? 'selected' : '' ?>>A-Z</option>
                            <option value="featured" <?= ($sort === 'featured') ? 'selected' : '' ?>>Unggulan</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-pink-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div>
                    <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300 focus:ring-2 focus:ring-offset-2 focus:ring-[#EBA1A1]">
                        <i class="fas fa-search mr-2"></i> Cari
                    </button>
                </div>
            </form>
        </div>

        <!-- Flash Message -->
        <?= showFlashMessage() ?>

        <!-- Items Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 mx-auto">
            <?php if (empty($items)): ?>
            <div class="col-span-full text-center py-12">
                <i class="fas fa-box-open text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500">Tidak ada baju yang tersedia</p>
            </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <?php
                    // Pastikan ukuran_tersedia tidak null sebelum di-explode
                    $ukuranArray = !empty($item['ukuran_tersedia']) ? explode(',', $item['ukuran_tersedia']) : [];
                    $variants = $db->fetchAll(
                        "SELECT iv.*, 
                                COALESCE(ia.stok_terpakai, 0) as stok_terpakai
                         FROM item_variants iv
                         LEFT JOIN (
                             SELECT variant_id, SUM(stok_terpakai) as stok_terpakai 
                             FROM item_availability 
                             WHERE tanggal = ?
                             GROUP BY variant_id
                         ) ia ON ia.variant_id = iv.id
                         WHERE iv.item_id = ?",
                        [$tanggal_sewa, $item['id']]
                    );

                    // Cek apakah semua varian stok habis
                    $allStokHabis = true;
                    foreach ($variants as $variant) {
                        $stokTotal = (int)$variant['stok_total'];
                        $stokTerpakai = (int)$variant['stok_terpakai'];
                        if ($stokTerpakai < 0) $stokTerpakai = 0;
                        $stokTersedia = $stokTotal - $stokTerpakai;
                        if ($stokTersedia > 0) {
                            $allStokHabis = false;
                            break;
                        }
                    }
                ?>
                <div class="bg-white overflow-hidden shadow-md rounded-xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 text-[10px] border border-[#fde8e8] group relative w-[90%] mx-auto">
                    <!-- Gambar -->
                    <div class="relative overflow-hidden" style="height: 234px;">
                        <?php if ($item['foto']): ?>
                        <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>"
                             alt="<?= htmlspecialchars($item['nama_baju']) ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Badge Kategori -->
                        <div class="absolute top-3 left-3">
                            <span class="bg-[#EBA1A1]/90 text-white px-2 py-1 rounded-full text-[9px] backdrop-blur-sm">
                                <?= htmlspecialchars($item['kategori']) ?>
                            </span>
                        </div>
                        
                        <!-- Stok Badge -->
                        <div class="absolute bottom-3 right-3">
                            <?php
                            $totalStok = 0;
                            foreach ($variants as $variant) {
                                if (!empty($variant)) {
                                    $stokTotal = (int)$variant['stok_total'];
                                    $stokTerpakai = (int)($variant['stok_terpakai'] ?? 0);
                                    if ($stokTerpakai < 0) $stokTerpakai = 0;
                                    $stokTersedia = $stokTotal - $stokTerpakai;
                                    if ($stokTersedia > 0) {
                                        $totalStok += $stokTersedia;
                                    }
                                }
                            }
                            ?>
                            <span class="bg-white/90 text-[#EBA1A1] px-2 py-1 rounded-full text-[9px] backdrop-blur-sm shadow-sm border border-[#fde8e8]">
                                <i class="fas fa-cubes mr-1"></i> Stok: <?= $totalStok ?>
                            </span>
                        </div>
                    </div>

                    <!-- Info Baju -->
                    <div class="px-4 py-3 bg-white">
                        <h3 class="font-semibold text-gray-800 text-[12px] mb-1"><?= htmlspecialchars($item['nama_baju']) ?></h3>
                        
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <?php 
                                // Hitung total stok tersedia
                                $stokTotal = 0;
                                $stokHabis = true;
                                
                                foreach ($variants as $variant) {
                                    if (!empty($variant)) {
                                        $stokTotal = (int)$variant['stok_total'];
                                        $stokTerpakai = (int)($variant['stok_terpakai'] ?? 0);
                                        if ($stokTerpakai < 0) $stokTerpakai = 0;
                                        $stokTersedia = $stokTotal - $stokTerpakai;
                                        if ($stokTersedia > 0) {
                                            $stokHabis = false;
                                            break;
                                        }
                                    }
                                }
                                
                                // Menampilkan stok dengan warna sesuai ketersediaan
                                $stokClass = 'text-green-500';
                                $stokText = 'Tersedia';
                                
                                if ($stokHabis) {
                                    $stokClass = 'text-red-500';
                                    $stokText = 'Habis';
                                } elseif ($stokTersedia <= 2) {
                                    $stokClass = 'text-orange-500';
                                    $stokText = 'Terbatas';
                                }
                                ?>
                                <span class="<?= $stokClass ?> text-[10px] font-medium">
                                    <i class="fas fa-circle text-[6px] mr-1"></i> <?= $stokText ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500">Ukuran: <?= htmlspecialchars(implode(', ', $ukuranArray)) ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-800 text-[13px]">
                                    <?php 
                                    // Tampilkan harga dari data yang benar
                                    if (isset($item['harga_min'])) {
                                        echo formatRupiah($item['harga_min']);
                                        if (isset($item['harga_max']) && $item['harga_max'] > $item['harga_min']) {
                                            echo ' - ' . formatRupiah($item['harga_max']);
                                        }
                                    } else if (isset($variants[0]) && isset($variants[0]['dp']) && isset($variants[0]['pelunasan'])) {
                                        echo formatRupiah($variants[0]['dp'] + $variants[0]['pelunasan']);
                                    } else {
                                        echo 'Rp 0';
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <!-- Tombol Cepat -->
                            <div>
                                <a href="order.php?item_id=<?= $item['id'] ?>&tanggal_sewa=<?= urlencode($tanggal_sewa) ?>" class="flex items-center justify-center rounded-full bg-[#fde8e8] hover:bg-[#fad1d1] w-9 h-9 transition-colors">
                                    <i class="fas fa-arrow-right text-[#EBA1A1]"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <!-- Button Pesan (Full Width) -->
                    <div class="px-4 pb-3">
                    <?php if ($stokHabis): ?>
                        <div class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-full shadow-sm text-[11px] font-medium text-gray-500 bg-gray-100 cursor-not-allowed">
                            <i class="fas fa-ban mr-1"></i>
                            <span>Stok Habis</span>
                        </div>
                    <?php else: ?>
                        <a href="order.php?item_id=<?= $item['id'] ?>&tanggal_sewa=<?= urlencode($tanggal_sewa) ?>"
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-[11px] font-medium text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            <span>Pesan Sekarang</span>
                        </a>
                    <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->


        <?php if ($totalPages > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-[#fde8e8] sm:px-6 mt-6 rounded-lg shadow-sm">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&tanggal_sewa=<?= urlencode($tanggal_sewa) ?>&kategori=<?= urlencode($kategori) ?>&sort=<?= urlencode($sort) ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&tanggal_sewa=<?= urlencode($tanggal_sewa) ?>&kategori=<?= urlencode($kategori) ?>&sort=<?= urlencode($sort) ?>" 
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
                        <a href="?page=<?= $i ?>&tanggal_sewa=<?= urlencode($tanggal_sewa) ?>&kategori=<?= urlencode($kategori) ?>&sort=<?= urlencode($sort) ?>" 
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
    </main>
    <!-- Footer -->
    <footer class="bg-gradient-to-r from-[#fde8e8] to-[#fad1d1] py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:justify-between">
                <div class="mb-8 md:mb-0">
                    <h2 class="text-lg font-bold text-[#EBA1A1] font-serif mb-4">Pearls Bridal</h2>
                    <p class="text-sm text-gray-600 max-w-xs">Menyediakan berbagai jenis baju pengantin, gaun pesta, dan aksesoris untuk momen spesial Anda.</p>
                </div>
                <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-4">Kontak Kami</h3>
                        <ul class="text-sm text-gray-500">
                            <li class="mb-2">
                                <a href="tel:+6281234567890" class="hover:text-[#EBA1A1] flex items-center">
                                    <i class="fas fa-phone-alt mr-2 text-[#EBA1A1]"></i> 081234567890
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="mailto:info@pearlsbridal.com" class="hover:text-[#EBA1A1] flex items-center">
                                    <i class="fas fa-envelope mr-2 text-[#EBA1A1]"></i> info@pearlsbridal.com
                                </a>
                            </li>
                            <li>
                                <span class="flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-[#EBA1A1]"></i> Jl. Contoh No. 123, Kota
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-4">Jam Operasional</h3>
                        <ul class="text-sm text-gray-500">
                            <li class="mb-2">Senin - Jumat: 09.00 - 17.00</li>
                            <li class="mb-2">Sabtu: 09.00 - 15.00</li>
                            <li>Minggu: Tutup</li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-[#f0c4c4] sm:mx-auto" />
            <div class="text-center">
                <p class="text-sm text-gray-500">&copy; <?= date('Y') ?> Pearls Bridal. Semua hak dilindungi.</p>
                <div class="flex justify-center mt-4 space-x-6">
                    <a href="#" class="text-gray-500 hover:text-pink-600">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-pink-600">
                        <i class="fab fa-facebook-square text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-pink-600">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('tanggal_sewa');
        const hiddenDateInput = document.getElementById('tanggal_sewa_hidden');
        const datePickerContainer = document.getElementById('date-picker-container');
        
        // Bulan dalam Bahasa Indonesia
        const namaBulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        // Fungsi untuk memformat tanggal
        function formatDate(date) {
            const day = date.getDate();
            const month = namaBulan[date.getMonth()];
            const year = date.getFullYear();
            return `${day} ${month} ${year}`;
        }
        
        // Fungsi untuk memformat tanggal ke format yyyy-mm-dd
        function formatDateForHidden(date) {
            const month = ('0' + (date.getMonth() + 1)).slice(-2);
            const day = ('0' + date.getDate()).slice(-2);
            return `${date.getFullYear()}-${month}-${day}`;
        }
        
        // Buat date picker sederhana
        dateInput.addEventListener('click', function() {
            // Toggle tampilan date picker
            datePickerContainer.classList.toggle('hidden');
            
            if (!datePickerContainer.classList.contains('hidden')) {
                // Buat date picker jika belum dibuat
                if (datePickerContainer.children.length === 0) {
                    createDatePicker();
                }
            }
        });
        
        // Fungsi untuk membuat date picker
        function createDatePicker() {
            // Bersihkan container
            datePickerContainer.innerHTML = '';
            
            // Ambil tanggal saat ini
            const currentDate = hiddenDateInput.value ? new Date(hiddenDateInput.value) : new Date();
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();
            
            // Buat header dengan bulan dan tahun
            const header = document.createElement('div');
            header.className = 'flex justify-between items-center mb-2';
            header.innerHTML = `
                <button type="button" class="prev-month px-2 py-1 text-[#EBA1A1] hover:bg-[#fde8e8] rounded-full">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="month-year font-medium">${namaBulan[currentMonth]} ${currentYear}</div>
                <button type="button" class="next-month px-2 py-1 text-[#EBA1A1] hover:bg-[#fde8e8] rounded-full">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            datePickerContainer.appendChild(header);
            
            // Event listener untuk bulan sebelumnya
            header.querySelector('.prev-month').addEventListener('click', function(e) {
                e.stopPropagation();
                const prevMonth = currentMonth === 0 ? 11 : currentMonth - 1;
                const prevYear = currentMonth === 0 ? currentYear - 1 : currentYear;
                createMonthCalendar(prevMonth, prevYear);
                header.querySelector('.month-year').textContent = `${namaBulan[prevMonth]} ${prevYear}`;
            });
            
            // Event listener untuk bulan berikutnya
            header.querySelector('.next-month').addEventListener('click', function(e) {
                e.stopPropagation();
                const nextMonth = currentMonth === 11 ? 0 : currentMonth + 1;
                const nextYear = currentMonth === 11 ? currentYear + 1 : currentYear;
                createMonthCalendar(nextMonth, nextYear);
                header.querySelector('.month-year').textContent = `${namaBulan[nextMonth]} ${nextYear}`;
            });
            
            // Buat kalender untuk bulan ini
            createMonthCalendar(currentMonth, currentYear);
        }
        
        function createMonthCalendar(month, year) {
            // Hapus kalender lama jika ada
            const oldCalendar = datePickerContainer.querySelector('.calendar');
            if (oldCalendar) {
                oldCalendar.remove();
            }
            
            // Buat container untuk kalender
            const calendar = document.createElement('div');
            calendar.className = 'calendar';
            datePickerContainer.appendChild(calendar);
            
            // Nama hari dalam seminggu
            const namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            
            // Buat header hari
            const daysRow = document.createElement('div');
            daysRow.className = 'grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 mb-1';
            namaHari.forEach(hari => {
                const dayCell = document.createElement('div');
                dayCell.className = 'w-8 h-8 flex items-center justify-center';
                dayCell.textContent = hari;
                daysRow.appendChild(dayCell);
            });
            calendar.appendChild(daysRow);
            
            // Buat grid untuk tanggal
            const datesGrid = document.createElement('div');
            datesGrid.className = 'grid grid-cols-7 gap-1 text-center';
            calendar.appendChild(datesGrid);
            
            // Hitung tanggal awal dan akhir bulan
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            // Tanggal saat ini dari input
            const selectedDate = hiddenDateInput.value ? new Date(hiddenDateInput.value) : null;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Tambahkan sel kosong untuk hari-hari sebelum tanggal 1
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'w-8 h-8';
                datesGrid.appendChild(emptyCell);
            }
            
            // Tambahkan tanggal-tanggal dalam bulan
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const dateCell = document.createElement('div');
                const currentDate = new Date(year, month, i);
                
                // Cek apakah tanggal ini adalah tanggal yang dipilih
                let isSelected = false;
                if (selectedDate) {
                    isSelected = currentDate.getDate() === selectedDate.getDate() && 
                                currentDate.getMonth() === selectedDate.getMonth() && 
                                currentDate.getFullYear() === selectedDate.getFullYear();
                }
                
                // Cek apakah tanggal ini adalah hari ini
                const isToday = currentDate.getDate() === today.getDate() && 
                               currentDate.getMonth() === today.getMonth() && 
                               currentDate.getFullYear() === today.getFullYear();
                
                // Set class berdasarkan kondisi
                dateCell.className = `w-8 h-8 rounded-full flex items-center justify-center text-sm cursor-pointer ${
                    isSelected ? 'bg-[#EBA1A1] text-white' : 
                    isToday ? 'border border-[#EBA1A1] text-[#EBA1A1]' : 
                    'hover:bg-[#fde8e8]'
                }`;
                
                dateCell.textContent = i;
                dateCell.addEventListener('click', function() {
                    const selectedDate = new Date(year, month, i);
                    dateInput.value = formatDate(selectedDate);
                    hiddenDateInput.value = formatDateForHidden(selectedDate);
                    datePickerContainer.classList.add('hidden');
                    
                    // Auto-submit form setelah memilih tanggal
                    dateInput.closest('form').submit();
                });
                
                datesGrid.appendChild(dateCell);
            }
        }
        
        // Tutup date picker jika klik di luar
        document.addEventListener('click', function(e) {
            if (!dateInput.contains(e.target) && !datePickerContainer.contains(e.target)) {
                datePickerContainer.classList.add('hidden');
            }
        });
    });
    </script>
</body>
</html>
