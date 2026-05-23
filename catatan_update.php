<?php
// FILE: catatan_update.php - Log Perubahan Sistem (Internal) dengan Fitur Edit

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DEKLARASI MENU SIDEBAR ---
$page = 'catatan_update';
$sub_page = '';
$page_title = 'Log Update Sistem';

require_once 'auth_guard.php';
require_once 'koneksi.php';

// Buat tabel otomatis jika belum ada
$sql_check_table = "CREATE TABLE IF NOT EXISTS system_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_file VARCHAR(255) NOT NULL,
    perubahan TEXT NOT NULL,
    script_update LONGTEXT,
    status ENUM('Selesai', 'Belum Selesai') DEFAULT 'Belum Selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql_check_table);

// Cek apakah kolom script_update sudah ada
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM system_updates LIKE 'script_update'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE system_updates ADD COLUMN script_update LONGTEXT AFTER perubahan");
}

$message = '';

// --- LOGIKA SIMPAN DATA (INSERT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_log'])) {
    $nama_file = mysqli_real_escape_string($conn, $_POST['nama_file']);
    $perubahan = mysqli_real_escape_string($conn, $_POST['perubahan']);
    $script_update = mysqli_real_escape_string($conn, $_POST['script_update']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql_insert = "INSERT INTO system_updates (nama_file, perubahan, script_update, status) VALUES ('$nama_file', '$perubahan', '$script_update', '$status')";
    if (mysqli_query($conn, $sql_insert)) {
        $message = "✅ Catatan update berhasil ditambahkan!";
    }
}

// --- LOGIKA UPDATE DATA (EDIT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_log'])) {
    $id_edit = (int)$_POST['id_edit'];
    $nama_file = mysqli_real_escape_string($conn, $_POST['nama_file']);
    $perubahan = mysqli_real_escape_string($conn, $_POST['perubahan']);
    $script_update = mysqli_real_escape_string($conn, $_POST['script_update']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql_update = "UPDATE system_updates SET nama_file='$nama_file', perubahan='$perubahan', script_update='$script_update', status='$status' WHERE id=$id_edit";
    if (mysqli_query($conn, $sql_update)) {
        $message = "✅ Catatan update berhasil diperbarui!";
    }
}

// --- LOGIKA UPDATE STATUS (TOGGLE) ---
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $current_status = $_GET['current'];
    $new_status = ($current_status == 'Selesai') ? 'Belum Selesai' : 'Selesai';
    mysqli_query($conn, "UPDATE system_updates SET status = '$new_status' WHERE id = $id");
    header("Location: catatan_update.php");
    exit();
}

// --- LOGIKA HAPUS ---
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM system_updates WHERE id = $id");
    header("Location: catatan_update.php");
    exit();
}

// URUTKAN BERDASARKAN TERBARU (DESC)
$query = mysqli_query($conn, "SELECT * FROM system_updates ORDER BY id DESC");

require_once 'template/header.php';
require_once 'template/navbar.php';
require_once 'template/sidebar.php';
?>

<style>
    .script-box {
        max-width: 400px; 
        max-height: 120px; 
        overflow: auto;    
        background-color: #ffffff; 
        border: 1px solid #dee2e6; 
        border-radius: 4px;
        padding: 8px;
    }
    pre { 
        margin-bottom: 0 !important; 
        white-space: pre; 
    }
    code {
        color: #212529; 
        font-size: 11px;
    }
    .table td { vertical-align: middle !important; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0"><i class="fas fa-history"></i> <?php echo $page_title; ?></h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

            <div class="card card-outline card-primary">
                <form method="POST">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Nama File</label>
                                <input type="text" name="nama_file" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label>Detail Perubahan</label>
                                <input type="text" name="perubahan" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <label>Status</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="Belum Selesai">Belum Selesai</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-2">
                                <label>Script Update</label>
                                <textarea name="script_update" class="form-control form-control-sm" rows="3" style="font-family: monospace;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" name="simpan_log" class="btn btn-primary btn-sm px-4">Simpan Catatan</button>
                    </div>
                </form>
            </div>

            <div class="card mt-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 40px">No.</th>
                                    <th>File</th>
                                    <th>Detail</th>
                                    <th>Script Update</th>
                                    <th style="width: 160px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($query) > 0): 
                                    $no = 1; 
                                    while($row = mysqli_fetch_assoc($query)): 
                                ?>
                                    <tr>
                                        <td class="text-center font-weight-bold"><?php echo $no++; ?></td>
                                        <td class="px-2">
                                            <strong><?php echo htmlspecialchars($row['nama_file']); ?></strong><br>
                                            <small class="text-muted"><?php echo date('d/m/y H:i', strtotime($row['created_at'])); ?></small>
                                        </td>
                                        <td class="px-2"><small><?php echo htmlspecialchars($row['perubahan']); ?></small></td>
                                        <td class="p-1">
                                            <?php if(!empty($row['script_update'])): ?>
                                                <div class="script-box">
                                                    <pre><code id="code_<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['script_update']); ?></code></pre>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small px-2"><em>-</em></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center p-2">
                                            <div class="btn-group-vertical w-100">
                                                <a href="?toggle_id=<?php echo $row['id']; ?>&current=<?php echo $row['status']; ?>" 
                                                   class="btn btn-xs btn-<?php echo ($row['status'] == 'Selesai') ? 'success' : 'warning'; ?> mb-1">
                                                    <?php echo $row['status']; ?>
                                                </a>
                                                
                                                <button class="btn btn-xs btn-primary mb-1" 
                                                        onclick="editLog('<?php echo $row['id']; ?>', '<?php echo addslashes($row['nama_file']); ?>', '<?php echo addslashes($row['perubahan']); ?>', '<?php echo addslashes($row['status']); ?>', 'code_<?php echo $row['id']; ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>

                                                <?php if(!empty($row['script_update'])): ?>
                                                <button class="btn btn-xs btn-info mb-1" onclick="copyCode('code_<?php echo $row['id']; ?>', this)">
                                                    <i class="far fa-copy"></i> Copy
                                                </button>
                                                <?php endif; ?>

                                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-xs btn-outline-danger" onclick="return confirm('Hapus?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center p-3 text-muted">Belum ada catatan update.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Catatan Update</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_edit" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Nama File</label>
                            <input type="text" name="nama_file" id="edit_nama_file" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="Belum Selesai">Belum Selesai</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>Detail Perubahan</label>
                            <input type="text" name="perubahan" id="edit_perubahan" class="form-control" required>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>Script Update</label>
                            <textarea name="script_update" id="edit_script" class="form-control" rows="8" style="font-family: monospace;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" name="update_log" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi untuk mengisi Modal Edit
function editLog(id, file, detail, status, scriptId) {
    const scriptContent = document.getElementById(scriptId) ? document.getElementById(scriptId).textContent : '';
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_file').value = file;
    document.getElementById('edit_perubahan').value = detail;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_script').value = scriptContent;
    $('#modalEdit').modal('show');
}

// Fungsi Copy Code
function copyCode(elementId, button) {
    const codeElement = document.getElementById(elementId);
    const textArea = document.createElement('textarea');
    textArea.value = codeElement.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.classList.replace('btn-info', 'btn-success');
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.replace('btn-success', 'btn-info');
        }, 1500);
    } catch (err) { alert('Gagal copy'); }
    document.body.removeChild(textArea);
}
</script>

<?php require_once 'template/footer.php'; ?>