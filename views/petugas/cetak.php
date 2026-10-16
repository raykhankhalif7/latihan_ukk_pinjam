<?php
require_once '../../config/koneksi.php';

$query = "SELECT p.*, u.nama_lengkap, pg.tanggal_dikembalikan, pg.denda, pg.status_pengembalian 
          FROM peminjaman p 
          JOIN users u ON p.id_peminjam=u.id_user
          LEFT JOIN pengembalian pg ON p.id_peminjaman=pg.id_peminjaman";
$data = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Laporan Peminjaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-white" onload="window.print()">
    <h1 class="text-2xl font-bold text-center mb-1">LAPORAN PEMINJAMAN ALAT</h1>
    <p class="text-center text-sm mb-6 text-slate-500">Tanggal Cetak: <?= date('d-m-Y') ?></p>

    <table class="w-full border-collapse border border-slate-400 text-sm">
        <thead>
            <tr class="bg-slate-100">
                <th class="border border-slate-300 p-2">ID</th>
                <th class="border border-slate-300 p-2">Peminjam</th>
                <th class="border border-slate-300 p-2">Tgl Pinjam</th>
                <th class="border border-slate-300 p-2">Status</th>
                <th class="border border-slate-300 p-2">Kondisi Kembalian</th>
                <th class="border border-slate-300 p-2">Denda</th>
            </tr>
        </thead>
        <tbody>
            <?php while($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td class="border border-slate-300 p-2 text-center"><?= $d['id_peminjaman'] ?></td>
                <td class="border border-slate-300 p-2"><?= $d['nama_lengkap'] ?></td>
                <td class="border border-slate-300 p-2"><?= $d['tanggal_pinjam'] ?></td>
                <td class="border border-slate-300 p-2 font-bold uppercase text-xs"><?= $d['status'] ?></td>
                <td class="border border-slate-300 p-2"><?= $d['status_pengembalian'] ?: '-' ?></td>
                <td class="border border-slate-300 p-2">Rp <?= number_format($d['denda'] ?: 0, 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>