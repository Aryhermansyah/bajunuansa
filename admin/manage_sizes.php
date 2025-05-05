<?php
$base_dir = __DIR__ . '/../';
require_once $base_dir . 'includes/config.php';
require_once $base_dir . 'includes/db.php';
require_once $base_dir . 'includes/functions.php';

session_start();

$db = Database::getInstance();

// Periksa apakah tabel custom_sizes sudah ada dan buat jika belum ada
$dbType = $db->getDbType();
$tableExists = false;

if ($dbType === 'mysql') {
    // Kueri untuk MySQL
    $result = $db->fetchOne("SHOW TABLES LIKE 'custom_sizes'");
    $tableExists = !empty($result);
} else {
    // Kueri untuk SQLite
    $result = $db->fetchOne(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'"
    );
    $tableExists = !empty($result);
}

if (!$tableExists) {
    try {
        $db->execute("
            CREATE TABLE custom_sizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                size_name TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {
        $errors[] = "Error saat membuat tabel: " . $e->getMessage();
    }
}

$errors = [];
$success = false;

// Proses form ketika disubmit untuk menambahkan ukuran baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_size'])) {
    $newSize = strtoupper(sanitizeInput($_POST['size_name'] ?? ''));
    
    // Validasi input
    if (empty($newSize)) {
        $errors[] = "Nama ukuran wajib diisi";
    } else {
        // Cek apakah ukuran sudah ada
        $existingSize = $db->fetchOne(
            "SELECT COUNT(*) as count FROM custom_sizes WHERE size_name = ?",
            [$newSize]
        );
        
        if ($existingSize && $existingSize['count'] > 0) {
            $errors[] = "Ukuran '$newSize' sudah ada";
        } else {
            try {
                // Simpan ukuran baru
                $db->insert(
                    "INSERT INTO custom_sizes (size_name) VALUES (?)",
                    [$newSize]
                );
                
                $success = true;
                redirect("manage_sizes.php", "Ukuran baru berhasil ditambahkan", "success");
            } catch (Exception $e) {
                $errors[] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Proses hapus ukuran
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $sizeToDelete = sanitizeInput($_GET['delete']);
    
    // Cek apakah ukuran digunakan oleh varian produk
    $usedSize = $db->fetchOne(
        "SELECT COUNT(*) as count FROM item_variants WHERE ukuran = ?",
        [$sizeToDelete]
    );
    
    if ($usedSize && $usedSize['count'] > 0) {
        $errors[] = "Ukuran '$sizeToDelete' tidak dapat dihapus karena masih digunakan oleh $usedSize[count] varian produk";
    } else {
        try {
            // Hapus ukuran
            $db->execute(
                "DELETE FROM custom_sizes WHERE size_name = ?",
                [$sizeToDelete]
            );
            
            $success = true;
            redirect("manage_sizes.php", "Ukuran berhasil dihapus", "success");
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Default sizes (tidak bisa dihapus)
$defaultSizes = ['S', 'M', 'L', 'XL', 'ALL SIZE'];

// Ambil ukuran kustom dari database
$customSizes = [];
try {
    $customSizes = $db->fetchAll(
        "SELECT size_name FROM custom_sizes ORDER BY size_name ASC"
    );
} catch (Exception $e) {
    // Jika gagal mengambil data, tampilkan pesan kesalahan tapi jangan berhenti eksekusi
    $errors[] = "Gagal mengambil data ukuran: " . $e->getMessage(); 
}

// Ambil ukuran yang digunakan dalam produk (untuk mengetahui jumlah produk)
$usedSizes = $db->fetchAll(
    "SELECT ukuran, COUNT(*) as count FROM item_variants GROUP BY ukuran ORDER BY ukuran ASC"
);
$usedSizesCount = [];
foreach ($usedSizes as $size) {
    $usedSizesCount[$size['ukuran']] = $size['count'];
}

// Gabungkan semua ukuran untuk ditampilkan
$allSizes = array_merge(
    array_map(function($size) { return ['size_name' => $size]; }, $defaultSizes),
    $customSizes
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ukuran - <?= APP_NAME ?></title>
    
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
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800"><?= APP_NAME ?></h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="fixed_rentals.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Pemesanan Fix
                        </a>
                        <a href="add_rental.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Tambah Penyewaan
                        </a>
                        <a href="view_products.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Produk
                        </a>
                        <a href="add_product.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Tambah Produk
                        </a>
                        <a href="manage_categories.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Kelola Kategori
                        </a>
                        <a href="manage_sizes.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Kelola Ukuran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Kelola Ukuran
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Menampilkan dan mengelola ukuran produk yang tersedia
                    </p>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?= showFlashMessage() ?>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 p-4 rounded-md mx-4 sm:mx-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Terdapat <?= count($errors) ?> kesalahan
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 px-4 sm:px-6">
            <!-- Form Tambah Ukuran -->
            <div class="md:col-span-5">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Tambah Ukuran Baru
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Masukkan nama ukuran baru yang ingin ditambahkan
                        </p>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                        <form action="manage_sizes.php" method="post">
                            <div class="mb-4">
                                <label for="size_name" class="block text-sm font-medium text-gray-700">Nama Ukuran</label>
                                <input type="text" name="size_name" id="size_name" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Contoh: XXL, 3XL, dll">
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="add_size" value="1" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-plus mr-2"></i> Tambah Ukuran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Daftar Ukuran -->
            <div class="md:col-span-7">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Daftar Ukuran
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Ukuran yang tersedia untuk produk
                        </p>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul class="divide-y divide-gray-200">
                            <?php if (empty($allSizes)): ?>
                            <li class="px-4 py-4 sm:px-6">
                                <p class="text-sm text-gray-500">Belum ada ukuran yang tersedia</p>
                            </li>
                            <?php else: ?>
                                <?php foreach ($allSizes as $size): ?>
                                <li class="px-4 py-4 sm:px-6 flex justify-between items-center">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($size['size_name']) ?>
                                        <?php if (in_array($size['size_name'], $defaultSizes)): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Default
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php 
                                        $isUsed = isset($usedSizesCount[$size['size_name']]) ? $usedSizesCount[$size['size_name']] : 0;
                                        $isDefaultSize = in_array($size['size_name'], $defaultSizes);
                                        
                                        if ($isUsed > 0):
                                        ?>
                                        <span class="text-sm text-gray-500" title="Ukuran ini digunakan oleh <?= $isUsed ?> varian produk">
                                            <?= $isUsed ?> varian
                                        </span>
                                        <?php elseif (!$isDefaultSize): ?>
                                        <a href="manage_sizes.php?delete=<?= urlencode($size['size_name']) ?>" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus ukuran ini?')"
                                            class="text-red-600 hover:text-red-900" title="Hapus Ukuran">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 