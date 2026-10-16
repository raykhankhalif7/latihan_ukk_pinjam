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

// 2. Pemrosesan Form Tambah User (Harus sebelum include header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $raw_password = trim($_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $role         = mysqli_real_escape_string($koneksi, $_POST['role']);

    if (!empty($username) && !empty($raw_password) && !empty($nama_lengkap)) {
        // Cek apakah username sudah dipakai di database
        $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username'");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $_SESSION['flash_error'] = "Username '$username' sudah digunakan oleh user lain!";
        } else {
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
            $q_insert = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password_hash', '$nama_lengkap', '$role')";
            
            if (mysqli_query($koneksi, $q_insert)) {
                $_SESSION['flash_pesan'] = "User baru ($username) berhasil ditambahkan!";
            } else {
                $_SESSION['flash_error'] = "Gagal menambah user: " . mysqli_error($koneksi);
            }
        }
    } else {
        $_SESSION['flash_error'] = "Semua field form wajib diisi!";
    }

    // Redirect aman karena belum ada HTML yang di-render
    header("Location: user.php");
    exit;
}

// 3. Pemrosesan Hapus User
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    if (isset($_SESSION['id_user']) && $_SESSION['id_user'] == $id_hapus) {
        $_SESSION['flash_error'] = "Anda tidak dapat menghapus akun yang sedang digunakan!";
    } else {
        if (mysqli_query($koneksi, "DELETE FROM users WHERE id_user = '$id_hapus'")) {
            $_SESSION['flash_pesan'] = "User berhasil dihapus!";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus user!";
        }
    }
    header("Location: user.php");
    exit;
}

// 4. Baru Sertakan Header Layout HTML setelah proses logika selesai
include_once __DIR__ . '/../layout/header.php';

// Ambil pesan notifikasi flash session
$pesan = $_SESSION['flash_pesan'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_pesan'], $_SESSION['flash_error']);

// Ambil daftar users
$users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user ASC");
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen Data User</h1>
        <p class="text-slate-500 text-sm">Kelola hak akses pengguna sistem (Admin, Petugas, dan Peminjam/Siswa).</p>
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

<!-- Form Tambah User -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
    <h2 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-user-plus text-blue-600"></i>
        <span>Tambah Pengguna Baru</span>
    </h2>

    <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Username <span class="text-rose-500">*</span></label>
            <input type="text" name="username" required placeholder="Masukkan username" autocomplete="off"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Password <span class="text-rose-500">*</span></label>
            <input type="password" name="password" required placeholder="••••••••"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" name="nama_lengkap" required placeholder="Nama pengelola / siswa"
                   class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Role / Hak Akses <span class="text-rose-500">*</span></label>
            <select name="role" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="admin">Admin</option>
                <option value="petugas">Petugas</option>
                <option value="peminjam">Peminjam</option>
            </select>
        </div>

        <div>
            <button type="submit" name="tambah_user"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition flex items-center justify-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah User</span>
            </button>
        </div>
    </form>
</div>

<!-- Tabel Daftar User -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h2 class="font-bold text-slate-800 text-base">Daftar Pengguna Sistem</h2>
        <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-semibold">
            Total: <?= mysqli_num_rows($users) ?> User
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-xs">
                <tr>
                    <th class="px-5 py-3.5 w-16 text-center">ID</th>
                    <th class="px-5 py-3.5">Username</th>
                    <th class="px-5 py-3.5">Nama Lengkap</th>
                    <th class="px-5 py-3.5 text-center">Role</th>
                    <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($users && mysqli_num_rows($users) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($users)): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-500 text-center">
                                #<?= $row['id_user'] ?>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                <?= htmlspecialchars($row['username']) ?>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <?= htmlspecialchars($row['nama_lengkap']) ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?php
                                $r = strtolower($row['role']);
                                $roleBadge = 'bg-slate-100 text-slate-700 border-slate-200';
                                if ($r === 'admin') $roleBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                                elseif ($r === 'petugas') $roleBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                                elseif ($r === 'peminjam') $roleBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider <?= $roleBadge ?>">
                                    <?= $r ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <a href="?hapus=<?= $row['id_user'] ?>" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"
                                   class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Hapus</span>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400">
                            Belum ada data user.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>