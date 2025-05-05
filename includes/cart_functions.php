<?php
/**
 * Cart Functions
 * Fungsi-fungsi untuk mengelola keranjang belanja
 */

require_once 'db.php';
require_once 'functions.php';

/**
 * Mendapatkan session ID keranjang
 * Fungsi ini memastikan ada session ID yang konsisten untuk keranjang
 */
function getCartSessionId() {
    if (!isset($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = session_id() . '_' . time();
    }
    return $_SESSION['cart_id'];
}

/**
 * Mendapatkan jumlah item dalam keranjang
 */
function getCartItemCount() {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    try {
        $result = $db->fetchOne(
            "SELECT SUM(quantity) as total FROM cart WHERE session_id = ?",
            [$sessionId]
        );
        
        return $result && isset($result['total']) ? (int)$result['total'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Menambahkan item ke keranjang
 */
function addToCart($variantId, $quantity, $tanggalSewa, $tanggalKembali) {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    // Validasi ketersediaan stok
    if (!checkStockAvailability($variantId, $tanggalSewa, $tanggalKembali, $quantity)) {
        return [
            'success' => false,
            'message' => 'Stok tidak mencukupi untuk tanggal yang dipilih'
        ];
    }
    
    try {
        // Cek apakah item sudah ada di keranjang dengan tanggal yang sama
        $existingItem = $db->fetchOne(
            "SELECT id, quantity FROM cart 
             WHERE session_id = ? AND variant_id = ? AND tanggal_sewa = ? AND tanggal_kembali = ?",
            [$sessionId, $variantId, $tanggalSewa, $tanggalKembali]
        );
        
        if ($existingItem) {
            // Update quantity jika item sudah ada
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Cek ketersediaan stok untuk quantity baru
            if (!checkStockAvailability($variantId, $tanggalSewa, $tanggalKembali, $newQuantity)) {
                return [
                    'success' => false,
                    'message' => 'Total stok di keranjang melebihi ketersediaan'
                ];
            }
            
            $db->execute(
                "UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$newQuantity, $existingItem['id']]
            );
            
            return [
                'success' => true,
                'message' => 'Jumlah item di keranjang diperbarui',
                'quantity' => $newQuantity
            ];
        } else {
            // Tambahkan item baru ke keranjang
            $db->execute(
                "INSERT INTO cart (session_id, variant_id, quantity, tanggal_sewa, tanggal_kembali) 
                 VALUES (?, ?, ?, ?, ?)",
                [$sessionId, $variantId, $quantity, $tanggalSewa, $tanggalKembali]
            );
            
            return [
                'success' => true,
                'message' => 'Item berhasil ditambahkan ke keranjang',
                'quantity' => $quantity
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Mengupdate jumlah item di keranjang
 */
function updateCartItem($cartId, $quantity) {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    try {
        // Dapatkan data item di keranjang
        $cartItem = $db->fetchOne(
            "SELECT variant_id, tanggal_sewa, tanggal_kembali FROM cart WHERE id = ? AND session_id = ?",
            [$cartId, $sessionId]
        );
        
        if (!$cartItem) {
            return [
                'success' => false,
                'message' => 'Item tidak ditemukan di keranjang'
            ];
        }
        
        // Jika quantity 0, hapus item dari keranjang
        if ($quantity <= 0) {
            return removeCartItem($cartId);
        }
        
        // Validasi ketersediaan stok
        if (!checkStockAvailability($cartItem['variant_id'], $cartItem['tanggal_sewa'], $cartItem['tanggal_kembali'], $quantity)) {
            return [
                'success' => false,
                'message' => 'Stok tidak mencukupi untuk jumlah yang dipilih'
            ];
        }
        
        // Update quantity
        $db->execute(
            "UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND session_id = ?",
            [$quantity, $cartId, $sessionId]
        );
        
        return [
            'success' => true,
            'message' => 'Jumlah item diperbarui',
            'quantity' => $quantity
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Menghapus item dari keranjang
 */
function removeCartItem($cartId) {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    try {
        $db->execute(
            "DELETE FROM cart WHERE id = ? AND session_id = ?",
            [$cartId, $sessionId]
        );
        
        return [
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Mengosongkan keranjang
 */
function clearCart() {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    try {
        $db->execute(
            "DELETE FROM cart WHERE session_id = ?",
            [$sessionId]
        );
        
        return [
            'success' => true,
            'message' => 'Keranjang berhasil dikosongkan'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

/**
 * Mendapatkan semua item di keranjang
 */
function getCartItems() {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    try {
        $items = $db->fetchAll(
            "SELECT c.*, 
                    v.ukuran, v.dp, v.pelunasan, v.stok_total,
                    i.nama_baju, i.kategori, i.foto
             FROM cart c
             JOIN item_variants v ON c.variant_id = v.id
             JOIN items i ON v.item_id = i.id
             WHERE c.session_id = ?
             ORDER BY c.created_at DESC",
            [$sessionId]
        );
        
        // Hitung total DP dan pelunasan untuk setiap item
        $cartTotal = 0;
        $dpTotal = 0;
        $pelunasanTotal = 0;
        
        foreach ($items as &$item) {
            // Hitung jumlah hari sewa
            $startDate = new DateTime($item['tanggal_sewa']);
            $endDate = new DateTime($item['tanggal_kembali']);
            $interval = $startDate->diff($endDate);
            $days = $interval->days + 1; // +1 karena hari terakhir juga dihitung
            
            $item['jumlah_hari'] = $days;
            $item['dp_total'] = $item['dp'] * $item['quantity'];
            $item['pelunasan_total'] = $item['pelunasan'] * $item['quantity'];
            $item['subtotal'] = ($item['dp'] + $item['pelunasan']) * $item['quantity'];
            
            $dpTotal += $item['dp_total'];
            $pelunasanTotal += $item['pelunasan_total'];
            $cartTotal += $item['subtotal'];
        }
        
        return [
            'items' => $items,
            'total' => [
                'dp' => $dpTotal,
                'pelunasan' => $pelunasanTotal,
                'grand_total' => $cartTotal
            ]
        ];
    } catch (Exception $e) {
        return [
            'items' => [],
            'total' => [
                'dp' => 0,
                'pelunasan' => 0,
                'grand_total' => 0
            ]
        ];
    }
}

/**
 * Proses checkout dari keranjang
 */
function processCheckout($customerData) {
    $db = Database::getInstance();
    $sessionId = getCartSessionId();
    
    // Validasi data pelanggan
    if (empty($customerData['nama']) || empty($customerData['hp']) || empty($customerData['lokasi'])) {
        return [
            'success' => false,
            'message' => 'Data pelanggan tidak lengkap'
        ];
    }
    
    try {
        // Mulai transaksi database
        $db->getConnection()->beginTransaction();
        
        // Ambil semua item di keranjang
        $cartItems = getCartItems()['items'];
        
        if (empty($cartItems)) {
            return [
                'success' => false,
                'message' => 'Keranjang kosong'
            ];
        }
        
        // Hitung total keseluruhan
        $totalDp = 0;
        $totalPelunasan = 0;
        $totalItems = 0;
        
        foreach ($cartItems as $item) {
            $totalDp += $item['dp_total'];
            $totalPelunasan += $item['pelunasan_total'];
            $totalItems += $item['quantity'];
        }
        
        // Insert data ke tabel rentals
        $rentalId = $db->insert(
            "INSERT INTO rentals (
                customer_nama, customer_hp, customer_lokasi,
                variant_id, tanggal_sewa, tanggal_kembali, tanggal_booking,
                jumlah, dp_bayar, pelunasan_bayar, status, catatan,
                jenis_jaminan, total_items
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)",
            [
                $customerData['nama'], 
                $customerData['hp'], 
                $customerData['lokasi'],
                $cartItems[0]['variant_id'], // Gunakan variant_id item pertama sebagai referensi
                $cartItems[0]['tanggal_sewa'], // Gunakan tanggal item pertama
                $cartItems[0]['tanggal_kembali'],
                date('Y-m-d'), // tanggal_booking hari ini
                1, // jumlah selalu 1 di tabel utama
                $totalDp,
                $totalPelunasan,
                $customerData['catatan'] ?? '',
                $customerData['jenis_jaminan'] ?? 'KTP',
                $totalItems
            ]
        );
        
        // Insert ke tabel rental_items untuk setiap item di keranjang
        foreach ($cartItems as $item) {
            $db->execute(
                "INSERT INTO rental_items (
                    rental_id, variant_id, quantity, price, dp, pelunasan,
                    tanggal_sewa, tanggal_kembali
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $rentalId,
                    $item['variant_id'],
                    $item['quantity'],
                    $item['dp'] + $item['pelunasan'], // total harga per item
                    $item['dp'],
                    $item['pelunasan'],
                    $item['tanggal_sewa'],
                    $item['tanggal_kembali']
                ]
            );
            
            // Update stok untuk setiap tanggal
            $dates = getRentalDateRange($item['tanggal_sewa'], $item['tanggal_kembali']);
            foreach ($dates as $date) {
                updateStock($item['variant_id'], $date, $item['quantity']);
            }
        }
        
        // Kosongkan keranjang
        $db->execute(
            "DELETE FROM cart WHERE session_id = ?",
            [$sessionId]
        );
        
        // Commit transaksi
        $db->getConnection()->commit();
        
        return [
            'success' => true,
            'message' => 'Pesanan berhasil dibuat',
            'rental_id' => $rentalId
        ];
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $db->getConnection()->rollBack();
        
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}
?>
