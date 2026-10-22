<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data ujian
$stmt = $pdo->query("SELECT * FROM exams ORDER BY start_date DESC");
$exams = $stmt->fetchAll();

// Ambil semua daftar kelas yang tersedia
$stmt_classes = $pdo->query("
    SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != ''
    UNION
    SELECT DISTINCT class_name as class FROM exam_classes WHERE class_name IS NOT NULL AND class_name != ''
    ORDER BY class ASC
");
$available_classes = $stmt_classes->fetchAll(PDO::FETCH_COLUMN);

// Ambil pemetaan kelas per ujian
$stmt_ec = $pdo->query("SELECT exam_id, class_name FROM exam_classes ORDER BY class_name ASC");
$exam_classes_map = [];
while ($row = $stmt_ec->fetch(PDO::FETCH_ASSOC)) {
    $exam_classes_map[$row['exam_id']][] = $row['class_name'];
}

// Ambil pengaturan batas minimal durasi ujian (%)
$stmt_min_dur = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'min_exam_duration_percent'");
$min_exam_duration_percent = (int)($stmt_min_dur->fetchColumn() ?: 50);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Manajemen Jadwal Ujian</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
        <i class="bi bi-calendar-plus"></i> Buat Jadwal Baru
    </button>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Operasi berhasil dilakukan!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_time'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Waktu selesai tidak boleh lebih kecil dari waktu mulai!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_no_class'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> Pilih minimal 1 kelas yang diizinkan mengakses ujian!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50px"></th> 
                        <th class="px-4">Nama Ujian</th>
                        <th>Akses Kelas</th>
                        <th>Waktu</th>
                        <th class="text-center">Token Ujian</th> 
                        <th>Status</th>
                        <th class="text-center">Review Siswa</th>
                        <th class="text-center">Urutan Soal</th>
                        <th class="text-center">Tampilkan Nilai</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $e): ?>
                    <?php 
                        $now = date('Y-m-d H:i:s');
                        $is_started = ($now >= $e['start_date'] && $now <= $e['end_date']);
                        $is_finished = ($now > $e['end_date']);
                        $e_classes = $exam_classes_map[$e['id']] ?? [];
                        $total_avail = count($available_classes);
                    ?>
                    <tr>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#subject-<?= $e['id'] ?>">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td class="px-4 fw-bold text-primary"><?= htmlspecialchars($e['exam_name']) ?></td>
                        <td>
                            <?php if (empty($e_classes)): ?>
                                <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Belum Ada Kelas</span>
                            <?php elseif (count($e_classes) === $total_avail && $total_avail > 0): ?>
                                <span class="badge bg-success" title="<?= htmlspecialchars(implode(', ', $e_classes)) ?>">
                                    <i class="bi bi-check-all me-1"></i>Semua Kelas (<?= count($e_classes) ?>)
                                </span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1" style="max-width: 200px;">
                                    <?php 
                                    $shown = array_slice($e_classes, 0, 2);
                                    foreach ($shown as $cls): ?>
                                        <span class="badge bg-info text-dark" style="font-size: 0.72rem;"><?= htmlspecialchars($cls) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($e_classes) > 2): ?>
                                        <span class="badge bg-secondary" style="font-size: 0.72rem;" title="<?= htmlspecialchars(implode(', ', $e_classes)) ?>">
                                            +<?= count($e_classes) - 2 ?> lainnya
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="d-block text-muted">Mulai: <?= date('d M Y, H:i', strtotime($e['start_date'])) ?></small>
                            <small class="d-block text-muted">Selesai: <?= date('d M Y, H:i', strtotime($e['end_date'])) ?></small>
                        </td>
                        
                        <td class="text-center">
                            <?php if ($is_started): ?>
                                <div id="token-box-<?= $e['id'] ?>">
                                    <button class="btn btn-sm btn-outline-primary" onclick="initToken(<?= $e['id'] ?>)">
                                        <i class="bi bi-key"></i> Release Token
                                    </button>
                                </div>
                            <?php elseif ($is_finished): ?>
                                <span class="text-muted small">Closed</span>
                            <?php else: ?>
                                <span class="text-muted small">Belum Mulai</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php 
                            if ($now < $e['start_date']) echo '<span class="badge bg-warning">Belum Mulai</span>';
                            elseif ($now > $e['end_date']) echo '<span class="badge bg-danger">Selesai</span>';
                            else echo '<span class="badge bg-success">Berlangsung</span>';
                            ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($e['allow_review'])): ?>
                                <a href="modules/exams/process.php?action=toggle_review&id=<?= $e['id'] ?>" 
                                   class="badge bg-success text-decoration-none" 
                                   title="Klik untuk nonaktifkan review siswa"
                                   onclick="return confirm('Nonaktifkan akses review untuk ujian ini?')">
                                    <i class="bi bi-eye me-1"></i> Aktif
                                </a>
                            <?php else: ?>
                                <a href="modules/exams/process.php?action=toggle_review&id=<?= $e['id'] ?>" 
                                   class="badge bg-secondary text-decoration-none" 
                                   title="Klik untuk aktifkan review siswa"
                                   onclick="return confirm('Aktifkan akses review untuk ujian ini?')">
                                    <i class="bi bi-eye-slash me-1"></i> Nonaktif
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!isset($e['randomize_questions']) || $e['randomize_questions'] == 1): ?>
                                <a href="modules/exams/process.php?action=toggle_random&id=<?= $e['id'] ?>" 
                                   class="badge bg-primary text-decoration-none" 
                                   title="Klik untuk ubah ke Berurutan"
                                   onclick="return confirm('Ubah tampilan soal menjadi Berurutan?')">
                                    <i class="bi bi-shuffle me-1"></i> Acak
                                </a>
                            <?php else: ?>
                                <a href="modules/exams/process.php?action=toggle_random&id=<?= $e['id'] ?>" 
                                   class="badge bg-secondary text-decoration-none" 
                                   title="Klik untuk ubah ke Acak"
                                   onclick="return confirm('Ubah tampilan soal menjadi Acak?')">
                                    <i class="bi bi-sort-numeric-down me-1"></i> Berurutan
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($e['show_score'])): ?>
                                <a href="modules/exams/process.php?action=toggle_score&id=<?= $e['id'] ?>" 
                                   class="badge bg-success text-decoration-none" 
                                   title="Klik untuk sembunyikan nilai dari siswa"
                                   onclick="return confirm('Nonaktifkan tampilan nilai untuk ujian ini?')">
                                    <i class="bi bi-eye me-1"></i> Aktif
                                </a>
                            <?php else: ?>
                                <a href="modules/exams/process.php?action=toggle_score&id=<?= $e['id'] ?>" 
                                   class="badge bg-secondary text-decoration-none" 
                                   title="Klik untuk tampilkan nilai kepada siswa"
                                   onclick="return confirm('Aktifkan tampilan nilai untuk ujian ini?')">
                                    <i class="bi bi-eye-slash me-1"></i> Nonaktif
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editExamModal<?= $e['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="modules/exams/process.php?action=delete&id=<?= $e['id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Menghapus Exam akan menghapus semua Subject di dalamnya. Lanjutkan?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    
                    <tr class="collapse" id="subject-<?= $e['id'] ?>">
                        <td colspan="10" class="bg-light p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-book me-2"></i>Daftar Mata Pelajaran</h6>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal<?= $e['id'] ?>">
                                    <i class="bi bi-plus-circle"></i> Tambah Mapel
                                </button>
                            </div>
                            
                            <?php 
                            // Cek apakah ada siswa yang sedang mengerjakan ujian ini
                            $stmtOngoing = $pdo->prepare("
                                SELECT COUNT(*) FROM exam_sessions 
                                WHERE exam_id = ? AND status = 'ongoing'
                            ");
                            $stmtOngoing->execute([$e['id']]);
                            $hasOngoing = $stmtOngoing->fetchColumn();
                            ?>
                            
                            <?php if ($hasOngoing > 0): ?>
                            <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center small">
                                <i class="bi bi-lock-fill me-2"></i>
                                <span>Urutan mapel <strong>terkunci</strong> — ada <strong><?= $hasOngoing ?></strong> siswa sedang mengerjakan ujian.</span>
                            </div>
                            <?php endif; ?>

                            <table class="table table-sm table-bordered bg-white shadow-sm">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th>Nama Mata Pelajaran</th>
                                        <th>Durasi</th>
                                        <th>Skor Maksimal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-subjects" data-exam-id="<?= $e['id'] ?>" <?= $hasOngoing > 0 ? 'data-locked="true"' : '' ?>>
                                    <?php 
                                    $stmtSub = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ? ORDER BY sort_order ASC, id ASC");
                                    $stmtSub->execute([$e['id']]);
                                    $subjects = $stmtSub->fetchAll();
                                    
                                    if(empty($subjects)):
                                    ?>
                                        <tr><td colspan="5" class="text-center text-muted">Belum ada mata pelajaran.</td></tr>
                                    <?php else: foreach($subjects as $si => $s): ?>
                                        <tr data-id="<?= $s['id'] ?>">
                                            <td class="text-center <?= $hasOngoing > 0 ? '' : 'drag-handle' ?>" <?= $hasOngoing > 0 ? '' : 'title="Drag untuk mengubah urutan"' ?>>
                                                <?php if ($hasOngoing > 0): ?>
                                                    <i class="bi bi-lock text-muted" title="Terkunci — ujian sedang berlangsung"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-grip-vertical text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3"><?= htmlspecialchars($s['name']) ?></td>
                                            <td>
                                                <?= $s['duration'] ?> Menit
                                                <?php if ($min_exam_duration_percent > 0): ?>
                                                    <br><span class="badge bg-light text-secondary border small mt-1" title="Batas minimal pengerjaan (<?= $min_exam_duration_percent ?>%)">
                                                        <i class="bi bi-clock-history me-1"></i>Min. <?= ceil($s['duration'] * ($min_exam_duration_percent / 100)) ?> mnt
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $s['max_score'] ?></td>
                                            <td class="text-center">
                                                <a href="dashboard.php?page=questions&subject_id=<?= $s['id'] ?>" class="btn btn-xs btn-primary me-1">
                                                    <i class="bi bi-pencil-square"></i> Kelola Soal
                                                </a>
                                                <button class="btn btn-xs btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editSubjectModal<?= $s['id'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="modules/subjects/process.php?action=delete&id=<?= $s['id'] ?>" 
                                                   class="btn btn-xs btn-danger" 
                                                   onclick="return confirm('Hapus mapel ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                    <?php if(!empty($subjects)): ?>
                                        <tr class="no-sort"><td colspan="5" class="text-center p-2 text-muted small">Total Mapel: <?= count($subjects) ?><?= $hasOngoing > 0 ? ' — <i class="bi bi-lock"></i> Terkunci' : ' — <i class="bi bi-grip-vertical"></i> Drag untuk ubah urutan' ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php 
                            // Edit modals for each subject
                            if(!empty($subjects)): 
                                foreach($subjects as $s): 
                            ?>
                            <div class="modal fade" id="editSubjectModal<?= $s['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="modules/subjects/process.php?action=edit" method="POST">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Mata Pelajaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-muted">Untuk Ujian: <b><?= htmlspecialchars($e['exam_name']) ?></b></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Nama Mata Pelajaran</label>
                                                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($s['name']) ?>">
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-sm-7">
                                                        <label class="form-label">Durasi (Menit)</label>
                                                        <input type="number" name="duration" class="form-control duration-input" required value="<?= $s['duration'] ?>" min="1" placeholder="Contoh: 90">
                                                    </div>
                                                    <div class="col-sm-5">
                                                        <label class="form-label text-muted">Batas Minimal</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control bg-light min-duration-preview" readonly value="<?= ceil($s['duration'] * ($min_exam_duration_percent / 100)) ?> Menit">
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
                                                    <input type="number" name="max_score" class="form-control" required value="<?= $s['max_score'] ?>" step="0.01">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="addSubjectModal<?= $e['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="modules/subjects/process.php?action=add" method="POST">
                                    <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Tambah Mata Pelajaran</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted">Untuk Ujian: <b><?= htmlspecialchars($e['exam_name']) ?></b></p>
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
                                            <input type="number" name="max_score" class="form-control" required step="0.01" placeholder="Contoh: 100">
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

                    <div class="modal fade" id="editExamModal<?= $e['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="modules/exams/process.php?action=edit" method="POST">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Jadwal Ujian</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Ujian</label>
                                            <input type="text" name="exam_name" class="form-control" required value="<?= htmlspecialchars($e['exam_name']) ?>">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Waktu Mulai</label>
                                                <input type="datetime-local" name="start_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($e['start_date'])) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Waktu Selesai</label>
                                                <input type="datetime-local" name="end_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($e['end_date'])) ?>">
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="allow_review" id="editAllowReview<?= $e['id'] ?>" value="1" <?= !empty($e['allow_review']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="editAllowReview<?= $e['id'] ?>">Izinkan Siswa Melihat Pembahasan / Review</label>
                                            <div class="form-text">Jika aktif, siswa yang telah selesai ujian dapat melihat pembahasan dan jawaban.</div>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="randomize_questions" id="editRandomizeQuestions<?= $e['id'] ?>" value="1" <?= (!isset($e['randomize_questions']) || $e['randomize_questions'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="editRandomizeQuestions<?= $e['id'] ?>">Acak Tampilan Soal Siswa</label>
                                            <div class="form-text">Jika aktif, urutan soal diacak untuk setiap siswa. Jika nonaktif, soal ditampilkan berurutan.</div>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="show_score" id="editShowScore<?= $e['id'] ?>" value="1" <?= !empty($e['show_score']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="editShowScore<?= $e['id'] ?>">Tampilkan Nilai ke Siswa</label>
                                            <div class="form-text">Jika aktif, nilai akan ditampilkan ke siswa setelah mengerjakan ujian.</div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold mb-0">
                                                    <i class="bi bi-people-fill text-primary me-1"></i>Akses Kelas
                                                </label>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="toggleAllClasses('editExamModal<?= $e['id'] ?>', true)">Pilih Semua</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleAllClasses('editExamModal<?= $e['id'] ?>', false)">Hapus Semua</button>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mb-2">Hanya siswa pada kelas yang dicentang yang dapat melihat dan mengerjakan ujian ini.</small>
                                            <div class="border rounded-3 p-3 bg-light" style="max-height: 180px; overflow-y: auto;">
                                                <div class="row row-cols-2 g-2">
                                                    <?php foreach ($available_classes as $c): ?>
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="classes[]" value="<?= htmlspecialchars($c) ?>" id="edit_cls_<?= $e['id'] ?>_<?= md5($c) ?>" <?= in_array($c, $e_classes) ? 'checked' : '' ?>>
                                                                <label class="form-check-label small" for="edit_cls_<?= $e['id'] ?>_<?= md5($c) ?>">
                                                                    <?= htmlspecialchars($c) ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-warning">Update Jadwal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($exams)): ?>
                        <tr><td colspan="10" class="text-center p-4 text-muted">Belum ada jadwal ujian.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addExamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/exams/process.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Jadwal Ujian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Ujian</label>
                        <input type="text" name="exam_name" class="form-control" required placeholder="Contoh: UAS Semester Ganjil 2026">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Waktu Mulai</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Waktu Selesai</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="allow_review" id="addAllowReview" value="1">
                        <label class="form-check-label fw-semibold" for="addAllowReview">Izinkan Siswa Melihat Pembahasan / Review (Default: Nonaktif)</label>
                        <div class="form-text">Jika tidak dicentang, siswa tidak dapat membuka halaman pembahasan setelah ujian selesai.</div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="randomize_questions" id="addRandomizeQuestions" value="1" checked>
                        <label class="form-check-label fw-semibold" for="addRandomizeQuestions">Acak Tampilan Soal Siswa (Default: Acak)</label>
                        <div class="form-text">Jika aktif, urutan soal diacak untuk setiap siswa. Jika nonaktif, soal ditampilkan berurutan.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="show_score" id="addShowScore" value="1">
                        <label class="form-check-label fw-semibold" for="addShowScore">Tampilkan Nilai ke Siswa (Default: Nonaktif)</label>
                        <div class="form-text">Jika tidak dicentang, nilai tidak akan diperlihatkan kepada siswa setelah ujian selesai.</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">
                                <i class="bi bi-people-fill text-primary me-1"></i>Kelas yang Diizinkan Mengakses
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="toggleAllClasses('addExamModal', true)">Pilih Semua</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleAllClasses('addExamModal', false)">Hapus Semua</button>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-2">Pilih kelas yang diizinkan mengakses ujian ini. Default: semua kelas terpilih.</small>
                        <div class="border rounded-3 p-3 bg-light" style="max-height: 180px; overflow-y: auto;">
                            <div class="row row-cols-2 g-2">
                                <?php foreach ($available_classes as $c): ?>
                                    <div class="col">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="classes[]" value="<?= htmlspecialchars($c) ?>" id="add_cls_<?= md5($c) ?>" checked>
                                            <label class="form-check-label small" for="add_cls_<?= md5($c) ?>">
                                                <?= htmlspecialchars($c) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Object untuk menyimpan interval timer agar tidak tumpang tindih
const tokenTimers = {};

function initToken(examId) {
    // Tampilkan loading sementara
    const container = document.getElementById(`token-box-${examId}`);
    container.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Loading...';

    // Panggil fungsi ambil token
    fetchToken(examId);
}

function fetchToken(examId) {
    const formData = new FormData();
    formData.append('exam_id', examId);

    fetch('modules/exams/token_handler.php?action=get_or_create', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Respon Server bukan JSON:', text);
            // Ambil pesan bersih dari HTML jika server mengembalikan error page
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = text;
            const cleanMsg = tempDiv.textContent.trim() || text.substring(0, 150);
            throw new Error(cleanMsg);
        }
    })
    .then(data => {
        if (data.status === 'success') {
            renderTokenUI(examId, data.token, data.remaining);
        } else {
            alert('Gagal mengambil token: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error fetchToken:', error);
        alert('Terjadi kesalahan saat memproses token: ' + error.message);
    });
}


function renderTokenUI(examId, tokenCode, remainingSeconds) {
    const container = document.getElementById(`token-box-${examId}`);
    
    // HTML Tampilan Token
    container.innerHTML = `
        <div class="d-flex flex-column align-items-center">
            <h4 class="fw-bold text-success mb-0" style="letter-spacing: 2px;">${tokenCode}</h4>
            <small class="text-muted" style="font-size: 0.75rem;">
                Refresh: <span id="timer-${examId}" class="fw-bold text-danger">--:--</span>
            </small>
        </div>
    `;

    // Hentikan timer lama jika ada
    if (tokenTimers[examId]) clearInterval(tokenTimers[examId]);

    // Mulai hitung mundur
    startTimer(examId, remainingSeconds);
}

function startTimer(examId, duration) {
    let timer = duration;
    
    // Fungsi update tampilan menit:detik
    const updateDisplay = () => {
        const minutes = parseInt(timer / 60, 10);
        const seconds = parseInt(timer % 60, 10);
        
        const displayMin = minutes < 10 ? "0" + minutes : minutes;
        const displaySec = seconds < 10 ? "0" + seconds : seconds;
        
        const timerEl = document.getElementById(`timer-${examId}`);
        if(timerEl) timerEl.textContent = displayMin + ":" + displaySec;
        
        // Jika waktu habis, refresh token otomatis
        if (--timer < 0) {
            clearInterval(tokenTimers[examId]);
            fetchToken(examId); // Panggil lagi untuk dapat token baru
        }
    };

    updateDisplay(); // Jalankan langsung sekali
    tokenTimers[examId] = setInterval(updateDisplay, 1000);
}

function toggleAllClasses(modalId, checkStatus) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    const checkboxes = modal.querySelectorAll('input[type="checkbox"][name="classes[]"]');
    checkboxes.forEach(cb => cb.checked = checkStatus);
}

// Hitung otomatis batas minimal durasi saat durasi diisi
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

<style>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing !important; }
.sortable-ghost { opacity: 0.4; background-color: #cfe2ff !important; }
.sortable-chosen { background-color: #e8f5e9 !important; }
</style>