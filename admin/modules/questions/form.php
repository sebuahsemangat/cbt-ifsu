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

// Decode answer_data for JS usage
$answerData = [];
if ($data && !empty($data['answer_data'])) {
    $decoded = json_decode($data['answer_data'], true);
    if (is_array($decoded)) {
        $answerData = $decoded;
    }
}

// Proteksi jika data question_text terpotong di database (misal MySQL TEXT 64KB)
$questionText = $data['question_text'] ?? '';
if (preg_match('/<img[^>]*$/', $questionText)) {
    $questionText .= '" alt="Gambar terpotong" class="img-fluid" />';
}
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>

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
                        <label class="form-label fw-bold">Pertanyaan</label>
                        <div id="editor" style="height: 300px;"><?= $questionText ?></div>
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

// Main Editor
var quill = new Quill('#editor', quillConfig);
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
                    <div class="d-flex align-items-center mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="correct_ans" value="${let}" ${checked} required>
                            <label class="form-check-label fw-bold">${let}</label>
                        </div>
                    </div>
                    <div id="editor_pg_${let}" style="height: 100px;"></div>
                    <input type="hidden" name="options[${let}]" id="input_pg_${let}">
                </div>`;
            container.appendChild(div);
            
            const q = new Quill(`#editor_pg_${let}`, quillConfig);
            q.root.innerHTML = val;
            optionQuills[`input_pg_${let}`] = q;
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
             <div id="editor_${id}" style="height: 80px;"></div>
             <input type="hidden" name="statements[]" id="input_${id}">
        </div>`;
    document.getElementById('bs-rows').appendChild(div);
    
    const q = new Quill(`#editor_${id}`, quillConfig);
    q.root.innerHTML = text;
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
             <div id="editor_${id}" style="height: 80px;"></div>
             <input type="hidden" name="multi_options[]" id="input_${id}">
        </div>`;
    document.getElementById('multi-rows').appendChild(div);
     
    const q = new Quill(`#editor_${id}`, quillConfig);
    q.root.innerHTML = text;
    optionQuills[`input_${id}`] = q;
}

function removeRow(btn, id) {
    btn.closest('.card').remove();
    delete optionQuills[`input_${id}`];
}

document.getElementById('questionForm').onsubmit = function() {
    // Sync Main Question
    document.getElementById('quill_html').value = quill.root.innerHTML;

    // Sync Options
    for (let id in optionQuills) {
        document.getElementById(id).value = optionQuills[id].root.innerHTML;
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