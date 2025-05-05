<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = Database::getInstance();

// Ambil daftar kategori dari database
$categories = $db->fetchAll(
    "SELECT DISTINCT kategori FROM items ORDER BY kategori ASC"
);

// Jika belum ada kategori, tambahkan kategori default
if (empty($categories)) {
    $categories = [
        ['kategori' => 'Formal'],
        ['kategori' => 'Casual'],
        ['kategori' => 'Traditional'],
        ['kategori' => 'Pesta'],
        ['kategori' => 'Adat']
    ];
}

// Ambil daftar ukuran dari database custom dan default
$defaultSizes = ['S', 'M', 'L', 'XL', 'ALL SIZE'];

// Cek apakah tabel custom_sizes ada
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

// Ambil ukuran kustom dari database
$customSizes = [];
if ($tableExists) {
    $customSizesData = $db->fetchAll(
        "SELECT size_name FROM custom_sizes ORDER BY size_name ASC"
    );
    foreach ($customSizesData as $size) {
        $customSizes[] = $size['size_name'];
    }
}

// Gabungkan ukuran default dan kustom
$sizes = array_merge($defaultSizes, $customSizes);

$errors = [];
$success = false;

// Proses form ketika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi dan validasi input untuk item
    $namaBaju = sanitizeInput($_POST['nama_baju'] ?? '');
    $kategori = sanitizeInput($_POST['kategori'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validasi input item
    if (empty($namaBaju)) {
        $errors[] = "Nama baju wajib diisi";
    }
    
    if (empty($kategori)) {
        $errors[] = "Kategori wajib dipilih";
    }
    
    // Proses upload foto jika ada
    $fotoFilename = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
        $fotoFilename = uploadImage($_FILES['foto']);
        if (!$fotoFilename) {
            $errors[] = "Upload foto gagal. Pastikan format file adalah jpg, jpeg, atau png.";
        }
    }
    
    // Validasi minimal satu varian
    $varianCount = 0;
    $varianDebug = []; // Array untuk menyimpan data debug
    
    foreach ($sizes as $size) {
        // Perhatikan bahwa kita menggunakan isset bukan empty karena nilai "0" dianggap empty oleh PHP
        $stokValue = isset($_POST["stok_{$size}"]) ? $_POST["stok_{$size}"] : '0';
        $stokInt = (int)$stokValue;
        
        // Simpan info debug
        $varianDebug[] = "Ukuran $size: nilai=$stokValue, integer=$stokInt";
        
        // Hanya masuk hitungan jika stok > 0
        if ($stokInt > 0) {
            $varianCount++;
            $varianDebug[] = "-> Dihitung untuk $size";
        }
    }
    
    error_log("Jumlah varian valid: $varianCount");
    error_log("Debug varian: " . implode("; ", $varianDebug));
    
    if ($varianCount === 0) {
        $errors[] = "Minimal satu varian ukuran harus diisi dengan stok > 0";
    }
    
    // Jika tidak ada error, proses penyimpanan data
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $db->getConnection()->beginTransaction();
            
            // Simpan data item (baju)
            $itemId = $db->insert(
                "INSERT INTO items (
                    nama_baju, kategori, deskripsi, foto, created_at, updated_at, is_featured
                ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)",
                [
                    $namaBaju, $kategori, $deskripsi, $fotoFilename, $isFeatured
                ]
            );
            
            // Simpan data varian untuk setiap ukuran
            foreach ($sizes as $size) {
                $stok = (int)($_POST["stok_{$size}"] ?? 0);
                $harga = (float)($_POST["harga_{$size}"] ?? 0);
                $dp = (float)($_POST["dp_{$size}"] ?? 0);
                $pelunasan = (float)($_POST["pelunasan_{$size}"] ?? 0);
                
                // Hanya simpan jika stok > 0
                if ($stok > 0) {
                    // Generate kode unik dan barcode
                    $kodeUnik = generateUniqueCode($size);
                    $barcode = generateBarcode($kodeUnik);
                    
                    // Simpan varian
                    $db->insert(
                        "INSERT INTO item_variants (
                            item_id, ukuran, stok_total, kode_unik, barcode,
                            harga, dp, pelunasan, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                        [
                            $itemId, $size, $stok, $kodeUnik, $barcode,
                            $harga, $dp, $pelunasan
                        ]
                    );
                }
            }
            
            // Commit transaksi
            $db->getConnection()->commit();
            $success = true;
            
            // Redirect dengan pesan sukses
            redirect("view_products.php", "Produk baru berhasil ditambahkan", "success");
        } catch (Exception $e) {
            // Rollback jika terjadi error
            $db->getConnection()->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk Baru - <?= APP_NAME ?></title>
    
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
        .hidden-section {
            display: none;
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
                        <a href="add_product.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Tambah Produk
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
            <h2 class="text-2xl font-bold text-gray-900">
                Tambah Produk Baru
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Formulir untuk menambahkan data produk baju baru dan variannya
            </p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Terdapat <?= count($errors) ?> kesalahan pada formulir
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

        <!-- Add Product Form -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <form action="add_product.php" method="post" enctype="multipart/form-data" class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Basic Product Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Dasar Produk</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nama_baju" class="block text-sm font-medium text-gray-700">Nama Baju</label>
                                <input type="text" name="nama_baju" id="nama_baju" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    value="<?= $_POST['nama_baju'] ?? '' ?>">
                            </div>
                            
                            <div>
                                <label for="kategori" class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select name="kategori" id="kategori" required
                                    class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['kategori'] ?>" <?= ($_POST['kategori'] ?? '') === $cat['kategori'] ? 'selected' : '' ?>><?= $cat['kategori'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            ><?= $_POST['deskripsi'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_featured" class="ml-2 block text-sm text-gray-900">
                                    Produk Unggulan (ditampilkan di bagian atas)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="foto" class="block text-sm font-medium text-gray-700">Foto Produk</label>
                            <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/jpg"
                                class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-gray-500">
                                Format yang didukung: JPG, JPEG, PNG. Maksimal 2MB.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Variant Information -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Varian</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Tambahkan detail varian untuk setiap ukuran yang tersedia. Isi stok dengan 0 untuk ukuran yang tidak tersedia.
                        </p>
                        
                        <!-- Nav Tabs untuk Varian Ukuran -->
                        <div class="mb-4 border-b border-gray-200">
                            <nav class="-mb-px flex flex-wrap space-x-8">
                                <?php foreach ($sizes as $index => $size): ?>
                                <button type="button" 
                                    class="<?= $index === 0 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm mb-2"
                                    data-target="tab-<?= $size ?>">
                                    Ukuran <?= $size ?>
                                </button>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                        
                        <!-- Tab Content untuk setiap ukuran -->
                        <?php foreach ($sizes as $index => $size): ?>
                        <div id="tab-<?= $size ?>" class="variant-tab <?= $index === 0 ? '' : 'hidden-section' ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="stok_<?= $size ?>" class="block text-sm font-medium text-gray-700">Stok Total (Ukuran <?= $size ?>)</label>
                                    <input type="number" name="stok_<?= $size ?>" id="stok_<?= $size ?>" min="0"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value="<?= $_POST["stok_{$size}"] ?? '0' ?>">
                                </div>
                                
                                <div>
                                    <label for="harga_<?= $size ?>" class="block text-sm font-medium text-gray-700">Harga Sewa (Ukuran <?= $size ?>)</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="number" name="harga_<?= $size ?>" id="harga_<?= $size ?>" min="0" step="1000"
                                            class="block w-full pl-10 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            value="<?= $_POST["harga_{$size}"] ?? '0' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label for="dp_<?= $size ?>" class="block text-sm font-medium text-gray-700">DP (Ukuran <?= $size ?>)</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="number" name="dp_<?= $size ?>" id="dp_<?= $size ?>" min="0" step="1000"
                                            class="block w-full pl-10 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            value="<?= $_POST["dp_{$size}"] ?? '0' ?>">
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="pelunasan_<?= $size ?>" class="block text-sm font-medium text-gray-700">Pelunasan (Ukuran <?= $size ?>)</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="number" name="pelunasan_<?= $size ?>" id="pelunasan_<?= $size ?>" min="0" step="1000"
                                            class="block w-full pl-10 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            value="<?= $_POST["pelunasan_{$size}"] ?? '0' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="auto_calc_<?= $size ?>" class="auto-calc h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="auto_calc_<?= $size ?>" class="ml-2 block text-sm text-gray-900">
                                        Hitung otomatis (DP 30%, Pelunasan 70%)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8 flex justify-end">
                    <a href="view_products.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Tab switching untuk varian ukuran
        document.querySelectorAll('[data-target]').forEach(tab => {
            tab.addEventListener('click', function() {
                // Hide all tabs
                document.querySelectorAll('.variant-tab').forEach(content => {
                    content.classList.add('hidden-section');
                });
                
                // Remove active class from all tabs
                document.querySelectorAll('[data-target]').forEach(t => {
                    t.classList.remove('border-indigo-500', 'text-indigo-600');
                    t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                });
                
                // Show selected tab
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).classList.remove('hidden-section');
                
                // Add active class to selected tab
                this.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                this.classList.add('border-indigo-500', 'text-indigo-600');
            });
        });

        // Auto calculate DP and Pelunasan based on total price
        document.querySelectorAll('.auto-calc').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const size = this.id.split('_')[2]; // Extract size from id (auto_calc_S)
                
                if (this.checked) {
                    const harga = parseFloat(document.getElementById(`harga_${size}`).value) || 0;
                    const dp = Math.round(harga * 0.3); // 30% for DP
                    const pelunasan = Math.round(harga * 0.7); // 70% for pelunasan
                    
                    document.getElementById(`dp_${size}`).value = dp;
                    document.getElementById(`pelunasan_${size}`).value = pelunasan;
                }
            });
        });
        
        // Update DP and Pelunasan when price changes and auto-calc is checked
        <?php foreach ($sizes as $size): ?>
        document.getElementById('harga_<?= $size ?>').addEventListener('input', function() {
            const checkbox = document.getElementById('auto_calc_<?= $size ?>');
            if (checkbox.checked) {
                const harga = parseFloat(this.value) || 0;
                const dp = Math.round(harga * 0.3);
                const pelunasan = Math.round(harga * 0.7);
                
                document.getElementById('dp_<?= $size ?>').value = dp;
                document.getElementById('pelunasan_<?= $size ?>').value = pelunasan;
            }
        });
        <?php endforeach; ?>
    </script>
</body>
</html> 