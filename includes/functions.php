<?php
require_once 'db.php';

// Fungsi untuk mendapatkan stok tersedia
function getAvailableStock($variantId, $tanggal) {
    $db = Database::getInstance();
    
    // Dapatkan total stok
    $stmt = $db->query(
        "SELECT stok_total FROM item_variants WHERE id = ?",
        [$variantId]
    );
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$variant) return 0;
    
    $stokTotal = $variant['stok_total'];

    // Dapatkan stok terpakai pada tanggal tersebut
    $stmt = $db->query(
        "SELECT COALESCE(SUM(stok_terpakai), 0) as terpakai 
         FROM item_availability 
         WHERE variant_id = ? AND tanggal = ?",
        [$variantId, $tanggal]
    );
    $availability = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stokTotal - $availability['terpakai'];
}

function updateStock($variantId, $tanggal, $jumlah) {
    $db = Database::getInstance();
    $dbType = $db->getDbType();

    try {
        // Gunakan INSERT syntax yang sesuai berdasarkan jenis database
        if ($dbType === 'mysql') {
            // Syntax untuk MySQL
            $db->query(
                "INSERT IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai) 
                 VALUES (?, ?, 0)",
                [$variantId, $tanggal]
            );
        } else {
            // Syntax untuk SQLite
            $db->query(
                "INSERT OR IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai) 
                 VALUES (?, ?, 0)",
                [$variantId, $tanggal]
            );
        }
        
        // Update stok terpakai
        $db->query(
            "UPDATE item_availability 
             SET stok_terpakai = stok_terpakai + ? 
             WHERE variant_id = ? AND tanggal = ?",
            [$jumlah, $variantId, $tanggal]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Fungsi untuk format harga
function formatRupiah($nominal) {
    // Pastikan nominal bukan null
    if ($nominal === null) {
        $nominal = 0;
    }
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Fungsi untuk validasi tanggal
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Fungsi untuk mendapatkan status rental dalam bahasa Indonesia
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Menunggu Konfirmasi',
        'approved' => 'Disetujui',
        'returned' => 'Dikembalikan',
        'canceled' => 'Dibatalkan'
    ];
    return $labels[$status] ?? $status;
}

// Fungsi untuk generate kode unik
function generateUniqueCode($prefix = 'BJ') {
    return $prefix . date('Ymd') . substr(uniqid(), -5);
}

// Fungsi untuk generate barcode (menggunakan library picqer/php-barcode-generator)
function generateBarcode($kodeUnik) {
    // Placeholder untuk implementasi barcode
    // Dalam implementasi sebenarnya, gunakan library barcode
    return $kodeUnik;
}

// Fungsi untuk upload gambar
function uploadImage($file, $targetDir = UPLOAD_PATH) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        error_log("Upload gagal: File tidak ditemukan");
        return false;
    }

    // Buat direktori jika belum ada
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            error_log("Upload gagal: Tidak dapat membuat direktori upload " . $targetDir);
            return false;
        }
    }

    // Pastikan direktori dapat ditulis
    if (!is_writable($targetDir)) {
        error_log("Upload gagal: Direktori " . $targetDir . " tidak dapat ditulis");
        return false;
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validasi ekstensi file
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        error_log("Upload gagal: Ekstensi file tidak diizinkan - " . $fileExtension);
        return false;
    }

    // Generate nama file unik
    $fileName = uniqid() . '.' . $fileExtension;
    $targetPath = $targetDir . $fileName;

    error_log("Mencoba upload file ke: " . $targetPath);

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("Upload berhasil: " . $fileName);
        return $fileName;
    }

    error_log("Upload gagal: Tidak dapat memindahkan file. Error: " . error_get_last()['message']);
    return false;
}

// Fungsi untuk validasi nomor HP
function validatePhoneNumber($phone) {
    // Hapus karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Cek panjang nomor (minimal 10, maksimal 13 digit)
    if (strlen($phone) < 10 || strlen($phone) > 13) {
        return false;
    }
    
    // Cek awalan nomor Indonesia
    if (!preg_match('/^(08|628)/', $phone)) {
        return false;
    }
    
    return $phone;
}

// Fungsi untuk mendapatkan rentang tanggal sewa
function getRentalDateRange($startDate, $endDate) {
    $dates = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);

    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

// Fungsi untuk cek ketersediaan stok dalam rentang tanggal
function checkStockAvailability($variantId, $startDate, $endDate, $jumlah) {
    $dates = getRentalDateRange($startDate, $endDate);
    
    foreach ($dates as $date) {
        if (getAvailableStock($variantId, $date) < $jumlah) {
            return false;
        }
    }
    
    return true;
}

// Fungsi untuk sanitasi input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk redirect dengan pesan
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    // Cek jika URL sudah absolut (dimulai dengan http:// atau https://)
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        // Deteksi base URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Normalisasi direktori
        if (substr($scriptDir, -1) !== '/') {
            $scriptDir .= '/';
        }
        
        // Jika URL dimulai dengan slash, gunakan root domain
        if (substr($url, 0, 1) === '/') {
            $url = $protocol . $host . $url;
        } else {
            // Gunakan direktori saat ini sebagai basis URL
            $baseUrl = $protocol . $host . $scriptDir;
            $url = $baseUrl . $url;
        }
    }
    
    header("Location: $url");
    exit();
}

// Fungsi untuk menampilkan pesan flash
function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        return sprintf(
            '<div class="alert alert-%s alert-dismissible fade show" role="alert">
                %s
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>',
            $type,
            $flash['message']
        );
    }
    return '';
}

function reduceStock($variantId, $tanggal, $jumlah) {
    $db = Database::getInstance();
    $dbType = $db->getDbType();
    
    try {
        // Pastikan baris ada dengan sintaks yang sesuai database
        if ($dbType === 'mysql') {
            // Syntax untuk MySQL
            $db->query(
                "INSERT IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai) VALUES (?, ?, 0)",
                [$variantId, $tanggal]
            );
        } else {
            // Syntax untuk SQLite
            $db->query(
                "INSERT OR IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai) VALUES (?, ?, 0)",
                [$variantId, $tanggal]
            );
        }
        error_log("[reduceStock] variantId=$variantId, tanggal=$tanggal, jumlah=$jumlah");
        $db->query(
            "UPDATE item_availability 
             SET stok_terpakai = CASE WHEN stok_terpakai - ? < 0 THEN 0 ELSE stok_terpakai - ? END
             WHERE variant_id = ? AND tanggal = ?",
            [$jumlah, $jumlah, $variantId, $tanggal]
        );
        return true;
    } catch (Exception $e) {
        error_log('[reduceStock][ERROR] ' . $e->getMessage());
        return false;
    }
}

function reduceStockRange($variantId, $startDate, $endDate, $jumlah) {
    $dates = getRentalDateRange($startDate, $endDate);
    error_log("[reduceStockRange] variantId=$variantId, startDate=$startDate, endDate=$endDate, jumlah=$jumlah, dates=" . implode(',', $dates));
    foreach ($dates as $date) {
        reduceStock($variantId, $date, $jumlah);
    }
}

function formatTanggalIndo($date) {
    if (!$date || $date === '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $exp = explode('-', $date);
    if (count($exp) === 3) {
        return ltrim($exp[2], '0') . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0];
    }
    return $date;
}
