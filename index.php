<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

// Fitur Filter / Pencarian Alat
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';

if (!empty($keyword)) {
    $query_alat = "SELECT a.*, k.nama_kategori 
                   FROM alat a 
                   JOIN kategori k ON a.id_kategori = k.id_kategori 
                   WHERE a.nama_alat LIKE '%$keyword%' OR k.nama_kategori LIKE '%$keyword%' 
                   ORDER BY a.id_alat DESC";
} else {
    $query_alat = "SELECT a.*, k.nama_kategori 
                   FROM alat a 
                   JOIN kategori k ON a.id_kategori = k.id_kategori 
                   ORDER BY a.id_alat DESC";
}

$result_alat = mysqli_query($koneksi, $query_alat);

// Hitung total statistik sederhana
$total_alat_tersedia = 0;
if ($result_alat) {
    $total_alat_tersedia = mysqli_num_rows($result_alat);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIP-SARPRAS - SMK Negeri 3 Kendal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col justify-between">

    <div>
        <!-- Navbar Header Landing -->
        <nav class="bg-slate-900 text-white border-b border-slate-800 shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-blue-600 group-hover:bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-md transition">
                        <i class="fa-solid fa-school text-lg"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-wide block leading-tight text-white">SIP-SARPRAS</span>
                        <span class="text-xs text-slate-400 font-medium block">SMK Negeri 3 Kendal</span>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    <?php if (isset($_SESSION['role'])): ?>
                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-gauge"></i>
                            <span>Masuk Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Login / Masuk</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="bg-white border-b border-slate-200 py-12 md:py-16">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7 space-y-4">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wider">
                        <i class="fa-solid fa-boxes-packing text-xs"></i> Layanan Sarana & Prasarana
                    </span>
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-slate-900 leading-tight">
                        Peminjaman Alat & Fasilitas <br><span class="text-blue-600">SMK Negeri 3 Kendal</span>
                    </h1>
                    <p class="text-slate-500 text-sm md:text-base max-w-2xl">
                        Sistem pelayanan inventaris sarana dan prasarana sekolah untuk mendukung kegiatan pembelajaran, praktikum, dan ekstrakurikuler siswa secara efisien.
                    </p>
                    <div class="pt-2 flex flex-wrap items-center gap-3">
                    </div>
                </div>

                <!-- Banner Infografis Samping -->
                <div class="lg:col-span-5 grid grid-cols-2 gap-4">
                    <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-sm border border-slate-800 flex flex-col justify-between">
                        <div class="w-10 h-10 bg-blue-600/30 text-blue-400 rounded-xl flex items-center justify-center text-lg mb-4">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <span class="text-2xl font-extrabold block">Cepat & Praktis</span>
                            <span class="text-xs text-slate-400">Pengajuan pinjam daring tanpa antre</span>
                        </div>
                    </div>
                    <div class="bg-blue-600 text-white p-5 rounded-2xl shadow-sm flex flex-col justify-between">
                        <div class="w-10 h-10 bg-white/20 text-white rounded-xl flex items-center justify-center text-lg mb-4">
                            <i class="fa-solid fa-shield-check"></i>
                        </div>
                        <div>
                            <span class="text-2xl font-extrabold block">Terdata Rapi</span>
                            <span class="text-xs text-blue-100">Pemantauan status pengajuan & denda</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Section: Katalog Alat -->
        <div id="katalog" class="max-w-7xl mx-auto px-6 py-12">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Katalog Sarana & Prasarana Tersedia</h2>
                    <p class="text-sm text-slate-500">Pilih barang yang Anda butuhkan lalu tekan tombol pinjam.</p>
                </div>

                <!-- Form Pencarian Alat -->
                <form action="index.php#katalog" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <input type="text" name="cari" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari barang atau kategori..." 
                               class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <i class="fa-solid fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
                        Cari
                    </button>
                    <?php if (!empty($keyword)): ?>
                        <a href="index.php#katalog" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-3 py-2 rounded-xl text-sm font-semibold transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Grid Kartu Inventaris Sarpras -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if ($result_alat && mysqli_num_rows($result_alat) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_alat)): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[11px] font-bold bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-full uppercase">
                                        <?= htmlspecialchars($row['nama_kategori']) ?>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-400">
                                        ID: #<?= $row['id_alat'] ?>
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-slate-800 mb-1">
                                    <?= htmlspecialchars($row['nama_alat']) ?>
                                </h3>

                                <div class="space-y-1 my-3 text-sm">
                                    <div class="flex justify-between text-slate-600">
                                        <span class="text-xs text-slate-400">Kondisi:</span>
                                        <span class="font-medium text-slate-700 capitalize"><?= htmlspecialchars($row['kondisi']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-slate-600">
                                        <span class="text-xs text-slate-400">Ketersediaan:</span>
                                        <?php if ($row['stok'] > 0): ?>
                                            <span class="font-extrabold text-emerald-600"><?= $row['stok'] ?> Unit</span>
                                        <?php else: ?>
                                            <span class="font-extrabold text-rose-500">Habis</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <?php if ($row['stok'] > 0): ?>
                                    <a href="views/peminjam/katalog.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-sm">
                                        <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                        <span>Pinjam Alat Ini</span>
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full bg-slate-100 text-slate-400 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed border border-slate-200">
                                        Stok Tidak Tersedia
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-400">
                        <i class="fa-solid fa-box-open text-4xl mb-3 block text-slate-300"></i>
                        <span>Belum ada sarana prasarana yang tersedia saat ini.</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Footer Sistem -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800 text-center text-xs">
        <div class="max-w-7xl mx-auto px-6">
            <p>&copy; <?= date('Y') ?> SIP-SARPRAS SMK Negeri 3 Kendal. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

</body>
</html>