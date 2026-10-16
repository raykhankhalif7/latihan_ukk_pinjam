<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $raw_password = trim($_POST['password']);
    $confirm_pass = trim($_POST['confirm_password']);

    // Validasi Input Form
    if (empty($username) || empty($nama_lengkap) || empty($raw_password)) {
        $error = "Semua kolom pendaftaran wajib diisi!";
    } elseif ($raw_password !== $confirm_pass) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek ketersediaan username di tabel users
        $cek_user = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username'");
        if ($cek_user && mysqli_num_rows($cek_user) > 0) {
            $error = "Username '$username' sudah terdaftar! Gunakan username lain.";
        } else {
            // Hash Password demi keamanan
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

            // Insert HANYA ke tabel 'users' dengan role 'peminjam'
            $q_user = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password_hash', '$nama_lengkap', 'peminjam')";
            
            if (mysqli_query($koneksi, $q_user)) {
                $_SESSION['flash_pesan'] = "Pendaftaran berhasil! Silakan login menggunakan akun Anda.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Siswa - SIP-SARPRAS SMKN 3 Kendal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md border border-slate-200">
        
        <!-- Header Identitas -->
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center mx-auto mb-2 text-xl shadow-md">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Registrasi Siswa</h2>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SIP-SARPRAS SMKN 3 Kendal</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-200 text-rose-700 p-3.5 rounded-xl mb-4 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_lengkap" required placeholder="Contoh: Ahmad Rizky" autocomplete="off"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Username <span class="text-rose-500">*</span></label>
                <input type="text" name="username" required placeholder="Buat username login" autocomplete="off"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Password <span class="text-rose-500">*</span></label>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-slate-600 text-xs font-bold uppercase mb-1">Konfirmasi Password <span class="text-rose-500">*</span></label>
                <input type="password" name="confirm_password" required placeholder="Ulangi password"
                       class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" name="register" class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-blue-700 transition shadow-sm">
                Daftar Sekarang
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-500 mb-2">Sudah memiliki akun?</p>
            <a href="login.php" class="text-xs text-blue-600 hover:underline inline-flex items-center gap-1 font-semibold">
                <i class="fa-solid fa-right-to-bracket text-[10px]"></i>
                <span>Kembali ke Halaman Login</span>
            </a>
        </div>

    </div>
</body>
</html>