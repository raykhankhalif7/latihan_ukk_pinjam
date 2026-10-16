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

// 2. Pemrosesan Pengembalian Barang oleh Petugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pengembalian'])) {
    $id_peminjaman       = (int)$_POST['id_peminjaman'];
    $tgl_dikembalikan    = date('Y-m-d');
    $denda_input         = (float)$_POST['denda'];
    $status_pengembalian = mysqli_real_escape_string($koneksi, $_POST['status_pengembalian']);

    if ($id_peminjaman > 0) {
        // Ambil data tanggal rencana kembali
        $q_p = mysqli_query($koneksi, "SELECT tanggal_kembali_rencana FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");
        $d_p = mysqli_fetch_assoc($q_p);
        $tgl_rencana = $d_p['tanggal_kembali_rencana'] ?? $tgl_dikembalikan;

        // Perhitungan Denda Otomatis (Rp 5.000 / hari jika terlambat)
        $total_denda = 0;
        if (strtotime($tgl_dikembalikan) > strtotime($tgl_rencana)) {
            $selisih_detik  = strtotime($tgl_dikembalikan) - strtotime($tgl_rencana);
            $hari_terlambat = floor($selisih_detik / (60 * 60 * 24));
            $total_denda   += ($hari_terlambat * 5000);
        }

        // Tambahan Denda Kondisi Barang
        if ($status_pengembalian === 'rusak') {
            $total_denda += ($denda_input > 0) ? $denda_input : 50000;
        } elseif ($status_pengembalian === 'hilang') {
            $total_denda += ($denda_input > 0) ? $denda_input : 150000;
        } else {
            $total_denda += $denda_input;
        }

        // Insert data ke tabel pengembalian
        $q_insert = "INSERT INTO pengembalian (id_peminjaman, tanggal_dikembalikan, denda, status_pengembalian) 
                     VALUES ('$id_peminjaman', '$tgl_dikembalikan', '$total_denda', '$status_pengembalian')";
        
        if (mysqli_query($koneksi, $q_insert)) {
            // Update status peminjaman menjadi 'selesai'
            mysqli_query($koneksi, "UPDATE peminjaman SET status = 'selesai' WHERE id_peminjaman = '$id_peminjaman'");

            // Kembalikan stok / perbarui kondisi alat
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

            $_SESSION['flash_pesan'] = "Transaksi #$id_peminjaman berhasil dikembalikan!";
        } else {
            $_SESSION['flash_error'] = "Gagal memproses pengembalian: " . mysqli_error($koneksi);
        }
    }

    header("Location: pengembalian.php");
    exit;
}

// 3. Sertakan Header Layout HTML
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Query seluruh transaksi peminjaman (JOIN LANGSUNG KE TABEL USERS TANPA TABEL PEMINJAM)
$query = "SELECT p.*, 
                 COALESCE(u.nama_lengkap, u.username, 'Siswa') AS nama_peminjam,
                 a.nama_alat,
                 dp.jumlah,
                 pg.tanggal_dikembalikan, pg.denda, pg.status_pengembalian
          FROM peminjaman p
          LEFT JOIN users u ON p.id_peminjam = u.id_user
          LEFT JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
          LEFT JOIN alat a ON dp.id_alat = a.id_alat
          LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
          WHERE p.status IN ('disetujui', 'selesai')
          ORDER BY p.id_peminjaman DESC";

$result = mysqli_query($koneksi, $query);
?>

<!-- Judul Halaman Persis Seperti Foto 2 -->
<div class="mb-5 flex items-center gap-2.5">
    <div class="w-8 h-8 rounded-full bg-amber-100 border border-amber-300 flex items-center justify-center text-amber-600 shadow-sm">
        <i class="fa-regular fa-eye text-base"></i>
    </div>
    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Memantau Pengembalian Alat</h1>
</div>

