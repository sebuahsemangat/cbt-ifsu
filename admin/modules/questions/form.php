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
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>

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
                        <div id="editor" style="height: 300px;"><?= $data['question_text'] ?? '' ?></div>
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
// Inisialisasi Quill
var quill = new Quill('#editor', { 
    modules: {
        imageResize: {
            displaySize: true
        },
        toolbar: [
          [{ header: [1, 2, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          [{ 'indent': '-1'}, { 'indent': '+1' }],
          ['image'],
        ],
    },
    theme: 'snow' 
});

function renderOptions() {
    const type = document.getElementById('question_type').value;
    const container = document.getElementById('optionsContainer');
    container.innerHTML = '';

    if (type === 'pg') {
        container.innerHTML = '<label class="form-label fw-bold">Opsi Jawaban & Kunci</label>';
        ['A', 'B', 'C', 'D', 'E'].forEach(let => {
            container.innerHTML += `
                <div class="input-group mb-2">
                    <div class="input-group-text"><input type="radio" name="correct_ans" value="${let}" required> &nbsp; ${let}</div>
                    <input type="text" name="options[${let}]" class="form-control" placeholder="Teks opsi ${let}" required>
                </div>`;
        });
    } else if (type === 'bs') {
        container.innerHTML = `
            <label class="form-label fw-bold">Pernyataan Benar/Salah</label>
            <div id="bs-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addBSRow()">+ Tambah Pernyataan</button>`;
        addBSRow();
    } else if (type === 'multi') {
        container.innerHTML = `
            <label class="form-label fw-bold">Pernyataan (Ceklis yang Benar)</label>
            <div id="multi-rows"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addMultiRow()">+ Tambah Opsi</button>`;
        addMultiRow();
    }
}

function addBSRow() {
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="statements[]" class="form-control" placeholder="Pernyataan..." required>
        <select name="statement_ans[]" class="form-select" style="max-width: 100px;">
            <option value="1">Benar</option>
            <option value="0">Salah</option>
        </select>
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
    document.getElementById('bs-rows').appendChild(div);
}

function addMultiRow() {
    // Hitung jumlah baris yang sudah ada untuk menentukan index
    const index = document.querySelectorAll('#multi-rows .input-group').length;

    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <div class="input-group-text">
            <input type="checkbox" name="multi_correct[]" value="${index}">
        </div>
        <input type="text" name="multi_options[]" class="form-control" placeholder="Pernyataan..." required>
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
    document.getElementById('multi-rows').appendChild(div);
}

// Sinkronisasi Quill ke Hidden Input saat Submit
document.getElementById('questionForm').onsubmit = function() {
    document.getElementById('quill_html').value = quill.root.innerHTML;
};

// Jalankan saat load
renderOptions();
</script>