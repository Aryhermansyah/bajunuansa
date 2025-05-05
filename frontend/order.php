<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

session_start();

// Validasi parameter
if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    redirect('index.php', 'ID baju tidak valid', 'error');
}

$itemId = (int)$_GET['item_id'];
$tanggal_sewa = isset($_GET['tanggal_sewa']) ? $_GET['tanggal_sewa'] : date('Y-m-d');
if (!validateDate($tanggal_sewa)) {
    redirect('index.php', 'Tanggal sewa tidak valid', 'error');
}

$db = Database::getInstance();

// Ambil detail baju
$item = $db->fetchOne(
    "SELECT i.*, 
            GROUP_CONCAT(DISTINCT iv.ukuran) as ukuran_tersedia
     FROM items i
     LEFT JOIN item_variants iv ON i.id = iv.item_id
     WHERE i.id = ?
     GROUP BY i.id",
    [$itemId]
);

if (!$item) {
    redirect('index.php', 'Baju tidak ditemukan', 'error');
}

// Ambil variant berdasarkan ukuran dan stok tersedia
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
     WHERE iv.item_id = ?
     AND (iv.stok_total - COALESCE(ia.stok_terpakai, 0)) > 0",
    [$tanggal_sewa, $itemId]
);

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitizeInput($_POST['nama'] ?? '');
    $hp = sanitizeInput($_POST['hp'] ?? '');
    $lokasi = sanitizeInput($_POST['lokasi'] ?? '');
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $tanggal_kembali = sanitizeInput($_POST['tanggal_kembali'] ?? '');
    $tanggal_booking = sanitizeInput($_POST['tanggal_booking'] ?? '');
    $catatan = sanitizeInput($_POST['catatan'] ?? '');
    $jenis_jaminan = sanitizeInput($_POST['jenis_jaminan'] ?? '');
    
    $errors = [];
    
    // Validasi input
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($hp) || !validatePhoneNumber($hp)) $errors[] = "Nomor HP tidak valid";
    if (empty($lokasi)) $errors[] = "Lokasi harus diisi";
    if ($variantId <= 0) $errors[] = "Pilih ukuran baju";
    if ($jumlah <= 0) $errors[] = "Jumlah harus lebih dari 0";
    if (!validateDate($tanggal_kembali)) $errors[] = "Tanggal kembali tidak valid";
    if (strtotime($tanggal_kembali) <= strtotime($tanggal_sewa)) {
        $errors[] = "Tanggal kembali harus setelah tanggal sewa";
    }
    
    // Cek ketersediaan stok
    if (empty($errors)) {
        if (!checkStockAvailability($variantId, $tanggal_sewa, $tanggal_kembali, $jumlah)) {
            $errors[] = "Stok tidak mencukupi untuk periode sewa yang dipilih";
        }
    }
    
    if (empty($errors)) {
        try {
            // Ambil detail variant
            $variant = $db->fetchOne(
                "SELECT * FROM item_variants WHERE id = ?",
                [$variantId]
            );
            
            // Hitung total pembayaran
            $total_dp = $variant['dp'] * $jumlah;
            $total_pelunasan = $variant['pelunasan'] * $jumlah;
            
            // Insert rental
            $rentalId = $db->insert(
                "INSERT INTO rentals (
                    customer_nama, customer_hp, customer_lokasi,
                    variant_id, tanggal_sewa, tanggal_kembali, tanggal_booking,
                    jumlah, dp_bayar, pelunasan_bayar, status, catatan,
                    jenis_jaminan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)",
                [
                    $nama, $hp, $lokasi,
                    $variantId, $tanggal_sewa, $tanggal_kembali, $tanggal_booking,
                    $jumlah, $total_dp, $total_pelunasan, $catatan,
                    $jenis_jaminan
                ]
            );
            
            // Update stok untuk setiap tanggal
            $dates = getRentalDateRange($tanggal_sewa, $tanggal_kembali);
            foreach ($dates as $date) {
                updateStock($variantId, $date, $jumlah);
            }
            
            header('Location: invoice.php?id=' . $rentalId);
            exit;
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan - <?= APP_NAME ?></title>
    
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
<body class="bg-[#fff5f5]">
    <style>
        :root {
            --pink-main: #EBA1A1;
            --pink-light: #fde8e8;
            --pink-dark: #d97c7c;
        }
        .bg-custom-pink { background-color: var(--pink-main); }
        .from-custom-pink { --tw-gradient-from: var(--pink-main); }
        .to-custom-pink { --tw-gradient-to: var(--pink-dark); }
        .hover\:from-custom-pink-dark:hover { --tw-gradient-from: var(--pink-dark); }
        .hover\:to-custom-pink-darker:hover { --tw-gradient-to: #c56e6e; }
        .text-custom-pink { color: var(--pink-main); }
        .border-custom-pink { border-color: var(--pink-main); }
        .border-custom-pink-light { border-color: var(--pink-light); }
    </style>
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-[#fde8e8] to-[#fad1d1] shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php">
                            <h1 class="text-xl font-bold text-[#EBA1A1] font-serif">Pearls Bridal</h1>
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="cart.php" class="text-[#EBA1A1] hover:text-[#d97c7c] flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i> <span>Keranjang</span>
                        <span class="ml-1 text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full"><?= getCartItemCount() ?></span>
                    </a>
                    <a href="index.php" class="text-[#EBA1A1] hover:text-[#d97c7c] flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-[#d97c7c]">
                Form Pemesanan
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Silakan lengkapi data pemesanan di bawah ini
            </p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="rounded-md bg-red-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Terdapat <?= count($errors) ?> kesalahan:
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

        <!-- Item Details -->
        <div class="bg-white shadow overflow-hidden sm:rounded-xl mb-6 border border-[#fde8e8]">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Gambar dan Info Baju -->
                    <div>
                        <?php if ($item['foto']): ?>
                        <div style="height: 300px; overflow: hidden;">
                            <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>"
                                 alt="<?= htmlspecialchars($item['nama_baju']) ?>"
                                 style="width: 100%; height: 100%; object-fit: contain; object-position: center;">
                        </div>
                        <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tshirt text-gray-400 text-4xl"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <?= htmlspecialchars($item['nama_baju']) ?>
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                <?= htmlspecialchars($item['kategori']) ?>
                            </p>
                            <?php if ($item['deskripsi']): ?>
                            <p class="mt-2 text-sm text-gray-600">
                                <?= nl2br(htmlspecialchars($item['deskripsi'])) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form Pemesanan -->
                    <div>
                        <form action="" method="POST" class="space-y-6">
                            <!-- Data Penyewa -->
                            <div>
                                <h4 class="text-lg font-medium text-[#d97c7c] mb-4">Data Penyewa</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="nama" class="block text-sm font-medium text-[#EBA1A1]">
                                            Nama Lengkap
                                        </label>
                                        <input type="text" 
                                               name="nama" 
                                               id="nama"
                                               value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                                               required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label for="hp" class="block text-sm font-medium text-[#EBA1A1]">
                                            Nomor HP
                                        </label>
                                        <input type="tel" 
                                               name="hp" 
                                               id="hp"
                                               value="<?= isset($_POST['hp']) ? htmlspecialchars($_POST['hp']) : '' ?>"
                                               required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label for="lokasi" class="block text-sm font-medium text-[#EBA1A1]">
                                            Alamat Lengkap
                                        </label>
                                        <textarea name="lokasi" 
                                                  id="lokasi"
                                                  rows="3"
                                                  required
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?= isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : '' ?></textarea>
                                    </div>

                                    <div>
                                        <label for="catatan" class="block text-sm font-medium text-gray-700">
                                            Catatan
                                        </label>
                                        <textarea name="catatan" 
                                                  id="catatan"
                                                  rows="3"
                                                  placeholder="Tuliskan catatan khusus untuk pesanan Anda (opsional)"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?= isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : '' ?></textarea>
                                    </div>

                                    <div>
                                        <label for="jenis_jaminan" class="block text-sm font-medium text-[#EBA1A1]">
                                            Jenis Jaminan <span class="text-red-500">*</span>
                                        </label>
                                        <select name="jenis_jaminan" 
                                                id="jenis_jaminan"
                                                required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Pilih Jenis Jaminan --</option>
                                            <option value="KTP" <?= isset($_POST['jenis_jaminan']) && $_POST['jenis_jaminan'] == 'KTP' ? 'selected' : '' ?>>KTP</option>
                                            <option value="SIM" <?= isset($_POST['jenis_jaminan']) && $_POST['jenis_jaminan'] == 'SIM' ? 'selected' : '' ?>>SIM</option>
                                            <option value="Kartu Pelajar" <?= isset($_POST['jenis_jaminan']) && $_POST['jenis_jaminan'] == 'Kartu Pelajar' ? 'selected' : '' ?>>Kartu Pelajar</option>
                                            <option value="Kartu Keluarga" <?= isset($_POST['jenis_jaminan']) && $_POST['jenis_jaminan'] == 'Kartu Keluarga' ? 'selected' : '' ?>>Kartu Keluarga</option>
                                            <option value="Lainnya" <?= isset($_POST['jenis_jaminan']) && $_POST['jenis_jaminan'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Pemesanan -->
                            <div>
                                <h4 class="text-lg font-medium text-[#d97c7c] mb-4">Data Pemesanan</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="tanggal_sewa" class="block text-sm font-medium text-[#EBA1A1]">
                                            Tanggal Sewa
                                        </label>
                                        <input type="date" 
                                               name="tanggal_sewa" 
                                               id="tanggal_sewa"
                                               value="<?= htmlspecialchars($tanggal_sewa) ?>"
                                               readonly
                                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm">
                                    </div>

                                    <div>
                                        <label for="tanggal_booking" class="block text-sm font-medium text-gray-700">
                                            Tanggal Booking <span class="text-xs text-gray-500">(opsional)</span>
                                        </label>
                                        <input type="date" 
                                               name="tanggal_booking" 
                                               id="tanggal_booking"
                                               value="<?= isset($_POST['tanggal_booking']) ? htmlspecialchars($_POST['tanggal_booking']) : date('Y-m-d') ?>"
                                               max="<?= date('Y-m-d', strtotime($tanggal_sewa . ' -1 day')) ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <p class="mt-1 text-xs text-gray-500">Tanggal untuk fitting/booking baju sebelum tanggal sewa.</p>
                                    </div>

                                    <div>
                                        <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700">
                                            Tanggal Kembali
                                        </label>
                                        <input type="date" 
                                               name="tanggal_kembali" 
                                               id="tanggal_kembali"
                                               value="<?= isset($_POST['tanggal_kembali']) ? htmlspecialchars($_POST['tanggal_kembali']) : '' ?>"
                                               min="<?= date('Y-m-d', strtotime($tanggal_sewa . ' +1 day')) ?>"
                                               required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label for="variant_id" class="block text-sm font-medium text-gray-700">
                                            Ukuran
                                        </label>
                                        <select name="variant_id" 
                                                id="variant_id"
                                                required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Pilih Ukuran</option>
                                            <?php foreach ($variants as $variant): ?>
                                            <?php $stokTersedia = $variant['stok_total'] - $variant['stok_terpakai']; ?>
                                            <option value="<?= $variant['id'] ?>"
                                                    data-harga="<?= $variant['harga'] ?>"
                                                    data-dp="<?= $variant['dp'] ?>"
                                                    data-pelunasan="<?= $variant['pelunasan'] ?>"
                                                    data-stok="<?= $stokTersedia ?>"
                                                    <?= isset($_POST['variant_id']) && $_POST['variant_id'] == $variant['id'] ? 'selected' : '' ?>>
                                                <?= $variant['ukuran'] ?> (Stok: <?= $stokTersedia ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="jumlah" class="block text-sm font-medium text-[#EBA1A1]">
                                            Jumlah
                                        </label>
                                        <input type="number" 
                                               name="jumlah" 
                                               id="jumlah"
                                               value="<?= isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1 ?>"
                                               min="1"
                                               required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Tambahkan ke Keranjang -->
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-[#d97c7c] mb-4">Opsi Pemesanan</h4>
                                
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-4">
                                    <div>
                                        <button type="button" id="add-to-cart-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none w-full md:w-auto">
                                            <i class="fas fa-shopping-cart mr-2"></i> Tambah ke Keranjang
                                        </button>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <p>atau</p>
                                    </div>
                                    <div>
                                        <button type="submit" class="w-full inline-flex justify-center py-2.5 px-6 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EBA1A1]" id="submit-order-btn">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Buat Pesanan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Pembayaran -->
                            <div id="payment-info" class="hidden">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Informasi Pembayaran</h4>
                                
                                <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                                    <p class="text-sm">
                                        <span class="font-medium">Harga per item:</span>
                                        <span id="harga-per-item"></span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Total DP:</span>
                                        <span id="total-dp"></span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Total Pelunasan:</span>
                                        <span id="total-pelunasan"></span>
                                    </p>
                                    <p class="text-sm font-medium text-indigo-600">
                                        <span class="font-medium">Total Pembayaran:</span>
                                        <span id="total-pembayaran"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit"
                                        class="w-full inline-flex justify-center py-2.5 px-6 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EBA1A1]">
                                    <i class="fas fa-shopping-cart mr-2"></i>
                                    Buat Pesanan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript untuk kalkulasi harga dan tambah ke keranjang -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tombol Tambah ke Keranjang
            const addToCartBtn = document.getElementById('add-to-cart-btn');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const variantId = document.querySelector('select[name="variant_id"]').value;
                    const quantity = document.querySelector('input[name="jumlah"]').value;
                    const tanggalSewa = document.querySelector('input[name="tanggal_sewa"]').value;
                    const tanggalKembali = document.querySelector('input[name="tanggal_kembali"]').value;
                    
                    if (!variantId || variantId <= 0) {
                        alert('Silakan pilih ukuran baju terlebih dahulu');
                        return;
                    }
                    
                    if (!quantity || quantity <= 0) {
                        alert('Jumlah harus lebih dari 0');
                        return;
                    }
                    
                    if (!tanggalSewa) {
                        alert('Silakan pilih tanggal sewa');
                        return;
                    }
                    
                    if (!tanggalKembali) {
                        alert('Silakan pilih tanggal kembali');
                        return;
                    }
                    
                    if (new Date(tanggalKembali) <= new Date(tanggalSewa)) {
                        alert('Tanggal kembali harus setelah tanggal sewa');
                        return;
                    }
                    
                    // Kirim data ke add_to_cart.php
                    const formData = new FormData();
                    formData.append('variant_id', variantId);
                    formData.append('quantity', quantity);
                    formData.append('tanggal_sewa', tanggalSewa);
                    formData.append('tanggal_kembali', tanggalKembali);
                    
                    // Tambahkan informasi pelanggan juga
                    const namaElement = document.querySelector('input[name="nama"]');
                    const hpElement = document.querySelector('input[name="hp"]');
                    const lokasiElement = document.querySelector('textarea[name="lokasi"]');
                    const jenisJaminanElement = document.querySelector('select[name="jenis_jaminan"]');
                    
                    // Ambil nilai jika elemen ditemukan
                    if (namaElement) formData.append('nama', namaElement.value);
                    if (hpElement) formData.append('hp', hpElement.value);
                    if (lokasiElement) formData.append('lokasi', lokasiElement.value);
                    if (jenisJaminanElement) formData.append('jenis_jaminan', jenisJaminanElement.value);
                    
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (confirm(data.message + '. Apakah Anda ingin melihat keranjang?')) {
                                window.location.href = 'cart.php';
                            }
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menambahkan item ke keranjang');
                    });
                });
            }
            const variantSelect = document.getElementById('variant_id');
            const jumlahInput = document.getElementById('jumlah');
            const paymentInfo = document.getElementById('payment-info');
            const hargaPerItem = document.getElementById('harga-per-item');
            const totalDP = document.getElementById('total-dp');
            const totalPelunasan = document.getElementById('total-pelunasan');
            const totalPembayaran = document.getElementById('total-pembayaran');
            
            function formatRupiah(nominal) {
                return 'Rp ' + nominal.toLocaleString('id-ID');
            }
            
            function updatePaymentInfo() {
                const selectedOption = variantSelect.options[variantSelect.selectedIndex];
                if (selectedOption.value) {
                    const harga = parseInt(selectedOption.dataset.harga);
                    const dp = parseInt(selectedOption.dataset.dp);
                    const pelunasan = parseInt(selectedOption.dataset.pelunasan);
                    const jumlah = parseInt(jumlahInput.value) || 0;
                    
                    hargaPerItem.textContent = formatRupiah(harga);
                    totalDP.textContent = formatRupiah(dp * jumlah);
                    totalPelunasan.textContent = formatRupiah(pelunasan * jumlah);
                    totalPembayaran.textContent = formatRupiah((dp + pelunasan) * jumlah);
                    
                    paymentInfo.classList.remove('hidden');
                } else {
                    paymentInfo.classList.add('hidden');
                }
            }
            
            variantSelect.addEventListener('change', updatePaymentInfo);
            jumlahInput.addEventListener('input', updatePaymentInfo);
            
            // Update initial state
            updatePaymentInfo();
        });
    </script>
</body>
</html>
