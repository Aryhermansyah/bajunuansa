<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('fixed_rentals.php', 'ID pesanan tidak valid', 'error');
}

$rentalId = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil data rental
$rental = $db->fetchOne(
    "SELECT * FROM rentals WHERE id = ?",
    [$rentalId]
);
if (!$rental) {
    redirect('fixed_rentals.php', 'Data pesanan tidak ditemukan', 'error');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerNama = sanitizeInput($_POST['customer_nama'] ?? '');
    $customerHp = sanitizeInput($_POST['customer_hp'] ?? '');
    $customerLokasi = sanitizeInput($_POST['customer_lokasi'] ?? '');
    $tanggalSewa = sanitizeInput($_POST['tanggal_sewa'] ?? '');
    $tanggalKembali = sanitizeInput($_POST['tanggal_kembali'] ?? '');
    $tanggalBooking = sanitizeInput($_POST['tanggal_booking'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $status = sanitizeInput($_POST['status'] ?? 'pending');
    $catatan = sanitizeInput($_POST['catatan'] ?? '');
    $jenisJaminan = sanitizeInput($_POST['jenis_jaminan'] ?? '');

    // Validasi sederhana
    if (empty($customerNama)) $errors[] = 'Nama penyewa wajib diisi';
    if (empty($customerHp)) $errors[] = 'Nomor HP wajib diisi';
    if (empty($customerLokasi)) $errors[] = 'Alamat wajib diisi';
    if (!validateDate($tanggalSewa) || !validateDate($tanggalKembali)) $errors[] = 'Tanggal sewa/kembali tidak valid';
    if ($jumlah <= 0) $errors[] = 'Jumlah harus lebih dari 0';

    if (empty($errors)) {
        try {
            $db->execute(
                "UPDATE rentals SET customer_nama=?, customer_hp=?, customer_lokasi=?, tanggal_sewa=?, tanggal_kembali=?, tanggal_booking=?, jumlah=?, status=?, catatan=?, jenis_jaminan=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                [$customerNama, $customerHp, $customerLokasi, $tanggalSewa, $tanggalKembali, $tanggalBooking, $jumlah, $status, $catatan, $jenisJaminan, $rentalId]
            );
            redirect('fixed_rentals.php', 'Data pesanan berhasil diperbarui', 'success');
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pemesanan - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
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
    <main class="max-w-2xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Edit Pemesanan</h2>
            <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-50 p-4 rounded-md">
                <ul class="list-disc pl-5 text-red-700">
                    <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Penyewa</label>
                    <input type="text" name="customer_nama" value="<?= htmlspecialchars($rental['customer_nama']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor HP</label>
                    <input type="text" name="customer_hp" value="<?= htmlspecialchars($rental['customer_hp']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                    <textarea name="customer_lokasi" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?= htmlspecialchars($rental['customer_lokasi']) ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Sewa</label>
                        <input type="date" name="tanggal_sewa" value="<?= htmlspecialchars($rental['tanggal_sewa']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                        <input type="date" name="tanggal_kembali" value="<?= htmlspecialchars($rental['tanggal_kembali']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Booking</label>
                    <input type="date" name="tanggal_booking" value="<?= htmlspecialchars($rental['tanggal_booking']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jumlah</label>
                    <input type="number" name="jumlah" value="<?= (int)$rental['jumlah'] ?>" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="pending" <?= $rental['status'] === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                        <option value="approved" <?= $rental['status'] === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="returned" <?= $rental['status'] === 'returned' ? 'selected' : '' ?>>Dikembalikan</option>
                        <option value="canceled" <?= $rental['status'] === 'canceled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea name="catatan" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?= htmlspecialchars($rental['catatan']) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis Jaminan</label>
                    <select name="jenis_jaminan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Pilih Jenis Jaminan --</option>
                        <option value="KTP" <?= $rental['jenis_jaminan'] === 'KTP' ? 'selected' : '' ?>>KTP</option>
                        <option value="SIM" <?= $rental['jenis_jaminan'] === 'SIM' ? 'selected' : '' ?>>SIM</option>
                        <option value="Kartu Pelajar" <?= $rental['jenis_jaminan'] === 'Kartu Pelajar' ? 'selected' : '' ?>>Kartu Pelajar</option>
                        <option value="Kartu Keluarga" <?= $rental['jenis_jaminan'] === 'Kartu Keluarga' ? 'selected' : '' ?>>Kartu Keluarga</option>
                        <option value="Lainnya" <?= $rental['jenis_jaminan'] === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <a href="fixed_rentals.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-2">Batal</a>
                    <button type="submit" class="ml-2 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 