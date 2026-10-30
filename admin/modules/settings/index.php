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
$cbt_cloud_sync_url = $settings['cbt_cloud_sync_url'] ?? 'http://cbt.page.gd';
$cbt_cloud_sync_token = $settings['cbt_cloud_sync_token'] ?? 'cbt-cloud-secret-key-2026';
$cbt_cloud_last_upload = $settings['cbt_cloud_last_upload'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4">
            <h4 class="fw-bold mb-4">Pengaturan Aplikasi</h4>

            <form id="settingsForm" action="modules/settings/process.php" method="POST">
                <input type="hidden" name="update_settings" value="1">
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
                                    <input type="file" class="form-control" id="bimbel_logo"
                                        accept="image/png, image/jpeg, .png, .jpg, .jpeg, .webp">
                                    <input type="hidden" name="bimbel_logo_base64" id="bimbel_logo_base64">
                                    <div class="form-text">Format: PNG, JPG, JPEG, WebP (Max 2MB). Disarankan PNG transparan untuk hasil terbaik.</div>
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
                                    <li>Sistem mendeteksi jika siswa <strong>minimize, berpindah tab, atau membuka aplikasi lain</strong>.</li>
                                    <li>Maksimal pelanggaran <strong>3 kali</strong>: pelanggaran 1 & 2 memunculkan peringatan, dan pelanggaran ke-3 <strong>ujian otomatis terkunci</strong> hingga dibuka oleh Pengawas.</li>
                                </ul>
                            </div>
                        </div>



                        <div class="mb-4 pt-3 border-top">
                            <label class="form-label fw-bold d-block text-primary">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Sinkronisasi CBT Cloud (cbt.page.gd)
                            </label>
                            <p class="text-muted small mb-3">
                                Konfigurasi server cloud terpusat untuk mengunggah nilai dan lembar jawaban siswa dari multi-lab offline.
                            </p>

                            <div class="mb-3">
                                <label for="cbt_cloud_sync_url" class="form-label fw-semibold small">URL Server CBT Cloud</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                    <input type="url" class="form-control" id="cbt_cloud_sync_url" name="cbt_cloud_sync_url"
                                        value="<?= htmlspecialchars($cbt_cloud_sync_url) ?>" placeholder="http://cbt.page.gd" required>
                                </div>
                                <div class="form-text">Alamat root domain server CBT Cloud.</div>
                            </div>

                            <div class="mb-3">
                                <label for="cbt_cloud_sync_token" class="form-label fw-semibold small">Token Rahasia Sinkronisasi (Sync Token)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="cbt_cloud_sync_token" name="cbt_cloud_sync_token"
                                        value="<?= htmlspecialchars($cbt_cloud_sync_token) ?>" placeholder="Masukkan token rahasia" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()">
                                        <i class="bi bi-eye" id="toggleTokenIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text">Kunci otentikasi rahasia yang sama dengan yang dipasang di server cloud.</div>
                            </div>

                            <div class="d-flex align-items-center gap-3 mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnTestCloudConn" onclick="testCloudConnection()">
                                    <i class="bi bi-broadcast me-1"></i> Tes Koneksi Cloud
                                </button>
                                <span id="testCloudResult" class="small"></span>
                            </div>

                            <?php if (!empty($cbt_cloud_last_upload)): ?>
                                <div class="mt-2 text-muted small">
                                    <i class="bi bi-clock-history me-1"></i> Terakhir berhasil upload nilai: <strong><?= date('d M Y H:i:s', strtotime($cbt_cloud_last_upload)) ?></strong>
                                </div>
                            <?php endif; ?>
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
    const logoInput = document.getElementById('bimbel_logo');
    const base64Input = document.getElementById('bimbel_logo_base64');
    const errorDiv = document.getElementById('file-error');

    logoInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
        base64Input.value = '';

        if (file) {
            // Validasi format
            const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            const validExtensions = ['png', 'jpg', 'jpeg', 'webp'];
            const ext = file.name.split('.').pop().toLowerCase();

            if (!validTypes.includes(file.type) && !validExtensions.includes(ext)) {
                errorDiv.textContent = 'Format file tidak didukung! Pilih file PNG, JPG/JPEG, atau WebP.';
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
                base64Input.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Pastikan jika user klik simpan langsung saat file baru dipilih, base64 sudah terisi
    document.getElementById('settingsForm').addEventListener('submit', function (e) {
        if (logoInput.files.length > 0 && !base64Input.value) {
            e.preventDefault();
            const reader = new FileReader();
            reader.onload = function (evt) {
                base64Input.value = evt.target.result;
                document.getElementById('settingsForm').submit();
            };
            reader.readAsDataURL(logoInput.files[0]);
        }
    });

    // Toggle tampilan token rahasia
    function toggleTokenVisibility() {
        const tokenInput = document.getElementById('cbt_cloud_sync_token');
        const icon = document.getElementById('toggleTokenIcon');
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            tokenInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // Tes koneksi ke server cloud
    function testCloudConnection() {
        const btn = document.getElementById('btnTestCloudConn');
        const resSpan = document.getElementById('testCloudResult');
        const urlInput = document.getElementById('cbt_cloud_sync_url');
        const tokenInput = document.getElementById('cbt_cloud_sync_token');

        const url = urlInput ? urlInput.value.trim() : '';
        const token = tokenInput ? tokenInput.value.trim() : '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji...';
        resSpan.innerHTML = '';

        const formData = new FormData();
        formData.append('action', 'test_connection');
        formData.append('url', url);
        formData.append('token', token);

        fetch('modules/scores/sync_process.php', {
            method: 'POST',
            body: formData
        })
        .then(async r => {
            const rawText = await r.text();
            try {
                return JSON.parse(rawText);
            } catch (e) {
                const cleanMsg = rawText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(cleanMsg ? cleanMsg.substring(0, 250) : "Respon server kosong");
            }
        })
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-broadcast me-1"></i> Tes Koneksi Cloud';
            if (data.success) {
                resSpan.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ' + (data.message || 'Terhubung dengan baik') + '</span>';
            } else {
                resSpan.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i> ' + (data.message || 'Koneksi gagal') + '</span>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-broadcast me-1"></i> Tes Koneksi Cloud';
            resSpan.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i> Error: ' + err.message + '</span>';
        });
    }
</script>