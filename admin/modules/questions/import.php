<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    if (!headers_sent()) {
        header("Location: dashboard.php?page=exams");
    } else {
        echo "<script>window.location.href='dashboard.php?page=exams';</script>";
    }
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

<link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

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

<!-- Result Area (Alert status setelah penyimpanan) -->
<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-3 text-success me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Import Berhasil!</h5>
                    <p class="mb-0">
                        Sebanyak <strong><?= (int) ($_GET['count'] ?? 0) ?></strong> soal berhasil disimpan ke Bank Soal
                        <?php if (!empty($_GET['ref_count'])): ?>
                            dan <strong><?= (int) $_GET['ref_count'] ?></strong> stimulus referensi baru telah dibuat.
                        <?php else: ?>.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="ms-auto">
                    <a href="dashboard.php?page=questions&subject_id=<?= $subject_id ?>" class="btn btn-success">
                        <i class="bi bi-list-check me-1"></i> Buka Bank Soal
                    </a>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Terjadi Kesalahan!</h5>
                    <p class="mb-0"><?= htmlspecialchars(urldecode($_GET['msg'] ?? 'Unknown error')) ?></p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- SEKSI UPLOAD & PETUNJUK (Tampilan Awal) -->
<!-- ========================================================================= -->
<div id="uploadViewSection">
<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-upload me-2"></i>Upload File Word Soal</h5>
            </div>
            <div class="card-body p-4">
                <form action="modules/questions/import_process.php" method="POST" id="importForm">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    <input type="hidden" name="word_base64" id="wordFileBase64">

                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Dokumen Word (.docx)</label>
                        <input type="file" name="word_file" class="form-control form-control-lg" accept=".docx" required
                            id="wordFile">
                        <small class="text-muted d-block mt-1">Maksimal 10MB. Dokumen akan diuji & ditampilkan previewnya terlebih dahulu.</small>
                    </div>

                    <div id="importProgress" class="d-none mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-primary fw-bold" id="progressStatus">Mengupload dokumen...</span>
                            <span class="text-muted" id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div id="fileInfo" class="alert alert-info d-none mb-4">
                        <i class="bi bi-file-earmark-word me-2"></i>
                        <span id="fileName"></span>
                        <span class="badge bg-secondary ms-2" id="fileSize"></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" id="submitBtn">
                            <i class="bi bi-search me-2"></i>Preview & Validasi Soal
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
                                    style="user-select: all; white-space: pre-line;">Buat sebuah file docx dari dokumen yang saya upload, sesuaikan formatnya dengan template yang disediakan.
Aturan penting:
1. REFERENSI / STIMULUS: Jika naskah memiliki teks bacaan, wacana, infografis, gambar, atau tabel data stimulus untuk sekelompok soal, sertakan langsung di dalam file docx menggunakan tag [REFERENSI: Judul Bacaan/Tabel] tepat sebelum kelompok soal tersebut. Jika soal berikutnya tidak menggunakan stimulus, gunakan tag [TANPA-REFERENSI].
2. TABEL: Jika stimulus atau soal memiliki tabel (misal tabel distribusi frekuensi, skor, dll), buatlah tetap sebagai Tabel Word yang rapi, jangan diubah menjadi teks biasa.
3. RUMUS & PECAHAN MATEMATIKA: Untuk rumus matematika, pecahan vertikal, akar, dan simbol khusus, gunakan notasi LaTeX standar \(...\).
- Pecahan vertikal/normal: tulis \( \frac{pembilang}{penyebut} \), contoh: \( \frac{1}{2} \) atau \( \frac{a\sqrt{6}}{2} \).
- JANGAN mengubah pecahan menjadi format garis miring (seperti 1/2 atau a√6/2). Pecahan harus selalu dalam format LaTeX \( \frac{...}{...} \) agar tampil bertumpuk secara normal di aplikasi ujian.</p>
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

                    <!-- Rumus Matematika & Pecahan -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatMath">
                                <strong class="text-danger"><i class="bi bi-calculator me-1"></i> Rumus Matematika & Pecahan</strong>
                            </button>
                        </h2>
                        <div id="formatMath" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Gunakan format LaTeX <code>\( \frac{a}{b} \)</code> agar pecahan tampil vertikal (bertumpuk normal), bukan garis miring <code>a/b</code>.</p>
                                <pre class="bg-light p-3 rounded small"><code>[SOAL]
