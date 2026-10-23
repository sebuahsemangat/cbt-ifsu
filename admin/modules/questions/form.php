<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

$subject_id = $_GET['subject_id'] ?? null;
$id = $_GET['id'] ?? null;

if (!$subject_id) {
    header("Location: dashboard.php?page=exams");
    exit;
}

// Ambil data referensi (stimulus) untuk dropdown
$stmtRef = $pdo->prepare("SELECT id, title, LEFT(content, 50) as snippet FROM `references` WHERE subject_id = ?");
$stmtRef->execute([$subject_id]);
$references = $stmtRef->fetchAll();

// Jika mode EDIT, ambil data soal lama
$data = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
}

// Helper untuk memastikan gambar relatif uploads/questions/ muncul di editor admin
function fixAdminImagePaths($content) {
    if (is_array($content)) {
        foreach ($content as $k => $v) {
            $content[$k] = fixAdminImagePaths($v);
        }
        return $content;
    }
    if (!is_string($content) || empty($content)) return $content;
    return preg_replace('/src=["\']uploads\/questions\//i', 'src="../uploads/questions/', $content);
}

// Decode answer_data for JS usage
$answerData = [];
if ($data && !empty($data['answer_data'])) {
    $decoded = json_decode($data['answer_data'], true);
    if (is_array($decoded)) {
        $answerData = fixAdminImagePaths($decoded);
    }
}

// Proteksi jika data question_text terpotong di database (misal MySQL TEXT 64KB)
$questionText = $data['question_text'] ?? '';
if (preg_match('/<img[^>]*$/', $questionText)) {
    $questionText .= '" alt="Gambar terpotong" class="img-fluid" />';
}
$questionText = fixAdminImagePaths($questionText);
?>

<link href="../assets/vendor/quill/quill.1.3.6.snow.css" rel="stylesheet">
<script src="../assets/vendor/quill/quill.1.3.6.min.js"></script>
<script src="../assets/vendor/quill/image-resize.min.js"></script>

<style>
    /* Cegah gambar besar merusak layout flexbox grid Bootstrap */
    #editor img, .ql-editor img {
        max-width: 100% !important;
        height: auto !important;
    }
    .ql-editor {
        min-height: 200px;
        max-height: 500px;
        overflow-y: auto;
    }
    .col-md-7, .col-md-5 {
        min-width: 0; /* Mengatasi flexbox auto min-width overflow */
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><?= $id ? 'Edit' : 'Tambah' ?> Soal</h3>
    <a href="dashboard.php?page=questions&subject_id=<?= $subject_id ?>" class="btn btn-secondary">Kembali</a>
</div>

<form action="modules/questions/process.php?action=save" method="POST" id="questionForm">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
    <input type="hidden" name="question_text" id="quill_html">

    <div class="row">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Referensi (Opsional)</label>
                        <select name="reference_id" class="form-select">
                            <option value="">-- Tanpa Referensi --</option>
                            <?php foreach ($references as $ref): ?>
                                <option value="<?= $ref['id'] ?>" <?= ($data['reference_id'] ?? '') == $ref['id'] ? 'selected' : '' ?>>
                                    <?= $ref['title'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Pertanyaan</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleMathPreview('main')">
                                <i class="bi bi-eye me-1"></i>Pratinjau Rumus (MathJax)
                            </button>
                        </div>
                        <div id="editor" class="tex2jax_ignore" style="height: 300px;"></div>
                        <div id="mainMathPreview" class="p-3 bg-white rounded border border-primary mt-2 d-none shadow-sm">
                            <div class="fw-bold text-primary small mb-1"><i class="bi bi-calculator me-1"></i>Pratinjau Tampilan Rumus Matematika Siswa:</div>
                            <div id="mainMathPreviewBody" class="fs-6 text-dark tex2jax_process"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Bentuk Soal</label>
                        <select name="question_type" id="question_type" class="form-select" onchange="renderOptions()" required>
                            <option value="pg" <?= ($data['question_type'] ?? '') == 'pg' ? 'selected' : '' ?>>Pilihan Ganda (A-E)</option>
                            <option value="bs" <?= ($data['question_type'] ?? '') == 'bs' ? 'selected' : '' ?>>Benar / Salah (Multi Pernyataan)</option>
                            <option value="multi" <?= ($data['question_type'] ?? '') == 'multi' ? 'selected' : '' ?>>Pernyataan Benar (Checkbox)</option>
                            <option value="isian" <?= ($data['question_type'] ?? '') == 'isian' ? 'selected' : '' ?>>Isian Singkat</option>
                        </select>
                    </div>

                    <div id="optionsContainer">
                        </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Simpan Soal</button>
        </div>
    </div>
</form>

<script>
// Data from PHP
const savedData = <?= json_encode($answerData) ?>;
const savedType = '<?= $data['question_type'] ?? 'pg' ?>';

// Config
const quillConfig = {
    modules: {
        imageResize: { displaySize: true },
        toolbar: [
          [{ header: [1, 2, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'script': 'sub'}, { 'script': 'super' }],
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          [{ 'indent': '-1'}, { 'indent': '+1' }],
          ['image'],
        ],
    },
    theme: 'snow' 
};

// Image upload handler untuk bypass OpenResty 403 Base64 overload
function selectLocalImage(targetQuill) {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/png, image/jpeg, image/jpg, image/webp');
    input.click();

    input.onchange = () => {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran gambar maksimal 5MB!');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const base64Data = e.target.result;
            const params = new URLSearchParams();
            params.append('image_base64', base64Data);

            fetch('modules/questions/upload_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.preview_url) {
                    const range = targetQuill.getSelection(true);
                    targetQuill.insertEmbed(range ? range.index : 0, 'image', res.preview_url);
                } else {
                    alert('Gagal mengupload gambar: ' + (res.message || 'Error'));
                }
            })
            .catch(err => {
                alert('Terjadi kesalahan koneksi saat mengupload gambar.');
                console.error(err);
            });
        };
        reader.readAsDataURL(file);
    };
}

