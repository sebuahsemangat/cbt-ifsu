<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data mata pelajaran
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY created_at DESC");
$subjects = $stmt->fetchAll();

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
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td class="px-4 fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= $s['duration'] ?> Menit</td>
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
                            <tr><td colspan="4" class="text-center p-4 text-muted">Belum ada data.</td></tr>
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
                    <div class="mb-3">
                        <label class="form-label">Durasi (Menit)</label>
                        <input type="number" name="duration" class="form-control" required placeholder="Contoh: 90">
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