<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data mata pelajaran
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY created_at DESC");
$subjects = $stmt->fetchAll();

// Ambil pengaturan batas minimal durasi ujian (%)
$stmt_min_dur = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'min_exam_duration_percent'");
$min_exam_duration_percent = (int)($stmt_min_dur->fetchColumn() ?: 50);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Mata Pelajaran</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Tambah Mapel
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Nama Mata Pelajaran</th>
                            <th>Durasi (Menit)</th>
                            <th>Skor Maksimal</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td class="px-4 fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                            <td>
                                <?= $s['duration'] ?> Menit
                                <?php if ($min_exam_duration_percent > 0): ?>
                                    <br><span class="badge bg-light text-secondary border small mt-1" title="Batas minimal pengerjaan (<?= $min_exam_duration_percent ?>%)">
                                        <i class="bi bi-clock-history me-1"></i>Min. <?= ceil($s['duration'] * ($min_exam_duration_percent / 100)) ?> mnt
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['max_score'] ?></td>
                            <td>
                                <span class="badge bg-<?= $s['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $s['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="modules/subjects/process.php?action=delete&id=<?= $s['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Hapus mapel ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subjects)): ?>
                            <tr><td colspan="5" class="text-center p-4 text-muted">Belum ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/subjects/process.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mata Pelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Mata Pelajaran</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Matematika">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">Durasi (Menit)</label>
                            <input type="number" name="duration" class="form-control duration-input" required placeholder="Contoh: 90" min="1">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label text-muted">Batas Minimal</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light min-duration-preview" readonly value="-">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill small"></i></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-text small text-muted">
                                <i class="bi bi-info-circle me-1"></i>Batas minimal pengerjaan: <strong><?= $min_exam_duration_percent ?>%</strong> dari total durasi (diatur di Pengaturan).
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Skor Maksimal</label>
                        <input type="number" name="max_score" class="form-control" required value="100" step="0.01" placeholder="Contoh: 100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const MIN_DURATION_PERCENT = <?= json_encode($min_exam_duration_percent) ?>;
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('duration-input')) {
        const modal = e.target.closest('.modal-content') || e.target.closest('form');
        const minPreview = modal ? modal.querySelector('.min-duration-preview') : null;
        if (minPreview) {
            const val = parseFloat(e.target.value) || 0;
            if (val > 0) {
                const minDur = Math.ceil(val * (MIN_DURATION_PERCENT / 100));
                minPreview.value = minDur + ' Menit';
            } else {
                minPreview.value = '-';
            }
        }
    }
});
</script>