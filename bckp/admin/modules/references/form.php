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
        <form action="modules/references/process.php?action=save" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            
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
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
                
                <?php if (!empty($data['image_path'])): ?>
                    <div class="mt-2">
                        <p>Gambar saat ini:</p>
                        <img src="../assets/uploads/references/<?= $data['image_path'] ?>" class="img-thumbnail" width="200">
                    </div>
                <?php endif; ?>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
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
        
        if (typeText) {
            inputContent.style.display = 'block';
            inputImage.style.display = 'none';
            // Set textarea sebagai required
            document.getElementById('editor').setAttribute('required', 'required');
            // Remove required dari file input
            document.querySelector('input[name="image"]').removeAttribute('required');
        } else if (typeImage) {
            inputContent.style.display = 'none';
            inputImage.style.display = 'block';
            // Remove required dari textarea
            document.getElementById('editor').removeAttribute('required');
            // Set file input sebagai required (hanya jika bukan mode edit dengan gambar sudah ada)
            <?php if (empty($data['image_path'])): ?>
            document.querySelector('input[name="image"]').setAttribute('required', 'required');
            <?php endif; ?>
        }
    }
    
    // Jalankan saat halaman dimuat untuk set initial state
    document.addEventListener('DOMContentLoaded', function() {
        toggleType();
    });
</script>