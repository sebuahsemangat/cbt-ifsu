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

// Ambil data soal JOIN dengan References untuk mendapatkan judul referensi
$query = "SELECT q.*, r.title as ref_title, r.type as ref_type 
          FROM questions q 
          LEFT JOIN `references` r ON q.reference_id = r.id 
          WHERE q.subject_id = ? 
          ORDER BY q.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$subject_id]);
$questions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="dashboard.php?page=exams">Ujian</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($subject['exam_name']) ?></li>
            </ol>
        </nav>
        <h3 class="fw-bold">Soal: <?= htmlspecialchars($subject['name']) ?></h3>
    </div>
    <div class="d-flex gap-2">
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
            Data soal berhasil disimpan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['status'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Data soal berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 20%;">Referensi (Stimulus)</th>
                        <th>Pertanyaan</th>
                        <th style="width: 150px;" class="text-center">Tipe</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-x display-4"></i>
                                <p class="mt-2">Belum ada soal untuk mata pelajaran ini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($questions as $index => $q): ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            
                            <td>
                                <?php if ($q['reference_id']): ?>
                                    <span class="badge bg-info text-dark mb-1">
                                        <i class="bi bi-link-45deg"></i> <?= strtoupper($q['ref_type'] ?? 'REF') ?>
                                    </span>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;">
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

                            <td class="text-center">
                                <?php 
                                $badges = [
                                    'pg' => ['bg' => 'primary', 'label' => 'Pilihan Ganda'],
                                    'bs' => ['bg' => 'warning', 'label' => 'Benar / Salah'],
                                    'multi' => ['bg' => 'purple', 'label' => 'Ceklis (Multi)'], // Custom color logic needed or use standard
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
                                       onclick="return confirm('Yakin ingin menghapus soal ini?')"
                                       title="Hapus Soal">
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