Diketahui kubus ABCD.EFGH dengan panjang rusuk a cm. Jarak titik F ke garis AG adalah...
[A] \( \frac{a\sqrt{6}}{2} \) cm
[B] \( \frac{a\sqrt{6}}{3} \) cm
[C] \( \frac{a\sqrt{6}}{4} \) cm
[D] \( \frac{a\sqrt{6}}{5} \) cm
[E] \( \frac{a\sqrt{6}}{6} \) cm
[JAWABAN] B</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Referensi / Stimulus -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#formatRef">
                                <strong class="text-primary"><i class="bi bi-file-text me-1"></i> Referensi / Stimulus (Teks, Gambar, & Tabel)</strong>
                            </button>
                        </h2>
                        <div id="formatRef" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Tuliskan sebelum soal yang bersangkutan. Bisa berisi teks wacana, gambar infografis, atau <strong>Tabel Word</strong>. Semua soal di bawahnya otomatis terhubung ke referensi ini sampai ditemukan referensi baru atau <code>[TANPA-REFERENSI]</code>.</p>
                                <pre class="bg-light p-3 rounded small"><code>[REFERENSI: Tabel Distribusi Frekuensi Nilai Siswa]
Perhatikan tabel data frekuensi berikut ini:
(Sisipkan Tabel Word di sini)

[SOAL]
Berdasarkan data tabel di atas, tentukan nilai modus data tersebut:
[A] 55,5
[B] 62,3
[C] 67,8
[D] 70,5
[E] 75,0
[JAWABAN] B

[TANPA-REFERENSI]
[SOAL]
Ibu kota Indonesia adalah...
[A] Jakarta
[B] Nusantara
[JAWABAN] B</code></pre>
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
                    <li>Stimulus teks, infografis, atau tabel data diawali dengan tag <code>[REFERENSI: Judul]</code></li>
                    <li>Tabel Word di dalam stimulus otomatis terbaca dan diimport ke sistem</li>
                    <li>Pecahan matematika ditulis dalam format LaTeX: <code>\( \frac{pembilang}{penyebut} \)</code> agar tampil bertumpuk normal</li>
                    <li>Setiap soal harus diawali dengan tag <code>[SOAL]</code>, <code>[SOAL-BS]</code>,
                        <code>[SOAL-MULTI]</code>, atau <code>[SOAL-ISIAN]</code>
                    </li>
                    <li>Pastikan tidak ada baris kosong di antara opsi jawaban</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div> <!-- End #uploadViewSection -->

