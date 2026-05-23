<?php
// 1. Inisialisasi Session & Koneksi
session_start();
require_once 'koneksi.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil ID Gelombang dari URL
$id_gelombang = isset($_GET['id_gelombang']) ? mysqli_real_escape_string($conn, $_GET['id_gelombang']) : '';
// Ambil Filter Status dari URL (Lulus / Tidak Lulus / Menunggu / Cadangan)
$view_status = isset($_GET['view']) ? mysqli_real_escape_string($conn, $_GET['view']) : '';

if (empty($id_gelombang)) {
    header("Location: admin_update.php?error=missing_id");
    exit;
}

// Ambil Nama Gelombang untuk Judul
$q_gel = mysqli_query($conn, "SELECT gelombang FROM tb_gelombang WHERE id = '$id_gelombang'");
$d_gel = mysqli_fetch_assoc($q_gel);
$nama_gelombang = $d_gel['gelombang'] ?? 'Tidak Diketahui';

// --- LOGIKA HITUNG REKAPAN (STATISTIK) ---
$q_stats = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_pengajuan = 'Lulus' THEN 1 ELSE 0 END) as jml_lulus,
    SUM(CASE WHEN hasil_ujikom = 'Tidak Lulus' THEN 1 ELSE 0 END) as jml_gagal,
    SUM(CASE WHEN status_pengajuan = 'Cadangan' THEN 1 ELSE 0 END) as jml_cadangan,
    SUM(CASE WHEN status_pengajuan = 'Terjadwal' AND (hasil_ujikom IS NULL OR hasil_ujikom = '') THEN 1 ELSE 0 END) as jml_proses
    FROM pengajuan_ujikom WHERE gelombang = '$id_gelombang'");
$stats = mysqli_fetch_assoc($q_stats);

// --- LOGIKA FILTER STATUS ---
$filter_sql = "";
$title_prefix = "Manajemen Hasil Uji Kompetensi";

if ($view_status === 'Lulus') {
    $filter_sql = " AND status_pengajuan = 'Lulus' ";
    $title_prefix = "Daftar Peserta: Lulus";
} elseif ($view_status === 'Tidak Lulus') {
    $filter_sql = " AND hasil_ujikom = 'Tidak Lulus' ";
    $title_prefix = "Daftar Peserta: Tidak Lulus";
} elseif ($view_status === 'Menunggu') {
    $filter_sql = " AND status_pengajuan = 'Terjadwal' AND (hasil_ujikom IS NULL OR hasil_ujikom = '') ";
    $title_prefix = "Daftar Peserta: Menunggu Proses";
} elseif ($view_status === 'Cadangan') {
    $filter_sql = " AND status_pengajuan = 'Cadangan' ";
    $title_prefix = "Daftar Peserta: Cadangan";
} else {
    // Default: Tampilkan semua status yang relevan termasuk Cadangan
    $filter_sql = " AND (status_pengajuan = 'Terjadwal' OR status_pengajuan = 'Lulus' OR status_pengajuan = 'Cadangan' OR hasil_ujikom = 'Tidak Lulus') ";
}

$sql_peserta = "SELECT * FROM pengajuan_ujikom 
                WHERE gelombang = '$id_gelombang' 
                $filter_sql 
                ORDER BY status_pengajuan DESC, nama ASC";
$query_peserta = mysqli_query($conn, $sql_peserta);

include 'template/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .radio-group-container { display: flex; gap: 15px; align-items: center; }
    .radio-item { display: flex; align-items: center; gap: 5px; cursor: pointer; }
    .radio-item label { margin-bottom: 0; cursor: pointer; font-size: 0.875rem; }
    .stat-card { border-left: 4px solid; }
    .input-tgl-gagal { display: none; margin-top: 8px; }
    .input-tgl-gagal.active { display: block; }
    .aksi-content.hidden-aksi { visibility: hidden; opacity: 0; pointer-events: none; }
    
    .mode-edit-view { display: none; }
    .is-editing .mode-edit-view { display: block !important; }
    .is-editing .mode-normal-view { display: none !important; }
</style>

