<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

$db = Database::getInstance();

// Mengambil daftar baju dan variannya untuk dropdown
$items = $db->fetchAll("
    SELECT i.id, i.nama_baju, i.kategori 
    FROM items i 
    ORDER BY i.nama_baju ASC
");

$errors = [];
$success = false;

// Proses form ketika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi dan validasi input
    $customerNama = sanitizeInput($_POST['customer_nama'] ?? '');
    $customerHp = sanitizeInput($_POST['customer_hp'] ?? '');
    $customerLokasi = sanitizeInput($_POST['customer_lokasi'] ?? '');
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $tanggalSewa = sanitizeInput($_POST['tanggal_sewa'] ?? '');
    $tanggalKembali = sanitizeInput($_POST['tanggal_kembali'] ?? '');
    $tanggalBooking = sanitizeInput($_POST['tanggal_booking'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $status = sanitizeInput($_POST['status'] ?? 'pending');
    $dpBayar = (float)($_POST['dp_bayar'] ?? 0);
    $pelunasanBayar = (float)($_POST['pelunasan_bayar'] ?? 0);
    $jenisJaminan = sanitizeInput($_POST['jenis_jaminan'] ?? '');

    // Validasi input
    if (empty($customerNama)) {
        $errors[] = "Nama pelanggan wajib diisi";
    }

    if (empty($customerHp) || !validatePhoneNumber($customerHp)) {
        $errors[] = "Nomor HP tidak valid";
    }

    if (empty($customerLokasi)) {
        $errors[] = "Lokasi pelanggan wajib diisi";
    }

    if ($variantId <= 0) {
        $errors[] = "Silakan pilih varian baju";
    }

    if (!validateDate($tanggalSewa) || !validateDate($tanggalKembali)) {
        $errors[] = "Format tanggal tidak valid";
    }

    if (strtotime($tanggalSewa) > strtotime($tanggalKembali)) {
        $errors[] = "Tanggal kembali harus setelah tanggal sewa";
    }

    if ($jumlah <= 0) {
        $errors[] = "Jumlah sewa harus lebih dari 0";
    }

    // Cek ketersediaan stok
    if (!checkStockAvailability($variantId, $tanggalSewa, $tanggalKembali, $jumlah)) {
        $errors[] = "Stok tidak mencukupi pada rentang tanggal yang dipilih";
    }

    // Jika tidak ada error, proses penyimpanan data
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $db->getConnection()->beginTransaction();

            // Simpan data rental
            $rentalId = $db->insert(
                "INSERT INTO rentals (
                    customer_nama, customer_hp, customer_lokasi, variant_id,
                    tanggal_sewa, tanggal_kembali, tanggal_booking, jumlah, dp_bayar,
                    pelunasan_bayar, status, jenis_jaminan,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [
                    $customerNama, $customerHp, $customerLokasi, $variantId,
                    $tanggalSewa, $tanggalKembali, $tanggalBooking, $jumlah, $dpBayar,
                    $pelunasanBayar, $status, $jenisJaminan
                ]
            );

            // Update stok untuk setiap tanggal dalam rentang
            $dateRange = getRentalDateRange($tanggalSewa, $tanggalKembali);
            foreach ($dateRange as $date) {
                updateStock($variantId, $date, $jumlah);
            }

            // Commit transaksi
            $db->getConnection()->commit();
            $success = true;
            
            // Redirect ke halaman daftar rental
            redirect("fixed_rentals.php", "Data pemesanan berhasil ditambahkan", "success");
        } catch (Exception $e) {
            // Rollback jika terjadi error
            $db->getConnection()->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Fungsi untuk mendapatkan varian berdasarkan item_id
function getVariants($itemId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT id, ukuran, stok_total, harga, dp, pelunasan, kode_unik 
         FROM item_variants 
         WHERE item_id = ? 
         ORDER BY ukuran ASC",
        [$itemId]
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Penyewaan Baru - <?= APP_NAME ?></title>
    
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
                    <a href="add_rental.php" class="border-indigo-500 text-indigo-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Tambah Penyewaan</a>
                    <a href="view_products.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Daftar Produk</a>
                    <a href="add_product.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Tambah Produk</a>
                </div>
            </div>
            <!-- Mobile Menu, hidden by default -->
            <div id="mobile-menu" class="sm:hidden hidden mt-2">
                <a href="rental_list.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Daftar Semua Pemesanan</a>
                <a href="fixed_rentals.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Daftar Pemesanan Fix</a>
                <a href="add_rental.php" class="block px-3 py-2 rounded-md text-base font-medium text-indigo-700 bg-indigo-50">Tambah Penyewaan</a>
                <a href="view_products.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Daftar Produk</a>
                <a href="add_product.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100">Tambah Produk</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">
                Tambah Penyewaan Baru
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Formulir untuk menambahkan data penyewaan baju baru
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

        <!-- Add Rental Form -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <form action="add_rental.php" method="post" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Informasi Pelanggan</h3>
                        
                        <div>
                            <label for="customer_nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" name="customer_nama" id="customer_nama" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                value="<?= $_POST['customer_nama'] ?? '' ?>">
                        </div>
                        
                        <div>
                            <label for="customer_hp" class="block text-sm font-medium text-gray-700">Nomor HP</label>
                            <input type="text" name="customer_hp" id="customer_hp" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                value="<?= $_POST['customer_hp'] ?? '' ?>">
                        </div>
                        
                        <div>
                            <label for="customer_lokasi" class="block text-sm font-medium text-gray-700">Alamat/Lokasi</label>
                            <textarea name="customer_lokasi" id="customer_lokasi" required rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?= $_POST['customer_lokasi'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <!-- Rental Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Informasi Penyewaan</h3>
                        
                        <div>
                            <label for="item_id" class="block text-sm font-medium text-gray-700">Pilih Baju</label>
                            <select id="item_id" 
                                class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Baju --</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['nama_baju']) ?> (<?= htmlspecialchars($item['kategori']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="variant_id" class="block text-sm font-medium text-gray-700">Varian/Ukuran</label>
                            <select name="variant_id" id="variant_id" required
                                class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Item Terlebih Dahulu --</option>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="tanggal_sewa" class="block text-sm font-medium text-gray-700">Tanggal Sewa</label>
                                <input type="date" name="tanggal_sewa" id="tanggal_sewa" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    value="<?= $_POST['tanggal_sewa'] ?? date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                                <input type="date" name="tanggal_kembali" id="tanggal_kembali" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    value="<?= $_POST['tanggal_kembali'] ?? date('Y-m-d', strtotime('+3 days')) ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label for="tanggal_booking" class="block text-sm font-medium text-gray-700">Tanggal Booking</label>
                            <input type="date" name="tanggal_booking" id="tanggal_booking"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                value="<?= $_POST['tanggal_booking'] ?? '' ?>">
                            <p class="mt-1 text-xs text-gray-500">Tanggal untuk fitting/booking baju sebelum tanggal sewa (opsional).</p>
                        </div>
                        
                        <div>
                            <label for="jumlah" class="block text-sm font-medium text-gray-700">Jumlah</label>
                            <input type="number" name="jumlah" id="jumlah" min="1" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                value="<?= $_POST['jumlah'] ?? '1' ?>">
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" required
                                class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                <option value="approved" <?= ($_POST['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                                <option value="returned" <?= ($_POST['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Dikembalikan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Pembayaran</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="dp_bayar" class="block text-sm font-medium text-gray-700">DP (Uang Muka)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">
                                        Rp
                                    </span>
                                </div>
                                <input type="number" name="dp_bayar" id="dp_bayar" min="0" step="1000" required
                                    class="block w-full pl-10 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    value="<?= $_POST['dp_bayar'] ?? '0' ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label for="pelunasan_bayar" class="block text-sm font-medium text-gray-700">Pelunasan</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">
                                        Rp
                                    </span>
                                </div>
                                <input type="number" name="pelunasan_bayar" id="pelunasan_bayar" min="0" step="1000" required
                                    class="block w-full pl-10 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    value="<?= $_POST['pelunasan_bayar'] ?? '0' ?>">
                            </div>
                        </div>
                    </div>

                    <div id="harga_info" class="bg-gray-50 p-4 rounded-md hidden">
                        <h4 class="font-medium text-gray-700">Informasi Harga Varian</h4>
                        <div class="mt-2 grid grid-cols-3 gap-4">
                            <div>
                                <span class="block text-sm text-gray-500">Harga Total</span>
                                <span id="harga_total" class="font-medium"></span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500">DP (Minimal)</span>
                                <span id="harga_dp" class="font-medium"></span>
                            </div>
                            <div>
                                <span class="block text-sm text-gray-500">Pelunasan</span>
                                <span id="harga_pelunasan" class="font-medium"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Jaminan -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Jaminan</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="jenis_jaminan" class="block text-sm font-medium text-gray-700">Jenis Jaminan</label>
                            <select name="jenis_jaminan" id="jenis_jaminan"
                                class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Jenis Jaminan --</option>
                                <option value="KTP" <?= ($_POST['jenis_jaminan'] ?? '') === 'KTP' ? 'selected' : '' ?>>KTP</option>
                                <option value="SIM" <?= ($_POST['jenis_jaminan'] ?? '') === 'SIM' ? 'selected' : '' ?>>SIM</option>
                                <option value="Kartu Pelajar" <?= ($_POST['jenis_jaminan'] ?? '') === 'Kartu Pelajar' ? 'selected' : '' ?>>Kartu Pelajar</option>
                                <option value="Kartu Keluarga" <?= ($_POST['jenis_jaminan'] ?? '') === 'Kartu Keluarga' ? 'selected' : '' ?>>Kartu Keluarga</option>
                                <option value="Lainnya" <?= ($_POST['jenis_jaminan'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex justify-end">
                    <a href="fixed_rentals.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Script untuk mendapatkan varian ketika item dipilih
        document.getElementById('item_id').addEventListener('change', function() {
            const itemId = this.value;
            const variantSelect = document.getElementById('variant_id');
            
            // Reset variant dropdown
            variantSelect.innerHTML = '<option value="">-- Pilih Varian --</option>';
            document.getElementById('harga_info').classList.add('hidden');
            
            if (!itemId) return;
            
            // Fetch variants
            fetch(`get_variants.php?item_id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        variantSelect.innerHTML = '<option value="">Tidak ada varian tersedia</option>';
                        return;
                    }
                    
                    data.forEach(variant => {
                        const option = document.createElement('option');
                        option.value = variant.id;
                        option.textContent = `${variant.ukuran} - Stok: ${variant.stok_total} - Kode: ${variant.kode_unik}`;
                        option.dataset.harga = variant.harga;
                        option.dataset.dp = variant.dp;
                        option.dataset.pelunasan = variant.pelunasan;
                        variantSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    variantSelect.innerHTML = '<option value="">Error loading variants</option>';
                });
        });
        
        // Script untuk menampilkan informasi harga ketika varian dipilih
        document.getElementById('variant_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const hargaInfo = document.getElementById('harga_info');
            
            if (this.value) {
                const harga = parseFloat(selectedOption.dataset.harga);
                const dp = parseFloat(selectedOption.dataset.dp);
                const pelunasan = parseFloat(selectedOption.dataset.pelunasan);
                
                document.getElementById('harga_total').textContent = formatRupiah(harga);
                document.getElementById('harga_dp').textContent = formatRupiah(dp);
                document.getElementById('harga_pelunasan').textContent = formatRupiah(pelunasan);
                
                document.getElementById('dp_bayar').value = dp;
                document.getElementById('pelunasan_bayar').value = pelunasan;
                
                hargaInfo.classList.remove('hidden');
            } else {
                hargaInfo.classList.add('hidden');
            }
        });
        
        // Format Rupiah function
        function formatRupiah(amount) {
            return 'Rp ' + amount.toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&.');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            menuToggle && menuToggle.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html> 