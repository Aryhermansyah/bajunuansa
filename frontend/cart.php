<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/cart_functions.php';

session_start();

$db = Database::getInstance();

// Proses aksi keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    
    $response = [];
    
    switch ($action) {
        case 'update':
            $response = updateCartItem($cartId, $quantity);
            break;
        case 'remove':
            $response = removeCartItem($cartId);
            break;
        case 'clear':
            $response = clearCart();
            break;
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Set flash message
        $_SESSION['flash'] = [
            'message' => $response['message'],
            'type' => $response['success'] ? 'success' : 'error'
        ];
        
        // Redirect kembali ke halaman keranjang
        header('Location: cart.php');
        exit;
    }
}

// Ambil data keranjang
$cartData = getCartItems();
$cartItems = $cartData['items'];
$cartTotal = $cartData['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - <?= APP_NAME ?></title>
    
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
                    <a href="index.php" class="text-[#EBA1A1] hover:text-[#d97c7c] flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> <span>Kembali ke Katalog</span>
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
                Keranjang Belanja
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Kelola item yang akan disewa
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

        <!-- Tombol Tambah Produk Baru -->
        <div class="flex justify-end mb-4">
            <a href="index.php" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300">
                <i class="fas fa-plus mr-2"></i> Tambah Produk Baru
            </a>
        </div>
        <!-- Cart Content -->
        <?php if (empty($cartItems)): ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-xl mb-6 border border-[#fde8e8]">
            <div class="px-4 py-12 text-center">
                <i class="fas fa-shopping-cart text-[#EBA1A1] text-5xl mb-4"></i>
                <h3 class="text-lg font-medium text-[#d97c7c] mb-1">Keranjang belanja Anda kosong</h3>
                <p class="text-gray-500 mb-6">Silakan pilih produk yang ingin Anda sewa</p>
                <a href="index.php" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali Berbelanja
                </a>
            </div>
        </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-xl mb-6 border border-[#fde8e8]">
                <!-- Cart Items -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#fff5f5]">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Produk
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Ukuran
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Tanggal Sewa
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Jumlah
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Harga
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Subtotal
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#d97c7c] uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($item['foto']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama_baju']) ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-tshirt text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($item['nama_baju']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($item['kategori']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($item['ukuran']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= formatTanggalIndo($item['tanggal_sewa']) ?> -<br>
                                        <?= formatTanggalIndo($item['tanggal_kembali']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        (<?= $item['jumlah_hari'] ?> hari)
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" max="<?= $item['stok_total'] ?>" 
                                               class="w-16 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300">
                                        <button type="submit" class="ml-2 inline-flex items-center p-1 border border-transparent rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= formatRupiah($item['dp'] + $item['pelunasan']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        DP: <?= formatRupiah($item['dp']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= formatRupiah($item['subtotal']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="text-[#EBA1A1] hover:text-[#d97c7c]">
                                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Cart Summary -->
                <div class="bg-[#fff5f5] px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    <i class="fas fa-trash-alt mr-2"></i> Kosongkan Keranjang
                                </button>
                            </form>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600 mb-1">
                                Total DP: <span class="font-semibold"><?= formatRupiah($cartTotal['dp']) ?></span>
                            </p>
                            <p class="text-sm text-gray-600 mb-1">
                                Total Pelunasan: <span class="font-semibold"><?= formatRupiah($cartTotal['pelunasan']) ?></span>
                            </p>
                            <p class="text-base font-bold text-gray-900">
                                Total Keseluruhan: <span><?= formatRupiah($cartTotal['grand_total']) ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Button -->
            <div class="flex justify-end px-4 sm:px-6">
                <a href="checkout.php" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] transition-all duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i> Lanjutkan ke Checkout
                </a>
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

    <!-- JavaScript -->
    <script>
        // Form validation and AJAX updates could be added here
    </script>
</body>
</html>
