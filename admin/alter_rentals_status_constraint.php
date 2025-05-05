<?php
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/config.php';
require_once 'C:/xampp/htdocs/ewa baju/php-simple-redirection-app/includes/db.php';

$db = Database::getInstance();

try {
    // 1. Rename tabel lama
    $db->execute("ALTER TABLE rentals RENAME TO rentals_old");

    // 2. Buat tabel baru dengan constraint status yang sudah diperbarui
    $db->execute("CREATE TABLE rentals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_nama TEXT NOT NULL,
        customer_hp TEXT NOT NULL,
        customer_lokasi TEXT NOT NULL,
        variant_id INTEGER NOT NULL,
        tanggal_sewa DATE NOT NULL,
        tanggal_kembali DATE NOT NULL,
        tanggal_booking DATE DEFAULT NULL,
        jumlah INTEGER NOT NULL,
        dp_bayar REAL DEFAULT 0,
        pelunasan_bayar REAL DEFAULT 0,
        status TEXT NOT NULL CHECK(status IN ('pending', 'approved', 'returned', 'canceled')),
        catatan TEXT DEFAULT NULL,
        jenis_jaminan TEXT DEFAULT NULL,
        kondisi_pengembalian TEXT DEFAULT NULL,
        catatan_pengembalian TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Cek kolom yang ada di rentals_old
    $columns = $db->fetchAll("PRAGMA table_info(rentals_old)");
    $oldCols = array_column($columns, 'name');
    $newCols = [
        'id', 'customer_nama', 'customer_hp', 'customer_lokasi', 'variant_id', 'tanggal_sewa', 'tanggal_kembali',
        'tanggal_booking', 'jumlah', 'dp_bayar', 'pelunasan_bayar', 'status', 'catatan', 'jenis_jaminan',
        'kondisi_pengembalian', 'catatan_pengembalian', 'created_at', 'updated_at'
    ];
    $copyCols = array_intersect($newCols, $oldCols);
    $copyColsStr = implode(',', $copyCols);

    // 4. Copy data dari tabel lama ke tabel baru (hanya kolom yang ada)
    $db->execute("INSERT INTO rentals ($copyColsStr) SELECT $copyColsStr FROM rentals_old");

    // 5. Hapus tabel lama
    $db->execute("DROP TABLE rentals_old");

    echo "Constraint status pada tabel rentals berhasil diupdate. Sekarang status 'canceled' sudah diperbolehkan. Kolom baru akan otomatis NULL/default untuk data lama.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 