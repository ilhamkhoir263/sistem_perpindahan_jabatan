<?php
// FILE: koneksi.php (PASTIKAN TIDAK ADA SPASI ATAU BARIS KOSONG SEBELUM TAG INI)

// --- PENTING: GANTI DENGAN DETAIL KONEKSI DATABASE ANDA ---
$host = "sql113.infinityfree.com";
$user = "if0_40304497"; 
$pass = "jfpkp123"; 
$db = "if0_40304497_db_jfpkp2"; 
// --------------------------------------------------------

// Koneksi ke Database menggunakan MySQLi Procedural
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek Koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$NAMA_TABEL_USERS = "users"; 
$NAMA_TABEL_FORMASI = "pengajuan_rekomendasi";
$NAMA_TABEL_PEGAWAI = "detailpegawai";

// TIDAK PERLU TAG PENUTUP 
?> 
