<?php
// FILE: config_email.php (atau email_sender.php)

// PENTING: Pastikan path ke folder 'src/' PHPMailer sudah benar
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// =========================================================
// BARIS UNTUK MEMUAT FILE PHPMailer SECARA MANUAL
// ASUMSI: FILE PHPMailer ADA DI 'vendor/phpmailer/phpmailer/src/' atau 'src/'
// =========================================================
// GANTI BARIS INI JIKA LOKASI FILE BERBEDA
require_once 'vendor/phpmailer/phpmailer/src/Exception.php'; 
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
// =========================================================


/**
 * Fungsi untuk mengirim email OTP.
 * @param string $recipient_email Email tujuan.
 * @param string $username Nama pengguna tujuan.
 * @param string $otp_code Kode OTP yang akan dikirim.
 * @return array Hasil pengiriman (['success' => bool, 'message' => string]).
 */
function send_otp_email($recipient_email, $username, $otp_code) {
    $mail = new PHPMailer(true); // Aktifkan Exception

    try {
        // Konfigurasi Server SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // GANTI INI DENGAN KREDENSIAL ASLI ANDA (TANPA SPASI)
        $mail->Username   = 'dendirp6@gmail.com'; // <--- EMAIL ANDA
        $mail->Password   = 'mqahidxgrpcqunpr';   // <--- SANDI APLIKASI 16 KARAKTER
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 

        $mail->CharSet    = 'UTF-8';
        $mail->SMTPKeepAlive = true;

        // Pengirim dan Penerima
        $mail->setFrom($mail->Username, 'Subdit Jafung Notifikasi');
        $mail->addAddress($recipient_email, $username);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Akun Anda - Kode OTP';
        $mail->Body    = "Halo **$username**, <br><br>Terima kasih telah mendaftar. Gunakan kode berikut untuk menyelesaikan pendaftaran:<br><br>
                          <div style='background-color:#f0f0f0; padding:15px; border-radius:5px; text-align:center;'>
                          <h2 style='color:#333333; margin:0;'>Kode OTP Anda: <b>$otp_code</b></h2>
                          </div><br>
                          Kode ini akan kedaluwarsa dalam 5 menit. Jangan berikan kode ini kepada siapapun.<br><br>
                          Salam Hormat,<br>Tim Subdit Jafung";

        $mail->AltBody = "Halo $username, Kode OTP Anda adalah: $otp_code. Kode ini akan kedaluwarsa dalam 5 menit.";
        
        $mail->send();
        return ['success' => true, 'message' => 'Email OTP berhasil dikirim.'];
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => "Email OTP gagal dikirim. Error: {$mail->ErrorInfo}"
        ];
    }
}
?>