<?php
function show_rental_table($rentals, $status) {
    ?>
    <div class="mt-2 bg-white shadow overflow-hidden sm:rounded-lg">
        <!-- Tombol untuk beralih tampilan -->
        <div class="bg-gray-50 p-4 border-b flex justify-between items-center">
            <h3 class="text-sm font-medium text-gray-700">Tampilan:</h3>
            <div class="flex space-x-2">
                <button id="view-table-btn-<?= $status ?>" class="px-3 py-1 text-xs font-medium rounded bg-indigo-100 text-indigo-800 hover:bg-indigo-200">
                    <i class="fas fa-table mr-1"></i> Tabel
                </button>
                <button id="view-card-btn-<?= $status ?>" class="px-3 py-1 text-xs font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    <i class="fas fa-th-large mr-1"></i> Card
                </button>
            </div>
        </div>
        
        <!-- Tampilan tabel untuk desktop (aktif saat tombol tabel diklik) -->
        <div id="table-view-<?= $status ?>" class="hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Pesanan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penyewa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Baju</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Sewa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($rentals)): ?>
                    <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data pesanan</td></tr>
                    <?php else: ?>
                    <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($rental['kode_unik'] ?? 'N/A') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><div class="font-medium"><?= htmlspecialchars($rental['customer_nama']) ?></div><div class="text-xs"><?= htmlspecialchars($rental['customer_hp']) ?></div></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><div><?= htmlspecialchars($rental['nama_baju']) ?></div><div class="text-xs">Ukuran: <?= htmlspecialchars($rental['ukuran']) ?></div></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatTanggalIndo($rental['tanggal_sewa']) ?><div class="text-xs">s/d</div><?= formatTanggalIndo($rental['tanggal_kembali']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php
                                switch ($status) {
                                    case 'pending':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'approved':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'returned':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'canceled':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= getStatusLabel($status) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($rental['catatan'] ?: '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($status === 'pending'): ?>
                            <a href="approve_rental.php?id=<?= $rental['id'] ?>" class="text-green-600 hover:text-green-900 mr-2"><i class="fas fa-check"></i> Setujui</a>
                            <?php endif; ?>
                            <a href="view_fixed_detail.php?id=<?= $rental['id'] ?>" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-eye"></i> Detail</a>
                            <?php if ($status === 'approved'): ?>
                            <a href="return_rental.php?id=<?= $rental['id'] ?>" class="text-blue-600 hover:text-blue-900 ml-2"><i class="fas fa-undo"></i> Kembalikan</a>
                            <?php endif; ?>
                            <a href="edit_rental.php?id=<?= $rental['id'] ?>" class="text-yellow-600 hover:text-yellow-900 ml-2"><i class="fas fa-edit"></i> Edit</a>
                            <?php if ($status !== 'returned' && $status !== 'canceled'): ?>
                            <a href="cancel_rental.php?id=<?= $rental['id'] ?>" class="text-gray-600 hover:text-gray-900 ml-2" onclick="return confirm('Batalkan pesanan ini?')"><i class="fas fa-ban"></i> Cancel</a>
                            <?php endif; ?>
                            <a href="delete_rental.php?id=<?= $rental['id'] ?>" class="text-red-600 hover:text-red-900 ml-2" onclick="return confirm('Hapus pesanan ini secara permanen?')"><i class="fas fa-trash"></i> Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tampilan card untuk semua perangkat - tampilan vertikal responsif -->
        <div id="card-view-<?= $status ?>" class="block w-full p-4">
            <div class="space-y-6">
                <?php if (empty($rentals)): ?>
                <div class="text-center text-sm text-gray-500 py-4">Tidak ada data pesanan</div>
                <?php else: ?>
                
                <?php foreach ($rentals as $rental): ?>

                <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100 mb-6 mx-2">
                    <!-- Header card dengan kode dan status -->
                    <div class="flex justify-between items-center p-4 border-b
                        <?php
                        switch ($status) {
                            case 'pending':
                                echo 'bg-gradient-to-r from-yellow-50 to-yellow-100 border-l-4 border-yellow-400';
                                break;
                            case 'approved':
                                echo 'bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-400';
                                break;
                            case 'returned':
                                echo 'bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-400';
                                break;
                            case 'canceled':
                                echo 'bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-400';
                                break;
                            default:
                                echo 'bg-gray-50 border-l-4 border-gray-400';
                        }
                        ?>">
                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($rental['kode_unik'] ?? 'N/A') ?></div>
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch ($status) {
                                case 'pending':
                                    echo 'bg-yellow-500 text-white';
                                    break;
                                case 'approved':
                                    echo 'bg-green-500 text-white';
                                    break;
                                case 'returned':
                                    echo 'bg-blue-500 text-white';
                                    break;
                                case 'canceled':
                                    echo 'bg-red-500 text-white';
                                    break;
                                default:
                                    echo 'bg-gray-500 text-white';
                            }
                            ?>">
                            <?= getStatusLabel($status) ?>
                        </span>
                    </div>
                    
                    <!-- Body card dengan informasi pesanan -->
                    <div class="p-4">
                        <!-- Info Penyewa dan Baju -->
                        <div class="flex flex-col gap-4 mb-4">
                            <!-- Info Penyewa -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 rounded-full bg-indigo-100 p-2 mr-3">
                                    <i class="fas fa-user text-indigo-600"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Penyewa</div>
                                    <div class="font-medium text-sm"><?= htmlspecialchars($rental['customer_nama']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($rental['customer_hp']) ?></div>
                                </div>
                            </div>
                            
                            <!-- Info Baju -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 rounded-full bg-purple-100 p-2 mr-3">
                                    <i class="fas fa-tshirt text-purple-600"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Baju</div>
                                    <div class="font-medium text-sm"><?= htmlspecialchars($rental['nama_baju']) ?></div>
                                    <div class="text-xs text-gray-500">Ukuran: <?= htmlspecialchars($rental['ukuran']) ?></div>
                                </div>
                            </div>
                            
                            <!-- Tanggal Sewa -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 rounded-full bg-blue-100 p-2 mr-3">
                                    <i class="fas fa-calendar-alt text-blue-600"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Periode Sewa</div>
                                    <div class="font-medium text-sm"><?= formatTanggalIndo($rental['tanggal_sewa']) ?></div>
                                    <div class="text-xs text-gray-500">s/d <?= formatTanggalIndo($rental['tanggal_kembali']) ?></div>
                                </div>
                            </div>
                            
                            <!-- Catatan -->
                            <div class="flex items-start">
                                <div class="flex-shrink-0 rounded-full bg-gray-100 p-2 mr-3">
                                    <i class="fas fa-sticky-note text-gray-600"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Catatan</div>
                                    <div class="text-sm"><?= htmlspecialchars($rental['catatan'] ?: '-') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tombol Aksi -->
                        <div class="border-t pt-3 mt-2 flex flex-wrap gap-2">
                            <?php if ($status === 'pending'): ?>
                            <a href="approve_rental.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-green-500 hover:bg-green-600 text-white rounded px-3 py-1.5 transition duration-200">
                                <i class="fas fa-check mr-1"></i> Setujui
                            </a>
                            <?php endif; ?>
                            
                            <a href="view_fixed_detail.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-indigo-500 hover:bg-indigo-600 text-white rounded px-3 py-1.5 transition duration-200">
                                <i class="fas fa-eye mr-1"></i> Detail
                            </a>
                            
                            <?php if ($status === 'approved'): ?>
                            <a href="return_rental.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-blue-500 hover:bg-blue-600 text-white rounded px-3 py-1.5 transition duration-200">
                                <i class="fas fa-undo mr-1"></i> Kembalikan
                            </a>
                            <?php endif; ?>
                            
                            <a href="edit_rental.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded px-3 py-1.5 transition duration-200">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            
                            <div class="flex-grow"></div>
                            
                            <?php if ($status !== 'returned' && $status !== 'canceled'): ?>
                            <a href="cancel_rental.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded px-3 py-1.5 transition duration-200" onclick="return confirm('Batalkan pesanan ini?')">
                                <i class="fas fa-ban mr-1"></i> Cancel
                            </a>
                            <?php endif; ?>
                            
                            <a href="delete_rental.php?id=<?= $rental['id'] ?>" class="inline-flex items-center text-xs font-medium bg-red-100 hover:bg-red-200 text-red-700 rounded px-3 py-1.5 transition duration-200" onclick="return confirm('Hapus pesanan ini secara permanen?')">
                                <i class="fas fa-trash mr-1"></i> Hapus
                            </a>
                        </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const status = '<?= $status ?>';
            const tableViewBtn = document.getElementById('view-table-btn-' + status);
            const cardViewBtn = document.getElementById('view-card-btn-' + status);
            const tableView = document.getElementById('table-view-' + status);
            const cardView = document.getElementById('card-view-' + status);
            
            if (!tableViewBtn || !cardViewBtn || !tableView || !cardView) {
                console.error('Error: One or more UI elements not found for status ' + status);
                return;
            }
            
            // Cek local storage untuk tampilan terakhir yang dipilih
            const savedView = localStorage.getItem('rentalViewPreference-' + status) || 'card';
            if (savedView === 'table') {
                showTableView();
            } else {
                showCardView();
            }
            
            // Event listener untuk tombol tampilan tabel
            tableViewBtn.addEventListener('click', function() {
                showTableView();
                localStorage.setItem('rentalViewPreference-' + status, 'table');
            });
            
            // Event listener untuk tombol tampilan card
            cardViewBtn.addEventListener('click', function() {
                showCardView();
                localStorage.setItem('rentalViewPreference-' + status, 'card');
            });
            
            function showTableView() {
                tableView.classList.remove('hidden');
                cardView.classList.add('hidden');
                tableViewBtn.classList.remove('bg-indigo-100', 'text-indigo-800');
                tableViewBtn.classList.add('bg-indigo-600', 'text-white');
                cardViewBtn.classList.remove('bg-indigo-600', 'text-white');
                cardViewBtn.classList.add('bg-indigo-100', 'text-indigo-800');
            }
            
            function showCardView() {
                tableView.classList.add('hidden');
                cardView.classList.remove('hidden');
                cardViewBtn.classList.remove('bg-indigo-100', 'text-indigo-800');
                cardViewBtn.classList.add('bg-indigo-600', 'text-white');
                tableViewBtn.classList.remove('bg-indigo-600', 'text-white');
                tableViewBtn.classList.add('bg-indigo-100', 'text-indigo-800');
            }
        });
    </script>
    <?php
}