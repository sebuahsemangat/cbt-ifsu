<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Wajib ada subject_id
$subject_id = $_GET['subject_id'] ?? null;
if (!$subject_id) {
    header("Location: dashboard.php?page=exams"); // Tendang balik jika tidak ada ID
    exit;
}

// Ambil detail mapel untuk judul halaman
$stmtSub = $pdo->prepare("SELECT s.*, e.exam_name FROM subjects s JOIN exams e ON s.exam_id = e.id WHERE s.id = ?");
$stmtSub->execute([$subject_id]);
$subject = $stmtSub->fetch();

if (!$subject) {
    echo "Mata pelajaran tidak ditemukan.";
    exit;
}

// Ambil daftar Referensi untuk mapel ini untuk keperluan dropdown bulk & preview
$stmtRef = $pdo->prepare("SELECT id, title, type, content, image_path FROM `references` WHERE subject_id = ? ORDER BY title ASC");
$stmtRef->execute([$subject_id]);
$availableReferences = $stmtRef->fetchAll();

// Ambil data soal JOIN dengan References untuk mendapatkan judul referensi
$query = "SELECT q.*, r.title as ref_title, r.type as ref_type 
          FROM questions q 
          LEFT JOIN `references` r ON q.reference_id = r.id 
          WHERE q.subject_id = ? 
          ORDER BY q.id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$subject_id]);
$questions = $stmt->fetchAll();
?>

