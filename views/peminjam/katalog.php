<?php
include_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Pastikan hanya peminjam yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../../login.php");
    exit;
}

$pesan = '';
$error = '';

// Pemrosesan Pengajuan Peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_pinjam'])) {
    $id_alat       = mysqli_real_escape_string($koneksi, $_POST['id_alat']);
    $id_user_siswa = (int)$_POST['id_user_siswa'];
    $tgl_pinjam    = date('Y-m-d');
    $tgl_kembali   = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $jumlah        = (int)$_POST['jumlah'];

    if ($id_user_siswa <= 0) {
        $error = "Silakan pilih nama siswa peminjam!";
    } else {
        // Cek stok alat
        $q_stok = mysqli_query($koneksi, "SELECT stok, nama_alat FROM alat WHERE id_alat = '$id_alat'");
        $d_stok = mysqli_fetch_assoc($q_stok);

        if ($d_stok && $d_stok['stok'] >= $jumlah) {
            
            // Query INSERT ke tabel peminjaman
            // id_peminjam diisi dengan id_user dari tabel users
            $query_pinjam = "INSERT INTO peminjaman (id_peminjam, tanggal_pinjam, tanggal_kembali_rencana, status) 
                             VALUES ('$id_user_siswa', '$tgl_pinjam', '$tgl_kembali', 'pending')";
            
            $res_insert = mysqli_query($koneksi, $query_pinjam);

            if ($res_insert) {
                $id_peminjaman_baru = mysqli_insert_id($koneksi);

                // Simpan ke tabel detail_peminjaman
                mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah) VALUES ('$id_peminjaman_baru', '$id_alat', '$jumlah')");

                $pesan = "Pengajuan peminjaman berhasil dikirim! Menunggu persetujuan petugas.";
            } else {
                $error = "Gagal mengajukan peminjaman: " . mysqli_error($koneksi);
            }

        } else {
            $error = "Stok sarpras tidak mencukupi!";
        }
    }
}

// Ambil daftar user dengan role peminjam untuk dropdown
$siswa_list = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role = 'peminjam' ORDER BY nama_lengkap ASC");

// Ambil daftar alat yang tersedia
$query_alat = "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.stok > 0";
$alat_list  = mysqli_query($koneksi, $query_alat);
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Katalog Peminjaman Sarpras</h1>
    <p class="text-slate-500 text-sm">Pilih barang dan pilih nama Anda untuk mengajukan peminjaman.</p>
</div>

<?php if ($pesan): ?>
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-base"></i>
        <span><?= $pesan ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-rose-100 border border-rose-200 text-rose-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-base"></i>
        <span><?= $error ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($alat_list && mysqli_num_rows($alat_list) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($alat_list)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-semibold bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full border border-blue-100 uppercase">
                        <?= htmlspecialchars($row['nama_kategori']) ?>
                    </span>
                    <h3 class="text-lg font-bold text-slate-800 mt-3"><?= htmlspecialchars($row['nama_alat']) ?></h3>
                    <p class="text-sm text-slate-500 mt-1">Kondisi: <span class="font-medium text-slate-700"><?= htmlspecialchars($row['kondisi']) ?></span></p>
                    <p class="text-sm text-slate-600 mt-1 mb-4">
                        Stok Tersedia: <span class="font-bold text-emerald-600"><?= $row['stok'] ?> unit</span>
                    </p>
                </div>

                <!-- Form Pengajuan Peminjaman -->
                <form action="" method="POST" class="border-t pt-4 mt-2 space-y-3">
                    <input type="hidden" name="id_alat" value="<?= $row['id_alat'] ?>">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Nama Siswa <span class="text-rose-500">*</span></label>
                        <select name="id_user_siswa" required class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-800">
                            <option value="">-- Pilih Nama Siswa --</option>
                            <?php 
                            if ($siswa_list && mysqli_num_rows($siswa_list) > 0) {
                                mysqli_data_seek($siswa_list, 0); // Reset pointer loop
                                while ($s = mysqli_fetch_assoc($siswa_list)) {
                                    $selected = (isset($_SESSION['id_user']) && $_SESSION['id_user'] == $s['id_user']) ? 'selected' : '';
                                    echo "<option value='{$s['id_user']}' $selected>" . htmlspecialchars($s['nama_lengkap']) . " (" . htmlspecialchars($s['username']) . ")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah Unit</label>
                            <input type="number" name="jumlah" min="1" max="<?= $row['stok'] ?>" value="1" required 
                                   class="w-full px-3 py-1.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tgl Pengembalian</label>
                            <input type="date" name="tgl_kembali" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                   class="w-full px-3 py-1.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <button type="submit" name="ajukan_pinjam" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Ajukan Peminjaman</span>
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full bg-white p-8 rounded-2xl border text-center text-slate-500">
            Belum ada sarpras yang siap dipinjam saat ini.
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>