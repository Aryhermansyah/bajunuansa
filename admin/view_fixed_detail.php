<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('fixed_rentals.php', 'ID pemesanan tidak valid', 'error');
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil detail pemesanan
$rental = $db->fetchOne(
    "SELECT r.*, 
            i.nama_baju,
            i.foto,
            i.kategori,
            iv.ukuran,
            iv.kode_unik,
            iv.barcode,
            iv.harga,
            iv.dp,
            iv.pelunasan
     FROM rentals r
     JOIN item_variants iv ON r.variant_id = iv.id
     JOIN items i ON iv.item_id = i.id
     WHERE r.id = ? AND r.status IN ('approved', 'returned')",
    [$rentalId]
);

if (!$rental) {
    redirect('fixed_rentals.php', 'Pemesanan tidak ditemukan', 'error');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemesanan - <?= APP_NAME ?></title>
    
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
                    <a href="fixed_rentals.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
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
                Detail Pemesanan
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Informasi lengkap pemesanan dengan kode: <?= htmlspecialchars($rental['kode_unik']) ?>
            </p>
        </div>

        <!-- Detail Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Informasi Penyewa -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-user mr-2"></i> Informasi Penyewa
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nama</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['customer_nama']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nomor HP</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['customer_hp']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Lokasi</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= nl2br(htmlspecialchars($rental['customer_lokasi'])) ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Informasi Baju -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-tshirt mr-2"></i> Informasi Baju
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Nama Baju</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['nama_baju']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kategori</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['kategori']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Ukuran</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['ukuran']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kode Unik</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($rental['kode_unik']) ?>
                                    </dd>
                                </div>
                                <?php if ($rental['foto']): ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Foto</dt>
                                    <dd class="mt-1">
                                        <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($rental['foto']) ?>" 
                                             alt="<?= htmlspecialchars($rental['nama_baju']) ?>"
                                             class="w-32 h-32 object-cover rounded-lg">
                                    </dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>

                    <!-- Informasi Pemesanan -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-calendar-alt mr-2"></i> Informasi Pemesanan
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tanggal Sewa</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($rental['tanggal_sewa'])) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tanggal Kembali</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($rental['tanggal_kembali'])) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Jumlah</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= $rental['jumlah'] ?> item
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $rental['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                            <?= getStatusLabel($rental['status']) ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Informasi Pembayaran -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-money-bill-wave mr-2"></i> Informasi Pembayaran
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Harga Total</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= formatRupiah($rental['harga'] * $rental['jumlah']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">DP</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= formatRupiah($rental['dp_bayar']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pelunasan</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= formatRupiah($rental['pelunasan_bayar']) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Total Dibayar</dt>
                                    <dd class="mt-1 text-sm font-medium text-green-600">
                                        <?= formatRupiah($rental['dp_bayar'] + $rental['pelunasan_bayar']) ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Barcode -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-barcode mr-2"></i> Barcode
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <!-- Placeholder untuk barcode - implementasi sesuai library yang digunakan -->
                        <div class="font-mono text-sm"><?= htmlspecialchars($rental['barcode']) ?></div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="mt-6 text-sm text-gray-500">
                    <p>Dibuat pada: <?= date('d/m/Y H:i', strtotime($rental['created_at'])) ?></p>
                    <p>Terakhir diupdate: <?= date('d/m/Y H:i', strtotime($rental['updated_at'])) ?></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
