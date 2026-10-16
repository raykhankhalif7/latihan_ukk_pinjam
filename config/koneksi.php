<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_pinjam";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// Fungsi bantu catat log aktivitas
function catatLog($koneksi, $id_user, $aktifitas) {
    $aktifitas = mysqli_real_escape_string($koneksi, $aktifitas);
    $query = "INSERT INTO log_aktifitas (id_user, aktifitas) VALUES ('$id_user', '$aktifitas')";
    mysqli_query($koneksi, $query);
}
?>