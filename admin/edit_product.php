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

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_baju'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $fotoBaru = $item['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png'])) {
            $namaFile = uniqid() . '.' . $ext;
            $target = dirname(__DIR__) . '/assets/images/' . $namaFile;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
                // Hapus foto lama jika ada
                if ($item['foto'] && file_exists(dirname(__DIR__) . '/assets/images/' . $item['foto'])) {
                    @unlink(dirname(__DIR__) . '/assets/images/' . $item['foto']);
                }
                $fotoBaru = $namaFile;
            }
        }
    }
    $db->execute("UPDATE items SET nama_baju=?, kategori=?, deskripsi=?, is_featured=?, foto=? WHERE id=?", [$nama, $kategori, $deskripsi, $is_featured, $fotoBaru, $id]);
    header("Location: view_product_detail.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - <?= htmlspecialchars($item['nama_baju']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50">
    <main class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Edit Produk</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block font-medium">Nama Baju</label>
                    <input type="text" name="nama_baju" value="<?= htmlspecialchars($item['nama_baju']) ?>" class="border rounded px-3 py-2 w-full">
                </div>
                <div class="mb-4">
                    <label class="block font-medium">Kategori</label>
                    <input type="text" name="kategori" value="<?= htmlspecialchars($item['kategori']) ?>" class="border rounded px-3 py-2 w-full">
                </div>
                <div class="mb-4">
                    <label class="block font-medium">Deskripsi</label>
                    <textarea name="deskripsi" class="border rounded px-3 py-2 w-full"><?= htmlspecialchars($item['deskripsi']) ?></textarea>
                </div>
                <div class="mb-4">
                    <label><input type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked' : '' ?>> Produk Unggulan</label>
                </div>
                <div class="mb-4">
                    <label class="block font-medium">Foto Produk</label>
                    <?php if ($item['foto']): ?>
                        <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['foto']) ?>" alt="Foto" class="w-32 h-32 object-contain rounded mb-2">
                    <?php endif; ?>
                    <input type="file" name="foto" accept="image/*" class="block mt-1">
                    <small class="text-gray-500">Format: jpg, jpeg, png. Biarkan kosong jika tidak ingin mengganti foto.</small>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"><i class="fas fa-save mr-1"></i> Simpan</button>
                <a href="view_product_detail.php?id=<?= $item['id'] ?>" class="ml-2 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"><i class="fas fa-arrow-left mr-1"></i> Batal</a>
            </form>
        </div>
    </main>
</body>
</html> 