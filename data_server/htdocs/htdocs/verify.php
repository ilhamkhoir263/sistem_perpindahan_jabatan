<?php
// FILE: verify.php - Menangani Verifikasi Token
// PASTIKAN TIDAK ADA SPASI ATAU BARIS KOSONG SEBELUM TAG INI

session_start();
require_once 'koneksi.php'; 

// --- LOGIKA MENDAPATKAN BASE URL APLIKASI SECARA DINAMIS ---
// Mendapatkan protokol (http atau https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

// Mendapatkan nama host/domain (localhost atau domainanda.com)
$host = $_SERVER['HTTP_HOST'];

// Mendapatkan path folder root aplikasi (misalnya: /jf_pkp2/)
// Menggunakan dirname() dan trim() untuk membersihkan path dari nama file saat ini
$folder_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if ($folder_path === '/') { // Jika di root domain, path-nya adalah kosong/slash
    $folder_path = '';
}

// URL dasar aplikasi: http(s)://host/jf_pkp2
$base_url = "{$protocol}://{$host}{$folder_path}/";

// URL tujuan pengalihan
$redirect_url = $base_url . 'login.php';
// -------------------------------------------------------------

$error_message = null;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // 1. Cek token di database
    $stmt = mysqli_prepare($conn, "SELECT id, nama FROM {$NAMA_TABEL_USERS} WHERE verification_token = ? AND is_verified = 0");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 1) {
        // 2. Token ditemukan, lakukan update
        $stmt_update = mysqli_prepare($conn, "UPDATE {$NAMA_TABEL_USERS} SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        mysqli_stmt_bind_param($stmt_update, "s", $token);
        
        if (mysqli_stmt_execute($stmt_update)) {
            // Verifikasi Sukses
            $_SESSION['popup_type'] = 'success';
            $_SESSION['popup_message'] = "✅ Akun Anda berhasil diverifikasi! Silakan login untuk masuk ke sistem.";
            
            // --- REDIRECTION SUCCESS MENGGUNAKAN URL LENGKAP ---
            header('Location: ' . $redirect_url);
            exit; 
            // ---------------------------------------------------
            
        } else {
            $error_message = "Gagal mengaktifkan akun. Silakan hubungi administrator.";
        }
        if (isset($stmt_update)) mysqli_stmt_close($stmt_update);
    } else {
        $error_message = "Tautan verifikasi tidak valid, sudah kedaluwarsa, atau akun Anda sudah aktif sebelumnya.";
    }
    
    if (isset($stmt)) mysqli_stmt_close($stmt);

} else {
    $error_message = "Token verifikasi tidak ditemukan.";
}
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Verifikasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>body{display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f6f9;}</style>
</head>
<body>
    <div class="error-page" style="width: 600px;">
        <h2 class="headline text-danger"><i class="fas fa-exclamation-triangle"></i></h2>
        <div class="error-content pt-4">
            <h3>Oops! Verifikasi Gagal.</h3>
            <p class="text-danger font-weight-bold">Alasan: <?php echo htmlspecialchars($error_message ?? 'Kesalahan tidak diketahui.'); ?></p>
            <p>Silakan coba kembali atau <a href="<?php echo htmlspecialchars($redirect_url); ?>">coba login</a> jika akun Anda sudah aktif.</p>
            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn btn-primary mt-3"><i class="fas fa-sign-in-alt"></i> Menuju Halaman Login</a>
        </div>
    </div>
</body>
</html>