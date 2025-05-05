<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Cek apakah ada checkout yang sukses
if (!isset($_SESSION['checkout_success']) || !$_SESSION['checkout_success']) {
    header('Location: index.php');
    exit;
}

// Ambil rental_id dari session atau dari parameter URL
$rentalId = $_SESSION['rental_id'] ?? ($_GET['id'] ?? 0);

// Reset session checkout setelah mengambil ID
if (isset($_SESSION['checkout_success'])) {
    $_SESSION['checkout_success'] = false;
}
if (isset($_SESSION['rental_id'])) {
    unset($_SESSION['rental_id']);
}

// Tambahkan logging untuk debugging
file_put_contents('../logs/order_success.log', date('Y-m-d H:i:s') . " - Rental ID: {$rentalId}\n", FILE_APPEND);

// Ambil data pesanan jika ada rental ID
$orderDetails = null;
if ($rentalId > 0) {
    $db = Database::getInstance();
    try {
        // Ambil data rental langsung dari tabel rentals dan join dengan item_variants dan items
        $rental = $db->fetchOne(
            "SELECT r.*, iv.ukuran, i.nama_baju, i.foto, i.kategori
             FROM rentals r
             LEFT JOIN item_variants iv ON r.variant_id = iv.id
             LEFT JOIN items i ON iv.item_id = i.id 
             WHERE r.id = ?",
            [$rentalId]
        );
        
        if ($rental) {
            // Jika tabel rental_items digunakan, ambil item-item dalam pesanan
            try {
                $items = $db->fetchAll(
                    "SELECT ri.*, v.ukuran, i.nama_baju, i.foto, i.kategori
                     FROM rental_items ri
                     JOIN item_variants v ON ri.variant_id = v.id
                     JOIN items i ON v.item_id = i.id
                     WHERE ri.rental_id = ?",
                    [$rentalId]
                );
            } catch (Exception $e) {
                // Jika error (misal tabel rental_items belum ada), gunakan data dari rental
                $items = [
                    [
                        'rental_id' => $rental['id'],
                        'variant_id' => $rental['variant_id'],
                        'jumlah' => $rental['jumlah'],
                        'harga' => $rental['dp_bayar'] + $rental['pelunasan_bayar'],
                        'ukuran' => $rental['ukuran'],
                        'nama_baju' => $rental['nama_baju'],
                        'foto' => $rental['foto'],
                        'kategori' => $rental['kategori']
                    ]
                ];
            }
            
            $orderDetails = [
                'rental' => $rental,
                'items' => !empty($items) ? $items : []
            ];
            
            // Log jika ditemukan
            file_put_contents('../logs/order_success.log', date('Y-m-d H:i:s') . " - Pesanan ditemukan dengan ID: {$rentalId}\n", FILE_APPEND);
        } else {
            // Log jika tidak ditemukan
            file_put_contents('../logs/order_success.log', date('Y-m-d H:i:s') . " - Pesanan tidak ditemukan dengan ID: {$rentalId}\n", FILE_APPEND);
            
            // Redirect ke halaman utama dengan pesan error
            $_SESSION['flash_message'] = "ID pesanan tidak valid. Silakan periksa kembali atau hubungi admin."; 
            $_SESSION['flash_type'] = "danger";
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        // Log error
        file_put_contents('../logs/order_success.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - <?= APP_NAME ?></title>
    
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
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 whitespace-nowrap">Pearls Bridal</h1>
                </div>
                <div class="flex items-center">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 flex items-center">
                        <i class="fas fa-home mr-2"></i> <span>Halaman Utama</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-xl mb-6 border border-[#fde8e8]">
            <div class="px-4 py-5 sm:px-6 text-center">
                <i class="fas fa-check-circle text-[#EBA1A1] text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-[#d97c7c]">
                    Pesanan Berhasil!
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Terima kasih telah melakukan pemesanan di Pearls Bridal
                </p>
            </div>
            
            <?php if ($orderDetails): ?>
            <div class="border-t border-[#fde8e8] px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-[#d97c7c] mb-4">Detail Pesanan</h3>
                
                <div class="bg-[#fff5f5] rounded-lg p-4 mb-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nomor Pesanan</dt>
                            <dd class="mt-1 text-sm text-gray-900">#<?= $orderDetails['rental']['id'] ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tanggal Pemesanan</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= formatTanggalIndo($orderDetails['rental']['tanggal_booking']) ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Pelanggan</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($orderDetails['rental']['customer_nama']) ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nomor Telepon</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($orderDetails['rental']['customer_hp']) ?></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($orderDetails['rental']['customer_lokasi'])) ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status Pesanan</dt>
                            <dd class="mt-1 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                    <?= ucfirst(htmlspecialchars($orderDetails['rental']['status'])) ?>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Jenis Jaminan</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($orderDetails['rental']['jenis_jaminan']) ?></dd>
                        </div>
                    </dl>
                </div>
                
                <h4 class="text-md font-medium text-gray-900 mb-3">Item yang Dipesan</h4>
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
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orderDetails['items'] as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($item['foto'])): ?>
    <?php 
    $fotoPath = $item['foto'];
    // Jika sudah URL absolut
    if (filter_var($fotoPath, FILTER_VALIDATE_URL)) {
        $src = $fotoPath;
    } else {
        // Jika hanya nama file atau path relatif, pastikan menuju ke assets/images
        $src = '../assets/images/' . ltrim(basename($fotoPath), '/');
    }
    ?>
    <img class="h-10 w-10 rounded-full object-cover" src="<?= $src ?>" alt="<?= htmlspecialchars($item['nama_baju']) ?>">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= formatRupiah($item['price'] * $item['quantity']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        DP: <?= formatRupiah($item['dp'] * $item['quantity']) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6 border-t border-[#fde8e8] pt-6">
                    <div class="flex justify-end">
                        <dl class="space-y-2 text-right">
                            <div class="flex justify-end">
                                <dt class="text-sm font-medium text-gray-500 mr-6">Total DP:</dt>
                                <dd class="text-sm font-medium text-gray-900"><?= formatRupiah($orderDetails['rental']['dp_bayar']) ?></dd>
                            </div>
                            <div class="flex justify-end">
                                <dt class="text-sm font-medium text-gray-500 mr-6">Total Pelunasan:</dt>
                                <dd class="text-sm font-medium text-gray-900"><?= formatRupiah($orderDetails['rental']['pelunasan_bayar']) ?></dd>
                            </div>
                            <div class="flex justify-end border-t border-gray-200 pt-2">
                                <dt class="text-base font-medium text-gray-900 mr-6">Total Keseluruhan:</dt>
                                <dd class="text-base font-medium text-gray-900"><?= formatRupiah($orderDetails['rental']['dp_bayar'] + $orderDetails['rental']['pelunasan_bayar']) ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6 text-center">
                <p class="text-gray-600">
                    Pesanan Anda telah berhasil diproses. Kami akan segera menghubungi Anda untuk konfirmasi lebih lanjut.
                </p>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-50 px-4 py-5 sm:px-6 text-center">
                <p class="text-sm text-gray-600 mb-4">
                    Kami akan segera menghubungi Anda untuk konfirmasi dan pembayaran DP.
                </p>
                <!-- Konfirmasi ke Admin via WhatsApp -->
                <?php
                $waNumber = '6282228287646'; // Ganti dengan nomor admin jika perlu
                $customerNama = $orderDetails['rental']['customer_nama'] ?? $orderDetails['rental']['nama'] ?? '';
                $customerHp = $orderDetails['rental']['customer_hp'] ?? $orderDetails['rental']['hp'] ?? '';
                $customerLokasi = $orderDetails['rental']['customer_lokasi'] ?? $orderDetails['rental']['lokasi'] ?? '';
                $tanggalSewa = isset($orderDetails['items'][0]['tanggal_sewa']) ? formatTanggalIndo($orderDetails['items'][0]['tanggal_sewa']) : '';
                $tanggalKembali = isset($orderDetails['items'][0]['tanggal_kembali']) ? formatTanggalIndo($orderDetails['items'][0]['tanggal_kembali']) : '';
                $totalDP = formatRupiah($orderDetails['rental']['dp_bayar'] ?? 0);
                $totalPelunasan = formatRupiah($orderDetails['rental']['pelunasan_bayar'] ?? 0);
                $pesananId = $orderDetails['rental']['id'] ?? '';
                $produkList = "";
                foreach ($orderDetails['items'] as $item) {
                    $produkList .= "• ".$item['nama_baju'].
                        " (Kategori: ".$item['kategori'].")\n   Ukuran: ".$item['ukuran'].
                        ", Jumlah: ".$item['quantity'].
                        ", Tgl Sewa: ".formatTanggalIndo($item['tanggal_sewa']).
                        " - ".formatTanggalIndo($item['tanggal_kembali']).
                        ", Harga: ".formatRupiah($item['price'] * $item['quantity'])."\n";
                }
                $waMsg = "Halo Admin, saya ingin konfirmasi pesanan rental baju. Berikut detail pesanan saya:\n"
                    ."=====================\n"
                    ."Nomor Pesanan: #$pesananId\n"
                    ."Nama: $customerNama\n"
                    ."No. HP: $customerHp\n"
                    ."Alamat: $customerLokasi\n"
                    ."=====================\n"
                    ."Daftar Produk:\n$produkList"
                    ."=====================\n"
                    ."Total DP: $totalDP\n"
                    ."Total Pelunasan: $totalPelunasan\n"
                    ."Total Keseluruhan: ".formatRupiah(($orderDetails['rental']['dp_bayar'] ?? 0) + ($orderDetails['rental']['pelunasan_bayar'] ?? 0))."\n"
                    ."=====================\n"
                    ."Mohon konfirmasi ketersediaan dan proses selanjutnya. Terima kasih.";
                $waLink = "https://wa.me/$waNumber?text=" . rawurlencode($waMsg);
                ?>
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mt-4">
                    <a href="<?= $waLink ?>" target="_blank" class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                        <i class="fab fa-whatsapp mr-2"></i> Konfirmasi ke Admin via WhatsApp
                    </a>
                    <button onclick="konfirmasiAdmin()" class="w-full sm:w-auto inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-check-circle mr-2"></i> Konfirmasi Admin
                    </button>
                </div>
                <a href="index.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-[#EBA1A1] to-[#d97c7c] hover:from-[#d97c7c] hover:to-[#c56e6e] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#EBA1A1]">
                    <i class="fas fa-home mr-2"></i> Kembali ke Beranda
                </a>
                <script>
                function konfirmasiAdmin() {
                    alert('Terima kasih! Admin akan segera menghubungi Anda.');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 10000);
                }
                </script>
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
