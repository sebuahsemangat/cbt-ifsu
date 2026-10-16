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
                <form action="modules/questions/import_process.php" method="POST" enctype="multipart/form-data"
                    id="importForm">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">

                    <!-- <div class="mb-4">
                        <label class="form-label fw-bold">Referensi/Stimulus (Opsional)</label>
                        <select name="reference_id" class="form-select">
                            <option value="">-- Tanpa Referensi --</option>
                            <?php foreach ($references as $ref): ?>
                                <option value="<?= $ref['id'] ?>"><?= htmlspecialchars($ref['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Referensi ini akan diterapkan ke semua soal yang diimport</small>
                    </div> -->

                    <div class="mb-4">
                        <label class="form-label fw-bold">File Word (.docx)</label>
                        <input type="file" name="word_file" class="form-control form-control-lg" accept=".docx" required
                            id="wordFile">
                        <small class="text-muted">Maksimal 10MB. Pastikan mengikuti format template.</small>
                    </div>

                    <div id="importProgress" class="d-none mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-primary fw-bold" id="progressStatus">Mengupload...</span>
                            <span class="text-muted" id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%"></div>
                        </div>
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
                    <?= (int) ($_GET['count'] ?? 0) ?> soal berhasil diimport.
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Terjadi Kesalahan!</strong><br>
                    <?= htmlspecialchars(urldecode($_GET['msg'] ?? 'Unknown error')) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Card Petunjuk Penyesuaian Template Menggunakan AI -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10 text-primary"
                        style="width: 40px; height: 40px;">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Petunjuk Penyesuaian Soal dengan AI</h6>
                        <small class="text-muted">Gunakan Claude AI untuk merapikan format soal ke template sistem
                            secara instan</small>
                    </div>
                </div>
                <span class="badge bg-warning text-dark"><i class="bi bi-stars me-1"></i>Tips AI</span>
            </div>
            <div class="card-body p-4">
                <ol class="mb-0 ps-3 text-secondary" style="line-height: 1.8;">
                    <li class="mb-3">
                        <strong class="text-dark">Download Template Soal</strong><br>
                        Unduh template dokumen Word resmi sistem melalui tombol
                        <a href="modules/questions/download_template.php"
                            class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 align-baseline">
                            <i class="bi bi-download me-1"></i>Download Template Word
                        </a>
                    </li>
                    <li class="mb-3">
                        <strong class="text-dark">Buka Website Claude AI</strong><br>
                        Buka browser dan akses website resmi
                        <a href="https://claude.ai" target="_blank" rel="noopener noreferrer"
                            class="fw-semibold text-primary text-decoration-none">
                            claude.ai <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </li>
                    <li class="mb-3">
                        <strong class="text-dark">Upload Dua File Sekaligus</strong><br>
                        Unggah / lampirkan dua file ke dalam percakapan Claude AI:
                        <ul class="mt-1 mb-0 ps-3 small text-muted">
                            <li>File soal Anda yang sudah ada</li>
                            <li>File template Word yang telah Anda download</li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong class="text-dark">Masukkan Prompt Perintah</strong><br>
                        Salin dan kirimkan prompt di bawah ini ke Claude AI:
                        <div class="bg-light p-3 rounded-3 border mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary-subtle text-secondary small font-monospace">
                                    <i class="bi bi-chat-left-dots-fill me-1"></i>Prompt AI
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-semibold"
                                    id="btnCopyPrompt" onclick="copyPrompt()">
                                    <i class="bi bi-clipboard me-1" id="copyIcon"></i><span id="copyText">Salin
                                        Prompt</span>
                                </button>
                            </div>
                            <div class="p-2 bg-white rounded border border-light-subtle">
                                <p class="mb-0 text-dark font-monospace small" id="promptContent"
                                    style="white-space: pre-wrap; user-select: all;">Buat sebuah file docx dari dokumen
                                    yang saya upload sesuaikan formatnya dengan template yang disediakan. jika soal
                                    mengandung referensi, maka pisahkan referensi di dalam chat jangan di dalam file
                                    docx</p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <strong class="text-dark">Download & Upload ke Sistem</strong><br>
                        Download file <code>.docx</code> yang telah dihasilkan oleh Claude AI, kemudian upload file
                        tersebut pada bagian <strong>Upload File Word</strong> di atas.
                    </li>
                </ol>
            </div>
        </div>
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
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatPG">
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
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatBS">
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
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatMulti">
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

                    <!-- Jawaban Singkat -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatIsian">
                                <strong>Jawaban Singkat</strong>
                            </button>
                        </h2>
                        <div id="formatIsian" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
                                <pre class="bg-light p-3 rounded small"><code>[SOAL-ISIAN]
Siapa nama presiden pertama Indonesia?
[JAWABAN] Soekarno</code></pre>
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
                    <li>Setiap soal harus diawali dengan tag <code>[SOAL]</code>, <code>[SOAL-BS]</code>,
                        <code>[SOAL-MULTI]</code>, atau <code>[SOAL-ISIAN]</code>
                    </li>
                    <li>Pastikan tidak ada baris kosong di antara opsi jawaban</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('wordFile').addEventListener('change', function (e) {
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

    document.getElementById('importForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const btn = document.getElementById('submitBtn');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressStatus = document.getElementById('progressStatus');
        const progressContainer = document.getElementById('importProgress');

        // UI states
        btn.disabled = true;
        progressContainer.classList.remove('d-none');

        const xhr = new XMLHttpRequest();

        // Track upload progress
        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';

                if (percent === 100) {
                    progressStatus.textContent = 'Memproses dokumen (Mungkin butuh waktu)...';
                    progressBar.classList.add('bg-success');
                }
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                btn.disabled = false;
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        window.location.href = `dashboard.php?page=questions_import&subject_id=<?= $subject_id ?>&status=success&count=${response.count}`;
                    } else {
                        alert('Error: ' + response.msg);
                    }
                } catch (e) {
                    alert('Terjadi kesalahan sistem saat memproses file.');
                    console.error(xhr.responseText);
                }
            }
        };

        xhr.open('POST', form.action, true);
        xhr.send(formData);
    });

    function copyPrompt() {
        const text = document.getElementById('promptContent').innerText.trim();
        const btn = document.getElementById('btnCopyPrompt');
        const icon = document.getElementById('copyIcon');
        const textSpan = document.getElementById('copyText');

        function showSuccess() {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');
            icon.className = 'bi bi-check-lg me-1';
            textSpan.textContent = 'Tersalin!';
            setTimeout(function () {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
                icon.className = 'bi bi-clipboard me-1';
                textSpan.textContent = 'Salin Prompt';
            }, 2000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showSuccess).catch(function () {
                fallbackCopy();
            });
        } else {
            fallbackCopy();
        }

        function fallbackCopy() {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showSuccess();
            } catch (e) {
                alert('Gagal menyalin otomatis. Silakan salin teks secara manual.');
            }
            document.body.removeChild(textarea);
        }
    }
</script>