<div class="d-flex" id="wrapper" style="min-height: 100vh;">
    <aside id="sidebar-container" style="width: 250px; flex-shrink: 0; background-color: #2c3e50;">
        <?php include 'template/sidebar.php'; ?>
    </aside>

    <main id="page-content-wrapper" class="flex-grow-1" style="background-color: #f4f7f6; min-width: 0;">
        <div class="container-fluid p-4">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_update.php" class="text-decoration-none">Update Konten</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Daftar Peserta</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="fw-bold text-secondary mb-0"><?= $title_prefix ?></h3>
                        <p class="text-muted">Gelombang: <strong><?= htmlspecialchars($nama_gelombang) ?></strong></p>
                    </div>
                    <div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle shadow-sm" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i> Filter: <?= ($view_status == 'Menunggu' ? 'Menunggu Proses' : ($view_status ?: 'Semua')) ?>
                            </button>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="?id_gelombang=<?= $id_gelombang ?>">Tampilkan Semua</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?id_gelombang=<?= $id_gelombang ?>&view=Lulus"><i class="fas fa-check-circle text-success me-2"></i>Lulus</a></li>
                                <li><a class="dropdown-item" href="?id_gelombang=<?= $id_gelombang ?>&view=Tidak Lulus"><i class="fas fa-times-circle text-danger me-2"></i>Tidak Lulus</a></li>
                                <li><a class="dropdown-item" href="?id_gelombang=<?= $id_gelombang ?>&view=Cadangan"><i class="fas fa-user-clock text-warning me-2"></i>Cadangan</a></li>
                                <li><a class="dropdown-item" href="?id_gelombang=<?= $id_gelombang ?>&view=Menunggu"><i class="fas fa-clock text-info me-2"></i>Menunggu Proses</a></li>
                            </ul>
                        </div>
                        <a href="admin_update.php" class="btn btn-secondary shadow-sm me-2">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <?php if (mysqli_num_rows($query_peserta) > 0): ?>
                        <button type="button" class="btn btn-success shadow-sm" onclick="simpanMassal()">
                            <i class="fas fa-save me-2"></i> Simpan Semua Hasil
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm stat-card border-primary"><div class="card-body py-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total</small><h5 class="mb-0 fw-bold"><?= $stats['total'] ?></h5></div></div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm stat-card border-success"><div class="card-body py-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Lulus</small><h5 class="mb-0 fw-bold text-success"><?= $stats['jml_lulus'] ?></h5></div></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm stat-card border-danger"><div class="card-body py-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Tidak Lulus</small><h5 class="mb-0 fw-bold text-danger"><?= $stats['jml_gagal'] ?></h5></div></div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-0 shadow-sm stat-card border-warning"><div class="card-body py-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Cadangan</small><h5 class="mb-0 fw-bold text-warning"><?= $stats['jml_cadangan'] ?></h5></div></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm stat-card border-info"><div class="card-body py-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Menunggu</small><h5 class="mb-0 fw-bold text-info"><?= $stats['jml_proses'] ?></h5></div></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <form id="formMassal">
                        <input type="hidden" name="aksi" value="update_hasil_massal">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3" width="40">No</th>
                                        <th class="py-3">Nama Peserta</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3" width="280">Hasil & Rentang Waktu</th>
                                        <th class="py-3" width="120">Angka Kredit</th>
                                        <th class="py-3" width="130">Sertifikat</th>
                                        <th class="py-3" width="230">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 0;
                                    if (mysqli_num_rows($query_peserta) > 0):
                                        while($row = mysqli_fetch_assoc($query_peserta)): 
                                            $no++;
                                            $is_lulus = ($row['status_pengajuan'] == 'Lulus');
                                            $is_tidak_lulus = ($row['hasil_ujikom'] == 'Tidak Lulus');
                                            $is_cadangan = ($row['status_pengajuan'] == 'Cadangan');
                                            $sudah_punya_hasil = ($is_lulus || $is_tidak_lulus);
                                            $sudah_isi_form = !empty($row['skp_lengkap']);
                                            $sudah_upload = !empty($row['file_sertifikat']);
                                            $ak_value = ($row['angka_kredit'] > 0) ? number_format($row['angka_kredit'], 3, ',', '.') : '-';
                                    ?>
                                    <tr id="row_<?= $no-1 ?>" class="<?= $sudah_punya_hasil ? 'has-result' : '' ?>">
                                        <td><?= $no ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td>
                                            <?php if($is_lulus): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Lulus</span>
                                            <?php elseif($is_tidak_lulus): ?>
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Tidak Lulus</span>
                                            <?php elseif($is_cadangan): ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-user-clock me-1"></i> Cadangan</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><i class="fas fa-clock me-1"></i> Terjadwal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="hidden" name="id_peserta[]" value="<?= $row['id'] ?>">
                                            
                                            <div class="mode-normal-view">
                                                <?php if($is_lulus): ?>
                                                    <span class="text-success small fw-bold"><i class="fas fa-verified me-1"></i> Terverifikasi Lulus</span>
                                                <?php elseif($is_tidak_lulus): ?>
                                                    <div class="text-danger small fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Terverifikasi Tidak Lulus</div>
                                                    <div class="row g-1">
                                                        <div class="col-6"><small class="text-muted" style="font-size: 9px;">Dari: <?= $row['tanggal_tidak_lulus'] ?? '-' ?></small></div>
                                                        <div class="col-6"><small class="text-muted" style="font-size: 9px;">Hingga: <?= $row['tanggal_re_registrasi'] ?? '-' ?></small></div>
                                                    </div>
                                                <?php elseif($is_cadangan): ?>
                                                    <!-- Kosong untuk status cadangan sesuai permintaan -->
                                                <?php endif; ?>
                                            </div>

                                            <div class="mode-edit-view <?= (!$sudah_punya_hasil && !$is_cadangan) ? 'd-block' : '' ?>">
                                                <?php if(!$is_cadangan || ($is_cadangan && $view_status == '')): ?>
                                                <div class="radio-group-container">
                                                    <div class="radio-item">
                                                        <input type="radio" id="lulus_<?= $no ?>" name="hasil_ujikom_radio_<?= $no-1 ?>" value="Lulus" 
                                                               <?= ($row['hasil_ujikom'] == 'Lulus' || $is_lulus) ? 'checked' : '' ?>
                                                               onchange="toggleGagalForm(this, <?= $no-1 ?>)">
                                                        <label for="lulus_<?= $no ?>" class="text-success">Lulus</label>
                                                    </div>
                                                    <div class="radio-item">
                                                        <input type="radio" id="tidak_lulus_<?= $no ?>" name="hasil_ujikom_radio_<?= $no-1 ?>" value="Tidak Lulus" 
                                                               <?= ($row['hasil_ujikom'] == 'Tidak Lulus') ? 'checked' : '' ?>
                                                               onchange="toggleGagalForm(this, <?= $no-1 ?>)">
                                                        <label for="tidak_lulus_<?= $no ?>" class="text-danger">Tidak Lulus</label>
                                                    </div>
                                                    <input type="hidden" class="hidden-hasil" name="hasil_ujikom[]" value="<?= $row['hasil_ujikom'] ?>">
                                                </div>

                                                <div id="form_gagal_<?= $no-1 ?>" class="input-tgl-gagal <?= ($row['hasil_ujikom'] == 'Tidak Lulus') ? 'active' : '' ?>">
                                                    <div class="row g-1">
                                                        <div class="col-6">
                                                            <label class="small text-muted" style="font-size: 10px;">Dari Tanggal:</label>
                                                            <input type="date" name="tgl_tidak_lulus_mulai[]" class="form-control form-control-sm" value="<?= $row['tanggal_tidak_lulus'] ?? '' ?>">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="small text-muted" style="font-size: 10px;">Sampai Dengan:</label>
                                                            <input type="date" name="tgl_tidak_lulus_selesai[]" class="form-control form-control-sm" value="<?= $row['tanggal_re_registrasi'] ?? '' ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                    <!-- Jika cadangan dan sedang tidak dalam mode edit, biarkan hidden value tetap ada -->
                                                    <input type="hidden" class="hidden-hasil" name="hasil_ujikom[]" value="<?= $row['hasil_ujikom'] ?>">
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-primary"><?= $ak_value ?></td>
                                        <td>
                                            <?php if($sudah_upload): ?>
                                                <a href="uploads/sertifikat/<?= $row['file_sertifikat'] ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100">
                                                    <i class="fas fa-file-pdf"></i> Lihat PDF
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div id="aksi_container_<?= $no-1 ?>" class="aksi-content d-flex gap-2">
                                                <?php if($sudah_punya_hasil || $is_cadangan): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning shadow-sm" onclick="editHasil(<?= $no-1 ?>)">
                                                        <i class="fas fa-edit"></i> Edit Hasil
                                                    </button>
                                                <?php endif; ?>

                                                <?php if($sudah_isi_form): ?>
                                                    <a href="detail_formulir_admin.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary shadow-sm">
                                                        <i class="fas fa-file-alt"></i> Lihat Form
                                                    </a>
                                                    <button type="button" class="btn btn-sm <?= $sudah_upload ? 'btn-outline-success' : 'btn-success' ?> shadow-sm btn-upload-modal" 
                                                            data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>" data-ak="<?= $row['angka_kredit'] ?>">
                                                        <i class="fas <?= $sudah_upload ? 'fa-edit' : 'fa-upload' ?>"></i> 
                                                        <?= $sudah_upload ? 'Edit Sertifikat' : 'Upload' ?>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small"><?= $is_lulus ? '<i>Menunggu Form</i>' : '-' ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted">Data tidak ditemukan untuk kategori ini.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="modalUploadSertifikat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Upload Sertifikat & Angka Kredit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadSertifikat" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="aksi" value="upload_sertifikat_single">
                    <input type="hidden" name="id_peserta_sertifikat" id="id_peserta_sertifikat">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Nama Peserta</label>
                        <input type="text" class="form-control bg-light" id="nama_peserta_sertifikat" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Angka Kredit</label>
                        <input type="number" step="0.001" name="angka_kredit" id="angka_kredit_input" class="form-control" required placeholder="0.000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">File Sertifikat (PDF)</label>
                        <input type="file" name="file_sertifikat" id="file_sertifikat_input" class="form-control" accept=".pdf">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnProsesUpload">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const uploadModal = new bootstrap.Modal(document.getElementById('modalUploadSertifikat'));

