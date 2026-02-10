<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    header("Location: dashboard.php?page=exams");
    exit;
}

// Get subject info
$stmtSubject = $pdo->prepare("SELECT s.name as subject_name, e.exam_name 
                               FROM subjects s 
                               JOIN exams e ON s.exam_id = e.id 
                               WHERE s.id = ?");
$stmtSubject->execute([$subject_id]);
$subjectInfo = $stmtSubject->fetch();

// Get references for this subject
$stmtRef = $pdo->prepare("SELECT id, title FROM `references` WHERE subject_id = ?");
$stmtRef->execute([$subject_id]);
$references = $stmtRef->fetchAll();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-word me-2"></i>Import Soal dari Word</h3>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($subjectInfo['exam_name'] ?? '') ?> - 
            <?= htmlspecialchars($subjectInfo['subject_name'] ?? '') ?>
        </p>
    </div>
    <a href="dashboard.php?page=questions&subject_id=<?= $subject_id ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Upload File Word</h5>
            </div>
            <div class="card-body">
                <form action="modules/questions/import_process.php" method="POST" enctype="multipart/form-data" id="importForm">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Referensi/Stimulus (Opsional)</label>
                        <select name="reference_id" class="form-select">
                            <option value="">-- Tanpa Referensi --</option>
                            <?php foreach ($references as $ref): ?>
                                <option value="<?= $ref['id'] ?>"><?= htmlspecialchars($ref['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Referensi ini akan diterapkan ke semua soal yang diimport</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">File Word (.docx)</label>
                        <input type="file" name="word_file" class="form-control form-control-lg" 
                               accept=".docx" required id="wordFile">
                        <small class="text-muted">Maksimal 10MB. Pastikan mengikuti format template.</small>
                    </div>

                    <div id="fileInfo" class="alert alert-info d-none">
                        <i class="bi bi-file-earmark-word me-2"></i>
                        <span id="fileName"></span>
                        <span class="badge bg-secondary ms-2" id="fileSize"></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="bi bi-cloud-upload me-2"></i>Import Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Result Area -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'success'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Import Berhasil!</strong> 
                    <?= (int)($_GET['count'] ?? 0) ?> soal berhasil diimport.
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Terjadi Kesalahan!</strong><br>
                    <?= htmlspecialchars(urldecode($_GET['msg'] ?? 'Unknown error')) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Format Template</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Gunakan format berikut dalam dokumen Word Anda:</p>

                <div class="accordion" id="formatAccordion">
                    <!-- Pilihan Ganda -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#formatPG">
                                <strong>Pilihan Ganda (PG)</strong>
                            </button>
                        </h2>
                        <div id="formatPG" class="accordion-collapse collapse show" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
<pre class="bg-light p-3 rounded small"><code>[SOAL]
Berapakah hasil dari 2 + 2?
[A] 3
[B] 4
[C] 5
[D] 6
[E] 7
[JAWABAN] B</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Benar Salah -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#formatBS">
                                <strong>Benar / Salah (BS)</strong>
                            </button>
                        </h2>
                        <div id="formatBS" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
<pre class="bg-light p-3 rounded small"><code>[SOAL-BS]
Tentukan benar atau salah:
[1] Matahari terbit dari timur [BENAR]
[2] Bumi berbentuk datar [SALAH]
[3] Air mendidih pada 100°C [BENAR]</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Multi Checkbox -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#formatMulti">
                                <strong>Multi Checkbox</strong>
                            </button>
                        </h2>
                        <div id="formatMulti" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
<pre class="bg-light p-3 rounded small"><code>[SOAL-MULTI]
Pilih semua yang termasuk buah:
[1] Apel [V]
[2] Wortel []
[3] Mangga [V]
[4] Kentang []</code></pre>
                                <small class="text-muted"><code>[V]</code> = benar, <code>[]</code> = salah</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-grid">
                    <a href="modules/questions/download_template.php" class="btn btn-outline-success">
                        <i class="bi bi-download me-2"></i>Download Template Word
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-warning">
            <div class="card-body">
                <h6 class="fw-bold text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Catatan Penting</h6>
                <ul class="mb-0 small">
                    <li>Gambar dalam dokumen harus <strong>inline</strong> (bukan floating)</li>
                    <li>Rumus matematika kompleks bisa disisipkan sebagai gambar</li>
                    <li>Setiap soal harus diawali dengan tag <code>[SOAL]</code>, <code>[SOAL-BS]</code>, atau <code>[SOAL-MULTI]</code></li>
                    <li>Pastikan tidak ada baris kosong di antara opsi jawaban</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('wordFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const fileInfo = document.getElementById('fileInfo');
    
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        fileInfo.classList.remove('d-none');
    } else {
        fileInfo.classList.add('d-none');
    }
});

document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>
