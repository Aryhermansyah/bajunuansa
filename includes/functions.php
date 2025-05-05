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

    try {
        // Coba insert dulu, jika gagal karena sudah ada, lakukan update
        $db->query(
            "INSERT OR IGNORE INTO item_availability (variant_id, tanggal, stok_terpakai) 
             VALUES (?, ?, 0)",
            [$variantId, $tanggal]
        );
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
        'returned' => 'Dikembalikan'
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
        return false;
    }

    // Buat direktori jika belum ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validasi ekstensi file
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return false;
    }

    // Generate nama file unik
    $fileName = uniqid() . '.' . $fileExtension;
    $targetPath = $targetDir . $fileName;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }

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
