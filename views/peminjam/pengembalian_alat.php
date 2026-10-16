<?php
// 1. Jalankan session & koneksi database sebelum ada output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/koneksi.php';

// Pastikan hanya peminjam yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../../login.php");
    exit;
}

$id_user_login = $_SESSION['id_user'] ?? 0;
$tarif_denda_per_hari = 5000; // Denda keterlambatan Rp 5.000 / hari

// 2. Pemrosesan Aksi Pengembalian Barang oleh Peminjam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pengembalian'])) {
    $id_peminjaman       = (int)$_POST['id_peminjaman'];
    $tgl_dikembalikan    = date('Y-m-d');
    $denda_input         = (float)($_POST['denda'] ?? 0);
    $status_pengembalian = mysqli_real_escape_string($koneksi, $_POST['status_pengembalian']);

    if ($id_peminjaman > 0) {
        // Ambil data tanggal rencana kembali
        $q_p = mysqli_query($koneksi, "SELECT tanggal_kembali_rencana FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");
        $d_p = mysqli_fetch_assoc($q_p);
        $tgl_rencana = $d_p['tanggal_kembali_rencana'] ?? $tgl_dikembalikan;

        // --- Perhitungan Denda Otomatis ---
        $total_denda = 0;

        // A. Hitung Denda Keterlambatan Tanggal
        if (strtotime($tgl_dikembalikan) > strtotime($tgl_rencana)) {
            $selisih_detik  = strtotime($tgl_dikembalikan) - strtotime($tgl_rencana);
            $hari_terlambat = floor($selisih_detik / (60 * 60 * 24));
            $total_denda   += ($hari_terlambat * $tarif_denda_per_hari);
        }

        // B. Hitung Denda Berdasarkan Kondisi Barang
        if ($status_pengembalian === 'rusak') {
            $total_denda += ($denda_input > 0) ? $denda_input : 50000;
        } elseif ($status_pengembalian === 'hilang') {
            $total_denda += ($denda_input > 0) ? $denda_input : 150000;
        } else {
            $total_denda += $denda_input;
        }

        // Cek apakah transaksi sudah pernah dikembalikan sebelumnya
        $cek_pg = mysqli_query($koneksi, "SELECT id_pengembalian FROM pengembalian WHERE id_peminjaman = '$id_peminjaman'");
        
        if ($cek_pg && mysqli_num_rows($cek_pg) == 0) {
            // INSERT ke tabel pengembalian
            $q_insert = "INSERT INTO pengembalian (id_peminjaman, tanggal_dikembalikan, denda, status_pengembalian) 
                         VALUES ('$id_peminjaman', '$tgl_dikembalikan', '$total_denda', '$status_pengembalian')";
            
            if (mysqli_query($koneksi, $q_insert)) {
                // Update status di tabel peminjaman menjadi 'selesai'
                mysqli_query($koneksi, "UPDATE peminjaman SET status = 'selesai' WHERE id_peminjaman = '$id_peminjaman'");

                // Kembalikan stok / perbarui kondisi alat di tabel 'alat'
                $q_detail = mysqli_query($koneksi, "SELECT id_alat, jumlah FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
                while ($d = mysqli_fetch_assoc($q_detail)) {
                    $id_alat = $d['id_alat'];
                    $jumlah  = $d['jumlah'];

                    if ($status_pengembalian === 'baik') {
                        mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jumlah WHERE id_alat = '$id_alat'");
                    } else {
                        mysqli_query($koneksi, "UPDATE alat SET kondisi = '$status_pengembalian' WHERE id_alat = '$id_alat'");
                        if ($status_pengembalian === 'rusak') {
                            mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jumlah WHERE id_alat = '$id_alat'");
                        }
                    }
                }

                $_SESSION['flash_pesan'] = "Barang peminjaman #$id_peminjaman berhasil dikembalikan dengan Denda Rp " . number_format($total_denda, 0, ',', '.') . "!";
            } else {
                $_SESSION['flash_error'] = "Gagal memproses pengembalian: " . mysqli_error($koneksi);
            }
        } else {
            $_SESSION['flash_error'] = "Transaksi peminjaman ini sudah selesai dikembalikan!";
        }
    }

    header("Location: pengembalian_alat.php");
    exit;
}

// 3. Sertakan Header Layout HTML setelah logika selesai
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Query mengambil transaksi peminjaman milik user login dari tabel users
$query = "SELECT p.*, 
                 u.nama_lengkap, u.username,
                 GROUP_CONCAT(CONCAT(a.nama_alat, ' (', dp.jumlah, ' unit)') SEPARATOR ', ') AS daftar_barang,
                 pg.tanggal_dikembalikan, pg.denda, pg.status_pengembalian
          FROM peminjaman p
          JOIN users u ON p.id_peminjam = u.id_user
          LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
          LEFT JOIN alat a ON dp.id_alat = a.id_alat
          LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
          WHERE p.id_peminjam = '$id_user_login'
          GROUP BY p.id_peminjaman
          ORDER BY p.id_peminjaman DESC";

