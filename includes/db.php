<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Buat direktori database jika belum ada
            $dbDir = dirname(DB_PATH);
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            $this->connection = new PDO("sqlite:" . DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign key support
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            // Create tables if not exists
            $this->createTables();
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    private function createTables() {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_baju TEXT NOT NULL,
                kategori TEXT NOT NULL,
                deskripsi TEXT,
                foto TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS item_variants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                item_id INTEGER NOT NULL,
                ukuran TEXT NOT NULL CHECK(ukuran IN ('S', 'M', 'L', 'XL')),
                stok_total INTEGER NOT NULL DEFAULT 0,
                kode_unik TEXT UNIQUE NOT NULL,
                barcode TEXT NOT NULL,
                harga REAL NOT NULL,
                dp REAL NOT NULL,
                pelunasan REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS item_availability (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                variant_id INTEGER NOT NULL,
                tanggal DATE NOT NULL,
                stok_terpakai INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE,
                UNIQUE(variant_id, tanggal)
            );

            CREATE TABLE IF NOT EXISTS rentals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_nama TEXT NOT NULL,
                customer_hp TEXT NOT NULL,
                customer_lokasi TEXT NOT NULL,
                variant_id INTEGER NOT NULL,
                tanggal_sewa DATE NOT NULL,
                tanggal_kembali DATE NOT NULL,
                jumlah INTEGER NOT NULL,
                dp_bayar REAL NOT NULL,
                pelunasan_bayar REAL NOT NULL,
                status TEXT NOT NULL CHECK(status IN ('pending', 'approved', 'returned')) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (variant_id) REFERENCES item_variants(id)
            );

            CREATE INDEX IF NOT EXISTS idx_rental_status ON rentals(status);
            CREATE INDEX IF NOT EXISTS idx_rental_dates ON rentals(tanggal_sewa, tanggal_kembali);
            CREATE INDEX IF NOT EXISTS idx_item_variants_item ON item_variants(item_id);
            CREATE INDEX IF NOT EXISTS idx_availability_variant ON item_availability(variant_id);
        ");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query error: " . $e->getMessage());
        }
    }

    // Method untuk mendapatkan satu baris data
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Method untuk mendapatkan semua baris data
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method untuk insert data dan return last insert id
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }

    // Method untuk update/delete data dan return affected rows
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}
