<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

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
                    variant_id, tanggal_sewa, tanggal_kembali,
                    jumlah, dp_bayar, pelunasan_bayar, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [
                    $nama, $hp, $lokasi,
                    $variantId, $tanggal_sewa, $tanggal_kembali,
                    $jumlah, $total_dp, $total_pelunasan
                ]
            );
            
            // Update stok untuk setiap tanggal
            $dates = getRentalDateRange($tanggal_sewa, $tanggal_kembali);
            foreach ($dates as $date) {
                updateStock($variantId, $date, $jumlah);
            }
            
            redirect('index.php', 'Pemesanan berhasil dibuat! Silakan tunggu konfirmasi dari admin.', 'success');
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
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800"><?= APP_NAME ?></h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Katalog
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">
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
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Gambar dan Info Baju -->
                    <div>
                        <?php if ($item['foto']): ?>
                        <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>"
                             alt="<?= htmlspecialchars($item['nama_baju']) ?>"
                             class="w-full h-64 object-cover rounded-lg">
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
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Data Penyewa</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="nama" class="block text-sm font-medium text-gray-700">
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
                                        <label for="hp" class="block text-sm font-medium text-gray-700">
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
                                        <label for="lokasi" class="block text-sm font-medium text-gray-700">
                                            Alamat Lengkap
                                        </label>
                                        <textarea name="lokasi" 
                                                  id="lokasi"
                                                  rows="3"
                                                  required
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?= isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : '' ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Pemesanan -->
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Data Pemesanan</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="tanggal_sewa" class="block text-sm font-medium text-gray-700">
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
                                        <label for="jumlah" class="block text-sm font-medium text-gray-700">
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
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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

    <!-- JavaScript untuk kalkulasi harga -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