// Helper aman untuk set HTML ke Quill tanpa merusak inline formatting (subscript/superscript)
function safeSetQuillHtml(targetQuill, html) {
    if (!targetQuill) return;
    if (!html || typeof html !== 'string') {
        targetQuill.setText('');
        return;
    }
    targetQuill.clipboard.dangerouslyPasteHTML(html);
}

// Helper render visual rumus menggunakan MathJax
function renderLiveMathJax(containerEl, html) {
    if (!containerEl) return;
    containerEl.innerHTML = html || '<span class="text-muted fst-italic">(kosong)</span>';
    if (window.MathJax && typeof window.MathJax.typesetPromise === 'function') {
        try {
            if (typeof window.MathJax.typesetClear === 'function') {
                window.MathJax.typesetClear([containerEl]);
            }
        } catch (e) {}
        window.MathJax.typesetPromise([containerEl]).catch(err => console.warn('MathJax preview:', err));
    }
}

function toggleMathPreview(target) {
    if (target === 'main') {
        const box = document.getElementById('mainMathPreview');
        const body = document.getElementById('mainMathPreviewBody');
        if (box.classList.contains('d-none')) {
            box.classList.remove('d-none');
            renderLiveMathJax(body, quill.root.innerHTML);
        } else {
            box.classList.add('d-none');
        }
    }
}

function toggleOptionPreview(letter) {
    const box = document.getElementById(`preview_pg_${letter}`);
    if (!box) return;
    const target = box.querySelector('.math-preview-target');
    const q = optionQuills[`input_pg_${letter}`];
    if (!q) return;

    if (box.classList.contains('d-none')) {
        box.classList.remove('d-none');
        renderLiveMathJax(target, q.root.innerHTML);
    } else {
        box.classList.add('d-none');
    }
}

// Main Editor
var quill = new Quill('#editor', quillConfig);
quill.getModule('toolbar').addHandler('image', function() {
    selectLocalImage(quill);
});
<?php if (!empty($questionText)): ?>
safeSetQuillHtml(quill, <?= json_encode($questionText) ?>);
<?php endif; ?>

// Auto-buka preview jika ada rumus matematika pada pertanyaan utama
quill.on('text-change', () => {
    const box = document.getElementById('mainMathPreview');
    if (box && !box.classList.contains('d-none')) {
        renderLiveMathJax(document.getElementById('mainMathPreviewBody'), quill.root.innerHTML);
    }
});
<?php if (!empty($questionText) && (strpos($questionText, '\\frac') !== false || strpos($questionText, '\\sqrt') !== false || strpos($questionText, '\\(') !== false || strpos($questionText, '<sub') !== false || strpos($questionText, '<sup') !== false)): ?>
setTimeout(() => {
    const box = document.getElementById('mainMathPreview');
    if (box) {
        box.classList.remove('d-none');
        renderLiveMathJax(document.getElementById('mainMathPreviewBody'), quill.root.innerHTML);
    }
}, 300);
<?php endif; ?>

var optionQuills = {};

