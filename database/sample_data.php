<?php
$rootDir = __DIR__ . '/..';
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

$db = Database::getInstance();

try {
    // Insert sample items
    $db->query("
        INSERT INTO items (nama_baju, kategori, deskripsi) VALUES 
        ('Jas Formal Hitam', 'Jas', 'Jas formal warna hitam untuk acara resmi'),
        ('Gaun Pesta Merah', 'Gaun', 'Gaun pesta elegan warna merah'),
        ('Kebaya Modern Hijau', 'Kebaya', 'Kebaya modern dengan detail bordir'),
        ('Batik Couple', 'Batik', 'Set batik couple untuk pesta')
    ");

    // Get the inserted item IDs
    $items = $db->fetchAll("SELECT id FROM items");
    
    // Insert variants for each item
    foreach ($items as $item) {
        $sizes = ['S', 'M', 'L', 'XL'];
        foreach ($sizes as $size) {
            $kodeUnik = 'BJ' . str_pad($item['id'], 3, '0', STR_PAD_LEFT) . $size;
            $db->query(
                "INSERT INTO item_variants (
                    item_id, ukuran, stok_total, kode_unik, barcode,
                    harga, dp, pelunasan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $item['id'],
                    $size,
                    10, // stok_total
                    $kodeUnik,
                    $kodeUnik, // using kode_unik as barcode for now
                    200000, // harga
                    50000,  // dp
                    150000  // pelunasan
                ]
            );
        }
    }

    echo "Sample data berhasil ditambahkan!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
