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

// 2. Pemrosesan Form Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($koneksi, trim($_POST['nama_kategori']));

    if (!empty($nama_kategori)) {
        // Cek duplikasi nama kategori
        $cek = mysqli_query($koneksi, "SELECT id_kategori FROM kategori WHERE nama_kategori = '$nama_kategori'");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $_SESSION['flash_error'] = "Kategori '$nama_kategori' sudah ada!";
        } else {
            $q_insert = "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')";
            if (mysqli_query($koneksi, $q_insert)) {
                $_SESSION['flash_pesan'] = "Kategori '$nama_kategori' berhasil ditambahkan!";
            } else {
                $_SESSION['flash_error'] = "Gagal menambah kategori: " . mysqli_error($koneksi);
            }
        }
    } else {
        $_SESSION['flash_error'] = "Nama kategori tidak boleh kosong!";
    }

    header("Location: kategori.php");
    exit;
}

// 3. Pemrosesan Hapus Kategori
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Cek apakah kategori masih dipakai oleh data alat
    $cek_alat = mysqli_query($koneksi, "SELECT id_alat FROM alat WHERE id_kategori = '$id_hapus'");
    if ($cek_alat && mysqli_num_rows($cek_alat) > 0) {
        $_SESSION['flash_error'] = "Kategori tidak dapat dihapus karena masih digunakan oleh data barang/alat!";
    } else {
        if (mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori = '$id_hapus'")) {
            $_SESSION['flash_pesan'] = "Kategori berhasil dihapus!";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus kategori!";
        }
    }

    header("Location: kategori.php");
    exit;
}

// 4. Sertakan Header Layout HTML setelah proses logika selesai
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan notifikasi flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Ambil daftar kategori
$categories = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori ASC");
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Kategori Alat</h1>
        <p class="text-slate-500 text-sm">Tambahkan dan kelola pengelompokan jenis sarana prasarana sekolah.</p>
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

<!-- Form Tambah Kategori -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
    <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-tags text-blue-600"></i>
        <span>Tambah Kategori Baru</span>
    </h2>

    <form action="" method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
            <input type="text" name="nama_kategori" required placeholder="Contoh: Elektronik, Olahraga, Multimedia" autocomplete="off"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" name="tambah_kategori"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tambah Kategori</span>
        </button>
    </form>
</div>

<!-- Tabel Daftar Kategori -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h2 class="font-bold text-slate-800 text-base">Daftar Kategori Alat</h2>
        <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-semibold">
            Total: <?= mysqli_num_rows($categories) ?> Kategori
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-20 text-center">ID</th>
                    <th class="px-5 py-3.5">Nama Kategori</th>
                    <th class="px-5 py-3.5 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($categories)): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-500 text-center">
                                #<?= $row['id_kategori'] ?>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                <span class="bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1 rounded-lg text-xs uppercase tracking-wider font-bold">
                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <a href="?hapus=<?= $row['id_kategori'] ?>" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
                                   class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Hapus</span>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-8 text-slate-400">
                            Belum ada data kategori.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>