<?php
/**
 * File: admin/modules/settings/index.php
 * Deskripsi: Halaman Pengaturan Aplikasi (Nama & Logo)
 */

// Ambil data pengaturan
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings_raw = $stmt->fetchAll();
    $settings = [];
    foreach ($settings_raw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    echo "Gagal mengambil data pengaturan: " . $e->getMessage();
    return;
}

$app_name = $settings['app_name'] ?? 'CBT Simulation';
$bimbel_name = $settings['bimbel_name'] ?? 'Bimbel Lita Sumedang';
$bimbel_logo = $settings['bimbel_logo'] ?? 'logo-bimbel.png';
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h4 class="fw-bold mb-4">Pengaturan Aplikasi</h4>
            
            <form action="modules/settings/process.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="app_name" class="form-label">Nama Aplikasi</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($app_name) ?>" required>
                            <div class="form-text">Nama yang muncul di judul halaman dan login.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bimbel_name" class="form-label">Nama Bimbel</label>
                            <input type="text" class="form-control" id="bimbel_name" name="bimbel_name" value="<?= htmlspecialchars($bimbel_name) ?>" required>
                            <div class="form-text">Nama instansi atau bimbel Anda.</div>
                        </div>

                        <div class="mb-4">
                            <label for="bimbel_logo" class="form-label">Logo Bimbel</label>
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-light p-2 rounded border" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                    <img src="../assets/img/<?= $bimbel_logo ?>" alt="Current Logo" id="preview-logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="bimbel_logo" name="bimbel_logo" accept="image/*">
                                    <div class="form-text">Format: PNG, JPG, JPEG (Max 2MB). Sistem akan mengompres gambar secara otomatis.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <button type="submit" name="update_settings" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview logo before upload
    document.getElementById('bimbel_logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-logo').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>
