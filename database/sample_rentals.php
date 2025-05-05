<?php
$rootDir = __DIR__ . '/..';
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';
require_once $rootDir . '/includes/functions.php';

$db = Database::getInstance();

try {
    // Get some variants
    $variants = $db->fetchAll("SELECT id FROM item_variants LIMIT 4");
    
    if (empty($variants)) {
        die("No variants found. Please run sample_data.php first.\n");
    }

    // Sample rental data
    $rentals = [
        [
            'customer_nama' => 'Budi Santoso',
            'customer_hp' => '081234567890',
            'customer_lokasi' => 'Jl. Merdeka No. 123, Jakarta',
            'variant_id' => $variants[0]['id'],
            'tanggal_sewa' => '2025-05-10',
            'tanggal_kembali' => '2025-05-12',
            'jumlah' => 1,
            'dp_bayar' => 50000,
            'pelunasan_bayar' => 150000,
            'status' => 'approved'
        ],
        [
            'customer_nama' => 'Siti Rahayu',
            'customer_hp' => '082345678901',
            'customer_lokasi' => 'Jl. Sudirman No. 45, Jakarta',
            'variant_id' => $variants[1]['id'],
            'tanggal_sewa' => '2025-05-08',
            'tanggal_kembali' => '2025-05-09',
            'jumlah' => 2,
            'dp_bayar' => 100000,
            'pelunasan_bayar' => 300000,
            'status' => 'returned'
        ],
        [
            'customer_nama' => 'Ahmad Hidayat',
            'customer_hp' => '083456789012',
            'customer_lokasi' => 'Jl. Gatot Subroto No. 67, Jakarta',
            'variant_id' => $variants[2]['id'],
            'tanggal_sewa' => '2025-05-15',
            'tanggal_kembali' => '2025-05-17',
            'jumlah' => 1,
            'dp_bayar' => 50000,
            'pelunasan_bayar' => 150000,
            'status' => 'approved'
        ]
    ];

    // Insert rentals
    foreach ($rentals as $rental) {
        $db->query(
            "INSERT INTO rentals (
                customer_nama, customer_hp, customer_lokasi,
                variant_id, tanggal_sewa, tanggal_kembali,
                jumlah, dp_bayar, pelunasan_bayar, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $rental['customer_nama'],
                $rental['customer_hp'],
                $rental['customer_lokasi'],
                $rental['variant_id'],
                $rental['tanggal_sewa'],
                $rental['tanggal_kembali'],
                $rental['jumlah'],
                $rental['dp_bayar'],
                $rental['pelunasan_bayar'],
                $rental['status']
            ]
        );

        // Update stock availability for the rental period
        $dates = getRentalDateRange($rental['tanggal_sewa'], $rental['tanggal_kembali']);
        foreach ($dates as $date) {
            // Insert or update stok_terpakai
            $db->query(
                "INSERT OR IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai)
                 VALUES (?, ?, 0)",
                [$rental['variant_id'], $date]
            );
            $db->query(
                "UPDATE item_availability 
                 SET stok_terpakai = stok_terpakai + ? 
                 WHERE variant_id = ? AND tanggal = ?",
                [$rental['jumlah'], $rental['variant_id'], $date]
            );
        }
    }

    echo "Sample rentals data berhasil ditambahkan!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
