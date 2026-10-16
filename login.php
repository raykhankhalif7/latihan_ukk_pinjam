<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

$error = '';
$pesan = '';

// Tampilkan pesan notifikasi dari registrasi jika ada
if (isset($_SESSION['flash_pesan'])) {
    $pesan = $_SESSION['flash_pesan'];
    unset($_SESSION['flash_pesan']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        
        // Autentikasi tunggal melalui tabel USERS
        $query_user = "SELECT * FROM users WHERE username = '$username' OR nama_lengkap = '$username'";
        $res_user   = mysqli_query($koneksi, $query_user);

        if ($res_user && mysqli_num_rows($res_user) > 0) {
            $user = mysqli_fetch_assoc($res_user);

            // Verifikasi Password (dukungan password_hash & plain-text)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $role = strtolower($user['role']);

                // Simpan data login utama ke session
                $_SESSION['id_user']      = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $role;

                // Catat Log Aktivitas Login jika fungsi tersedia
                if (function_exists('catatLog')) {
                    catatLog($koneksi, $user['id_user'], "User ".$user['username']." ($role) login ke sistem.");
                }

                // Routing Otomatis Berdasarkan Role
                if ($role === 'admin') {
                    header('Location: views/admin/index.php');
                    exit;
                } elseif ($role === 'petugas') {
                    header('Location: views/petugas/persetujuan.php');
                    exit;
                } elseif ($role === 'peminjam') {
                    // Menyediakan alias session peminjam dari data user
                    $_SESSION['id_peminjam']   = $user['id_user'];
                    $_SESSION['nama_peminjam'] = $user['nama_lengkap'];

                    header('Location: views/peminjam/katalog.php');
                    exit;
                }
            } else {
                $error = 'Password yang Anda masukkan salah!';
            }

        } else {
            $error = 'Akun tidak ditemukan! Silakan periksa username atau lakukan registrasi.';
        }

    } else {
        $error = 'Username dan Password wajib diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIP-SARPRAS SMKN 3 Kendal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md border border-slate-200">
        
        <!-- Header Identitas Sekolah -->
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center mx-auto mb-2 text-xl shadow-md">
                <i class="fa-solid fa-school"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">SIP-SARPRAS</h2>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SMK Negeri 3 Kendal</p>
        </div>

        <?php if ($pesan): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl mb-4 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($pesan) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-200 text-rose-700 p-3.5 rounded-xl mb-4 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Username / Nama Lengkap</label>
                <input type="text" name="username" required placeholder="Masukkan username Anda" autocomplete="off"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Password</label>
                <input type="password" name="password" required placeholder="Masukkan password Anda"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition shadow-sm">
                Masuk Sistem
            </button>
        </form>

        <!-- Tombol Registrasi Akun Siswa Baru -->
        <div class="mt-6 pt-4 border-t border-slate-100 text-center space-y-3">
            <p class="text-xs text-slate-500">Belum punya akun siswa?</p>
            <a href="registrasi.php" class="w-full inline-block bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 py-2 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-user-plus mr-1"></i> Daftar Akun Siswa Baru
            </a>

            <div class="pt-2">
                <a href="index.php" class="text-xs text-blue-600 hover:underline inline-flex items-center gap-1 font-semibold">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog Utama</span>
                </a>
            </div>
        </div>

    </div>
</body>
</html>