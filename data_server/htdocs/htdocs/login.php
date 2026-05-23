<?php
// FILE: login.php - Halaman Login

session_start();
require_once 'koneksi.php'; 

// Jika pengguna sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$is_error = false;
$input_email = ''; // Untuk mengisi ulang email di form

// --- LOGIKA POPUP DARI SESSION (Verifikasi/Pendaftaran Sukses) ---
$popup_data = [];
if (isset($_SESSION['popup_message']) && isset($_SESSION['popup_type'])) {
    $popup_data = [
        'type' => $_SESSION['popup_type'],
        'message' => $_SESSION['popup_message'],
    ];
    // Hapus session agar tidak muncul saat refresh
    unset($_SESSION['popup_message']);
    unset($_SESSION['popup_type']);
}
// ------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $input_email = $email;

    if (empty($email) || empty($password)) {
        $is_error = true;
        $message = "Email dan Password wajib diisi.";
    } else {
        // 1. Cari user berdasarkan Email
        $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password_hash, role, is_verified FROM {$NAMA_TABEL_USERS} WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // 2. Cek Password
            if (password_verify($password, $user['password_hash'])) {
                
                // 3. Cek Verifikasi Akun
                if ($user['is_verified'] == 0) {
                    $is_error = true;
                    $message = "❌ Akun Anda belum diverifikasi. Silakan cek email Anda untuk tautan verifikasi.";
                } else {
                    // Login Berhasil!
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Siapkan notifikasi sukses di dashboard (index.php)
                    $_SESSION['popup_type'] = 'success';
                    $_SESSION['popup_message'] = "Selamat Datang kembali, **{$user['nama']}**! Anda berhasil login.";

                    header('Location: index.php');
                    exit;
                }
            } else {
                $is_error = true;
                $message = "❌ Email atau Password salah.";
            }
        } else {
            $is_error = true;
            $message = "❌ Email atau Password salah.";
        }
        mysqli_stmt_close($stmt);
    }
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
    <title>Login | Instansi Pembina JF</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>Login</b> Akun</a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Masuk untuk memulai sesi Anda</p>

            <?php if ($is_error && $message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required value="<?php echo htmlspecialchars($input_email); ?>">
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember">
                            <label for="remember">Ingat Saya</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </div>
            </form>

            <p class="mb-1"><a href="forgot-password.php">Saya lupa password</a></p>
            <p class="mb-0"><a href="register.php" class="text-center">Daftar Akun Baru</a></p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<?php if (!empty($popup_data)): ?>
<div class="modal fade" id="sessionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-<?php echo ($popup_data['type'] == 'success' ? 'success' : 'info'); ?>">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Notifikasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <p><?php echo $popup_data['message']; ?></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        if ($('#sessionModal').length) {
            $('#sessionModal').modal('show');
        }
    });
</script>
<?php endif; ?>
</body>
</html>