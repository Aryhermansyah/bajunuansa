<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID produk tidak valid.');
}

$id = (int)$_GET['id'];
$db = Database::getInstance();

// Ambil data produk
$item = $db->fetchOne("SELECT * FROM items WHERE id = ?", [$id]);
if (!$item) {
    die('Produk tidak ditemukan.');
}
// Ambil varian produk
$variants = $db->fetchAll("SELECT * FROM item_variants WHERE item_id = ?", [$id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Produk - <?= htmlspecialchars($item['nama_baju']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <main class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Detail Produk</h2>
            <div class="mb-4">
                <strong>Nama:</strong> <?= htmlspecialchars($item['nama_baju']) ?><br>
                <strong>Kategori:</strong> <?= htmlspecialchars($item['kategori']) ?><br>
                <strong>Deskripsi:</strong> <?= nl2br(htmlspecialchars($item['deskripsi'])) ?><br>
                <strong>Unggulan:</strong> <?= $item['is_featured'] ? 'Ya' : 'Tidak' ?><br>
                <?php if ($item['foto']): ?>
                <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>" alt="Foto" class="w-40 h-40 object-contain rounded-lg mt-2">
                <?php endif; ?>
            </div>
            <h3 class="text-lg font-semibold mb-2">Daftar Varian</h3>
            <table class="min-w-full divide-y divide-gray-200 mb-4">
                <thead><tr><th>Ukuran</th><th>Stok</th><th>Harga</th><th>DP</th><th>Kode Unik</th></tr></thead>
                <tbody>
                <?php foreach ($variants as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['ukuran']) ?></td>
                    <td><?= htmlspecialchars($v['stok_total']) ?></td>
                    <td><?= htmlspecialchars($v['harga']) ?></td>
                    <td><?= htmlspecialchars($v['dp']) ?></td>
                    <td><?= htmlspecialchars($v['kode_unik']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a href="view_products.php" class="inline-block px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
            <a href="edit_product.php?id=<?= $item['id'] ?>" class="inline-block px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 ml-2"><i class="fas fa-edit mr-1"></i> Edit Produk</a>
        </div>
    </main>
</body>
</html> 