<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role'])) {
    header("Location: ../../login.php");
    exit;
}
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIP-SARPRAS - SMKN 3 Kendal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 flex flex-col justify-between">

    <div>
        <!-- Navbar Utama -->
        <nav class="bg-slate-900 text-white border-b border-slate-800 shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 py-3.5 flex flex-col lg:flex-row justify-between items-center gap-4">
                
                <!-- Brand Logo / Identity -->
                <a href="#" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-blue-600 group-hover:bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-md transition">
                        <i class="fa-solid fa-school text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-lg tracking-wide block leading-tight text-white">SIP-SARPRAS</span>
                            <span class="bg-blue-500/20 text-blue-400 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-500/30 uppercase">
                                <?= $role ?>
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-medium block">SMK Negeri 3 Kendal</span>
                    </div>
                </a>

                <!-- Menu Navigasi Berdasarkan Role -->
                <div class="flex flex-wrap items-center justify-center gap-1 bg-slate-800/80 p-1.5 rounded-xl border border-slate-700/60 text-xs font-medium">
                    <?php if ($role == 'admin'): ?>
                        <a href="../admin/index.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'index.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-gauge"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="../admin/user.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'user.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-users"></i>
                            <span>Data User</span>
                        </a>
                        <a href="../admin/kategori.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'kategori.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-tags"></i>
                            <span>Kategori</span>
                        </a>
                        <a href="../admin/alat.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'alat.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Data Alat</span>
                        </a>
                        <a href="../petugas/persetujuan.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'persetujuan.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Peminjaman</span>
                        </a>
                        <a href="../petugas/pengembalian.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'pengembalian.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Pengembalian</span>
                        </a>
                        <a href="../admin/log.php" 
                           class="px-3 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'log.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Log Aktivitas</span>
                        </a>

                    <?php elseif ($role == 'petugas'): ?>
                        <a href="../petugas/persetujuan.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'persetujuan.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span>Persetujuan</span>
                        </a>
                        <a href="../petugas/pengembalian.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'pengembalian.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Pengembalian</span>
                        </a>
                        <a href="../petugas/cetak.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'cetak.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-print"></i>
                            <span>Cetak Laporan</span>
                        </a>

                    <?php elseif ($role == 'peminjam'): ?>
                        <a href="../peminjam/katalog.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'katalog.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-box-archive"></i>
                            <span>Katalog Sarpras</span>
                        </a>
                        <a href="../peminjam/riwayat.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'riwayat.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-history"></i>
                            <span>Riwayat Saya</span>
                        </a>
                        <a href="../peminjam/pengembalian_alat.php" 
                           class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?= $current_page == 'pengembalian_alat.php' ? 'bg-blue-600 text-white shadow' : 'text-slate-300 hover:text-white hover:bg-slate-700/60' ?>">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                            <span>Pengembalian</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Profile Info & Logout -->
                <div class="flex items-center gap-3 border-l border-slate-800 pl-4">
                    <div class="text-right hidden sm:block">
                        <span class="block text-xs font-semibold text-slate-200">
                            <?= $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Admin' ?>
                        </span>
                        <span class="text-[10px] text-slate-400">
                            <?= $_SESSION['username'] ?? 'admin' ?>
                        </span>
                    </div>
                    <a href="../../logout.php" 
                       onclick="return confirm('Apakah Anda yakin ingin keluar?')"
                       class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </div>

            </div>
        </nav>

        <!-- Container Konten Utama -->
        <div class="max-w-7xl mx-auto p-6">