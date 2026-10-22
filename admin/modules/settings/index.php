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
$allow_student_change_password = ($settings['allow_student_change_password'] ?? '1') === '1';
$min_exam_duration_percent = (int) ($settings['min_exam_duration_percent'] ?? 50);
$enable_anti_cheating = ($settings['enable_anti_cheating'] ?? '1') === '1';
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
                            <input type="text" class="form-control" id="app_name" name="app_name"
                                value="<?= htmlspecialchars($app_name) ?>" required>
                            <div class="form-text">Nama yang muncul di judul halaman dan login.</div>
                        </div>

                        <div class="mb-3">
                            <label for="bimbel_name" class="form-label">Nama Sekolah</label>
                            <input type="text" class="form-control" id="bimbel_name" name="bimbel_name"
                                value="<?= htmlspecialchars($bimbel_name) ?>" required>
                            <div class="form-text">Nama instansi atau sekolah Anda.</div>
                        </div>

                        <?php
                        $logo_path = '../assets/img/' . $bimbel_logo;
                        $logo_version = (file_exists($logo_path) && is_file($logo_path)) ? '?v=' . filemtime($logo_path) : '';
                        ?>
                        <div class="mb-4">
                            <label for="bimbel_logo" class="form-label">Logo Sekolah</label>
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded border"
                                    style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; background: repeating-conic-gradient(#f8f9fa 0% 25%, #fff 0% 50%) 50% / 16px 16px;">
                                    <img src="../assets/img/<?= htmlspecialchars($bimbel_logo) . $logo_version ?>" alt="Current Logo" id="preview-logo"
                                        style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="bimbel_logo" name="bimbel_logo"
                                        accept="image/png, image/jpeg, .png, .jpg, .jpeg">
                                    <div class="form-text">Format: PNG, JPG, JPEG (Max 2MB). Disarankan PNG transparan untuk hasil terbaik.</div>
                                    <div id="file-error" class="text-danger small mt-1" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 pt-3 border-top">
                            <label class="form-label fw-bold d-block">Batas Minimal Pengerjaan Ujian</label>
                            <div class="row align-items-center g-2">
                                <div class="col-sm-4 col-md-3">
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="min_exam_duration_percent" name="min_exam_duration_percent"
                                            value="<?= $min_exam_duration_percent ?>" min="0" max="100" step="1" required>
                                        <span class="input-group-text fw-bold">%</span>
                                    </div>
                                </div>
                                <div class="col-sm-8 col-md-9">
                                    <div class="form-text mt-0">
                                        Persentase durasi minimal sebelum siswa diizinkan mengumpulkan ujian. Contoh: jika durasi mapel <strong>90 menit</strong> dan setting <strong>50%</strong>, maka siswa baru bisa submit setelah <strong>45 menit</strong>. (Isi <strong>0</strong> untuk menonaktifkan).
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 pt-3 border-top">
                            <label class="form-label fw-bold d-block">Fitur Siswa</label>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input" type="checkbox" role="switch" id="allow_student_change_password" name="allow_student_change_password" value="1" <?= $allow_student_change_password ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="allow_student_change_password">
                                    Izinkan Siswa Mengubah Password
                                </label>
                            </div>
                            <div class="form-text">
                                Jika diaktifkan, siswa dapat mengubah password mereka sendiri melalui dashboard siswa. Jika dinonaktifkan, menu ganti password akan disembunyikan dan diblokir.
                            </div>
                        </div>

                        <div class="mb-4 pt-3 border-top">
                            <label class="form-label fw-bold d-block">Fitur Anti-Cheating (Integritas Ujian)</label>
                            <div class="form-check form-switch fs-6 mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_anti_cheating" name="enable_anti_cheating" value="1" <?= $enable_anti_cheating ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="enable_anti_cheating">
                                    Aktifkan Proteksi Anti-Cheating & Kunci Ujian
                                </label>
                            </div>
                            <div class="form-text">
                                Jika diaktifkan:
                                <ul class="mb-0 ps-3 small text-muted">
                                    <li>Siswa <strong>dilarang menyalin (copy) teks</strong> soal/referensi, klik kanan, dan shortcut keyboard.</li>
                                    <li>Siswa diwajibkan dalam <strong>mode layar penuh (fullscreen)</strong>.</li>
                                    <li>Sistem mendeteksi jika siswa <strong>minimize, berpindah tab, atau membuka aplikasi lain</strong>.</li>
                                    <li>Maksimal pelanggaran <strong>3 kali</strong>: pelanggaran 1 & 2 memunculkan peringatan, dan pelanggaran ke-3 <strong>ujian otomatis terkunci</strong> hingga dibuka oleh Pengawas.</li>
                                </ul>
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
    // Preview logo before upload and validate file
    document.getElementById('bimbel_logo').addEventListener('change', function (e) {
        const file = e.target.files[0];
        const errorDiv = document.getElementById('file-error');
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        if (file) {
            // Validasi format
            const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            const validExtensions = ['png', 'jpg', 'jpeg'];
            const ext = file.name.split('.').pop().toLowerCase();

            if (!validTypes.includes(file.type) && !validExtensions.includes(ext)) {
                errorDiv.textContent = 'Format file tidak didukung! Pilih file PNG atau JPG/JPEG.';
                errorDiv.style.display = 'block';
                this.value = '';
                return;
            }

            // Validasi ukuran (2MB = 2097152 bytes)
            if (file.size > 2 * 1024 * 1024) {
                errorDiv.textContent = 'Ukuran file terlalu besar! Maksimal 2MB (File Anda: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB).';
                errorDiv.style.display = 'block';
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('preview-logo').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>