<?php if ($pesan): ?>
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl mb-5 text-xs font-semibold flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-sm"></i>
        <span><?= htmlspecialchars($pesan) ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-rose-100 border border-rose-200 text-rose-800 p-3.5 rounded-xl mb-5 text-xs font-semibold flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-sm"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<!-- Tabel Memantau Pengembalian Alat Persis Foto 2 -->
<div class="bg-white rounded-xl border border-slate-200/80 shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-700">
            <!-- Header Hitam Pekat Sesuai Gambar 2 -->
            <thead class="bg-slate-900 text-white font-extrabold border-b border-slate-800 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3.5 w-10 text-center">No</th>
                    <th class="px-4 py-3.5">Peminjam</th>
                    <th class="px-4 py-3.5">Alat</th>
                    <th class="px-4 py-3.5">Jumlah</th>
                    <th class="px-4 py-3.5">Rencana Kembali</th>
                    <th class="px-4 py-3.5">Tgl Dikembalikan</th>
                    <th class="px-4 py-3.5 text-center">Kondisi Alat</th>
                    <th class="px-4 py-3.5">Denda</th>
                    <th class="px-4 py-3.5 text-center">Status Transaction</th>
                    <th class="px-4 py-3.5 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-medium">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $is_selesai   = (strtolower($row['status']) === 'selesai');
                        $tgl_rencana  = $row['tanggal_kembali_rencana'] ? date('d/m/Y', strtotime($row['tanggal_kembali_rencana'])) : '-';
                        $tgl_kembali  = ($is_selesai && $row['tanggal_dikembalikan']) ? date('d/m/Y', strtotime($row['tanggal_dikembalikan'])) : '-';
                        
                        // Hitung Denda
                        $denda_val = (float)($row['denda'] ?? 0);
                        if (!$is_selesai) {
                            $tgl_sekarang = date('Y-m-d');
                            if (strtotime($tgl_sekarang) > strtotime($row['tanggal_kembali_rencana'])) {
                                $selisih = floor((strtotime($tgl_sekarang) - strtotime($row['tanggal_kembali_rencana'])) / (60 * 60 * 24));
                                $denda_val = $selisih * 5000;
                            }
                        }
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3.5 text-center font-bold text-slate-500">
                                <?= $no++ ?>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-800">
                                <?= htmlspecialchars($row['nama_peminjam']) ?>
                            </td>
                            <td class="px-4 py-3.5 text-slate-700">
                                <?= htmlspecialchars($row['nama_alat'] ?? 'Sarpras') ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <?= ($row['jumlah'] ?? 1) ?> unit
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-slate-600">
                                <?= $tgl_rencana ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-slate-600">
                                <?= $tgl_kembali ?>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <?php if ($is_selesai && !empty($row['status_pengembalian'])): ?>
                                    <?php
                                    $k = strtolower($row['status_pengembalian']);
                                    $badgeKondisi = 'bg-sky-500 text-white';
                                    if ($k === 'rusak') $badgeKondisi = 'bg-amber-500 text-white';
                                    elseif ($k === 'hilang') $badgeKondisi = 'bg-rose-600 text-white';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold capitalize inline-block shadow-sm <?= $badgeKondisi ?>">
                                        <?= ucfirst($k) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 font-bold">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-rose-600 whitespace-nowrap">
                                Rp <?= number_format($denda_val, 0, ',', '.') ?>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <?php if ($is_selesai): ?>
                                    <span class="bg-emerald-600 text-white px-3 py-1 rounded-full text-[11px] font-extrabold shadow-sm inline-block">
                                        Dikembalikan
                                    </span>
                                <?php else: ?>
                                    <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-[11px] font-extrabold shadow-sm inline-block">
                                        Sedang Dipinjam
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <?php if (!$is_selesai): ?>
                                    <button onclick="bukaModal(<?= $row['id_peminjaman'] ?>)" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-center gap-1 mx-auto">
                                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                        <span>Proses</span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-8 text-slate-400">
                            Belum ada transaksi peminjaman / pengembalian alat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Pop-up Proses Pengembalian -->
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