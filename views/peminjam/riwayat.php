<?php
include_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Pastikan hanya peminjam yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../../login.php");
    exit;
}

$id_peminjam_session = $_SESSION['id_peminjam'] ?? 1;

// Query mengambil data peminjaman beserta nama peminjam, kelas (alamat), dan daftar barang
$query = "SELECT p.*, 
                 u.nama_lengkap, 
                 GROUP_CONCAT(CONCAT(a.nama_alat, ' (', dp.jumlah, ' unit)') SEPARATOR ', ') AS daftar_barang,
                 pg.denda
          FROM peminjaman p
          LEFT JOIN users u ON p.id_peminjam = u.id_user
          LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
          LEFT JOIN alat a ON dp.id_alat = a.id_alat
          LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
          WHERE p.id_peminjam = '$id_peminjam_session'
          GROUP BY p.id_peminjaman
          ORDER BY p.id_peminjaman DESC";

$result = mysqli_query($koneksi, $query);
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Riwayat Peminjaman Saya</h1>
        <p class="text-slate-500 text-sm">Daftar transaksi pengajuan peminjaman sarpras yang pernah Anda ajukan.</p>
    </div>
    <a href="katalog.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
        <i class="fa-solid fa-plus text-xs"></i>
        <span>Pinjam Sarpras</span>
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5">ID Transaksi</th>
                    <th class="px-5 py-3.5">Nama Peminjam</th>
                    <th class="px-5 py-3.5">Barang Dipinjam</th>
                    <th class="px-5 py-3.5">Tanggal Pinjam</th>
                    <th class="px-5 py-3.5">Rencana Kembali</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Denda</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-4 font-bold text-slate-800">
                                #<?= $row['id_peminjaman'] ?>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                <?= htmlspecialchars($row['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? 'Siswa') ?>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border text-xs font-medium inline-block">
                                    <?= htmlspecialchars($row['daftar_barang'] ?? 'Alat Sarpras') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?= $row['tanggal_pinjam'] ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?= $row['tanggal_kembali_rencana'] ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php
                                $status = strtoupper($row['status']);
                                $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                
                                if ($status === 'PENDING') {
                                    $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                } elseif ($status === 'DISETUJUI') {
                                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                } elseif ($status === 'SELESAI') {
                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                } elseif ($status === 'DITOLAK') {
                                    $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                }
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-medium whitespace-nowrap">
                                Rp <?= number_format($row['denda'] ?? 0, 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-slate-400">
                            Belum ada riwayat peminjaman sarpras.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>