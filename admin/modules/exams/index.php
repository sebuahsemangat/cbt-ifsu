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

// Ambil kuesioner survei lokal untuk pilihan guru pengampu
$allLocalSurveys = $pdo->query("SELECT kode_akses, guru_nama, guru_nip, judul, mata_pelajaran FROM cbt_surveys_local ORDER BY guru_nama ASC")->fetchAll();

// Ambil pemetaan survei guru per mata pelajaran
$stmt_ss = $pdo->query("SELECT subject_id, teacher_name, survey_code FROM subject_surveys");
$subject_surveys_map = [];
while ($row = $stmt_ss->fetch(PDO::FETCH_ASSOC)) {
    $subject_surveys_map[$row['subject_id']][] = $row;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">Manajemen Jadwal Ujian</h3>
        <p class="text-muted small mb-0">Kelola jadwal ujian, token, dan sinkronisasi data dari Cloud</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#syncPullModal" onclick="initSyncPullModal()">
            <i class="bi bi-cloud-arrow-down me-1"></i> Tarik Data dari Cloud
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
            <i class="bi bi-calendar-plus me-1"></i> Buat Jadwal Baru
        </button>
    </div>
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
                                            <td class="px-3">
                                                <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                                <?php $assignedTeachers = $subject_surveys_map[$s['id']] ?? []; ?>
                                                <?php if (!empty($assignedTeachers)): ?>
                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                        <?php foreach ($assignedTeachers as $at): ?>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.72rem;" title="Kode: <?= htmlspecialchars($at['survey_code']) ?>">
                                                                <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($at['teacher_name']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
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

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                                                        <span>Guru Pengampu & Survei Evaluasi</span>
                                                        <span class="badge bg-light text-secondary border small fw-normal">Bisa 1-3 guru</span>
                                                    </label>
                                                    <?php 
                                                    $assignedCodes = array_column($subject_surveys_map[$s['id']] ?? [], 'survey_code');
                                                    if (empty($allLocalSurveys)): ?>
                                                        <div class="alert alert-light border small py-2 mb-0">
                                                            <i class="bi bi-info-circle me-1 text-primary"></i> Belum ada survei guru. Silakan tarik data di menu <a href="dashboard.php?page=surveys" target="_blank" class="fw-bold">Survei Guru</a>.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="border rounded p-2 bg-light" style="max-height: 160px; overflow-y: auto;">
                                                            <?php foreach ($allLocalSurveys as $lsrv): ?>
                                                                <div class="form-check py-1 border-bottom border-light">
                                                                    <input class="form-check-input" type="checkbox" name="survey_codes[]" value="<?= htmlspecialchars($lsrv['kode_akses']) ?>" id="chk_edit_<?= $s['id'] ?>_<?= $lsrv['kode_akses'] ?>" <?= in_array($lsrv['kode_akses'], $assignedCodes) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label small" for="chk_edit_<?= $s['id'] ?>_<?= $lsrv['kode_akses'] ?>">
                                                                        <strong><?= htmlspecialchars($lsrv['guru_nama']) ?></strong> 
                                                                        <span class="text-muted">(<?= htmlspecialchars($lsrv['judul']) ?>)</span>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="form-text small text-muted">Centang 1 hingga 3 guru pengampu. Siswa di kelas akan memilih salah satu guru ini untuk diisi surveinya.</div>
                                                    <?php endif; ?>
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
                                            <input type="number" name="max_score" class="form-control" required value="100" step="0.01" placeholder="Contoh: 100">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                                                <span>Guru Pengampu & Survei Evaluasi</span>
                                                <span class="badge bg-light text-secondary border small fw-normal">Bisa 1-3 guru</span>
                                            </label>
                                            <?php if (empty($allLocalSurveys)): ?>
                                                <div class="alert alert-light border small py-2 mb-0">
                                                    <i class="bi bi-info-circle me-1 text-primary"></i> Belum ada survei guru. Silakan tarik data di menu <a href="dashboard.php?page=surveys" target="_blank" class="fw-bold">Survei Guru</a>.
                                                </div>
                                            <?php else: ?>
                                                <div class="border rounded p-2 bg-light" style="max-height: 160px; overflow-y: auto;">
                                                    <?php foreach ($allLocalSurveys as $lsrv): ?>
                                                        <div class="form-check py-1 border-bottom border-light">
                                                            <input class="form-check-input" type="checkbox" name="survey_codes[]" value="<?= htmlspecialchars($lsrv['kode_akses']) ?>" id="chk_add_<?= $e['id'] ?>_<?= $lsrv['kode_akses'] ?>">
                                                            <label class="form-check-label small" for="chk_add_<?= $e['id'] ?>_<?= $lsrv['kode_akses'] ?>">
                                                                <strong><?= htmlspecialchars($lsrv['guru_nama']) ?></strong> 
                                                                <span class="text-muted">(<?= htmlspecialchars($lsrv['judul']) ?>)</span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="form-text small text-muted">Centang 1 hingga 3 guru pengampu. Siswa di kelas akan memilih salah satu guru ini untuk diisi surveinya.</div>
                                            <?php endif; ?>
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

<!-- Modal Tarik Data Ujian dari Cloud -->
<div class="modal fade" id="syncPullModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="syncPullModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="syncPullModalLabel">Tarik Data Ujian dari Cloud</h5>
                        <small class="text-muted">Sinkronisasi paket ujian, bank soal, siswa, dan gambar dari server pusat</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="btnSyncPullClose" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Status Koneksi Server Cloud -->
                <div class="card border bg-light mb-4 shadow-xs">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-hdd-network text-secondary fs-5"></i>
                            <div>
                                <div class="small fw-semibold text-secondary">Server Cloud Pusat:</div>
                                <span class="fw-bold text-dark font-monospace small" id="cloudUrlDisplay">Memeriksa koneksi...</span>
                            </div>
                        </div>
                        <div id="cloudConnStatusBadge">
                            <span class="badge bg-secondary"><span class="spinner-border spinner-border-sm me-1"></span>Mengecek status...</span>
                        </div>
                    </div>
                </div>

                <!-- Alert Notifikasi / Error -->
                <div id="syncPullAlert" style="display: none;"></div>

                <!-- Bagian 1: Pemilihan Ujian dari Cloud -->
                <div id="syncPullSelectSection">
                    <div id="syncLoadingExams" class="text-center py-4">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="text-muted small mb-0">Menghubungkan ke Cloud dan mengambil daftar ujian...</p>
                    </div>

                    <div id="syncExamsContent" style="display: none;">
                        <label class="form-label fw-bold text-dark">Pilih Paket Ujian yang Ingin Ditarik:</label>
                        <select class="form-select form-select-lg mb-3 shadow-xs" id="selectCloudExam" onchange="onSelectCloudExamChange()">
                            <option value="">-- Pilih Ujian dari Cloud --</option>
                        </select>

                        <!-- Detail Paket Ujian Terpilih -->
                        <div id="selectedExamDetailCard" class="card border rounded-3 p-3 bg-white mb-3" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small">Nama Jadwal:</div>
                                    <div class="fw-bold text-primary" id="detailExamName">-</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">Waktu Pelaksanaan:</div>
                                    <div class="fw-semibold text-dark small" id="detailExamTime">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">Jumlah Mapel:</div>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fs-6" id="detailSubjectCount">0</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">Jumlah Soal:</div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fs-6" id="detailQuestionCount">0</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">Siswa Terkait:</div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6" id="detailStudentCount">0</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small">Akses Kelas:</div>
                                    <div class="small fw-semibold text-truncate" id="detailClasses">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Opsi Sinkronisasi -->
                        <div class="border rounded-3 p-3 bg-light mb-3">
                            <label class="form-label fw-bold small text-secondary mb-2">Item yang Akan Disinkronkan:</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled id="chkSyncMeta">
                                        <label class="form-check-label small" for="chkSyncMeta">
                                            Jadwal Ujian, Pengaturan & Mapel
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled id="chkSyncQuestions">
                                        <label class="form-check-label small" for="chkSyncQuestions">
                                            Bank Soal, Opsi Jawaban & Stimulus
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked id="chkSyncStudents">
                                        <label class="form-check-label small fw-semibold text-dark" for="chkSyncStudents">
                                            Data Siswa Terkait & Token Ujian
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked id="chkSyncMedia">
                                        <label class="form-check-label small fw-semibold text-dark" for="chkSyncMedia">
                                            Unduh Otomatis File Gambar & Audio
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Sinkronisasi akan mengunduh paket ujian ini ke database lokal lab. File gambar yang sudah ada tidak akan diunduh ulang.
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Tampilan Progres Sinkronisasi Real-Time -->
                <div id="syncPullProgressSection" style="display: none;">
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2">
                            <i class="bi bi-arrow-repeat fs-2 animate-spin" id="syncSpinIcon"></i>
                        </div>
                        <h5 class="fw-bold mb-1" id="syncProgressTitle">Sedang Menyinkronkan Data...</h5>
                        <p class="text-muted small mb-0" id="syncProgressDetail">Memulai proses sinkronisasi...</p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-4" style="height: 18px; border-radius: 9px;">
                        <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold" role="progressbar" style="width: 0%; font-size: 0.75rem;">0%</div>
                    </div>

                    <!-- Step-by-Step Checklist Cards -->
                    <div class="list-group shadow-xs mb-3">
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3" id="stepItem1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-icon text-muted" id="stepIcon1"><i class="bi bi-circle"></i></span>
                                <span class="small fw-semibold">1. Handshake & Verifikasi Token Cloud</span>
                            </div>
                            <span class="badge bg-light text-secondary border small step-status" id="stepStatus1">Menunggu</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3" id="stepItem2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-icon text-muted" id="stepIcon2"><i class="bi bi-circle"></i></span>
                                <span class="small fw-semibold">2. Struktur Ujian, Kelas & Mata Pelajaran</span>
                            </div>
                            <span class="badge bg-light text-secondary border small step-status" id="stepStatus2">Menunggu</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3" id="stepItem3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-icon text-muted" id="stepIcon3"><i class="bi bi-circle"></i></span>
                                <span class="small fw-semibold">3. Bank Soal, Opsi Jawaban & Stimulus Referensi</span>
                            </div>
                            <span class="badge bg-light text-secondary border small step-status" id="stepStatus3">Menunggu</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3" id="stepItem4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-icon text-muted" id="stepIcon4"><i class="bi bi-circle"></i></span>
                                <span class="small fw-semibold">4. Data Siswa Peserta Ujian & Token</span>
                            </div>
                            <span class="badge bg-light text-secondary border small step-status" id="stepStatus4">Menunggu</span>
                        </div>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3" id="stepItem5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="step-icon text-muted" id="stepIcon5"><i class="bi bi-circle"></i></span>
                                <span class="small fw-semibold">5. Unduh File Gambar & Media Pendukung Soal</span>
                            </div>
                            <span class="badge bg-light text-secondary border small step-status" id="stepStatus5">Menunggu</span>
                        </div>
                    </div>

                    <!-- Summary Card Hasil Sinkronisasi -->
                    <div id="syncResultSummaryCard" class="card border-success bg-success bg-opacity-10 p-3 rounded-3" style="display: none;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-2"></i>
                            <div>
                                <h6 class="fw-bold text-success mb-1">Sinkronisasi Data Berhasil!</h6>
                                <p class="small text-dark mb-0" id="syncResultSummaryText">Semua data ujian dan berkas gambar telah siap digunakan di server lab lokal.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelSync">Batal</button>
                <button type="button" class="btn btn-primary px-4" id="btnStartPullSync" onclick="startPullSync()" disabled>
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> Mulai Tarik Data
                </button>
                <button type="button" class="btn btn-success px-4" id="btnFinishReload" onclick="location.reload()" style="display: none;">
                    <i class="bi bi-arrow-clockwise me-1"></i> Selesai & Refresh Halaman
                </button>
            </div>
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
    const params = new URLSearchParams();
    params.append('exam_id', examId);

    fetch('modules/exams/token_handler.php?action=get_or_create', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params
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

// ========================================================
// JAVASCRIPT LOGIC: SINKRONISASI DATA DARI CLOUD (PULL SYNC)
// ========================================================
let cloudExamsCache = [];
let isPullSyncInProgress = false;

function initSyncPullModal() {
    isPullSyncInProgress = false;
    
    // Reset view visibility
    document.getElementById('syncPullSelectSection').style.display = 'block';
    document.getElementById('syncPullProgressSection').style.display = 'none';
    document.getElementById('syncLoadingExams').style.display = 'block';
    document.getElementById('syncExamsContent').style.display = 'none';
    document.getElementById('selectedExamDetailCard').style.display = 'none';
    document.getElementById('syncResultSummaryCard').style.display = 'none';
    
    // Reset buttons
    const btnStart = document.getElementById('btnStartPullSync');
    const btnCancel = document.getElementById('btnCancelSync');
    const btnFinish = document.getElementById('btnFinishReload');
    const btnClose = document.getElementById('btnSyncPullClose');

    btnStart.style.display = 'inline-block';
    btnStart.disabled = true;
    btnCancel.disabled = false;
    btnCancel.style.display = 'inline-block';
    btnClose.style.display = 'inline-block';
    btnFinish.style.display = 'none';

    // Reset Progress & Steps
    updateSyncProgressBar(0, 'Menyiapkan proses...', '');
    for (let i = 1; i <= 5; i++) {
        resetStepState(i);
    }

    const alertBox = document.getElementById('syncPullAlert');
    alertBox.style.display = 'none';
    alertBox.innerHTML = '';

    const badge = document.getElementById('cloudConnStatusBadge');
    badge.innerHTML = '<span class="badge bg-secondary"><span class="spinner-border spinner-border-sm me-1"></span>Mengecek status...</span>';

    // 1. Tes Koneksi ke Cloud
    fetch('modules/exams/sync_pull_process.php?action=test_connection')
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); } catch (e) { throw new Error(text.replace(/<[^>]*>?/gm, ' ').trim().substring(0, 150)); }
        })
        .then(data => {
            const urlDisp = document.getElementById('cloudUrlDisplay');
            if (data.cloud_url) urlDisp.textContent = data.cloud_url;

            if (data.success) {
                badge.innerHTML = `<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Terhubung (Waktu Cloud: ${data.server_time || '-'})</span>`;
                // 2. Ambil daftar ujian dari cloud
                return fetch('modules/exams/sync_pull_process.php?action=fetch_cloud_exams');
            } else {
                badge.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Gagal Terhubung</span>';
                throw new Error(data.message || 'Tidak dapat terhubung ke server cloud.');
            }
        })
        .then(async r => {
            if (!r) return;
            const text = await r.text();
            try { return JSON.parse(text); } catch (e) { throw new Error(text.replace(/<[^>]*>?/gm, ' ').trim().substring(0, 150)); }
        })
        .then(data => {
            if (!data) return;
            document.getElementById('syncLoadingExams').style.display = 'none';
            document.getElementById('syncExamsContent').style.display = 'block';

            if (!data.success) {
                throw new Error(data.message || 'Gagal memuat daftar ujian.');
            }

            cloudExamsCache = data.exams || [];
            const selectEl = document.getElementById('selectCloudExam');
            selectEl.innerHTML = '<option value="">-- Pilih Ujian dari Cloud --</option>';

            if (cloudExamsCache.length === 0) {
                selectEl.innerHTML = '<option value="">(Tidak ada ujian aktif di server cloud)</option>';
                return;
            }

            cloudExamsCache.forEach(ex => {
                const opt = document.createElement('option');
                opt.value = ex.id;
                opt.textContent = `${ex.exam_name} (${ex.subject_count} Mapel, ${ex.question_count} Soal)`;
                selectEl.appendChild(opt);
            });
        })
        .catch(err => {
            document.getElementById('syncLoadingExams').style.display = 'none';
            alertBox.className = 'alert alert-danger';
            alertBox.style.display = 'block';
            alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Koneksi Gagal:</strong> ${err.message}<br><small class="mt-1 d-block">Pastikan server memiliki koneksi internet dan pengaturan <em>Token Sinkronisasi</em> di menu <strong>Pengaturan</strong> sudah sesuai.</small>`;
        });
}

function onSelectCloudExamChange() {
    const examId = parseInt(document.getElementById('selectCloudExam').value, 10);
    const detailCard = document.getElementById('selectedExamDetailCard');
    const btnStart = document.getElementById('btnStartPullSync');

    if (!examId) {
        detailCard.style.display = 'none';
        btnStart.disabled = true;
        return;
    }

    const exam = cloudExamsCache.find(e => parseInt(e.id, 10) === examId);
    if (!exam) {
        detailCard.style.display = 'none';
        btnStart.disabled = true;
        return;
    }

    document.getElementById('detailExamName').textContent = exam.exam_name;
    document.getElementById('detailExamTime').textContent = `${exam.start_date} s/d ${exam.end_date}`;
    document.getElementById('detailSubjectCount').textContent = exam.subject_count + ' Mapel';
    document.getElementById('detailQuestionCount').textContent = exam.question_count + ' Butir Soal';
    document.getElementById('detailStudentCount').textContent = exam.student_count + ' Siswa';
    document.getElementById('detailClasses').textContent = (exam.classes && exam.classes.length > 0) ? exam.classes.join(', ') : 'Semua Kelas (Tanpa Filter)';

    detailCard.style.display = 'block';
    btnStart.disabled = false;
}

function updateSyncProgressBar(percent, title, detail) {
    const bar = document.getElementById('syncProgressBar');
    bar.style.width = percent + '%';
    bar.textContent = percent + '%';
    if (title) document.getElementById('syncProgressTitle').textContent = title;
    if (detail) document.getElementById('syncProgressDetail').textContent = detail;
}

function resetStepState(stepNum) {
    document.getElementById(`stepIcon${stepNum}`).innerHTML = '<i class="bi bi-circle text-muted"></i>';
    const status = document.getElementById(`stepStatus${stepNum}`);
    status.className = 'badge bg-light text-secondary border small';
    status.textContent = 'Menunggu';
}

function setStepActive(stepNum, text) {
    document.getElementById(`stepIcon${stepNum}`).innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    const status = document.getElementById(`stepStatus${stepNum}`);
    status.className = 'badge bg-primary text-white small';
    status.textContent = text || 'Memproses...';
}

function setStepSuccess(stepNum, text) {
    document.getElementById(`stepIcon${stepNum}`).innerHTML = '<i class="bi bi-check-circle-fill text-success fs-6"></i>';
    const status = document.getElementById(`stepStatus${stepNum}`);
    status.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small';
    status.textContent = text || 'Selesai';
}

function setStepFailed(stepNum, text) {
    document.getElementById(`stepIcon${stepNum}`).innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-6"></i>';
    const status = document.getElementById(`stepStatus${stepNum}`);
    status.className = 'badge bg-danger text-white small';
    status.textContent = text || 'Gagal';
}

async function startPullSync() {
    const selectEl = document.getElementById('selectCloudExam');
    const examId = parseInt(selectEl.value, 10);
    if (!examId) return;

    const exam = cloudExamsCache.find(e => parseInt(e.id, 10) === examId);
    const examName = exam ? exam.exam_name : 'Ujian Terpilih';

    if (!confirm(`Mulai sinkronisasi data untuk "${examName}"?\nData soal, opsi, dan gambar pendukung akan diunduh ke server lab.`)) {
        return;
    }

    isPullSyncInProgress = true;

    // Kunci tombol penutup modal
    document.getElementById('btnCancelSync').disabled = true;
    document.getElementById('btnSyncPullClose').style.display = 'none';
    document.getElementById('btnStartPullSync').style.display = 'none';

    // Beralih ke view progress
    document.getElementById('syncPullSelectSection').style.display = 'none';
    document.getElementById('syncPullProgressSection').style.display = 'block';

    const spinIcon = document.getElementById('syncSpinIcon');
    spinIcon.classList.add('animate-spin');

    const syncStudents = document.getElementById('chkSyncStudents').checked;
    const syncMedia = document.getElementById('chkSyncMedia').checked;

    try {
        // ======================================
        // STEP 1: Handshake & Verifikasi Token
        // ======================================
        updateSyncProgressBar(10, 'Langkah 1/5: Verifikasi Token Cloud', 'Memeriksa otentikasi server...');
        setStepActive(1, 'Memverifikasi...');
        
        const testRes = await fetchJson('modules/exams/sync_pull_process.php?action=test_connection');
        if (!testRes.success) throw new Error('Otentikasi cloud gagal: ' + testRes.message);
        setStepSuccess(1, 'Terhubung');

        // ======================================
        // STEP 2: Sinkronisasi Metadata Ujian & Mapel
        // ======================================
        updateSyncProgressBar(25, 'Langkah 2/5: Mengunduh Struktur Ujian', 'Menyimpan jadwal ujian & mata pelajaran...');
        setStepActive(2, 'Mengunduh...');
        
        const metaFormData = new FormData();
        metaFormData.append('action', 'sync_exam_meta');
        metaFormData.append('exam_id', examId);
        
        const metaRes = await fetchJson('modules/exams/sync_pull_process.php', { method: 'POST', body: metaFormData });
        if (!metaRes.success) throw new Error('Gagal sync metadata: ' + metaRes.message);
        setStepSuccess(2, `${metaRes.total_subjects || 0} Mapel tersimpan`);

        // ======================================
        // STEP 3: Sinkronisasi Soal, Opsi, & Stimulus
        // ======================================
        updateSyncProgressBar(50, 'Langkah 3/5: Mengunduh Bank Soal', 'Menyimpan butir pertanyaan, opsi & referensi...');
        setStepActive(3, 'Mengunduh...');

        const qFormData = new FormData();
        qFormData.append('action', 'sync_questions');
        qFormData.append('exam_id', examId);

        const qRes = await fetchJson('modules/exams/sync_pull_process.php', { method: 'POST', body: qFormData });
        if (!qRes.success) throw new Error('Gagal sync soal: ' + qRes.message);
        setStepSuccess(3, `${qRes.total_questions || 0} Soal, ${qRes.total_options || 0} Opsi`);

        // ======================================
        // STEP 4: Sinkronisasi Siswa Terkait
        // ======================================
        updateSyncProgressBar(70, 'Langkah 4/5: Menyelaraskan Siswa Peserta', 'Menyimpan akun siswa dan token...');
        setStepActive(4, 'Menyelaraskan...');

        let studentCount = 0;
        if (syncStudents) {
            const stuFormData = new FormData();
            stuFormData.append('action', 'sync_students');
            stuFormData.append('exam_id', examId);

            const stuRes = await fetchJson('modules/exams/sync_pull_process.php', { method: 'POST', body: stuFormData });
            if (!stuRes.success) throw new Error('Gagal sync siswa: ' + stuRes.message);
            studentCount = stuRes.total_students || 0;
            setStepSuccess(4, `${studentCount} Siswa tersimpan`);
        } else {
            setStepSuccess(4, 'Dilewati');
        }

        // ======================================
        // STEP 5: Unduh File Media / Gambar
        // ======================================
        updateSyncProgressBar(80, 'Langkah 5/5: Memeriksa Aset Gambar', 'Menghitung berkas gambar pendukung soal...');
        setStepActive(5, 'Memeriksa berkas...');

        let totalDownloadedMedia = 0;
        let cachedMedia = 0;

        if (syncMedia) {
            const queueRes = await fetchJson(`modules/exams/sync_pull_process.php?action=get_media_queue&exam_id=${examId}`);
            if (!queueRes.success) throw new Error('Gagal memeriksa berkas media: ' + queueRes.message);

            const queue = queueRes.queue || [];
            cachedMedia = queueRes.already_cached || 0;
            const totalQueue = queue.length;

            if (totalQueue > 0) {
                for (let i = 0; i < totalQueue; i++) {
                    const item = queue[i];
                    const pct = Math.round(80 + ((i + 1) / totalQueue) * 18); // Bergerak dari 80% ke 98%
                    updateSyncProgressBar(pct, 'Langkah 5/5: Mengunduh Berkas Media', `Mengunduh ${item.file} (${i + 1}/${totalQueue})...`);
                    setStepActive(5, `Download (${i + 1}/${totalQueue})...`);

                    const mediaFormData = new FormData();
                    mediaFormData.append('action', 'download_single_media');
                    mediaFormData.append('type', item.type);
                    mediaFormData.append('file', item.file);

                    await fetchJson('modules/exams/sync_pull_process.php', { method: 'POST', body: mediaFormData });
                    totalDownloadedMedia++;
                }
                setStepSuccess(5, `${totalDownloadedMedia} baru, ${cachedMedia} lokal`);
            } else {
                setStepSuccess(5, `${cachedMedia} berkas sudah lengkap`);
            }
        } else {
            setStepSuccess(5, 'Dilewati');
        }

        // ======================================
        // SELESAI 100%
        // ======================================
        updateSyncProgressBar(100, 'Sinkronisasi Selesai!', 'Seluruh paket data berhasil diunduh ke server lab.');
        spinIcon.classList.remove('animate-spin');
        spinIcon.className = 'bi bi-check2-all fs-2 text-success';

        document.getElementById('syncResultSummaryCard').style.display = 'block';
        document.getElementById('syncResultSummaryText').innerHTML = `
            Paket ujian <strong>${examName}</strong> siap digunakan offline.<br>
            <span class="small text-muted">&bull; Soal: ${qRes.total_questions || 0} &bull; Stimulus: ${qRes.total_references || 0} &bull; Siswa: ${studentCount} &bull; Gambar: ${totalDownloadedMedia} baru (${cachedMedia} sudah ada).</span>
        `;

        document.getElementById('btnFinishReload').style.display = 'inline-block';
        document.getElementById('btnCancelSync').style.display = 'none';

    } catch (err) {
        spinIcon.classList.remove('animate-spin');
        spinIcon.className = 'bi bi-exclamation-triangle fs-2 text-danger';

        updateSyncProgressBar(100, 'Sinkronisasi Terhenti', 'Terjadi kesalahan saat memproses data.');
        const bar = document.getElementById('syncProgressBar');
        bar.className = 'progress-bar bg-danger';

        alert(`Gagal menyelesaikan sinkronisasi:\n${err.message}`);
        
        // Aktifkan kembali tombol batal agar modal bisa ditutup
        document.getElementById('btnCancelSync').disabled = false;
        document.getElementById('btnSyncPullClose').style.display = 'inline-block';
    }
}

async function fetchJson(url, options = {}) {
    const res = await fetch(url, options);
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        const cleanMsg = text.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(cleanMsg ? cleanMsg.substring(0, 200) : 'Respon server kosong');
    }
}
</script>

<style>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing !important; }
.sortable-ghost { opacity: 0.4; background-color: #cfe2ff !important; }
.sortable-chosen { background-color: #e8f5e9 !important; }

@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.animate-spin { animation: spin 0.9s linear infinite; }
</style>