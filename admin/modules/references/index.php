<?php
// Ambil daftar Mapel untuk filter (Opsional, tapi bagus untuk UX)
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();

// Logic Filter
$filter_subject = $_GET['subject_id'] ?? '';
$sql = "SELECT r.*, s.name as subject_name FROM `references` r 
        JOIN subjects s ON r.subject_id = s.id";

if ($filter_subject) {
    $sql .= " WHERE r.subject_id = $filter_subject";
}
$sql .= " ORDER BY r.id DESC";
$references = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Bank Referensi (Stimulus)</h3>
    <a href="dashboard.php?page=references_form" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Referensi
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="dashboard.php" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="references">
            <div class="col-auto"><label class="fw-bold">Filter Mapel:</label></div>
            <div class="col-auto">
                <select name="subject_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Mapel</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <?php foreach ($references as $r): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="badge bg-primary"><?= htmlspecialchars($r['subject_name']) ?></span>
                <span class="badge bg-secondary"><?= $r['title'] ?></span>
            </div>
            <div class="card-body overflow-auto" style="max-height: 200px;">
                <?php if ($r['type'] == 'text'): ?>
                    <div class="small text-muted"><?= $r['content'] // CKEditor output is HTML safe ?></div>
                <?php else: ?>
                    <img src="../assets/uploads/references/<?= $r['image_path'] ?>" class="img-fluid rounded" alt="Referensi">
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="dashboard.php?page=references_form&id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="modules/references/process.php?action=delete&id=<?= $r['id'] ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Hapus referensi ini? Soal yang terhubung akan kehilangan referensinya.')">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($references)): ?>
        <div class="col-12 text-center text-muted py-5">Belum ada data referensi.</div>
    <?php endif; ?>
</div>