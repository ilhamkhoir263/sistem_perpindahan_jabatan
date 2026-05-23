<?php
// FILE: verify_otp.php - Halaman Input dan Proses Verifikasi OTP

session_start();
require_once 'koneksi.php'; 
// Asumsikan koneksi email dimuat untuk fitur kirim ulang
require_once 'config_email.php'; 

// --- PENTING: ASUMSI NAMA TABEL USERS ---
if (!isset($NAMA_TABEL_USERS)) {
    $NAMA_TABEL_USERS = "users"; 
}

$message = '';
$is_error = false;
$email_to_verify = $_SESSION['verify_email'] ?? ''; 
$redirect_url = 'login.php';

// Pastikan ada email yang perlu diverifikasi di sesi
if (empty($email_to_verify)) {
    $_SESSION['popup_type'] = 'error';
    $_SESSION['popup_message'] = "❌ Sesi verifikasi tidak ditemukan. Silakan daftar ulang atau login.";
    header('Location: ' . $redirect_url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_otp'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    // Sanitasi dan validasi dasar
    $otp_input_safe = htmlspecialchars($otp_input); 

    if (empty($otp_input_safe) || !is_numeric($otp_input_safe) || strlen($otp_input_safe) !== 6) {
        $is_error = true;
        $message = "Kode OTP wajib diisi dan harus 6 digit angka.";
    } else {
        
        // --- 1. AMBIL DATA PENGGUNA DARI DATABASE ---
        $sql_fetch = "SELECT id, otp_code, otp_expiry, is_verified FROM {$NAMA_TABEL_USERS} 
                      WHERE email = ?";
        $stmt_fetch = mysqli_prepare($conn, $sql_fetch);
        mysqli_stmt_bind_param($stmt_fetch, "s", $email_to_verify);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_fetch);

        if (!$user) {
            $is_error = true;
            $message = "❌ Akun untuk email ini tidak ditemukan.";

        } elseif ($user['is_verified'] == 1) {
            $is_error = true;
            $message = "Akun Anda sudah **aktif**! Silakan langsung menuju halaman login.";

        } else {
            // --- 2. BANDINGKAN OTP DAN CEK KEDALUWARSA DI PHP ---
            
            // Trim stored OTP in case of database whitespace
            $stored_otp = trim($user['otp_code'] ?? ''); 
            $otp_expiry_time = strtotime($user['otp_expiry']);
            $current_time = time();

            // Perbandingan ketat (===) untuk memastikan tidak ada perbedaan tipe data atau spasi
            if ($otp_input_safe === $stored_otp && !empty($stored_otp)) {
                // OTP Cocok
                if ($current_time <= $otp_expiry_time) {
                    
                    // OTP Cocok dan Belum Kedaluwarsa - LAKUKAN VERIFIKASI
                    $stmt_update = mysqli_prepare($conn, "UPDATE {$NAMA_TABEL_USERS} SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
                    mysqli_stmt_bind_param($stmt_update, "s", $email_to_verify);
                    
                    if (mysqli_stmt_execute($stmt_update)) {
                        // Verifikasi Sukses
                        $_SESSION['popup_type'] = 'success';
                        $_SESSION['popup_message'] = "✅ Akun Anda berhasil diverifikasi! Silakan login untuk masuk ke sistem.";
                        unset($_SESSION['verify_email']); 
                        mysqli_stmt_close($stmt_update);
                        header('Location: ' . $redirect_url);
                        exit; 
                    } else {
                        $is_error = true;
                        $message = "Gagal memproses verifikasi. Silakan coba lagi. (Error Update DB)";
                    }
                    if (isset($stmt_update)) mysqli_stmt_close($stmt_update);

                } else {
                    // OTP Cocok TAPI Kedaluwarsa
                    $is_error = true;
                    $message = "Kode OTP telah **kedaluwarsa** (Telah melewati batas waktu 5 menit). Silakan <a href='resend_otp.php'>kirim ulang OTP</a>.";
                }
            } else {
                // OTP TIDAK COCOK ATAU OTP SUDAH NULL DI DB
                $is_error = true;
                $message = "Kode OTP yang Anda masukkan **salah**. Mohon periksa kembali kodenya.";
            }
        }
    }
}

// Tutup koneksi database (dilakukan di luar blok POST agar memastikan koneksi tertutup)
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h1>Verifikasi Akun</h1>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Masukkan Kode OTP yang telah kami kirimkan ke email Anda: <b><?php echo htmlspecialchars($email_to_verify); ?></b></p>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $is_error ? 'danger' : 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="input-group mb-3">
                    <input type="text" name="otp_code" class="form-control" placeholder="Kode OTP (6 Digit)" required maxlength="6">
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" name="submit_otp" class="btn btn-primary btn-block">Verifikasi</button>
                    </div>
                </div>
            </form>

            <p class="mt-3 text-center">
                <a href="resend_otp.php">Kirim ulang Kode OTP?</a>
            </p>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>