function renderOptions() {
    const type = document.getElementById('question_type').value;
    const container = document.getElementById('optionsContainer');
    container.innerHTML = '';
    optionQuills = {}; // Reset

    if (type === 'pg') {
        container.innerHTML = '<label class="form-label fw-bold">Opsi Jawaban & Kunci</label>';
        ['A', 'B', 'C', 'D', 'E'].forEach(let => {
            const val = (savedType === 'pg' && savedData && savedData.options && savedData.options[let]) ? savedData.options[let] : '';
            const checked = (savedType === 'pg' && savedData && savedData.correct === let) ? 'checked' : '';
            
            const div = document.createElement('div');
            div.className = 'card mb-3 shadow-sm';
            div.innerHTML = `
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="correct_ans" value="${let}" ${checked} required>
                            <label class="form-check-label fw-bold">${let}</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 text-muted" onclick="toggleOptionPreview('${let}')">
                            <i class="bi bi-eye me-1"></i>Pratinjau Rumus
                        </button>
                    </div>
                    <div id="editor_pg_${let}" class="tex2jax_ignore" style="height: 100px;"></div>
                    <div id="preview_pg_${let}" class="p-2 bg-light rounded border mt-2 small d-none">
                        <span class="text-primary small fw-bold d-block mb-1"><i class="bi bi-calculator me-1"></i>Tampilan Visual Siswa:</span>
                        <div class="math-preview-target tex2jax_process fw-semibold text-dark"></div>
                    </div>
                    <input type="hidden" name="options[${let}]" id="input_pg_${let}">
                </div>`;
            container.appendChild(div);
            
            const q = new Quill(`#editor_pg_${let}`, quillConfig);
            q.getModule('toolbar').addHandler('image', function() { selectLocalImage(q); });
            safeSetQuillHtml(q, val);
            optionQuills[`input_pg_${let}`] = q;

            // Live updates for option preview
            q.on('text-change', () => {
                const box = document.getElementById(`preview_pg_${let}`);
                if (box && !box.classList.contains('d-none')) {
                    renderLiveMathJax(box.querySelector('.math-preview-target'), q.root.innerHTML);
                }
            });

            // Otomatis tampilkan preview jika opsi mengandung rumus pecahan/akar atau sub/sup
            if (val && (val.includes('\\frac') || val.includes('\\sqrt') || val.includes('\\(') || val.includes('<sub>') || val.includes('<sup>'))) {
                setTimeout(() => {
                    const box = document.getElementById(`preview_pg_${let}`);
                    if (box) {
                        box.classList.remove('d-none');
                        renderLiveMathJax(box.querySelector('.math-preview-target'), val);
                    }
                }, 350);
            }
        });
    } else if (type === 'bs') {
        const posLabel = (savedType === 'bs' && savedData && savedData.labels && savedData.labels.pos) ? savedData.labels.pos : 'Benar';
        const negLabel = (savedType === 'bs' && savedData && savedData.labels && savedData.labels.neg) ? savedData.labels.neg : 'Salah';
        
        container.innerHTML = `
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold">Label Positif (B)</label>
                    <input type="text" name="bs_label_pos" class="form-control form-control-sm" value="${posLabel}" placeholder="Contoh: Benar, Sesuai, Mendukung" required onkeyup="updateBSLabels()">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Label Negatif (S)</label>
                    <input type="text" name="bs_label_neg" class="form-control form-control-sm" value="${negLabel}" placeholder="Contoh: Salah, Tidak Sesuai" required onkeyup="updateBSLabels()">
                </div>
            </div>
            <label class="form-label fw-bold">Pernyataan & Kunci</label>
            <div id="bs-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addBSRow()">+ Tambah Pernyataan</button>`;
        
        if (savedType === 'bs') {
            const items = Array.isArray(savedData) ? savedData : ((savedData && Array.isArray(savedData.items)) ? savedData.items : []);
            if (items.length > 0) {
                items.forEach(item => addBSRow(item.text, item.is_correct));
            } else {
                addBSRow();
            }
        } else {
            addBSRow();
        }
    } else if (type === 'multi') {
        container.innerHTML = `
            <label class="form-label fw-bold">Pernyataan (Ceklis yang Benar)</label>
            <div id="multi-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addMultiRow()">+ Tambah Opsi</button>`;
        
        if (savedType === 'multi' && Array.isArray(savedData)) {
            savedData.forEach(item => addMultiRow(item.text, item.is_correct));
        } else {
            addMultiRow();
        }
    } else if (type === 'isian') {
        const val = (savedType === 'isian' && savedData && savedData.correct) ? savedData.correct : '';
        container.innerHTML = `
            <label class="form-label fw-bold">Kunci Jawaban Singkat</label>
            <div class="card shadow-sm">
                <div class="card-body">
                    <input type="text" name="isian_correct" class="form-control" value="${val}" placeholder="Masukkan jawaban yang benar" required>
                    <small class="text-muted mt-1 d-block font-italic">* Jawaban tidak sensitif huruf besar/kecil (case-insensitive)</small>
                </div>
            </div>`;
    }
}