function editHasil(index) {
    const row = document.getElementById('row_' + index);
    const aksiContainer = document.getElementById('aksi_container_' + index);
    row.classList.add('is-editing');
    
    // Khusus untuk baris cadangan yang diedit, kita perlu memastikan radio group muncul
    // Meskipun awalnya kosong, saat tombol edit diklik, admin bisa memberikan hasil
    const editView = row.querySelector('.mode-edit-view');
    if (editView && editView.innerHTML.trim() === "") {
        // Logika untuk menyuntikkan radio button jika baris cadangan diedit (opsional jika PHP sudah merender)
    }

    if (aksiContainer) {
        aksiContainer.classList.add('hidden-aksi');
    }
}

function toggleGagalForm(radio, index) {
    const hiddenInputs = document.querySelectorAll('.hidden-hasil');
    const formGagal = document.getElementById('form_gagal_' + index);
    const aksiContainer = document.getElementById('aksi_container_' + index);
    
    if (hiddenInputs[index]) {
        hiddenInputs[index].value = radio.value;
    }

    if (aksiContainer) {
        aksiContainer.classList.add('hidden-aksi');
    }

    if (radio.value === 'Tidak Lulus') {
        formGagal.classList.add('active');
        formGagal.querySelectorAll('input').forEach(i => i.required = true);
    } else {
        formGagal.classList.remove('active');
        formGagal.querySelectorAll('input').forEach(i => i.required = false);
    }
}

