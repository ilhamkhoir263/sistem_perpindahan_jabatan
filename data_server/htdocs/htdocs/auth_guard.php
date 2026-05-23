<?php
// FILE: auth_guard.php
// "Penjaga" untuk setiap halaman yang dilindungi

// Periksa apakah sesi sudah dimulai.
// Ini mencegah error jika session_start() dipanggil dua kali.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah 'user_id' ada di dalam Sesi.
// 'user_id' adalah yang Anda atur di login.php
if (!isset($_SESSION['user_id'])) {
    
    // --- PENGGUNA TIDAK LOGIN ---
    
    // 1. Simpan pesan "peringatan" untuk ditampilkan di halaman login
    $_SESSION['popup_type'] = 'error'; // Anda bisa ganti 'error' atau 'info'
    $_SESSION['popup_message'] = 'Anda harus login untuk mengakses halaman tersebut.';
    
    // 2. "Tendang" pengguna kembali ke halaman login.php
    header('Location: login.php');
    exit;
}

// --- PENGGUNA SUDAH LOGIN ---
// Jika script lolos dari 'if' di atas, berarti pengguna sudah login.
// Kita bisa siapkan variabel ini untuk dipakai di halaman manapun.
$user_id_sesi = $_SESSION['user_id'];
$user_nama_sesi = $_SESSION['user_name'] ?? 'Pengguna';
$user_email_sesi = $_SESSION['user_email'] ?? '';
$user_role_sesi = $_SESSION['user_role'] ?? 'user';

?>