<style>
    /* Styling Floating Bulk Action Bar */
    .bulk-action-bar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(120px);
        background: #1e293b;
        color: #fff;
        border-radius: 16px;
        padding: 12px 24px;
        z-index: 1050;
        min-width: 320px;
        max-width: 900px;
        width: calc(100% - 40px);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
        opacity: 0;
        pointer-events: none;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .bulk-action-bar.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .question-row.row-selected {
        background-color: rgba(58, 121, 122, 0.08) !important;
    }

    .row-highlight-success {
        animation: highlightAnim 2s ease;
    }

    @keyframes highlightAnim {
        0% {
            background-color: rgba(40, 167, 69, 0.35);
        }

        100% {
            background-color: transparent;
        }
    }

    .form-check-input {
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .bulk-action-bar {
            bottom: 12px;
            padding: 12px 16px;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="dashboard.php?page=exams">Ujian</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($subject['exam_name']) ?></li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0">Soal: <?= htmlspecialchars($subject['name']) ?></h3>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!empty($questions)): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                data-bs-target="#rangeSelectModal" title="Pilih rentang nomor soal">
                <i class="bi bi-ui-checks"></i> Pilih Rentang No
            </button>
        <?php endif; ?>
        <a href="dashboard.php?page=references_form&subject_id=<?= $subject_id ?>" class="btn btn-outline-info">
            <i class="bi bi-file-earmark-text"></i> Tambah Referensi
        </a>
        <a href="dashboard.php?page=questions_import&subject_id=<?= $subject_id ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-word"></i> Import dari Word
        </a>
        <a href="dashboard.php?page=questions_form&subject_id=<?= $subject_id ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Soal
        </a>
    </div>
</div>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Data soal berhasil disimpan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'ref_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Referensi baru berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Data soal berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'bulk_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Berhasil memperbarui referensi untuk
            <?= htmlspecialchars($_GET['count'] ?? '') ?> soal.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i><?= htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan.') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="selectAllQuestions" class="form-check-input"
                                title="Pilih Semua Soal">
                        </th>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 22%;">Referensi (Stimulus)</th>
                        <th>Pertanyaan</th>
                        <th style="width: 15%;">Kunci Jawaban</th>
                        <th style="width: 150px;" class="text-center">Tipe</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x display-4"></i>
                                <p class="mt-2">Belum ada soal untuk mata pelajaran ini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($questions as $index => $q): ?>
                            <tr id="question-row-<?= $q['id'] ?>" class="question-row" data-id="<?= $q['id'] ?>"
                                data-index="<?= $index + 1 ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input question-checkbox" value="<?= $q['id'] ?>"
                                        data-index="<?= $index + 1 ?>">
                                </td>
                                <td class="text-center fw-medium text-muted"><?= $index + 1 ?></td>

                                <td id="ref-cell-<?= $q['id'] ?>">
                                    <?php if ($q['reference_id']): ?>
                                        <span class="badge bg-info text-dark mb-1">
                                            <i class="bi bi-link-45deg"></i> <?= strtoupper($q['ref_type'] ?? 'REF') ?>
                                        </span>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;"
                                            title="<?= htmlspecialchars($q['ref_title'] ?? '') ?>">
                                            <?= htmlspecialchars($q['ref_title'] ?? '-') ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">- Tidak ada -</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="text-truncate-2" style="max-height: 3em; overflow: hidden;">
                                        <?= strip_tags($q['question_text']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="small fw-bold">
                                        <?php
                                        $ansData = json_decode($q['answer_data'], true);
                                        if ($q['question_type'] == 'pg') {
                                            echo "Kunci: <span class='text-primary'>" . ($ansData['correct'] ?? '-') . "</span>";
                                        } elseif ($q['question_type'] == 'bs') {
                                            $items = isset($ansData['items']) ? $ansData['items'] : (is_array($ansData) ? $ansData : []);
                                            $labels = $ansData['labels'] ?? ['pos' => 'Benar', 'neg' => 'Salah'];
                                            $posLabel = ($labels['pos'] == 'Benar') ? 'B' : $labels['pos'];
                                            $negLabel = ($labels['neg'] == 'Salah') ? 'S' : $labels['neg'];

                                            echo "<ol class='ps-3 mb-0 text-warning' style='font-size: 0.85rem;'>";
                                            foreach ($items as $i => $item) {
                                                $shortLabel = (($item['is_correct'] ?? 0) == 1) ? $posLabel : $negLabel;
                                                echo "<li>" . $shortLabel . "</li>";
                                            }
                                            echo "</ol>";
                                        } elseif ($q['question_type'] == 'multi') {
                                            echo "<ol class='ps-3 mb-0 text-success'>";
                                            foreach ($ansData as $item) {
                                                if (($item['is_correct'] ?? 0) == 1) {
                                                    echo "<li>" . strip_tags($item['text']) . "</li>";
                                                }
                                            }
                                            echo "</ol>";
                                        } elseif ($q['question_type'] == 'isian') {
                                            echo "<span class='text-info'>" . htmlspecialchars($ansData['correct'] ?? '-') . "</span>";
                                        }
                                        ?>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <?php
                                    $badges = [
                                        'pg' => ['bg' => 'primary', 'label' => 'Pilihan Ganda'],
                                        'bs' => ['bg' => 'warning', 'label' => 'Benar / Salah'],
                                        'multi' => ['bg' => 'info', 'label' => 'Ceklis (Multi)'],
                                        'isian' => ['bg' => 'success', 'label' => 'Isian Singkat'],
                                    ];
                                    $type = $q['question_type'];
                                    $badge = $badges[$type] ?? ['bg' => 'secondary', 'label' => $type];
                                    ?>
                                    <span class="badge bg-<?= $badge['bg'] == 'purple' ? 'dark' : $badge['bg'] ?>">
                                        <?= $badge['label'] ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="dashboard.php?page=questions_form&subject_id=<?= $subject_id ?>&id=<?= $q['id'] ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit Soal">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="modules/questions/process.php?action=delete&id=<?= $q['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus soal ini?')" title="Hapus Soal">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Floating Bulk Action Bar -->
<div id="bulkActionBar" class="bulk-action-bar">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">
                <i class="bi bi-check2-circle me-1"></i> <span id="selectedCount">0</span> Soal Dipilih
            </span>
            <button type="button" class="btn btn-link text-decoration-none text-light p-0 ms-2 small opacity-75"
                id="btnDeselectAll">
                Batal
            </button>
        </div>
        <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width: 600px;">
            <select id="bulkReferenceSelect" class="form-select form-select-sm" style="max-width: 320px;">
                <option value="">-- Pilih Referensi (Stimulus) --</option>
                <option value="null" class="text-danger fw-bold">❌ Kosongkan / Lepas Referensi</option>
                <?php if (!empty($availableReferences)): ?>
                    <optgroup label="Referensi Tersedia (<?= count($availableReferences) ?>)">
                        <?php foreach ($availableReferences as $ref): ?>
                            <option value="<?= $ref['id'] ?>">
                                [<?= strtoupper($ref['type']) ?>] <?= htmlspecialchars($ref['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
            <button type="button" id="btnPreviewRef" class="btn btn-outline-light btn-sm" title="Pratinjau Referensi"
                style="display: none;">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" id="btnApplyBulkRef" class="btn btn-success btn-sm px-3 fw-semibold">
                <i class="bi bi-check-lg me-1"></i> Terapkan
            </button>
        </div>
    </div>
</div>

<!-- Modal Quick Range Selector -->
<div class="modal fade" id="rangeSelectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-ui-checks me-1 text-primary"></i> Pilih Rentang Nomor
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Centang banyak soal sekaligus secara berurutan sesuai nomor di tabel.
                </p>
                <div class="row g-2 align-items-center mb-2">
                    <div class="col">
                        <label class="form-label small fw-bold mb-1">Dari No</label>
                        <input type="number" id="rangeStart" class="form-control form-control-sm text-center fw-bold"
                            min="1" max="<?= count($questions) ?>" value="1">
                    </div>
                    <div class="col-auto pt-3 text-muted fw-bold">s/d</div>
                    <div class="col">
                        <label class="form-label small fw-bold mb-1">Sampai No</label>
                        <input type="number" id="rangeEnd" class="form-control form-control-sm text-center fw-bold"
                            min="1" max="<?= count($questions) ?>" value="<?= min(5, count($questions)) ?>">
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Total ada <strong><?= count($questions) ?></strong> soal.</small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnApplyRange" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-check2"></i> Pilih Soal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Referensi -->
<div class="modal fade" id="previewRefModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="previewRefTitle">Pratinjau Referensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="previewRefBody" style="max-height: 65vh; overflow-y: auto;">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifikasi AJAX -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <div id="bulkToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5" id="toastIcon"></i>
                <span id="toastMessage">Operasi berhasil dilakukan.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    // Data referensi untuk keperluan preview instan
    const AVAILABLE_REFERENCES = <?= json_encode($availableReferences, JSON_UNESCAPED_UNICODE) ?>;
    const CURRENT_SUBJECT_ID = <?= (int) $subject_id ?>;

    let lastChecked = null;

    // Helper Toast
    function showToast(message, isSuccess = true) {
        const toastEl = document.getElementById('bulkToast');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');

        toastMsg.textContent = message;
        if (isSuccess) {
            toastEl.className = 'toast align-items-center text-white bg-success border-0 shadow-lg';
            toastIcon.className = 'bi bi-check-circle-fill fs-5';
        } else {
            toastEl.className = 'toast align-items-center text-white bg-danger border-0 shadow-lg';
            toastIcon.className = 'bi bi-exclamation-triangle-fill fs-5';
        }

        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    }

    // Update Tampilan Floating Action Bar
    function updateBulkBar() {
        const checkboxes = document.querySelectorAll('.question-checkbox:checked');
        const count = checkboxes.length;
        const totalCount = document.querySelectorAll('.question-checkbox').length;
        const bar = document.getElementById('bulkActionBar');
        const countSpan = document.getElementById('selectedCount');
        const selectAllCheckbox = document.getElementById('selectAllQuestions');

        countSpan.textContent = count;

        if (count > 0) {
            bar.classList.add('show');
        } else {
            bar.classList.remove('show');
        }

        // Sinkronisasi status master checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (count === totalCount && totalCount > 0);
            selectAllCheckbox.indeterminate = (count > 0 && count < totalCount);
        }

        // Highlight visual pada baris yang dipilih
        document.querySelectorAll('.question-row').forEach(row => {
            const cb = row.querySelector('.question-checkbox');
            if (cb && cb.checked) {
                row.classList.add('row-selected');
            } else {
                row.classList.remove('row-selected');
            }
        });
    }

    // Inisialisasi Event Listener
    document.addEventListener('DOMContentLoaded', function () {
        const allCheckboxes = Array.from(document.querySelectorAll('.question-checkbox'));
        const selectAllCb = document.getElementById('selectAllQuestions');
        const btnDeselectAll = document.getElementById('btnDeselectAll');
        const refSelect = document.getElementById('bulkReferenceSelect');
        const btnPreviewRef = document.getElementById('btnPreviewRef');
        const btnApplyBulkRef = document.getElementById('btnApplyBulkRef');
        const btnApplyRange = document.getElementById('btnApplyRange');

        // 1. Shift + Click Range Selection
        allCheckboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('click', function (e) {
                if (e.shiftKey && lastChecked !== null) {
                    const start = Math.min(allCheckboxes.indexOf(lastChecked), allCheckboxes.indexOf(this));
                    const end = Math.max(allCheckboxes.indexOf(lastChecked), allCheckboxes.indexOf(this));

                    for (let i = start; i <= end; i++) {
                        allCheckboxes[i].checked = lastChecked.checked;
                    }
                }
                lastChecked = this;
                updateBulkBar();
            });
        });

        // 2. Select All Master Checkbox
        if (selectAllCb) {
            selectAllCb.addEventListener('change', function () {
                const isChecked = this.checked;
                allCheckboxes.forEach(cb => {
                    cb.checked = isChecked;
                });
                updateBulkBar();
            });
        }

        // 3. Tombol Batal Pilih
        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', function () {
                allCheckboxes.forEach(cb => cb.checked = false);
                lastChecked = null;
                updateBulkBar();
            });
        }

        // 4. Quick Range Selector Modal
        if (btnApplyRange) {
            btnApplyRange.addEventListener('click', function () {
                const start = parseInt(document.getElementById('rangeStart').value, 10);
                const end = parseInt(document.getElementById('rangeEnd').value, 10);

                if (isNaN(start) || isNaN(end) || start < 1 || end < start) {
                    alert('Rentang nomor tidak valid. Pastikan nomor awal tidak lebih besar dari nomor akhir.');
                    return;
                }

                allCheckboxes.forEach(cb => {
                    const idx = parseInt(cb.getAttribute('data-index'), 10);
                    if (idx >= start && idx <= end) {
                        cb.checked = true;
                    }
                });

                // Tutup modal
                const modalEl = document.getElementById('rangeSelectModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                updateBulkBar();
                showToast(`Memilih soal No ${start} sampai ${end}`);
            });
        }

        // 5. Preview Referensi
        if (refSelect && btnPreviewRef) {
            refSelect.addEventListener('change', function () {
                const val = this.value;
                if (val && val !== 'null') {
                    btnPreviewRef.style.display = 'inline-block';
                } else {
                    btnPreviewRef.style.display = 'none';
                }
            });

            btnPreviewRef.addEventListener('click', function () {
                const refId = parseInt(refSelect.value, 10);
                const refData = AVAILABLE_REFERENCES.find(r => parseInt(r.id, 10) === refId);

                if (!refData) return;

                document.getElementById('previewRefTitle').innerHTML = `
                    <span class="badge bg-primary me-2">${refData.type.toUpperCase()}</span>
                    ${refData.title}
                `;

                let bodyHtml = '';
                if (refData.type === 'text') {
                    bodyHtml = `<div class="p-3 bg-light rounded">${refData.content || '<em class="text-muted">Konten teks kosong</em>'}</div>`;
                } else if (refData.type === 'image') {
                    bodyHtml = `
                        <div class="text-center">
                            <img src="../assets/uploads/references/${refData.image_path}" class="img-fluid rounded shadow-sm" alt="${refData.title}">
                        </div>
                    `;
                } else {
                    bodyHtml = `<div class="p-3 bg-light rounded">${refData.content || ''}</div>`;
                }

                document.getElementById('previewRefBody').innerHTML = bodyHtml;
                const modal = new bootstrap.Modal(document.getElementById('previewRefModal'));
                modal.show();
            });
        }

        // 6. Eksekusi Terapkan Referensi Massal via AJAX
        if (btnApplyBulkRef) {
            btnApplyBulkRef.addEventListener('click', async function () {
                const checkedBoxes = Array.from(document.querySelectorAll('.question-checkbox:checked'));
                if (checkedBoxes.length === 0) {
                    alert('Silakan pilih minimal satu soal.');
                    return;
                }

                const refVal = refSelect.value;
                if (refVal === '') {
                    alert('Silakan pilih referensi yang ingin diterapkan, atau pilih opsi "Kosongkan / Lepas Referensi".');
                    refSelect.focus();
                    return;
                }

                const questionIds = checkedBoxes.map(cb => parseInt(cb.value, 10));
                const isClearRef = (refVal === 'null');

                // Konfirmasi jika aksi lepas referensi
                if (isClearRef && !confirm(`Yakin ingin melepas referensi dari ${questionIds.length} soal terpilih?`)) {
                    return;
                }

                // Loading State
                const originalBtnHtml = btnApplyBulkRef.innerHTML;
                btnApplyBulkRef.disabled = true;
                btnApplyBulkRef.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...`;

                try {
                    const response = await fetch('modules/questions/process.php?action=bulk_reference', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            subject_id: CURRENT_SUBJECT_ID,
                            reference_id: isClearRef ? null : parseInt(refVal, 10),
                            question_ids: questionIds
                        })
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Gagal memproses perubahan referensi.');
                    }

                    // Update DOM secara instan tanpa reload halaman
                    questionIds.forEach(qid => {
                        const cell = document.getElementById(`ref-cell-${qid}`);
                        const row = document.getElementById(`question-row-${qid}`);

                        if (cell) {
                            if (result.reference_id && result.ref_title) {
                                cell.innerHTML = `
                                    <span class="badge bg-info text-dark mb-1">
                                        <i class="bi bi-link-45deg"></i> ${result.ref_type.toUpperCase()}
                                    </span>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;" title="${result.ref_title}">
                                        ${result.ref_title}
                                    </div>
                                `;
                            } else {
                                cell.innerHTML = `<span class="text-muted small">- Tidak ada -</span>`;
                            }
                        }

                        // Efek highlight hijau lembut
                        if (row) {
                            row.classList.add('row-highlight-success');
                            setTimeout(() => {
                                row.classList.remove('row-highlight-success');
                            }, 2000);
                        }
                    });

                    // Reset centang & sembunyikan bar
                    allCheckboxes.forEach(cb => cb.checked = false);
                    lastChecked = null;
                    refSelect.value = '';
                    if (btnPreviewRef) btnPreviewRef.style.display = 'none';
                    updateBulkBar();

                    showToast(result.message || 'Referensi berhasil diperbarui.');

                } catch (err) {
                    console.error('Error bulk reference:', err);
                    showToast(err.message || 'Terjadi kesalahan sistem.', false);
                } finally {
                    btnApplyBulkRef.disabled = false;
                    btnApplyBulkRef.innerHTML = originalBtnHtml;
                }
            });
        }

        if (window.MathJax && typeof window.MathJax.typesetPromise === 'function') {
            window.MathJax.typesetPromise().catch(err => console.warn(err));
        }
    });
</script>