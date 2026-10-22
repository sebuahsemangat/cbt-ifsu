<?php
// Cek apakah mode Edit
$id = $_GET['id'] ?? null;
$data = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM `references` WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><?= $id ? 'Edit' : 'Tambah' ?> Referensi</h3>
    <a href="dashboard.php?page=references" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error_unique'): ?>
    <div class="alert alert-danger">Nama Referensi sudah digunakan! Gunakan nama lain.</div>
<?php endif; ?>
        <form action="modules/references/process.php?action=save" method="POST" id="referenceForm">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="image_base64" id="refImageBase64">
            
            <div class="mb-3">
                <label class="form-label">Mata Pelajaran</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($data['subject_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
        <label class="form-label fw-bold">Nama / Judul Referensi</label>
        <input type="text" name="title" class="form-control" 
               placeholder="Contoh: Bacaan Teks Proklamasi / Gambar Siklus Air" 
               value="<?= htmlspecialchars($data['title'] ?? '') ?>" required>
        <small class="text-muted">Nama ini yang akan muncul di dropdown saat input soal.</small>
    </div>     

            <div class="mb-3">
                <label class="form-label d-block">Tipe Referensi</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="typeText" value="text" 
                        <?= ($data['type'] ?? 'text') == 'text' ? 'checked' : '' ?> onclick="toggleType()">
                    <label class="form-check-label" for="typeText">Teks Bacaan</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="typeImage" value="image"
                        <?= ($data['type'] ?? '') == 'image' ? 'checked' : '' ?> onclick="toggleType()">
                    <label class="form-check-label" for="typeImage">Gambar / Infografis</label>
                </div>
            </div>

            <div class="mb-3" id="inputContent">
                <label class="form-label">Isi Bacaan</label>
                <!-- Quill Editor Container -->
                <div id="editor-container" style="height: 300px; background: white;"></div>
                <!-- Hidden textarea untuk menyimpan data -->
                <textarea name="content" id="editor" style="display: none;"><?= $data['content'] ?? '' ?></textarea>
            </div>

            <div class="mb-3" id="inputImage" style="display: none;">
                <label class="form-label">Upload Gambar</label>
                <input type="file" id="refImageInput" name="image" class="form-control" accept="image/jpeg,image/png,image/jpg">
                <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
                
                <div id="imagePreviewContainer" class="mt-2" style="display: none;">
                    <p class="mb-1 text-muted small">Preview gambar baru:</p>
                    <img id="imagePreview" src="" class="img-thumbnail" style="max-height: 200px;">
                </div>

                <?php if (!empty($data['image_path'])): ?>
                    <div class="mt-2" id="currentImageContainer">
                        <p class="mb-1 text-muted small">Gambar saat ini:</p>
                        <img src="../assets/uploads/references/<?= $data['image_path'] ?>" class="img-thumbnail" width="200">
                    </div>
                <?php endif; ?>
            </div>

            <hr>
            <button type="submit" id="saveRefBtn" class="btn btn-primary px-4">Simpan Data</button>
        </form>
    </div>
</div>

<script>
    // Fungsi untuk toggle antara input text dan image
    function toggleType() {
        const typeText = document.getElementById('typeText').checked;
        const typeImage = document.getElementById('typeImage').checked;
        const inputContent = document.getElementById('inputContent');
        const inputImage = document.getElementById('inputImage');
        const refImageInput = document.getElementById('refImageInput');
        
        if (typeText) {
            inputContent.style.display = 'block';
            inputImage.style.display = 'none';
            document.getElementById('editor').setAttribute('required', 'required');
            refImageInput.removeAttribute('required');
        } else if (typeImage) {
            inputContent.style.display = 'none';
            inputImage.style.display = 'block';
            document.getElementById('editor').removeAttribute('required');
            <?php if (empty($data['image_path'])): ?>
            refImageInput.setAttribute('required', 'required');
            <?php endif; ?>
        }
    }

    // Handler konversi file gambar ke Base64 (Bypass OpenResty 403)
    const refImageInput = document.getElementById('refImageInput');
    const refImageBase64 = document.getElementById('refImageBase64');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');

    if (refImageInput) {
        refImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) {
                refImageBase64.value = '';
                imagePreviewContainer.style.display = 'none';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran gambar melebihi batas maksimal 2MB!');
                this.value = '';
                refImageBase64.value = '';
                imagePreviewContainer.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                refImageBase64.value = e.target.result;
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // Form submit check
    document.getElementById('referenceForm').addEventListener('submit', function(e) {
        const isImage = document.getElementById('typeImage').checked;
        const hasExistingImg = <?= !empty($data['image_path']) ? 'true' : 'false' ?>;
        
        if (isImage && !hasExistingImg && !refImageBase64.value) {
            alert('Silakan pilih file gambar terlebih dahulu.');
            e.preventDefault();
            return false;
        }
    });
    
    // Jalankan saat halaman dimuat untuk set initial state
    document.addEventListener('DOMContentLoaded', function() {
        toggleType();
    });
</script>