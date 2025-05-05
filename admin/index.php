<?php
/**
 * Admin Dashboard
 * Halaman utama panel admin dengan navigasi sederhana
 */

// Load semua yang diperlukan
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Mulai session
session_start();

// Periksa apakah user sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect ke halaman login jika belum login
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$dbType = $db->getDbType();

// Ambil informasi statistik (optional)
try {
    $totalProducts = $db->fetchOne("SELECT COUNT(*) as total FROM items")['total'];
    $totalRentals = $db->fetchOne("SELECT COUNT(*) as total FROM rentals")['total'];
    $pendingRentals = $db->fetchOne("SELECT COUNT(*) as total FROM rentals WHERE status = 'pending'")['total'];
    $fixedRentals = $db->fetchOne("SELECT COUNT(*) as total FROM rentals WHERE status = 'approved'")['total'];
} catch (Exception $e) {
    // Tangani error jika terjadi
    $totalProducts = 0;
    $totalRentals = 0;
    $pendingRentals = 0;
    $fixedRentals = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pearls Bridal</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 whitespace-nowrap">Pearls Bridal</h1>
                </div>
                <div class="flex items-center">
                    <a href="../frontend/index.php" class="text-gray-600 hover:text-gray-900 flex items-center">
                        <i class="fas fa-home mr-2"></i> <span>Kembali ke Frontend</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-2xl font-bold text-gray-900">
                Dashboard Admin
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Selamat datang di panel admin. Pilih menu di bawah untuk mengelola aplikasi.
            </p>
        </div>

        <!-- Statistics -->
        <div class="mt-4 px-4 sm:px-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Produk -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <i class="fas fa-tshirt text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Produk
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?= $totalProducts ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Pesanan -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <i class="fas fa-shopping-cart text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Total Pesanan
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?= $totalRentals ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pesanan Pending -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Pesanan Pending
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?= $pendingRentals ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pesanan Fix -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Pesanan Fix
                                    </dt>
                                    <dd>
                                        <div class="text-lg font-medium text-gray-900">
                                            <?= $fixedRentals ?>
                                        </div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Cards -->
        <div class="mt-8 px-4 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Menu Utama</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Kelola Pemesanan -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-full p-5 mb-4">
                                <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Kelola Pemesanan</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Lihat dan kelola semua pemesanan dan penyewaan.
                            </p>
                            <div class="flex space-x-3">
                                <a href="rental_list.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                                    Semua Pesanan
                                </a>
                                <a href="fixed_rentals.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    Pesanan Fix
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kelola Produk -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-full p-5 mb-4">
                                <i class="fas fa-tshirt text-indigo-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Kelola Produk</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Tambah, edit, dan lihat semua produk baju.
                            </p>
                            <div class="flex space-x-3">
                                <a href="view_products.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                    Lihat Produk
                                </a>
                                <a href="add_product.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    Tambah Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Master -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-5 mb-4">
                                <i class="fas fa-database text-blue-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Data Master</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Kelola kategori, ukuran, dan setting lainnya.
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="manage_categories.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                    Kategori
                                </a>
                                <a href="manage_sizes.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    Ukuran
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tools & Utilities -->
        <div class="mt-8 px-4 sm:px-6 mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tools & Utilities</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Tambah Pesanan Manual -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-full p-5 mb-4">
                                <i class="fas fa-plus-circle text-purple-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Tambah Pesanan</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Tambahkan pesanan baru secara manual.
                            </p>
                            <a href="add_rental.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none">
                                Tambah Pesanan
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Pengaturan Database -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-gray-100 rounded-full p-5 mb-4">
                                <i class="fas fa-tools text-gray-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Database Tools</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Tools untuk pemeliharaan database.
                            </p>
                            <a href="../tools/setup_categories_sizes.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none">
                                Setup Database
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kembali ke Frontend -->
                <div class="card bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex flex-col items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-5 mb-4">
                                <i class="fas fa-home text-yellow-600 text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Frontend</h4>
                            <p class="text-sm text-gray-500 text-center mb-4">
                                Lihat tampilan frontend aplikasi.
                            </p>
                            <a href="../frontend/index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none">
                                Buka Frontend
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-inner py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; <?= date('Y') ?> Pearls Bridal. All rights reserved.</p>
                <p class="mt-1">Database: <?= strtoupper($dbType) ?></p>
            </div>
        </div>
    </footer>
</body>
</html>