<!-- ========================================================================= -->
<!-- SEKSI PREVIEW & VALIDASI SOAL (Tampil Otomatis Setelah Dokumen Diuji) -->
<!-- ========================================================================= -->
<div id="previewViewSection" class="d-none">
    <!-- Top Summary & Control Bar (Sticky) -->
    <div class="card border-0 shadow mb-4 sticky-top bg-white border-top border-4 border-primary" style="top: 15px; z-index: 1020;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center">
                        <i class="bi bi-ui-checks text-primary me-2 fs-4"></i>
                        Preview & Validasi Dokumen Soal
                    </h5>
                    <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                        <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill" id="badgeTotalSoal">
                            <i class="bi bi-files me-1"></i> <span id="txtTotalSoal">0</span> Total Soal
                        </span>
                        <span class="badge bg-success px-3 py-2 fs-6 rounded-pill" id="badgeValidSoal">
                            <i class="bi bi-check-circle me-1"></i> <span id="txtValidSoal">0</span> Siap Diimport
                        </span>
                        <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill" id="badgeWarningSoal">
                            <i class="bi bi-exclamation-triangle me-1"></i> <span id="txtWarningSoal">0</span> Perlu Periksa
                        </span>
                        <span class="badge bg-info text-dark px-3 py-2 fs-6 rounded-pill" id="badgeRefSoal">
                            <i class="bi bi-collection-play me-1"></i> <span id="txtRefSoal">0</span> Stimulus
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="cancelPreview()">
                        <i class="bi bi-arrow-left me-1"></i> Ganti File
                    </button>
                    <button type="button" class="btn btn-success btn-lg fw-bold px-4 shadow-sm" id="btnConfirmSave" onclick="submitConfirmedQuestions()">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Simpan ke Bank Soal (<span class="selectedCountText">0</span>)
                    </button>
                </div>
            </div>

            <hr class="my-3">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox" checked onchange="toggleSelectAll(this.checked)">
                        <label class="form-check-label fw-bold text-dark" for="selectAllCheckbox">Pilih Semua Soal</label>
                    </div>
                    <div class="vr mx-2 text-muted"></div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="filterAllBtn" onclick="filterPreview('all')">
                            Semua (<span id="countFilterAll">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-warning text-dark" id="filterWarningBtn" onclick="filterPreview('warning')">
                            Perlu Periksa (<span id="countFilterWarning">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-success" id="filterValidBtn" onclick="filterPreview('valid')">
                            Valid Saja (<span id="countFilterValid">0</span>)
                        </button>
                    </div>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Klik pilihan pada soal PG untuk menetapkan kunci jawaban secara instan jika belum ada.
                </div>
            </div>
        </div>
    </div>

    <!-- Kotak Stimulus / Referensi Terdeteksi -->
    <div id="referencesPreviewBox" class="card border-0 shadow-sm mb-4 d-none">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-collection-play-fill text-info me-2"></i>
                Stimulus / Referensi Terdeteksi (<span id="refBoxCount">0</span>)
            </h6>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRefBody">
                <i class="bi bi-chevron-down"></i> Buka / Tutup
            </button>
        </div>
        <div class="collapse show" id="collapseRefBody">
            <div class="card-body p-3" id="referencesPreviewList">
                <!-- Rendered dynamically -->
            </div>
        </div>
    </div>

    <!-- Daftar Kartu Soal -->
    <div id="questionsPreviewList">
        <!-- Rendered dynamically -->
    </div>

    <!-- Tombol Simpan Bawah -->
    <div class="card border-0 shadow-sm mt-4 mb-5 text-center bg-light">
        <div class="card-body py-4">
            <h5 class="fw-bold mb-2">Selesai Meninjau Soal?</h5>
            <p class="text-muted mb-3">Pastikan soal yang dipilih sudah sesuai. Soal yang dicentang akan otomatis disimpan ke database ujian.</p>
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="cancelPreview()">
                    <i class="bi bi-arrow-left me-1"></i> Ganti File Word
                </button>
                <button type="button" class="btn btn-success btn-lg fw-bold px-5 shadow-sm" onclick="submitConfirmedQuestions()">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i>Simpan ke Bank Soal (<span class="selectedCountText">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // State Preview Data
    let currentQuestions = [];
    let currentReferences = {};
    let activeFilter = 'all';

    // File input preview info & Base64 encoder
    document.getElementById('wordFile').addEventListener('change', function (e) {
        const file = e.target.files[0];
        const fileInfo = document.getElementById('fileInfo');

        if (file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
            fileInfo.classList.remove('d-none');

            // Baca file ke Base64 (Bypass OpenResty 403 Multipart WAF)
            const reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('wordFileBase64').value = evt.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('wordFileBase64').value = '';
            fileInfo.classList.add('d-none');
        }
    });

    // Form submit -> action=preview
    document.getElementById('importForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const form = this;
        const file = document.getElementById('wordFile').files[0];
        let wordBase64 = document.getElementById('wordFileBase64').value;
        const btn = document.getElementById('submitBtn');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressStatus = document.getElementById('progressStatus');
        const progressContainer = document.getElementById('importProgress');

        btn.disabled = true;
        progressContainer.classList.remove('d-none');
        progressBar.style.width = '10%';
        progressPercent.textContent = '10%';
        progressStatus.textContent = 'Mengunggah dokumen...';

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';

                if (percent === 100) {
                    progressStatus.textContent = 'Membedah dan memvalidasi dokumen Word...';
                    progressBar.classList.add('bg-success');
                }
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                btn.disabled = false;
                progressContainer.classList.add('d-none');

                if (xhr.status === 403) {
                    alert('Error 403 Forbidden (OpenResty WAF): Server menolak unggahan file. Pastikan format file sesuai.');
                    return;
                }

                if (xhr.status === 404) {
                    alert('Error 404 Not Found: Server web Nginx tidak menemukan endpoint atau menolak ukuran payload. Pastikan file script terbaru telah di-upload ke server hosting.');
                    console.error('Server 404 response:', xhr.responseText);
                    return;
                }

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'preview') {
                        // Render Preview & Validasi UI
                        currentQuestions = response.questions || [];
                        currentReferences = response.references || {};
                        renderPreviewUI();
                    } else if (response.status === 'success') {
                        window.location.href = `dashboard.php?page=questions_import&subject_id=<?= $subject_id ?>&status=success&count=${response.count}`;
                    } else {
                        alert('Gagal memproses dokumen: ' + (response.msg || 'Format dokumen tidak sesuai.'));
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat memproses respon dari server. Periksa konsol browser.');
                    console.error('Server response:', xhr.responseText);
                }
            }
        };

        function executeSend(b64Data) {
            const endpoint = 'modules/questions/import_process.php';
            xhr.open('POST', endpoint, true);
            const formData = new FormData();
            formData.append('subject_id', '<?= $subject_id ?>');
            formData.append('action', 'preview');
            if (b64Data) {
                formData.append('word_base64', b64Data);
            }
            xhr.send(formData);
        }

        if (!wordBase64 && file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                wordBase64 = evt.target.result;
                document.getElementById('wordFileBase64').value = wordBase64;
                executeSend(wordBase64);
            };
            reader.readAsDataURL(file);
        } else {
            executeSend(wordBase64);
        }
    });

    // Render Preview Section
    function renderPreviewUI() {
        document.getElementById('uploadViewSection').classList.add('d-none');
        document.getElementById('previewViewSection').classList.remove('d-none');

        // Initialize selection state for all questions (default checked)
        currentQuestions.forEach(q => {
            if (q.is_selected === undefined) {
                q.is_selected = true;
            }
        });

        renderReferences();
        renderQuestions();
        updateStatsSummary();

        // Scroll to preview section
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Trigger MathJax typeset
        triggerMathTypeset();
    }

    // Render Stimulus / Referensi list
    function renderReferences() {
        const refKeys = Object.keys(currentReferences);
        const refBox = document.getElementById('referencesPreviewBox');
        const refList = document.getElementById('referencesPreviewList');

        if (refKeys.length === 0) {
            refBox.classList.add('d-none');
            return;
        }

        refBox.classList.remove('d-none');
        document.getElementById('refBoxCount').textContent = refKeys.length;

        let html = '<div class="row g-3">';
        refKeys.forEach((key, idx) => {
            const ref = currentReferences[key];
            const typeBadge = (ref.has_image && ref.content) 
                ? '<span class="badge bg-primary text-white ms-auto" style="font-size: 0.72rem;">Teks & Gambar</span>'
                : (ref.has_image 
                    ? '<span class="badge bg-success text-white ms-auto" style="font-size: 0.72rem;">Gambar</span>'
                    : '<span class="badge bg-secondary text-white ms-auto" style="font-size: 0.72rem;">Teks Bacaan</span>');

            html += `
                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light h-100 shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-info text-dark me-2">Stimulus #${idx + 1}</span>
                            <strong class="text-dark text-truncate" title="${escapeHtml(ref.title)}">${escapeHtml(ref.title)}</strong>
                            ${typeBadge}
                        </div>
                        ${ref.content ? `<div class="small text-secondary mb-2" style="max-height: 140px; overflow-y: auto;">${ref.content}</div>` : ''}
                        ${ref.has_image ? `<div class="mb-2 text-center"><img src="data:image/${ref.image_ext};base64,${ref.image_base64}" class="img-fluid rounded border shadow-sm" style="max-height: 200px; object-fit: contain;"></div>` : ''}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        refList.innerHTML = html;
    }

    // Render Questions list
    function renderQuestions() {
        const container = document.getElementById('questionsPreviewList');
        let html = '';

        currentQuestions.forEach((q, idx) => {
            const isWarning = !q.is_valid || (q.warnings && q.warnings.length > 0);
            const cardBorderClass = isWarning ? 'border-warning' : 'border-success';

            // Filter logic
            let isVisible = true;
            if (activeFilter === 'warning' && !isWarning) isVisible = false;
            if (activeFilter === 'valid' && isWarning) isVisible = false;

            html += `
                <div class="card border-2 shadow-sm mb-3 question-card ${cardBorderClass} ${isVisible ? '' : 'd-none'}" id="card_${q.temp_id}" data-type="${q.question_type}" data-warning="${isWarning ? '1' : '0'}">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-check-input q-select-chk me-2 fs-5" type="checkbox" id="chk_${q.temp_id}" ${q.is_selected ? 'checked' : ''} onchange="toggleQuestionSelect('${q.temp_id}', this.checked)">
                            <span class="badge bg-dark fs-6 px-3 py-1">Soal #${q.number}</span>
                            <span class="badge bg-secondary">${escapeHtml(q.question_type_label || q.question_type.toUpperCase())}</span>
                            ${q.ref_title ? `<span class="badge bg-info bg-opacity-25 text-dark border border-info border-opacity-50"><i class="bi bi-link-45deg"></i> ${escapeHtml(q.ref_title)}</span>` : ''}
                        </div>
                        <div id="status_badge_${q.temp_id}">
                            ${isWarning 
                                ? `<span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perlu Periksa</span>`
                                : `<span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Siap Diimport</span>`
                            }
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Warning Alert if any -->
                        <div id="warning_box_${q.temp_id}" class="${isWarning ? '' : 'd-none'}">
                            <div class="alert alert-warning py-2 px-3 small d-flex align-items-center mb-3">
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning me-2"></i>
                                <div>
                                    <strong>Perhatian:</strong> <span id="warning_text_${q.temp_id}">${escapeHtml((q.warnings || []).join(' | '))}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Teks Soal -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Teks Pertanyaan:</label>
                            <div class="p-3 bg-light rounded border text-dark" style="font-size: 1.05rem; line-height: 1.6;">
                                ${q.question_text}
                            </div>
                        </div>

                        <!-- Pilihan Jawaban / Kunci -->
                        <div class="mt-3">
                            <label class="form-label text-muted small fw-bold">Pilihan Jawaban & Kunci:</label>
                            ${renderAnswerOptions(q)}
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Render answer options based on question type
    function renderAnswerOptions(q) {
        if (q.question_type === 'pg') {
            const opts = q.answer_data && q.answer_data.options ? q.answer_data.options : {};
            const correctKey = q.answer_data ? q.answer_data.correct : '';
            let optHtml = '<div class="row g-2">';

            ['A', 'B', 'C', 'D', 'E'].forEach(letter => {
                const optText = opts[letter] || '';
                if (!optText && ['D', 'E'].includes(letter)) return; // skip unused D/E if empty

                const isCorrect = (correctKey === letter);
                optHtml += `
                    <div class="col-md-12">
                        <label class="form-check d-flex align-items-start p-3 rounded border mb-0 cursor-pointer ${isCorrect ? 'border-success bg-success bg-opacity-10' : 'bg-white'}" id="opt_box_${q.temp_id}_${letter}" style="cursor: pointer;">
                            <input class="form-check-input me-3 mt-1" type="radio" name="opt_${q.temp_id}" value="${letter}" ${isCorrect ? 'checked' : ''} onchange="setQuestionKey('${q.temp_id}', '${letter}')">
                            <div class="w-100">
                                <span class="badge ${isCorrect ? 'bg-success' : 'bg-secondary'} me-2 fs-6 px-2 py-1" id="badge_letter_${q.temp_id}_${letter}">[${letter}]</span>
                                <span class="text-dark">${optText !== '' ? optText : '<em class="text-muted">(Pilihan kosong)</em>'}</span>
                            </div>
                        </label>
                    </div>
                `;
            });
            optHtml += '</div>';
            return optHtml;
        }

        if (q.question_type === 'bs') {
            const statements = Array.isArray(q.answer_data) ? q.answer_data : [];
            let bsHtml = '<div class="table-responsive"><table class="table table-bordered mb-0"><thead><tr class="table-light"><th style="width: 50px;">No</th><th>Pernyataan</th><th style="width: 120px;" class="text-center">Kunci</th></tr></thead><tbody>';
            statements.forEach((st, sIdx) => {
                bsHtml += `
                    <tr>
                        <td class="text-center fw-bold">${sIdx + 1}</td>
                        <td>${escapeHtml(st.text || '')}</td>
                        <td class="text-center">
                            <span class="badge ${st.is_correct ? 'bg-success' : 'bg-danger'} px-3 py-2">
                                ${st.is_correct ? 'BENAR' : 'SALAH'}
                            </span>
                        </td>
                    </tr>
                `;
            });
            bsHtml += '</tbody></table></div>';
            return bsHtml;
        }

        if (q.question_type === 'multi') {
            const options = Array.isArray(q.answer_data) ? q.answer_data : [];
            let mHtml = '<div class="row g-2">';
            options.forEach((opt, oIdx) => {
                mHtml += `
                    <div class="col-md-12">
                        <div class="p-3 rounded border d-flex align-items-center ${opt.is_correct ? 'border-success bg-success bg-opacity-10' : 'bg-white'}">
                            <span class="badge ${opt.is_correct ? 'bg-success' : 'bg-secondary'} me-3 px-2 py-1">[${oIdx + 1}]</span>
                            <div class="flex-grow-1">${escapeHtml(opt.text || '')}</div>
                            ${opt.is_correct ? '<span class="badge bg-success ms-2"><i class="bi bi-check-lg me-1"></i>Pilihan Benar [V]</span>' : ''}
                        </div>
                    </div>
                `;
            });
            mHtml += '</div>';
            return mHtml;
        }

        if (q.question_type === 'isian') {
            const correct = q.answer_data && q.answer_data.correct ? q.answer_data.correct : '';
            return `
                <div class="p-3 bg-white rounded border">
                    <span class="text-muted me-2">Kunci Jawaban Singkat:</span>
                    <strong class="text-primary fs-5">${escapeHtml(correct || '(Belum Ditentukan)')}</strong>
                </div>
            `;
        }

        return '<em class="text-muted">Format khusus</em>';
    }

    // Set correct answer key in real-time
    window.setQuestionKey = function(tempId, letter) {
        const q = currentQuestions.find(item => item.temp_id === tempId);
        if (!q) return;

        q.answer_data.correct = letter;

        // Clear warning about missing key
        if (q.warnings) {
            q.warnings = q.warnings.filter(w => !w.toLowerCase().includes('kunci'));
        }
        q.is_valid = (!q.warnings || q.warnings.length === 0);

        // Update UI option boxes styling
        ['A', 'B', 'C', 'D', 'E'].forEach(l => {
            const box = document.getElementById(`opt_box_${tempId}_${l}`);
            const badge = document.getElementById(`badge_letter_${tempId}_${l}`);
            if (box && badge) {
                if (l === letter) {
                    box.className = 'form-check d-flex align-items-start p-3 rounded border mb-0 cursor-pointer border-success bg-success bg-opacity-10';
                    badge.className = 'badge bg-success me-2 fs-6 px-2 py-1';
                } else {
                    box.className = 'form-check d-flex align-items-start p-3 rounded border mb-0 cursor-pointer bg-white';
                    badge.className = 'badge bg-secondary me-2 fs-6 px-2 py-1';
                }
            }
        });

        // Update card header & status badge
        const card = document.getElementById(`card_${tempId}`);
        const statusBadge = document.getElementById(`status_badge_${tempId}`);
        const warningBox = document.getElementById(`warning_box_${tempId}`);

        if (card && statusBadge) {
            if (q.is_valid) {
                card.className = card.className.replace('border-warning', 'border-success');
                card.dataset.warning = '0';
                statusBadge.innerHTML = `<span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Siap Diimport</span>`;
                if (warningBox) warningBox.classList.add('d-none');
            } else {
                statusBadge.innerHTML = `<span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perlu Periksa</span>`;
            }
        }

        updateStatsSummary();
    };

    // Toggle single question selection
    window.toggleQuestionSelect = function(tempId, isChecked) {
        const q = currentQuestions.find(item => item.temp_id === tempId);
        if (q) {
            q.is_selected = isChecked;
        }
        updateStatsSummary();
    };

    // Toggle select all
    window.toggleSelectAll = function(isChecked) {
        currentQuestions.forEach(q => {
            q.is_selected = isChecked;
            const chk = document.getElementById(`chk_${q.temp_id}`);
            if (chk) chk.checked = isChecked;
        });
        updateStatsSummary();
    };

    // Filter preview questions
    window.filterPreview = function(filter) {
        activeFilter = filter;
        document.getElementById('filterAllBtn').className = `btn btn-outline-primary ${filter === 'all' ? 'active' : ''}`;
        document.getElementById('filterWarningBtn').className = `btn btn-outline-warning text-dark ${filter === 'warning' ? 'active' : ''}`;
        document.getElementById('filterValidBtn').className = `btn btn-outline-success ${filter === 'valid' ? 'active' : ''}`;

        currentQuestions.forEach(q => {
            const card = document.getElementById(`card_${q.temp_id}`);
            if (!card) return;
            const isWarning = (card.dataset.warning === '1');

            if (filter === 'all') {
                card.classList.remove('d-none');
            } else if (filter === 'warning') {
                if (isWarning) card.classList.remove('d-none');
                else card.classList.add('d-none');
            } else if (filter === 'valid') {
                if (!isWarning) card.classList.remove('d-none');
                else card.classList.add('d-none');
            }
        });
    };

    // Update statistical counters
    function updateStatsSummary() {
        const total = currentQuestions.length;
        const valid = currentQuestions.filter(q => q.is_valid).length;
        const warning = total - valid;
        const refCount = Object.keys(currentReferences).length;
        const selected = currentQuestions.filter(q => q.is_selected).length;

        document.getElementById('txtTotalSoal').textContent = total;
        document.getElementById('txtValidSoal').textContent = valid;
        document.getElementById('txtWarningSoal').textContent = warning;
        document.getElementById('txtRefSoal').textContent = refCount;

        document.getElementById('countFilterAll').textContent = total;
        document.getElementById('countFilterWarning').textContent = warning;
        document.getElementById('countFilterValid').textContent = valid;

        document.querySelectorAll('.selectedCountText').forEach(el => {
            el.textContent = selected;
        });

        // Update select all checkbox state
        const selectAllChk = document.getElementById('selectAllCheckbox');
        if (selectAllChk) {
            selectAllChk.checked = (selected === total && total > 0);
            selectAllChk.indeterminate = (selected > 0 && selected < total);
        }
    }

    // Cancel preview and return to upload view
    window.cancelPreview = function() {
        if (confirm('Kembali ke tampilan upload file? Perubahan preview saat ini tidak akan disimpan.')) {
            document.getElementById('previewViewSection').classList.add('d-none');
            document.getElementById('uploadViewSection').classList.remove('d-none');
            document.getElementById('wordFile').value = '';
            document.getElementById('wordFileBase64').value = '';
            document.getElementById('fileInfo').classList.add('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // Submit confirmed questions to database
    window.submitConfirmedQuestions = function() {
        const selectedQuestions = currentQuestions.filter(q => q.is_selected);

        if (selectedQuestions.length === 0) {
            alert('Silakan pilih minimal 1 soal yang ingin diimport.');
            return;
        }

        // Check if any selected question has missing key
        const questionMissingKey = selectedQuestions.find(q => q.question_type === 'pg' && (!q.answer_data.correct || q.answer_data.correct === ''));
        if (questionMissingKey) {
            if (!confirm(`Soal #${questionMissingKey.number} belum memiliki kunci jawaban. Apakah Anda yakin ingin tetap menyimpannya? Soal tanpa kunci jawaban tidak dapat dinilai secara otomatis.`)) {
                // Scroll to this question
                const card = document.getElementById(`card_${questionMissingKey.temp_id}`);
                if (card) {
                    card.classList.remove('d-none');
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
        }

        if (!confirm(`Konfirmasi: Simpan ${selectedQuestions.length} soal ke Bank Soal sekarang?`)) {
            return;
        }

        // Show spinner on buttons
        const btn = document.getElementById('btnConfirmSave');
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan Soal...';

        const xhrSave = new XMLHttpRequest();
        xhrSave.open('POST', 'modules/questions/import_process.php', true);

        xhrSave.onreadystatechange = function() {
            if (xhrSave.readyState === 4) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;

                let res = null;
                try {
                    res = JSON.parse(xhrSave.responseText);
                } catch (e) {}

                if (xhrSave.status === 200 && res && res.status === 'success') {
                    window.location.href = `dashboard.php?page=questions_import&subject_id=<?= $subject_id ?>&status=success&count=${res.count}&ref_count=${res.ref_count || 0}`;
                } else if (res && res.msg) {
                    alert('Gagal menyimpan: ' + res.msg);
                } else if (xhrSave.status === 404) {
                    alert('Error 404 Not Found: Server web tidak menemukan endpoint atau menolak ukuran payload. Pastikan file script terbaru telah di-upload ke server.');
                    console.error('Server 404 response:', xhrSave.responseText);
                } else {
                    let errMsg = 'Gagal menghubungi server (Status: ' + xhrSave.status + ').';
                    if (xhrSave.responseText) {
                        errMsg += '\n\nRespon server:\n' + xhrSave.responseText.substring(0, 300);
                    }
                    alert(errMsg);
                    console.error('Server error response:', xhrSave.responseText);
                }
            }
        };

        const formData = new FormData();
        formData.append('action', 'confirm_save');
        formData.append('subject_id', '<?= $subject_id ?>');
        formData.append('questions_json', JSON.stringify(selectedQuestions));
        formData.append('references_json', JSON.stringify(currentReferences));

        xhrSave.send(formData);
    };

    // Helper: Trigger MathJax typeset safely
    function triggerMathTypeset() {
        setTimeout(function() {
            if (window.triggerMathTypeset) {
                window.triggerMathTypeset(document.getElementById('previewViewSection'));
            } else if (window.MathJax && window.MathJax.typesetPromise) {
                window.MathJax.typesetPromise([document.getElementById('previewViewSection')]).catch(function(err) {
                    console.warn('MathJax preview typeset:', err);
                });
            }
        }, 150);
    }

    // Helper: Escape HTML strings
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Copy prompt helper for AI instructions
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