$result = mysqli_query($koneksi, $query);
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Form & Riwayat Pengembalian Sarpras</h1>
        <p class="text-slate-500 text-sm">Kembalikan alat yang dipinjam, cek estimasi denda jika terlambat/rusak/hilang, dan pantau riwayat pengembalian.</p>
    </div>
    <a href="katalog.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2 self-start md:self-auto shadow-sm">
        <i class="fa-solid fa-plus text-xs"></i>
        <span>Pinjam Alat Lain</span>
    </a>
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
        <h2 class="font-bold text-slate-800 text-base">Daftar Peminjaman Saya</h2>
        <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-semibold">
            Total: <?= $result ? mysqli_num_rows($result) : 0 ?> Transaksi
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-16 text-center">ID</th>
                    <th class="px-5 py-3.5">Barang Dipinjam</th>
                    <th class="px-5 py-3.5">Tanggal Pinjam</th>
                    <th class="px-5 py-3.5">Batas Kembali</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center">Denda</th>
                    <th class="px-5 py-3.5 text-center w-52">Aksi Pengembalian</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $status_pinjam = strtolower($row['status']);
                        $tgl_sekarang  = date('Y-m-d');
                        $tgl_rencana   = $row['tanggal_kembali_rencana'];
                        
                        $denda_display = 0;

                        if ($status_pinjam === 'selesai') {
                            $denda_display = (float)($row['denda'] ?? 0);
                        } elseif ($status_pinjam === 'disetujui') {
                            if (strtotime($tgl_sekarang) > strtotime($tgl_rencana)) {
                                $selisih_detik = strtotime($tgl_sekarang) - strtotime($tgl_rencana);
                                $hari_terlambat = floor($selisih_detik / (60 * 60 * 24));
                                $denda_display  = $hari_terlambat * $tarif_denda_per_hari;
                            }
                        }
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-500 text-center">
                                #<?= $row['id_peminjaman'] ?>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">
                                <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg border border-blue-100 text-xs font-semibold inline-block">
                                    <?= htmlspecialchars($row['daftar_barang'] ?? 'Alat Sarpras') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <?= $row['tanggal_pinjam'] ?>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-700">
                                <?= $row['tanggal_kembali_rencana'] ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php
                                $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                if ($status_pinjam === 'pending') $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                elseif ($status_pinjam === 'disetujui') $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                elseif ($status_pinjam === 'selesai') $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                elseif ($status_pinjam === 'ditolak') $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider <?= $badgeClass ?>">
                                    <?= $status_pinjam ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="font-extrabold text-slate-800 block">
                                    Rp <?= number_format($denda_display, 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php if ($status_pinjam === 'disetujui'): ?>
                                    <!-- Tombol Pemicu Modal Proses Pengembalian -->
                                    <button onclick="bukaModal(<?= $row['id_peminjaman'] ?>)" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm mx-auto">
                                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                        <span>Proses</span>
                                    </button>
                                <?php elseif ($status_pinjam === 'pending'): ?>
                                    <span class="text-xs text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-100 font-medium inline-block">
                                        Menunggu Persetujuan
                                    </span>
                                <?php elseif ($status_pinjam === 'selesai'): ?>
                                    <div class="text-xs text-slate-500 text-left bg-slate-50 p-2 rounded-lg border border-slate-100">
                                        <div>Dikembalikan: <b><?= $row['tanggal_dikembalikan'] ?></b></div>
                                        <div>Kondisi: <b class="uppercase <?= strtolower($row['status_pengembalian']) === 'baik' ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $row['status_pengembalian'] ?></b></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">Pengajuan Ditolak</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400">
                            Belum ada transaksi peminjaman alat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Pop-up Proses Pengembalian Barang -->
<div id="modalPengembalian" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-boxes-packing text-blue-600"></i>
            <span>Proses Pengembalian Barang</span>
        </h3>
        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="id_peminjaman" id="modal_id_peminjaman">

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Kondisi Barang Dikembalikan <span class="text-rose-500">*</span></label>
                <select name="status_pengembalian" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="baik">BAIK (Normal)</option>
                    <option value="rusak">RUSAK (+Rp 50.000)</option>
                    <option value="hilang">HILANG (+Rp 150.000)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Denda Tambahan Manual (Opsional)</label>
                <input type="number" name="denda" min="0" value="0" placeholder="0" 
                       class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="tutupModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" name="proses_pengembalian" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-sm">
                    Simpan Pengembalian
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) {
    document.getElementById('modal_id_peminjaman').value = id;
    document.getElementById('modalPengembalian').classList.remove('hidden');
    document.getElementById('modalPengembalian').classList.add('flex');
}

function tutupModal() {
    document.getElementById('modalPengembalian').classList.add('hidden');
    document.getElementById('modalPengembalian').classList.remove('flex');
}
</script>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>    