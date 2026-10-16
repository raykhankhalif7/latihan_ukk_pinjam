<?php
// 1. Jalankan session & koneksi database sebelum ada output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/koneksi.php';

// Verifikasi hak akses (Admin & Petugas)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'petugas'])) {
    header("Location: ../../login.php");
    exit;
}

// 2. Pemrosesan Aksi Persetujuan / Penolakan Peminjaman
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_peminjaman = (int)$_GET['id'];
    $aksi          = $_GET['aksi'];
    $id_petugas    = $_SESSION['id_user'] ?? 1;

    if (in_array($aksi, ['setujui', 'tolak'])) {
        $status_baru = ($aksi === 'setujui') ? 'disetujui' : 'ditolak';

        // Update status peminjaman
        $q_update = "UPDATE peminjaman SET status = '$status_baru', id_petugas = '$id_petugas' WHERE id_peminjaman = '$id_peminjaman'";
        
        if (mysqli_query($koneksi, $q_update)) {
            // Jika disetujui, kurangi stok alat sesuai detail peminjaman
            if ($status_baru === 'disetujui') {
                $q_detail = mysqli_query($koneksi, "SELECT id_alat, jumlah FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
                while ($d = mysqli_fetch_assoc($q_detail)) {
                    $id_alat = $d['id_alat'];
                    $jumlah  = $d['jumlah'];
                    mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jumlah WHERE id_alat = '$id_alat'");
                }
            }

            $_SESSION['flash_pesan'] = "Status peminjaman #$id_peminjaman berhasil diperbarui menjadi " . strtoupper($status_baru) . "!";
        } else {
            $_SESSION['flash_error'] = "Gagal memperbarui status peminjaman: " . mysqli_error($koneksi);
        }
    }

    header("Location: persetujuan.php");
    exit;
}

// 3. Sertakan Header Layout HTML setelah logika selesai
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Query mengambil daftar pengajuan peminjaman (JOIN LANGSUNG KE TABEL USERS)
$query = "SELECT p.*, 
                 COALESCE(u.nama_lengkap, u.username, 'Siswa') AS nama_peminjam,
                 GROUP_CONCAT(CONCAT(a.nama_alat, ' (', dp.jumlah, ' unit)') SEPARATOR ', ') AS daftar_barang
          FROM peminjaman p
          LEFT JOIN users u ON p.id_peminjam = u.id_user
          LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
          LEFT JOIN alat a ON dp.id_alat = a.id_alat
          GROUP BY p.id_peminjaman
          ORDER BY p.id_peminjaman DESC";

$result = mysqli_query($koneksi, $query);
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Persetujuan Peminjaman Sarpras</h1>
        <p class="text-slate-500 text-sm">Verifikasi dan kelola pengajuan izin peminjaman alat dari siswa/peminjam.</p>
    </div>
</div>

<?php if ($pesan): ?>
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-base"></i>
        <span><?= htmlspecialchars($pesan) ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-rose-100 border border-rose-200 text-rose-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-base"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h2 class="font-bold text-slate-800 text-base">Daftar Pengajuan Peminjaman</h2>
        <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-semibold">
            Total: <?= $result ? mysqli_num_rows($result) : 0 ?> Transaksi
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-16 text-center">ID</th>
                    <th class="px-5 py-3.5">Nama Peminjam</th>
                    <th class="px-5 py-3.5">Barang Dipinjam</th>
                    <th class="px-5 py-3.5">Tanggal Pinjam</th>
                    <th class="px-5 py-3.5">Rencana Kembali</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center w-40">Aksi Persetujuan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-500 text-center">
                                #<?= $row['id_peminjaman'] ?>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">
                                <?= htmlspecialchars($row['nama_peminjam']) ?>
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
                                if ($status === 'PENDING') $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                elseif ($status === 'DISETUJUI') $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                elseif ($status === 'SELESAI') $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                elseif ($status === 'DITOLAK') $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <?php if (strtolower($row['status']) === 'pending'): ?>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="?aksi=setujui&id=<?= $row['id_peminjaman'] ?>" 
                                           onclick="return confirm('Setujui peminjaman ini?')"
                                           class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1 shadow-sm">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            <span>Setujui</span>
                                        </a>
                                        <a href="?aksi=tolak&id=<?= $row['id_peminjaman'] ?>" 
                                           onclick="return confirm('Tolak peminjaman ini?')"
                                           class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1 shadow-sm">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                            <span>Tolak</span>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">Selesai Diproses</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400">
                            Belum ada pengajuan peminjaman.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>