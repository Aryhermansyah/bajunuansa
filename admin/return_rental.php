<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Validasi ID pesanan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('fixed_rentals.php', 'ID pesanan tidak valid', 'error');
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil data pesanan
$rental = $db->fetchOne(
    "SELECT r.*, 
            i.nama_baju,
            iv.ukuran,
            iv.kode_unik
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.id = ? AND r.status = 'approved'",
    [$rentalId]
);

// Jika pesanan tidak ditemukan atau bukan status approved
if (!$rental) {
    redirect('fixed_rentals.php', 'Pesanan tidak ditemukan atau belum disetujui/sudah dikembalikan', 'error');
}

// Proses pengembalian pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kondisi = sanitizeInput($_POST['kondisi'] ?? 'baik');
    $catatan_pengembalian = sanitizeInput($_POST['catatan_pengembalian'] ?? '');
    
    try {
        error_log('[return_rental] Masuk proses return, rentalId=' . $rentalId);
        // Kurangi stok_terpakai saat pengembalian
        error_log('[return_rental] Panggil reduceStockRange, variant_id=' . $rental['variant_id'] . ', tanggal_sewa=' . $rental['tanggal_sewa'] . ', tanggal_kembali=' . $rental['tanggal_kembali'] . ', jumlah=' . $rental['jumlah']);
        reduceStockRange($rental['variant_id'], $rental['tanggal_sewa'], $rental['tanggal_kembali'], $rental['jumlah']);
        error_log('[return_rental] Selesai reduceStockRange');
        // Update status pesanan menjadi returned
        $db->execute(
            "UPDATE rentals SET 
                status = 'returned', 
                kondisi_pengembalian = ?, 
                catatan_pengembalian = ?,
                updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [$kondisi, $catatan_pengembalian, $rentalId]
        );
        
        redirect('fixed_rentals.php', 'Baju berhasil dikembalikan', 'success');
    } catch (Exception $e) {
        redirect('fixed_rentals.php', 'Gagal memproses pengembalian: ' . $e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Baju - <?= APP_NAME ?></title>
    
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
                        <a href="rental_list.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Semua Pemesanan
                        </a>
                        <a href="fixed_rentals.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Pemesanan Fix
                        </a>
                        <a href="add_rental.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Tambah Penyewaan
                        </a>
                        <a href="view_products.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Daftar Produk
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
                Pengembalian Baju
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Proses pengembalian baju yang sudah selesai disewa
            </p>
        </div>

        <!-- Rental Detail Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Detail Pesanan
                </h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Nama Penyewa
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($rental['customer_nama']) ?>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Nomor HP
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($rental['customer_hp']) ?>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Baju
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($rental['nama_baju']) ?> (Ukuran: <?= htmlspecialchars($rental['ukuran']) ?>)
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Periode Sewa
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= formatTanggalIndo($rental['tanggal_sewa']) ?> s/d <?= formatTanggalIndo($rental['tanggal_kembali']) ?>
                            <?php if (!empty($rental['tanggal_booking'])): ?>
                            <p class="text-xs mt-1">Tanggal Booking: <?= formatTanggalIndo($rental['tanggal_booking']) ?></p>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Jumlah
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($rental['jumlah']) ?> unit
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Pembayaran
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <p>DP: <?= formatRupiah($rental['dp_bayar']) ?></p>
                            <p>Pelunasan: <?= formatRupiah($rental['pelunasan_bayar']) ?></p>
                            <p class="font-medium">Total: <?= formatRupiah($rental['dp_bayar'] + $rental['pelunasan_bayar']) ?></p>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Catatan
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= !empty($rental['catatan']) ? nl2br(htmlspecialchars($rental['catatan'])) : '-' ?>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Jaminan
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php if (!empty($rental['jenis_jaminan'])): ?>
                                <p><span class="font-medium">Jenis:</span> <?= htmlspecialchars($rental['jenis_jaminan']) ?></p>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Return Form -->
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Form Pengembalian
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Isi detail pengembalian baju
                </p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <form method="post">
                    <div class="space-y-6">
                        <div>
                            <label for="kondisi" class="block text-sm font-medium text-gray-700">
                                Kondisi Baju
                            </label>
                            <select name="kondisi" id="kondisi" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                                <option value="hilang">Hilang</option>
                            </select>
                        </div>

                        <div>
                            <label for="catatan_pengembalian" class="block text-sm font-medium text-gray-700">
                                Catatan Pengembalian
                            </label>
                            <textarea id="catatan_pengembalian" name="catatan_pengembalian" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Tambahkan catatan mengenai kondisi barang yang dikembalikan"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row sm:justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-check mr-2"></i> Proses Pengembalian
                        </button>
                        <a href="fixed_rentals.php" class="w-full sm:w-auto inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html> 