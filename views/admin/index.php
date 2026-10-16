<?php
include_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Verifikasi hak akses admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Administrator';

// Fetch statistik data
$total_users     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;
$total_kategori  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kategori"))['total'] ?? 0;
$total_alat      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM alat"))['total'] ?? 0;
$total_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman"))['total'] ?? 0;

// Fetch log aktivitas terbaru (5 data)
$query_log = "SELECT l.*, u.username, u.role 
              FROM log_aktifitas l 
              LEFT JOIN users u ON l.id_user = u.id_user 
              ORDER BY l.waktu DESC LIMIT 5";
$res_log = mysqli_query($koneksi, $query_log);
?>

<!-- Banner Selamat Datang -->
<div class="bg-slate-900 text-white p-6 md:p-8 rounded-2xl shadow-md border border-slate-800 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
            Selamat Datang di Panel Administrator, <span class="text-blue-400"><?= htmlspecialchars($nama_admin) ?></span>!
        </h1>
        <p class="text-slate-400 text-sm mt-1">
            Kelola data pengguna, kategori, inventaris alat, peminjaman, pengembalian, dan pantau log aktifitas sistem.
        </p>
    </div>
    <div class="bg-blue-600/20 border border-blue-500/30 px-4 py-2 rounded-xl text-blue-300 text-xs font-semibold flex items-center gap-2">
        <i class="fa-solid fa-shield-halved text-sm"></i>
        <span>Hak Akses: Administrator</span>
    </div>
</div>

<!-- Grid Kartu Statistik Summary -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Card Data Users -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">DATA USERS</span>
                <span class="text-3xl font-black text-slate-800"><?= $total_users ?></span>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-users font-bold"></i>
            </div>
        </div>
        <a href="user.php" class="bg-slate-50 hover:bg-blue-50 border-t border-slate-100 px-5 py-2.5 text-xs font-semibold text-blue-600 flex items-center justify-between transition">
            <span>Kelola Users</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <!-- Card Kategori Alat -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">KATEGORI ALAT</span>
                <span class="text-3xl font-black text-slate-800"><?= $total_kategori ?></span>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-tags"></i>
            </div>
        </div>
        <a href="kategori.php" class="bg-slate-50 hover:bg-emerald-50 border-t border-slate-100 px-5 py-2.5 text-xs font-semibold text-emerald-600 flex items-center justify-between transition">
            <span>Kelola Kategori</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <!-- Card Total Alat -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">TOTAL ALAT</span>
                <span class="text-3xl font-black text-slate-800"><?= $total_alat ?></span>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>
        <a href="alat.php" class="bg-slate-50 hover:bg-amber-50 border-t border-slate-100 px-5 py-2.5 text-xs font-semibold text-amber-600 flex items-center justify-between transition">
            <span>Kelola Data Alat</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <!-- Card Total Transaksi -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
        <div class="p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">TOTAL TRANSAKSI</span>
                <span class="text-3xl font-black text-slate-800"><?= $total_transaksi ?></span>
            </div>
            <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>
        <a href="../petugas/persetujuan.php" class="bg-slate-50 hover:bg-sky-50 border-t border-slate-100 px-5 py-2.5 text-xs font-semibold text-sky-600 flex items-center justify-between transition">
            <span>Lihat Peminjaman</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

</div>

<!-- Tabel Log Aktivitas Terbaru -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
            <h2 class="font-bold text-slate-800">Log Aktifitas Terbaru</h2>
        </div>
        <a href="log.php" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3.5 py-2 rounded-xl font-semibold transition flex items-center gap-1.5">
            <span>Lihat Semua Log</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-16">No</th>
                    <th class="px-5 py-3.5">Pengguna</th>
                    <th class="px-5 py-3.5">Role</th>
                    <th class="px-5 py-3.5">Aktifitas</th>
                    <th class="px-5 py-3.5">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($res_log && mysqli_num_rows($res_log) > 0): ?>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($res_log)): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-semibold text-slate-500"><?= $no++ ?></td>
                            <td class="px-5 py-3.5 font-bold text-slate-800">
                                <?= htmlspecialchars($row['username'] ?? 'Sistem') ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <?php
                                $r = strtolower($row['role'] ?? 'admin');
                                $roleBadge = 'bg-slate-100 text-slate-600 border-slate-200';
                                if ($r === 'admin') $roleBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                                elseif ($r === 'petugas') $roleBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                                elseif ($r === 'peminjam') $roleBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                                ?>
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border uppercase <?= $roleBadge ?>">
                                    <?= htmlspecialchars($r) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                <?= htmlspecialchars($row['aktifitas'] ?? '-') ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                <?= $row['waktu'] ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400">
                            Belum ada log aktivitas yang tercatat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>