document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-upload-modal')) {
        const btn = e.target.closest('.btn-upload-modal');
        document.getElementById('id_peserta_sertifikat').value = btn.getAttribute('data-id');
        document.getElementById('nama_peserta_sertifikat').value = btn.getAttribute('data-nama');
        document.getElementById('angka_kredit_input').value = btn.getAttribute('data-ak');
        document.getElementById('file_sertifikat_input').value = '';
        uploadModal.show();
    }
});

document.getElementById('formUploadSertifikat').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnProsesUpload');
    btn.disabled = true;
    fetch('proses_sertifikat.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.json()).then(data => {
        if (data.status === 'success') Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
        else { Swal.fire('Gagal!', data.message, 'error'); btn.disabled = false; }
    }).catch(err => { console.error(err); btn.disabled = false; });
});

async function simpanMassal() {
    const form = document.getElementById('formMassal');
    const allRows = form.querySelectorAll('tbody tr');
    let dataSiapKirim = false;
    let adaLulusBaru = false;

    allRows.forEach((row, idx) => {
        const radioChecked = row.querySelector('input[type="radio"]:checked');
        if (radioChecked && (!row.classList.contains('has-result') || row.classList.contains('is-editing'))) {
            dataSiapKirim = true;
            if (radioChecked.value === "Lulus") adaLulusBaru = true;
        }
    });

    if (!dataSiapKirim) {
        Swal.fire('Informasi', 'Tidak ada perubahan hasil yang perlu disimpan.', 'info');
        return;
    }

    let catatanLulus = "";
    if (adaLulusBaru) {
        const { value: text, isConfirmed } = await Swal.fire({
            title: 'Instruksi Peserta Lulus',
            input: 'textarea',
            inputLabel: 'Masukkan pesan instruksi untuk peserta yang lulus',
            showCancelButton: true,
            confirmButtonText: 'Simpan Semua',
            inputValidator: (v) => { if (!v) return 'Wajib diisi!'; }
        });
        if (!isConfirmed) return;
        catatanLulus = text;
    } else {
        const res = await Swal.fire({ title: 'Simpan?', text: "Simpan semua perubahan hasil pemeriksaan?", icon: 'question', showCancelButton: true });
        if (!res.isConfirmed) return;
    }

    Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });
    const formData = new FormData(form);
    formData.append('catatan_lulus', catatanLulus);

    fetch('proses_sertifikat.php', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if (data.status === 'success') Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
        else Swal.fire('Gagal!', data.message, 'error');
    });
}
</script>
<?php include 'template/footer.php'; ?>