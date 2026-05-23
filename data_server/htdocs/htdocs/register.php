<?php
// FILE: register.php - Halaman Pendaftaran (Diperbarui untuk OTP)

session_start();
require_once 'koneksi.php'; // Memuat koneksi dan nama tabel
// Pastikan file ini berisi fungsi send_otp_email
require_once 'config_email.php'; 

// Cek apakah user sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$is_error = false;
$input_values = $_POST; // Simpan input POST untuk mengisi ulang form

// --- PENTING: ASUMSI NAMA TABEL USERS ---
if (!isset($NAMA_TABEL_USERS)) {
    $NAMA_TABEL_USERS = "users"; 
}
// ----------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- 1. Ambil & Bersihkan Input ---
    $nama = trim($input_values['nama'] ?? '');
    $email = trim($input_values['email'] ?? '');
    $instansi = trim($input_values['instansi'] ?? '');
    $password = $input_values['password'] ?? '';
    $password_confirm = $input_values['password_confirm'] ?? '';
    $role = 'user'; // Default role
    $is_verified = 0; // Belum terverifikasi
    
    // --- LOGIKA OTP BARU ---
    // Hasilkan OTP 6 digit dan waktu kedaluwarsa 5 menit
    $otp_code = rand(100000, 999999); 
    $otp_expiry = date('Y-m-d H:i:s', time() + (5 * 60)); 
    // -----------------------

    // --- 2. Validasi Form ---
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        $is_error = true;
        $message = "Semua kolom wajib diisi.";
    } elseif ($password !== $password_confirm) {
        $is_error = true;
        $message = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $is_error = true;
        $message = "Password minimal 6 karakter.";
    } else {
        // --- 3. Cek Duplikasi Email ---
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM {$NAMA_TABEL_USERS} WHERE email = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $is_error = true;
            $message = "❌ Email ini sudah terdaftar. Silakan login atau gunakan email lain.";
        } else {
            // --- 4. Simpan ke Database ---
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // QUERY BARU: Menyimpan otp_code dan otp_expiry, menghilangkan verification_token
            $sql_insert = "INSERT INTO {$NAMA_TABEL_USERS} (nama, email, password_hash, instansi, role, is_verified, otp_code, otp_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "sssssiss", 
                $nama, $email, $password_hash, $instansi, $role, $is_verified, $otp_code, $otp_expiry);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                
                // --- 5. Kirim Email OTP ---
                // Panggil fungsi pengiriman OTP yang baru
                $email_result = send_otp_email($email, $nama, $otp_code);
                
                if ($email_result['success']) {
                    // BERHASIL SEMUA -> Simpan email di sesi dan alihkan ke halaman input OTP
                    $_SESSION['popup_type'] = 'info';
                    $_SESSION['popup_message'] = "✅ Pendaftaran berhasil! Kode OTP telah dikirimkan ke email **$email**. Silakan masukkan kode verifikasi.";
                    // Simpan email agar halaman verify_otp.php tahu akun mana yang diverifikasi
                    $_SESSION['verify_email'] = $email; 
                    
                    header('Location: verify_otp.php'); // ALIHKAN KE HALAMAN INPUT OTP BARU
                    exit; 
                } else {
                    // Gagal kirim email: Hapus data yang baru didaftarkan agar user bisa mencoba lagi
                    $sql_delete = "DELETE FROM {$NAMA_TABEL_USERS} WHERE email = ? AND is_verified = 0";
                    $stmt_delete = mysqli_prepare($conn, $sql_delete);
                    mysqli_stmt_bind_param($stmt_delete, "s", $email);
                    mysqli_stmt_execute($stmt_delete);
                    mysqli_stmt_close($stmt_delete);
                    
                    $is_error = true;
                    $message = "❌ Pendaftaran gagal karena email verifikasi tidak terkirim. Error: " . $email_result['message'];
                }
            } else {
                $is_error = true;
                $message = "❌ Pendaftaran gagal disimpan di database: " . mysqli_error($conn);
            }
            if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
        }
        if (isset($stmt_check)) mysqli_stmt_close($stmt_check);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun | Instansi Pembina JF</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition register-page">
<div class="register-box">
    <div class="register-logo">
        <a href="#"><b>Registrasi</b> Akun</a>
    </div>

    <div class="card">
        <div class="card-body register-card-body">
            <p class="login-box-msg">Daftar Akun Baru</p>

            <?php 
            // Tampilkan alert error jika ada
            if ($is_error && $message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post">
                <div class="input-group mb-3">
                    <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required value="<?php echo htmlspecialchars($input_values['nama'] ?? ''); ?>">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required value="<?php echo htmlspecialchars($input_values['email'] ?? ''); ?>">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" name="instansi" class="form-control" placeholder="Instansi/Unit Kerja" required value="<?php echo htmlspecialchars($input_values['instansi'] ?? ''); ?>">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-building"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password (Min. 6 Karakter)" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi Password" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="agreeTerms" name="terms" value="agree" required>
                            <label for="agreeTerms">Saya setuju dengan <a href="#">ketentuan</a></label>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Daftar</button>
                    </div>
                </div>
            </form>
            <a href="login.php" class="text-center">Saya sudah punya akun</a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>