<?php
// FILE: email_sender.php
// Berisi fungsi untuk mengirim email verifikasi menggunakan PHPMailer.

// Pastikan path ke file PHPMailer sudah benar
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

// =========================================================
// KREDENSIAL PENGIRIM DARI AKUN GOOGLE ANDA (SUDAH DIPERBAIKI)
// =========================================================
define('SMTP_USER', 'dendi.rpriandanu023@gmail.com'); 
define('SMTP_PASS', 'ofotnjoggydevday'); // Sandi Aplikasi 16 Karakter Tanpa Spasi!
// =========================================================


/**
 * Mengirim email verifikasi ke pengguna baru.
 *
 * @param string $recipient_email
 * @param string $username
 * @param string $verification_link
 * @return array
 */
function send_verification_email($recipient_email, $username, $verification_link) {
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi Server SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        $mail->Username   = SMTP_USER; 
        $mail->Password   = SMTP_PASS; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 

        // Opsional: Untuk debugging, hapus komentar pada baris di bawah ini
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; 

        $mail->CharSet    = 'UTF-8';

        // Pengirim dan Penerima
        $mail->setFrom(SMTP_USER, 'Subdit Jafung Notifikasi');
        $mail->addAddress($recipient_email, $username);

        // Konten Email (HTML)
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Akun Anda - Instansi Pembina JF';
        $mail->Body    = "
            Halo **$username**, <br><br>
            Terima kasih telah mendaftar. Akun Anda berhasil dibuat dan memerlukan verifikasi.<br>
            Silakan klik tautan berikut untuk memverifikasi email Anda:<br><br>
            <a href=\"$verification_link\" style=\"background-color:#007bff; color:#ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;\">
                Verifikasi Akun Saya
            </a>
            <br><br>
            Jika tombol di atas tidak berfungsi, salin tautan ini ke browser Anda:<br>
            <small>$verification_link</small><br><br>
            Salam Hormat,<br>Tim Subdit Jafung";

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        // Mengembalikan pesan error detail
        return [
            'success' => false, 
            'message' => $mail->ErrorInfo 
        ];
    }
}
?>