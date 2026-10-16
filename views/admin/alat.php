<?php
// 1. Jalankan session & koneksi database sebelum ada output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/koneksi.php';

// Verifikasi hak akses admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// 2. Pemrosesan Form Tambah Alat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_alat'])) {
    $id_kategori = (int)$_POST['id_kategori'];
    $nama_alat   = mysqli_real_escape_string($koneksi, trim($_POST['nama_alat']));
    $stok        = (int)$_POST['stok'];
    $kondisi     = mysqli_real_escape_string($koneksi, trim($_POST['kondisi']));

    if ($id_kategori > 0 && !empty($nama_alat) && $stok >= 0 && !empty($kondisi)) {
        $q_insert = "INSERT INTO alat (id_kategori, nama_alat, stok, kondisi) 
                     VALUES ('$id_kategori', '$nama_alat', '$stok', '$kondisi')";
        if (mysqli_query($koneksi, $q_insert)) {
            $_SESSION['flash_pesan'] = "Barang / Alat '$nama_alat' berhasil ditambahkan!";
        } else {
            $_SESSION['flash_error'] = "Gagal menambah data alat: " . mysqli_error($koneksi);
        }
    } else {
        $_SESSION['flash_error'] = "Semua kolom form wajib diisi dengan benar!";
    }

    header("Location: alat.php");
    exit;
}

// 3. Pemrosesan Hapus Alat
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Cek apakah alat masih ada di transaksi peminjaman
    $cek_pinjam = mysqli_query($koneksi, "SELECT id_detail FROM detail_peminjaman WHERE id_alat = '$id_hapus'");
    if ($cek_pinjam && mysqli_num_rows($cek_pinjam) > 0) {
        $_SESSION['flash_error'] = "Data alat tidak dapat dihapus karena pernah tercatat dalam transaksi peminjaman!";
    } else {
        if (mysqli_query($koneksi, "DELETE FROM alat WHERE id_alat = '$id_hapus'")) {
            $_SESSION['flash_pesan'] = "Data alat berhasil dihapus!";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus data alat!";
        }
    }

    header("Location: alat.php");
    exit;
}

// 4. Sertakan Header Layout HTML setelah proses logika selesai
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan notifikasi flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Ambil daftar kategori untuk dropdown
$kategori_options = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Ambil daftar alat dengan join kategori
$query_alat = "SELECT a.*, k.nama_kategori 
               FROM alat a 
               LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
               ORDER BY a.id_alat DESC";
$res_alat = mysqli_query($koneksi, $query_alat);
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Inventaris Data Alat</h1>
        <p class="text-slate-500 text-sm">Manajemen sarana dan prasarana yang tersedia untuk dipinjamkan.</p>
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

<!-- Form Tambah Alat -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
    <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-boxes-stacked text-blue-600"></i>
        <span>Tambah Alat / Barang Baru</span>
    </h2>

    <form action="" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori <span class="text-rose-500">*</span></label>
            <select name="id_kategori" required class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">-- Pilih Kategori --</option>
                <?php if ($kategori_options && mysqli_num_rows($kategori_options) > 0): ?>
                    <?php while ($kat = mysqli_fetch_assoc($kategori_options)): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Alat <span class="text-rose-500">*</span></label>
            <input type="text" name="nama_alat" required placeholder="Contoh: Proyektor LCD / Kabel VGA" autocomplete="off"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Jumlah Stok <span class="text-rose-500">*</span></label>
            <input type="number" name="stok" min="0" required placeholder="0"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Kondisi Barang <span class="text-rose-500">*</span></label>
            <input type="text" name="kondisi" required placeholder="Contoh: Baik / Rusak Ringan" autocomplete="off"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <button type="submit" name="tambah_alat"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Alat</span>
            </button>
        </div>
    </form>
</div>

<!-- Tabel Daftar Alat -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h2 class="font-bold text-slate-800 text-base">Daftar Inventaris Sarpras</h2>
        <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-semibold">
            Total: <?= mysqli_num_rows($res_alat) ?> Barang
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-16 text-center">ID</th>
                    <th class="px-5 py-3.5">Kategori</th>
                    <th class="px-5 py-3.5">Nama Alat / Barang</th>
                    <th class="px-5 py-3.5 text-center">Stok Available</th>
                    <th class="px-5 py-3.5 text-center">Kondisi</th>
                    <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($res_alat && mysqli_num_rows($res_alat) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($res_alat)): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-500 text-center">
                                #<?= $row['id_alat'] ?>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-blue-50 text-blue-700 border border-blue-100 px-2.5 py-1 rounded-lg text-xs font-bold uppercase">
                                    <?= htmlspecialchars($row['nama_kategori'] ?? 'Uncategorized') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">
                                <?= htmlspecialchars($row['nama_alat']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php if ($row['stok'] > 0): ?>
                                    <span class="font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full text-xs">
                                        <?= $row['stok'] ?> Unit
                                    </span>
                                <?php else: ?>
                                    <span class="font-extrabold text-rose-600 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full text-xs">
                                        Habis
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-xs font-medium border border-slate-200 capitalize">
                                    <?= htmlspecialchars($row['kondisi']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <a href="?hapus=<?= $row['id_alat'] ?>" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')"
                                   class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Hapus</span>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400">
                            Belum ada inventaris alat yang tersimpan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>