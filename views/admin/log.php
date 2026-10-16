<?php
require_once '../../config/koneksi.php';
require_once '../layout/header.php';

if ($_SESSION['role'] !== 'admin') { header("Location: ../../index.php"); exit; }

$logs = mysqli_query($koneksi, "SELECT l.*, u.username FROM log_aktifitas l LEFT JOIN users u ON l.id_user=u.id_user ORDER BY l.waktu DESC");
?>

<h1 class="text-2xl font-bold mb-4">Log Aktivitas Sistem</h1>

<table class="w-full bg-white shadow rounded">
    <thead class="bg-slate-200 border-b">
        <tr>
            <th class="p-3 text-left">Waktu</th>
            <th class="p-3 text-left">User</th>
            <th class="p-3 text-left">Aktivitas</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($l = mysqli_fetch_assoc($logs)): ?>
        <tr class="border-b">
            <td class="p-3 text-sm text-slate-500"><?= $l['waktu'] ?></td>
            <td class="p-3 font-semibold"><?= $l['username'] ?: 'Sistem' ?></td>
            <td class="p-3"><?= $l['aktifitas'] ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once '../layout/footer.php'; ?>