<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

session_start();

$db = Database::getInstance();

// Cek jika keranjang kosong, redirect ke halaman keranjang
$cartData = getCartItems();
if (empty($cartData['items'])) {
    $_SESSION['flash'] = [
        'message' => 'Keranjang belanja Anda kosong',
        'type' => 'error'
    ];
    header('Location: cart.php');
    exit;
}

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerData = [
        'nama' => $_POST['nama'] ?? '',
        'hp' => $_POST['hp'] ?? '',
        'lokasi' => $_POST['lokasi'] ?? '',
        'catatan' => $_POST['catatan'] ?? '',
        'jenis_jaminan' => $_POST['jenis_jaminan'] ?? 'KTP'
    ];
    
    $result = processCheckout($customerData);
    
    if ($result['success']) {
        // Redirect ke halaman sukses
        $_SESSION['checkout_success'] = true;
        $_SESSION['rental_id'] = $result['rental_id'];
        header('Location: order_success.php');
        exit;
    } else {
        // Set error message
        $_SESSION['flash'] = [
            'message' => $result['message'],
            'type' => 'error'
        ];
    }
}

// Data untuk form
$formData = $_POST ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= APP_NAME ?></title>
    
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
                        <h1 class="text-xl font-bold text-[#EBA1A1] font-serif">Pearls Bridal</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="cart.php" class="text-[#EBA1A1] hover:text-[#d97c7c] flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> <span>Kembali ke Keranjang</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-[#EBA1A1]">
                Checkout
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Lengkapi informasi pemesanan Anda
            </p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
        <div class="mx-4 sm:mx-6 mb-4">
            <div class="rounded-md bg-<?= $_SESSION['flash']['type'] === 'error' ? 'red' : 'green' ?>-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'exclamation-circle text-red-400' : 'check-circle text-green-400' ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-<?= $_SESSION['flash']['type'] === 'error' ? 'red' : 'green' ?>-800">
                            <?= $_SESSION['flash']['message'] ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['flash']); endif; ?>

        <!-- Checkout Content -->
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <!-- Order Summary -->
            <div class="md:col-span-1">
                <div class="bg-white shadow overflow-hidden sm:rounded-xl mb-6 border border-[#fde8e8]">
                    <div class="px-4 py-5 sm:px-6 border-b border-[#fde8e8]">
                        <h3 class="text-lg font-medium leading-6 text-[#d97c7c]">Ringkasan Pesanan</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="space-y-3">
                            <div class="font-medium text-gray-900 mb-2">Item (<?= count($cartData['items']) ?>)</div>
                            <?php foreach ($cartData['items'] as $item): ?>
                            <div class="flex justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-700"><?= htmlspecialchars($item['nama_baju']) ?> (<?= htmlspecialchars($item['ukuran']) ?>)</dt>
                                    <dd class="mt-1 text-xs text-gray-500">
                                        <?= $item['quantity'] ?> x <?= formatRupiah($item['dp'] + $item['pelunasan']) ?>
                                    </dd>
                                    <dd class="mt-1 text-xs text-gray-500">
                                        <?= formatTanggalIndo($item['tanggal_sewa']) ?> - <?= formatTanggalIndo($item['tanggal_kembali']) ?>
                                    </dd>
                                </div>
                                <div class="text-sm text-gray-900">
                                    <?= formatRupiah($item['subtotal']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="pt-3">
                                <div class="flex justify-between mb-1">
                                    <dt class="text-sm text-gray-500">Total DP</dt>
                                    <dd class="text-sm font-medium text-gray-900"><?= formatRupiah($cartData['total']['dp']) ?></dd>
                                </div>
                                <div class="flex justify-between mb-1">
                                    <dt class="text-sm text-gray-500">Total Pelunasan</dt>
                                    <dd class="text-sm font-medium text-gray-900"><?= formatRupiah($cartData['total']['pelunasan']) ?></dd>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-3 mt-3">
                                    <dt class="text-base font-medium text-gray-900">Total Keseluruhan</dt>
                                    <dd class="text-base font-medium text-gray-900"><?= formatRupiah($cartData['total']['grand_total']) ?></dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information Form -->
            <div class="md:col-span-2">
                <div class="bg-white shadow overflow-hidden sm:rounded-xl border border-[#fde8e8]">
                    <div class="px-4 py-5 sm:px-6 border-b border-[#fde8e8]">
                        <h3 class="text-lg font-medium leading-6 text-[#d97c7c]">Informasi Pelanggan</h3>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <form method="POST" action="checkout.php">
                            <!-- Catatan tambahan saja -->
                            <div class="grid grid-cols-6 gap-6">
                                <!-- Tampilkan data customer yang sudah ada -->
                                <div class="col-span-6 sm:col-span-4">
                                    <p class="block text-sm font-medium text-gray-700">Nama Lengkap</p>
                                    <p class="mt-1 text-sm text-[#EBA1A1] font-medium">
                                        <?php 
                                        $customerInfo = $_SESSION['customer_info'] ?? [];
                                        $nama = $customerInfo['nama'] ?? ''; 
                                        echo htmlspecialchars($nama);
                                        ?>
                                        <input type="hidden" name="nama" value="<?= htmlspecialchars($nama) ?>">
                                    </p>
                                </div>
                                
                                <div class="col-span-6 sm:col-span-3">
                                    <p class="block text-sm font-medium text-gray-700">Nomor HP</p>
                                    <p class="mt-1 text-sm text-[#EBA1A1] font-medium">
                                        <?php 
                                        $hp = $customerInfo['hp'] ?? ''; 
                                        echo htmlspecialchars($hp);
                                        ?>
                                        <input type="hidden" name="hp" value="<?= htmlspecialchars($hp) ?>">
                                    </p>
                                </div>
                                
                                <div class="col-span-6">
                                    <p class="block text-sm font-medium text-gray-700">Alamat Lengkap</p>
                                    <p class="mt-1 text-sm text-[#EBA1A1] font-medium">
                                        <?php 
                                        $lokasi = $customerInfo['lokasi'] ?? ''; 
                                        echo nl2br(htmlspecialchars($lokasi));
                                        ?>
                                        <input type="hidden" name="lokasi" value="<?= htmlspecialchars($lokasi) ?>">
                                    </p>
                                </div>
                                
                                <div class="col-span-6">
                                    <p class="block text-sm font-medium text-gray-700">Jenis Jaminan</p>
                                    <p class="mt-1 text-sm text-[#EBA1A1] font-medium">
                                        <?php 
                                        $jenis_jaminan = $customerInfo['jenis_jaminan'] ?? 'KTP'; 
                                        echo htmlspecialchars($jenis_jaminan);
                                        ?>
                                        <input type="hidden" name="jenis_jaminan" value="<?= htmlspecialchars($jenis_jaminan) ?>">
                                    </p>
                                </div>
                                
                                <div class="col-span-6">
                                    <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan Tambahan (opsional)</label>
                                    <textarea name="catatan" id="catatan" rows="3"
                                             class="mt-1 focus:ring-[#EBA1A1] focus:border-[#EBA1A1] block w-full shadow-sm sm:text-sm border-[#fde8e8] rounded-md"><?= htmlspecialchars($formData['catatan'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-8 text-right">
                                <button type="submit" class="w-full flex justify-center py-2.5 px-6 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EBA1A1]">
                                    <i class="fas fa-check-circle mr-2"></i> Konfirmasi Pesanan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
                            <li>Senin - Jumat: 10:00 - 19:00</li>
                            <li>Sabtu: 10:00 - 16:00</li>
                            <li>Minggu: Tutup</li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-[#f0c4c4] sm:mx-auto" />
            <div class="text-center">
                <p class="text-sm text-gray-500">&copy; <?= date('Y') ?> Pearls Bridal. Semua hak dilindungi.</p>
                <div class="flex justify-center mt-4 space-x-6">
                    <a href="#" class="text-gray-500 hover:text-[#EBA1A1]">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-[#EBA1A1]">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-[#EBA1A1]">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
