-- Membuat database
CREATE DATABASE IF NOT EXISTS rental_baju;
USE rental_baju;

-- Tabel items (data baju)
CREATE TABLE items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_baju VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel item_variants (variasi ukuran & harga)
CREATE TABLE item_variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    ukuran ENUM('S', 'M', 'L', 'XL') NOT NULL,
    stok_total INT NOT NULL DEFAULT 0,
    kode_unik VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    dp DECIMAL(10,2) NOT NULL,
    pelunasan DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Tabel item_availability (ketersediaan stok per tanggal)
CREATE TABLE item_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    variant_id INT NOT NULL,
    tanggal DATE NOT NULL,
    stok_terpakai INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (variant_id) REFERENCES item_variants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_variant_date (variant_id, tanggal)
);

-- Tabel rentals (data penyewaan)
CREATE TABLE rentals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_nama VARCHAR(100) NOT NULL,
    customer_hp VARCHAR(20) NOT NULL,
    customer_lokasi TEXT NOT NULL,
    variant_id INT NOT NULL,
    tanggal_sewa DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    jumlah INT NOT NULL,
    dp_bayar DECIMAL(10,2) NOT NULL,
    pelunasan_bayar DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'returned') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (variant_id) REFERENCES item_variants(id)
);

-- Indeks untuk optimasi query
CREATE INDEX idx_rental_status ON rentals(status);
CREATE INDEX idx_rental_dates ON rentals(tanggal_sewa, tanggal_kembali);
CREATE INDEX idx_item_variants_item ON item_variants(item_id);
CREATE INDEX idx_availability_variant ON item_availability(variant_id);
