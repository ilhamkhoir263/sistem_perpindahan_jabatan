<?php
require_once 'auth_guard.php';
require_once 'koneksi.php';

// Cek Izin (Hanya Super Admin & Admin)
if (($user_role_sesi ?? '') !== 'user_super_admin' && ($user_role_sesi ?? '') !== 'admin') {
    header("Location: pengaturan.php?msg_type=error&msg_text=" . urlencode("Akses ditolak!"));
    exit();
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id > 0) {
    // Jangan izinkan hapus diri sendiri
    if ($id == ($user_id_sesi ?? 0)) {
        header("Location: pengaturan.php?msg_type=error&msg_text=" . urlencode("Gagal: Anda tidak bisa menghapus akun Anda sendiri!"));
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: pengaturan.php?msg_type=success&msg_text=" . urlencode("✅ Pengguna berhasil dihapus!"));
    } else {
        header("Location: pengaturan.php?msg_type=error&msg_text=" . urlencode("❌ Gagal menghapus pengguna: " . $conn->error));
    }
    $stmt->close();
} else {
    header("Location: pengaturan.php");
}
exit();