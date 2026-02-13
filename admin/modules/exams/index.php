<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data ujian
$stmt = $pdo->query("SELECT * FROM exams ORDER BY start_date DESC");
$exams = $stmt->fetchAll();
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
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50px"></th> 
                        <th class="px-4">Nama Ujian</th>
                        <th>Waktu</th>
                        <th class="text-center">Token Ujian</th> <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $e): ?>
                    <?php 
                        $now = date('Y-m-d H:i:s');
                        $is_started = ($now >= $e['start_date'] && $now <= $e['end_date']);
                        $is_finished = ($now > $e['end_date']);
                    ?>
                    <tr>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#subject-<?= $e['id'] ?>">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td class="px-4 fw-bold text-primary"><?= htmlspecialchars($e['exam_name']) ?></td>
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
                        <td colspan="6" class="bg-light p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-book me-2"></i>Daftar Mata Pelajaran</h6>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal<?= $e['id'] ?>">
                                    <i class="bi bi-plus-circle"></i> Tambah Mapel
                                </button>
                            </div>
                            
                            <table class="table table-sm table-bordered bg-white shadow-sm">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Nama Mata Pelajaran</th>
                                        <th>Durasi</th>
                                        <th>Skor Maksimal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stmtSub = $pdo->prepare("SELECT * FROM subjects WHERE exam_id = ?");
                                    $stmtSub->execute([$e['id']]);
                                    $subjects = $stmtSub->fetchAll();
                                    
                                    if(empty($subjects)):
                                    ?>
                                        <tr><td colspan="3" class="text-center text-muted">Belum ada mata pelajaran.</td></tr>
                                    <?php else: foreach($subjects as $s): ?>
                                        <tr>
                                            <td class="px-3"><?= htmlspecialchars($s['name']) ?></td>
                                            <td><?= $s['duration'] ?> Menit</td>
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
                                        <tr><td colspan="4" class="text-center p-2 text-muted small">Total Mapel: <?= count($subjects) ?></td></tr>
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
                                                <div class="mb-3">
                                                    <label class="form-label">Durasi (Menit)</label>
                                                    <input type="number" name="duration" class="form-control" required value="<?= $s['duration'] ?>">
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
                                        <div class="mb-3">
                                            <label class="form-label">Durasi (Menit)</label>
                                            <input type="number" name="duration" class="form-control" required placeholder="Contoh: 90">
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
                        <tr><td colspan="6" class="text-center p-4 text-muted">Belum ada jadwal ujian.</td></tr>
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
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderTokenUI(examId, data.token, data.remaining);
        } else {
            alert('Gagal mengambil token: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
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

// Cek jika ada token yang aktif saat halaman di-load (Opsional: Bisa dijalankan otomatis)
// document.addEventListener('DOMContentLoaded', () => {
//    Cek local storage atau panggil API untuk cek status token yang sedang aktif
// });
</script>