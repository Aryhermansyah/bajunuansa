<?php
/**
 * Script untuk menambahkan kolom kondisi_pengembalian ke tabel rentals
 */

// Load required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start session
session_start();

// Get database connection
$db = Database::getInstance();
$dbType = $db->getDbType();

echo '<h1>Menambahkan Kolom Kondisi Pengembalian</h1>';
echo '<p>Jenis Database: <strong>' . strtoupper($dbType) . '</strong></p>';

try {
    // Check if column already exists
    $columnExists = false;

    if ($dbType === 'mysql') {
        // For MySQL
        $result = $db->fetchAll(
            "SHOW COLUMNS FROM rentals LIKE 'kondisi_pengembalian'"
        );
        $columnExists = !empty($result);
    } else {
        // For SQLite
        $result = $db->fetchAll(
            "PRAGMA table_info(rentals)"
        );
        foreach ($result as $column) {
            if ($column['name'] === 'kondisi_pengembalian') {
                $columnExists = true;
                break;
            }
        }
    }

    // Add column if it doesn't exist
    if (!$columnExists) {
        if ($dbType === 'mysql') {
            // For MySQL
            $db->execute(
                "ALTER TABLE rentals ADD COLUMN kondisi_pengembalian TEXT NULL"
            );
        } else {
            // For SQLite
            $db->execute(
                "ALTER TABLE rentals ADD COLUMN kondisi_pengembalian TEXT"
            );
        }
        echo '<p style="color: green;">Kolom kondisi_pengembalian berhasil ditambahkan ke tabel rentals!</p>';
    } else {
        echo '<p>Kolom kondisi_pengembalian sudah ada di tabel rentals.</p>';
    }

    // Check for other return-related columns
    $returnColumns = [
        'tanggal_kembali_aktual' => 'DATE',
        'catatan_pengembalian' => 'TEXT',
        'denda' => 'DECIMAL(10,2)'
    ];

    foreach ($returnColumns as $columnName => $columnType) {
        $columnExists = false;

        if ($dbType === 'mysql') {
            // For MySQL
            $result = $db->fetchAll(
                "SHOW COLUMNS FROM rentals LIKE '$columnName'"
            );
            $columnExists = !empty($result);
        } else {
            // For SQLite
            $result = $db->fetchAll(
                "PRAGMA table_info(rentals)"
            );
            foreach ($result as $column) {
                if ($column['name'] === $columnName) {
                    $columnExists = true;
                    break;
                }
            }
        }

        // Add column if it doesn't exist
        if (!$columnExists) {
            if ($dbType === 'mysql') {
                // For MySQL
                if ($columnType === 'DATE') {
                    $db->execute(
                        "ALTER TABLE rentals ADD COLUMN $columnName $columnType NULL"
                    );
                } else if ($columnType === 'DECIMAL(10,2)') {
                    $db->execute(
                        "ALTER TABLE rentals ADD COLUMN $columnName $columnType DEFAULT 0"
                    );
                } else {
                    $db->execute(
                        "ALTER TABLE rentals ADD COLUMN $columnName $columnType NULL"
                    );
                }
            } else {
                // For SQLite
                $db->execute(
                    "ALTER TABLE rentals ADD COLUMN $columnName TEXT"
                );
            }
            echo "<p style=\"color: green;\">Kolom $columnName berhasil ditambahkan ke tabel rentals!</p>";
        } else {
            echo "<p>Kolom $columnName sudah ada di tabel rentals.</p>";
        }
    }

    echo '<p style="margin-top: 20px;"><a href="../admin/return_rental.php">Kembali ke Halaman Pengembalian</a></p>';

} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}
?>