function addBSRow(text = '', isCorrect = 1) {
    const id = 'bs_' + Math.random().toString(36).substr(2, 9);
    const posLabel = document.getElementsByName('bs_label_pos')[0]?.value || 'Benar';
    const negLabel = document.getElementsByName('bs_label_neg')[0]?.value || 'Salah';
    
    const div = document.createElement('div');
    div.className = 'card mb-2 shadow-sm bs-row'; 
    div.innerHTML = `
        <div class="card-body p-2">
             <div class="d-flex justify-content-between mb-2">
                <select name="statement_ans[]" class="form-select form-select-sm bs-select" style="width: 140px;">
                    <option value="1" ${isCorrect == 1 ? 'selected' : ''}>${posLabel}</option>
                    <option value="0" ${isCorrect == 0 ? 'selected' : ''}>${negLabel}</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this, '${id}')"><i class="bi bi-x"></i></button>
             </div>
             <div id="editor_${id}" class="tex2jax_ignore" style="height: 80px;"></div>
             <input type="hidden" name="statements[]" id="input_${id}">
        </div>`;
    document.getElementById('bs-rows').appendChild(div);
    
    const q = new Quill(`#editor_${id}`, quillConfig);
    q.getModule('toolbar').addHandler('image', function() { selectLocalImage(q); });
    safeSetQuillHtml(q, text);
    optionQuills[`input_${id}`] = q;
}

function updateBSLabels() {
    const posLabel = document.getElementsByName('bs_label_pos')[0].value || 'Benar';
    const negLabel = document.getElementsByName('bs_label_neg')[0].value || 'Salah';
    
    document.querySelectorAll('.bs-select').forEach(sel => {
        sel.options[0].text = posLabel;
        sel.options[1].text = negLabel;
    });
}

function addMultiRow(text = '', isCorrect = 0) { 
    const id = 'multi_' + Math.random().toString(36).substr(2, 9);
    const div = document.createElement('div');
    div.className = 'card mb-2 shadow-sm multi-row';
    div.innerHTML = `
        <div class="card-body p-2">
             <div class="d-flex justify-content-between mb-2">
                <div class="form-check">
                    <input class="form-check-input multi-check" type="checkbox" ${isCorrect == 1 ? 'checked' : ''}>
                    <label class="form-check-label">Benar</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this, '${id}')"><i class="bi bi-x"></i></button>
             </div>
             <div id="editor_${id}" class="tex2jax_ignore" style="height: 80px;"></div>
             <input type="hidden" name="multi_options[]" id="input_${id}">
        </div>`;
    document.getElementById('multi-rows').appendChild(div);
     
    const q = new Quill(`#editor_${id}`, quillConfig);
    q.getModule('toolbar').addHandler('image', function() { selectLocalImage(q); });
    safeSetQuillHtml(q, text);
    optionQuills[`input_${id}`] = q;
}

function removeRow(btn, id) {
    btn.closest('.card').remove();
    delete optionQuills[`input_${id}`];
}

function cleanHtmlForSave(html) {
    if (!html || typeof html !== 'string') return '';
    return html.replace(/\.\.\/uploads\/questions\//g, 'uploads/questions/');
}

document.getElementById('questionForm').onsubmit = function() {
    // Sync Main Question & normalisasi path gambar
    document.getElementById('quill_html').value = cleanHtmlForSave(quill.root.innerHTML);

    // Sync Options & normalisasi path gambar
    for (let id in optionQuills) {
        document.getElementById(id).value = cleanHtmlForSave(optionQuills[id].root.innerHTML);
    }

    // Handle Multi Checkboxes
    const type = document.getElementById('question_type').value;
    if (type === 'multi') {
        // Remove old hidden inputs for multi_correct to avoid duplicates if re-submitting 
        // (Use Array.from to avoid live collection issues during removal if modifying DOM, though querySelectorAll returns static NodeList usually? No, it's static.)
        document.querySelectorAll('input[name="multi_correct[]"]').forEach(el => el.remove());

        const rows = document.querySelectorAll('.multi-row');
        rows.forEach((row, index) => {
            const check = row.querySelector('.multi-check');
            if (check.checked) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'multi_correct[]';
                hidden.value = index;
                row.appendChild(hidden);
            }
        });
    }
};

// Start
try {
    renderOptions();
} catch (err) {
    console.error('Gagal me-render opsi jawaban:', err);
}
</script>