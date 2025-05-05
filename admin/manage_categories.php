<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = Database::getInstance();

$errors = [];
$success = false;

// Proses form ketika disubmit untuk menambahkan kategori baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $newCategory = sanitizeInput($_POST['category_name'] ?? '');
    
    // Validasi input
    if (empty($newCategory)) {
        $errors[] = "Nama kategori wajib diisi";
    } else {
        // Cek apakah kategori sudah ada
        $existingCategory = $db->fetchOne(
            "SELECT COUNT(*) as count FROM items WHERE kategori = ?",
            [$newCategory]
        );
        
        if ($existingCategory['count'] > 0) {
            $errors[] = "Kategori '$newCategory' sudah ada";
        } else {
            try {
                // Simpan kategori sebagai item dummy yang tidak akan ditampilkan di frontend
                // Ini adalah cara cepat untuk menyimpan kategori tanpa membuat tabel baru
                $db->insert(
                    "INSERT INTO items (nama_baju, kategori, deskripsi, created_at, updated_at) 
                     VALUES ('__kategori_placeholder', ?, 'Placeholder untuk kategori', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$newCategory]
                );
                
                $success = true;
                redirect("manage_categories.php", "Kategori baru berhasil ditambahkan", "success");
            } catch (Exception $e) {
                $errors[] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Proses hapus kategori
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $kategoriToDelete = sanitizeInput($_GET['delete']);
    
    // Cek apakah kategori digunakan oleh produk
    $usedCategory = $db->fetchOne(
        "SELECT COUNT(*) as count FROM items WHERE kategori = ? AND nama_baju != '__kategori_placeholder'",
        [$kategoriToDelete]
    );
    
    if ($usedCategory['count'] > 0) {
        $errors[] = "Kategori '$kategoriToDelete' tidak dapat dihapus karena masih digunakan oleh produk";
    } else {
        try {
            // Hapus kategori (placeholder)
            $db->execute(
                "DELETE FROM items WHERE kategori = ? AND nama_baju = '__kategori_placeholder'",
                [$kategoriToDelete]
            );
            
            $success = true;
            redirect("manage_categories.php", "Kategori berhasil dihapus", "success");
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil semua kategori dari database
$allCategories = $db->fetchAll(
    "SELECT DISTINCT kategori FROM items WHERE nama_baju != '__kategori_placeholder' OR nama_baju IS NULL ORDER BY kategori ASC"
);

// Tambahkan kategori placeholder yang tidak digunakan oleh produk lain
$placeholderCategories = $db->fetchAll(
    "SELECT kategori FROM items WHERE nama_baju = '__kategori_placeholder' 
     AND kategori NOT IN (SELECT DISTINCT kategori FROM items WHERE nama_baju != '__kategori_placeholder') 
     ORDER BY kategori ASC"
);

// Gabungkan semua kategori
foreach ($placeholderCategories as $cat) {
    $allCategories[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - <?= APP_NAME ?></title>
    
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
                        <a href="manage_categories.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Kelola Kategori
                        </a>
                        <a href="manage_sizes.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
                        Kelola Kategori
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Menampilkan dan mengelola kategori produk yang tersedia
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
            <!-- Form Tambah Kategori -->
            <div class="md:col-span-5">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Tambah Kategori Baru
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Masukkan nama kategori baru yang ingin ditambahkan
                        </p>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                        <form action="manage_categories.php" method="post">
                            <div class="mb-4">
                                <label for="category_name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                                <input type="text" name="category_name" id="category_name" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Contoh: Formal, Casual, dll">
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="add_category" value="1" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-plus mr-2"></i> Tambah Kategori
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Daftar Kategori -->
            <div class="md:col-span-7">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Daftar Kategori
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Kategori yang digunakan untuk mengelompokkan produk
                        </p>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul class="divide-y divide-gray-200">
                            <?php if (empty($allCategories)): ?>
                            <li class="px-4 py-4 sm:px-6">
                                <p class="text-sm text-gray-500">Belum ada kategori yang tersedia</p>
                            </li>
                            <?php else: ?>
                                <?php foreach ($allCategories as $category): ?>
                                <li class="px-4 py-4 sm:px-6 flex justify-between items-center">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($category['kategori']) ?>
                                    </div>
                                    <div>
                                        <!-- Cek apakah kategori digunakan dalam produk -->
                                        <?php 
                                        $isUsed = $db->fetchOne(
                                            "SELECT COUNT(*) as count FROM items WHERE kategori = ? AND nama_baju != '__kategori_placeholder'",
                                            [$category['kategori']]
                                        );
                                        
                                        if ($isUsed['count'] == 0):
                                        ?>
                                        <a href="manage_categories.php?delete=<?= urlencode($category['kategori']) ?>" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
                                            class="text-red-600 hover:text-red-900" title="Hapus Kategori">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-sm text-gray-500" title="Kategori ini digunakan oleh <?= $isUsed['count'] ?> produk">
                                            <?= $isUsed['count'] ?> produk
                                        </span>
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