<?php
// FILE: auth_guard.php
// "Penjaga" untuk setiap halaman yang dilindungi

// Periksa apakah sesi sudah dimulai.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- MEMUAT FUNGSI BANTUAN ROLE (LANGKAH 1) ---
require_once 'role_helpers.php'; 
// ------------------------------------------------

// Periksa apakah 'user_id' ada di dalam Sesi.
if (!isset($_SESSION['user_id'])) {
    
    // --- PENGGUNA TIDAK LOGIN ---
    $_SESSION['popup_type'] = 'error'; 
    $_SESSION['popup_message'] = 'Anda harus login untuk mengakses halaman tersebut.';
    
    header('Location: login.php');
    exit;
}

// =========================================================================
// ✅ PERBAIKAN KRITIS: SINKRONISASI VARIABEL SESI DENGAN AKHIRAN _SESI
// =========================================================================

// 1. Ambil data asli dari sesi
$original_user_id = $_SESSION['user_id'];
$original_user_name = $_SESSION['user_name'] ?? ($_SESSION['nama'] ?? 'Pengguna');

// --- LOGIKA EMAIL ---
if (isset($_SESSION['user_email'])) {
    $original_user_email = $_SESSION['user_email'];
} elseif (isset($_SESSION['email'])) {
    $original_user_email = $_SESSION['email'];
} elseif (isset($_SESSION['user_mail'])) {
    $original_user_email = $_SESSION['user_mail'];
} else {
    $original_user_email = ''; 
}

$original_user_role = $_SESSION['user_role'] ?? 'guest';
$original_user_nip = $_SESSION['user_nip'] ?? ($_SESSION['nip'] ?? null); 
$original_join_date = $_SESSION['join_date'] ?? null;

// --- FIX KHUSUS INSTANSI (DARI TABEL USERS) ---
// Mengambil data instansi yang diset saat login
$original_user_instansi = $_SESSION['instansi'] ?? ($_SESSION['user_instansi'] ?? 'Instansi Tidak Diketahui');

// 2. Tulis data ke variabel Sesi standar (*_sesi)
$_SESSION['user_id_sesi'] = $original_user_id;
$_SESSION['user_nama_sesi'] = $original_user_name;
$_SESSION['user_email_sesi'] = $original_user_email;
$_SESSION['user_role_sesi'] = $original_user_role;
$_SESSION['user_nip_sesi'] = $original_user_nip;
$_SESSION['user_instansi_sesi'] = $original_user_instansi; // Menyimpan instansi dari tabel users
$_SESSION['join_date_sesi'] = $original_join_date;

// 3. Set variabel lokal (untuk digunakan langsung di halaman yang meng-include)
$user_id_sesi = $_SESSION['user_id_sesi'];
$user_nama_sesi = $_SESSION['user_nama_sesi'];
$user_email_sesi = $_SESSION['user_email_sesi'];
$user_role_sesi = $_SESSION['user_role_sesi'];
$user_nip_sesi = $_SESSION['user_nip_sesi'];
$user_instansi_sesi = $_SESSION['user_instansi_sesi']; // Variabel untuk Profil & Pengaturan
$join_date_sesi = $_SESSION['join_date_sesi'];

// -------------------------------------------------------------
// CATATAN: 
// Variabel $user_instansi_sesi di atas merujuk pada identitas pengguna (Tabel Users).
// Untuk instansi di tabel pengajuan_ujikom, Anda tetap bisa memanggilnya secara 
// terpisah lewat query database di halaman terkait pengajuan tersebut.
// -------------------------------------------------------------
?>