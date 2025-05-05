<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $dbType;
    
    // Getter untuk mendapatkan tipe database
    public function getDbType() {
        return $this->dbType;
    }

    private function __construct() {
        try {
            // Cek tipe database dari konfigurasi
            $this->dbType = defined('DB_TYPE') ? DB_TYPE : 'sqlite';
            
            if ($this->dbType === 'mysql') {
                // Koneksi MySQL untuk Hostinger
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } else {
                // Koneksi SQLite untuk pengembangan lokal
                // Buat direktori database jika belum ada
                $dbDir = dirname(DB_PATH);
                if (!file_exists($dbDir)) {
                    mkdir($dbDir, 0777, true);
                }
                
                $this->connection = new PDO("sqlite:" . DB_PATH);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Enable foreign key support untuk SQLite
                $this->connection->exec('PRAGMA foreign_keys = ON');
            }
            
            // Create tables if not exists
            $this->createTables();
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    private function createTables() {
        // Buat table berdasarkan tipe database
        if ($this->dbType === 'mysql') {
            // Schema untuk MySQL
            $this->createMySQLTables();
        } else {
            // Schema untuk SQLite
            $this->createSQLiteTables();
        }
    }
    
    private function createSQLiteTables() {
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
                status TEXT NOT NULL CHECK(status IN ('pending', 'approved', 'returned', 'canceled')) DEFAULT 'pending',
                catatan TEXT,
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
    
    private function createMySQLTables() {
        // Tabel items
        $this->connection->exec("CREATE TABLE IF NOT EXISTS items (
            id INT(11) NOT NULL AUTO_INCREMENT,
            nama_baju VARCHAR(255) NOT NULL,
            kategori VARCHAR(100) NOT NULL,
            deskripsi TEXT,
            foto VARCHAR(255),
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Tabel item_variants
        $this->connection->exec("CREATE TABLE IF NOT EXISTS item_variants (
            id INT(11) NOT NULL AUTO_INCREMENT,
            item_id INT(11) NOT NULL,
            ukuran ENUM('S', 'M', 'L', 'XL') NOT NULL,
            stok_total INT(11) NOT NULL DEFAULT 0,
            kode_unik VARCHAR(50) NOT NULL,
            barcode VARCHAR(100) NOT NULL,
            harga DECIMAL(10,2) NOT NULL,
            dp DECIMAL(10,2) NOT NULL,
            pelunasan DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (kode_unik),
            CONSTRAINT fk_item_variants_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Tabel item_availability
        $this->connection->exec("CREATE TABLE IF NOT EXISTS item_availability (
            id INT(11) NOT NULL AUTO_INCREMENT,
            variant_id INT(11) NOT NULL,
            tanggal DATE NOT NULL,
            stok_terpakai INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (variant_id, tanggal),
            CONSTRAINT fk_availability_variant FOREIGN KEY (variant_id) REFERENCES item_variants (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Tabel rentals
        $this->connection->exec("CREATE TABLE IF NOT EXISTS rentals (
            id INT(11) NOT NULL AUTO_INCREMENT,
            customer_nama VARCHAR(255) NOT NULL,
            customer_hp VARCHAR(50) NOT NULL,
            customer_lokasi VARCHAR(255) NOT NULL,
            variant_id INT(11) NOT NULL,
            tanggal_sewa DATE NOT NULL,
            tanggal_kembali DATE NOT NULL,
            tanggal_booking DATE DEFAULT NULL,
            jumlah INT(11) NOT NULL,
            dp_bayar DECIMAL(10,2) NOT NULL,
            pelunasan_bayar DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'approved', 'returned', 'canceled') NOT NULL DEFAULT 'pending',
            catatan TEXT,
            jenis_jaminan VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_rentals_variant FOREIGN KEY (variant_id) REFERENCES item_variants (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Indeks untuk optimasi query
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_rental_status ON rentals(status);");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_rental_dates ON rentals(tanggal_sewa, tanggal_kembali);");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_item_variants_item ON item_variants(item_id);");
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
