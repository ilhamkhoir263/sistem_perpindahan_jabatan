<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengajuan_raw = $_POST['id_pengajuan'] ?? ''; 
    // Jika tidak pilih verifikator, set ke null
    $id_verifikator = !empty($_POST['id_verifikator']) ? (int)$_POST['id_verifikator'] : null;

    if (empty($id_pengajuan_raw)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada pengusul yang dipilih.']);
        exit;
    }

    $ids = explode(',', $id_pengajuan_raw);
    $ids = array_filter($ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Jika id_verifikator null, maka kolom verifikator_id di DB akan di-set NULL
    // Ini yang menyebabkan tampilan berubah jadi "BELUM DISPOSISI"
    $sql = "UPDATE pengajuan_ujikom SET 
            verifikator_id = ?, 
            status_pengajuan = 'Menunggu Verifikasi' 
            WHERE id IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    
    // Bind parameter: 'i' untuk integer, 's' untuk null (jika menggunakan mysqli)
    // Agar lebih aman dengan null, kita gunakan bind_param secara dinamis
    $types = ($id_verifikator === null ? 's' : 'i') . str_repeat('i', count($ids));
    $stmt->bind_param($types, $id_verifikator, ...$ids);

    if ($stmt->execute()) {
        $msg = ($id_verifikator === null) ? "Berhasil dikosongkan (Belum Disposisi)." : "Berhasil didisposisikan.";
        echo json_encode(['status' => 'success', 'message' => count($ids) . " Pengusul $msg"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $stmt->error]);
    }
}