<?php
// FILE: delete_pegawai.php - Script untuk memproses penghapusan data Pegawai

// --- Memuat file penting ---
// Pastikan file ini ada dan berisi variabel $conn (koneksi database)
require_once 'koneksi.php'; 
// Jika Anda menggunakan autentikasi, pastikan script ini hanya bisa diakses oleh user berizin
require_once 'auth_guard.php'; 

// Tentukan nama tabel pegawai yang digunakan (HARUS SAMA dengan database.php)
$NAMA_TABEL_PEGAWAI = "detailpegawai"; 

// =========================================================
// 1. Validasi Input ID
// =========================================================

// Cek apakah parameter 'id' ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect jika ID tidak ditemukan di URL
    header("Location: database.php?error=" . urlencode("ID data tidak ditemukan untuk dihapus."));
    exit();
}

// Ambil ID dari URL dan sanitasi (Pastikan ID adalah integer)
$id_to_delete = intval($_GET['id']); 

// =========================================================
// 2. Proses Penghapusan Data
// =========================================================

// Cek koneksi
if (!$conn) {
    header("Location: database.php?error=" . urlencode("Koneksi database gagal saat proses hapus."));
    exit();
}

// Gunakan Prepared Statement untuk mencegah SQL Injection
// Asumsi kolom kunci primer adalah 'id'
$sql_delete = "DELETE FROM {$NAMA_TABEL_PEGAWAI} WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql_delete);

if ($stmt) {
    // Binding parameter (asumsi 'id' adalah tipe integer 'i')
    mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
    
    if (mysqli_stmt_execute($stmt)) {
        // Berhasil dihapus
        // Cek apakah ada baris yang benar-benar terpengaruh
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: database.php?success=" . urlencode("Data pegawai (ID: {$id_to_delete}) berhasil dihapus."));
        } else {
             header("Location: database.php?error=" . urlencode("Data pegawai (ID: {$id_to_delete}) tidak ditemukan di database."));
        }
    } else {
        // Gagal eksekusi query (misalnya: Foreign Key Constraint atau masalah izin DB)
        $error_msg = "Gagal menghapus data. Kemungkinan: " . mysqli_error($conn);
        header("Location: database.php?error=" . urlencode($error_msg));
    }
    
    mysqli_stmt_close($stmt);
} else {
    // Gagal menyiapkan statement SQL
    header("Location: database.php?error=" . urlencode("Sistem gagal menyiapkan query penghapusan."));
}

// Tutup koneksi database (penting)
if (isset($conn) && $conn) {
    mysqli_close($conn);
}

exit();
?>