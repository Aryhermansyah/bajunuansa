<?php
function show_rental_table($rentals, $status) {
    ?>
    <div class="mt-2 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
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
    </div>
    <?php
} 