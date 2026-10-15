<?php
session_start();


require_once '../config/database.php';

// Cek Otorisasi (Hanya Student)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Validasi status login dan ambil info kelas siswa dari database
$stmt = $pdo->prepare("SELECT is_logged_in, class FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student_status = $stmt->fetch();

if (!$student_status || $student_status['is_logged_in'] == 0) {
    // Jika is_logged_in = 0, redirect ke logout
    header("Location: ../logout.php");
    exit;
}

$student_class = trim($student_status['class'] ?? '');

$message = '';

// --- PROSES GANTI PASSWORD ---
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Ambil password lama dari DB
    $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
            $upd->execute([$hashed_pass, $student_id]);
            $message = "<div class='alert alert-success'>Password berhasil diubah!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Konfirmasi password baru tidak cocok.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Password lama salah.</div>";
    }
}

// --- AMBIL DAFTAR UJIAN SESUAI KELAS SISWA ---
// Siswa hanya dapat mengakses ujian yang mengizinkan kelas mereka
$stmt = $pdo->prepare("SELECT e.*, 
    (SELECT COUNT(*) FROM subjects s WHERE s.exam_id = e.id) as total_subjects_cnt,
    (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.student_id = ? AND es.status = 'finished') as finished_subjects_cnt
FROM exams e
JOIN exam_classes ec ON ec.exam_id = e.id AND ec.class_name = ?
ORDER BY e.start_date DESC");
$stmt->execute([$student_id, $student_class]);
$exams = $stmt->fetchAll();

// === AMBIL SETTING DARI DATABASE ===
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

$bimbel_name = $settings['bimbel_name'] ?? 'BIMBINGAN BELAJAR LITA';
$app_name    = $settings['app_name'] ?? 'CBT Simulation';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT - <?= htmlspecialchars($bimbel_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-teal: #3a797a;
            --mint-green: #a2d9c1;
            --soft-bg: #f0fdf9;
        }
        body {
            background-color: var(--soft-bg);
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-teal) 0%, #4b8b8c 100%);
            box-shadow: 0 4px 15px rgba(58, 121, 122, 0.2);
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
        }
        .navbar-brand {
            white-space: normal;
            max-width: 80%;
            font-size: 1.1rem;
        }
        .card-exam {
            border: 2px solid transparent;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(58, 121, 122, 0.08);
            display: flex;
            flex-direction: column;
        }
        .card-exam .card-body {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }
        .card-exam:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(58, 121, 122, 0.15);
            border-color: var(--mint-green);
        }
        .card-header-exam {
            background-color: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.5rem;
        }
        .btn-countdown {
            background-color: #64748b;
            color: #fff;
            border: none;
            font-variant-numeric: tabular-nums;
        }
        .btn-countdown:disabled {
            opacity: 0.9;
            background-color: #64748b;
            color: #fff;
            border: none;
        }
        .countdown-display {
            font-family: monospace;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        @keyframes pulse-ready {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(58, 121, 122, 0.6); }
            70% { transform: scale(1.02); box-shadow: 0 0 0 10px rgba(58, 121, 122, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(58, 121, 122, 0); }
        }
        .btn-ready-animate {
            animation: pulse-ready 1.8s ease-in-out infinite;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5em 1em;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-start {
            border-radius: 12px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: var(--primary-teal);
            border: none;
        }
        .btn-primary:hover {
            background-color: #2d6161;
            box-shadow: 0 4px 12px rgba(58, 121, 122, 0.3);
        }
        .btn-review {
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.3s;
            background: linear-gradient(135deg, #0369a1, #0284c7);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            width: 100%;
        }
        .btn-review:hover {
            background: linear-gradient(135deg, #075985, #0369a1);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(3, 105, 161, 0.30);
        }
        .progress {
            height: 8px;
            border-radius: 10px;
            background-color: #eef2f3;
        }
        .progress-bar {
            background: linear-gradient(to right, #3fb27f, var(--mint-green));
        }
        .text-primary {
            color: var(--primary-teal) !important;
        }
        .btn-outline-primary {
            color: var(--primary-teal);
            border-color: var(--primary-teal);
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-teal);
            border-color: var(--primary-teal);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-mortarboard-fill me-2"></i>CBT - <?= htmlspecialchars($bimbel_name) ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <span>Halo, <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="bi bi-key me-2"></i>Ganti Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <?= $message ?>
    <?php if (isset($_GET['msg']) && in_array($_GET['msg'], ['exam_finished', 'all_done'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Ujian telah selesai dikerjakan. Terima kasih!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'review_disabled'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>Fitur pembahasan dan review untuk ujian ini belum diaktifkan oleh administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="fw-bold text-dark">Daftar Ujian</h3>
            <p class="text-muted">Pilih ujian yang tersedia pada jadwal yang ditentukan.</p>
        </div>
    </div>

    <div class="row g-4">
        
        <?php 
        if (isset($_SESSION['error_token'])) {
    $message = "<div class='alert alert-danger alert-dismissible fade show'>" . $_SESSION['error_token'] . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    echo $message;
    unset($_SESSION['error_token']);
}
        if (empty($exams)): ?>
            <div class="col-12 text-center py-5">
                <img src="https://placehold.co/200x200/e9ecef/adb5bd?text=No+Data" class="mb-3 rounded-circle opacity-50" width="150">
                <h5 class="text-muted">Belum ada jadwal ujian.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($exams as $exam): ?>
                <?php 
                    $now = date('Y-m-d H:i:s');
                    $start = $exam['start_date'];
                    $end = $exam['end_date'];
                    
                    // --- Progress Calculation (Optimized) ---
                    $totalSubjects = $exam['total_subjects_cnt'];
                    $finishedSubjects = $exam['finished_subjects_cnt'];

                    $progress = ($totalSubjects > 0) ? round(($finishedSubjects / $totalSubjects) * 100) : 0;
                    
                    $status = '';
                    $badgeClass = '';
                    $btnDisabled = false;

                    // Override logic based on progress
                    if ($progress >= 100 && $totalSubjects > 0) {
                        $status = 'Sudah Dikerjakan';
                        $badgeClass = 'bg-primary'; // Blue for completed by user
                         $btnDisabled = true;
                    } elseif ($now < $start) {
                        $status = 'Belum Mulai';
                        $badgeClass = 'bg-warning text-dark';
                        $btnDisabled = true;
                    } elseif ($now > $end) {
                        $status = 'Selesai (Waktu Habis)';
                        $badgeClass = 'bg-danger';
                        $btnDisabled = true;
                    } else {
                        // Ongoing range
                        if ($progress > 0) {
                             $status = 'Sedang Dikerjakan';
                             $badgeClass = 'bg-info text-dark';
                        } else {
                             $status = 'Berlangsung';
                             $badgeClass = 'bg-success';
                        }
                        $btnDisabled = false;
                    }
                ?>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card card-exam h-100 shadow-sm">
                        <div class="card-header-exam d-flex justify-content-between align-items-start">
                            <div style="flex: 1;">
                                <h5 class="fw-bold mb-1 text-primary text-truncate" title="<?= htmlspecialchars($exam['exam_name']) ?>"><?= htmlspecialchars($exam['exam_name']) ?></h5>
                                <span class="badge <?= $badgeClass ?> status-badge" id="badge-exam-<?= $exam['id'] ?>"><?= $status ?></span>
                            </div>
                            <!-- Icon/Decoration -->
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-3 text-secondary">
                                <li class="mb-2">
                                    <i class="bi bi-clock me-2"></i>Mulai: 
                                    <span class="fw-medium text-dark"><?= date('d M Y, H:i', strtotime($start)) ?></span>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-clock-history me-2"></i>Selesai: 
                                    <span class="fw-medium text-dark"><?= date('d M Y, H:i', strtotime($end)) ?></span>
                                </li>
                            </ul>
                            
                            <?php if ($now < $start): ?>
                                <!-- Info Placeholder Saat Ujian Belum Dimulai -->
                                <div class="mb-4 p-3 rounded-3 bg-light border border-light-subtle text-center" id="info-box-<?= $exam['id'] ?>">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-hourglass-split text-warning"></i>
                                        <span class="fw-semibold">Ujian Belum Dimulai</span>
                                    </div>
                                    <div class="small text-secondary">
                                        <?= $totalSubjects > 0 ? "Total: <strong>{$totalSubjects} Mata Pelajaran</strong>" : 'Jadwal telah ditentukan' ?>
                                    </div>
                                </div>
                                <?php if ($totalSubjects > 0): ?>
                                <div class="mb-4 d-none" id="progress-box-<?= $exam['id'] ?>">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Progres Pengerjaan</span>
                                        <span class="fw-bold text-primary">0%</span>
                                    </div>
                                    <div class="progress" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="text-end mt-1">
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            0 dari <?= $totalSubjects ?> Mapel Selesai
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($totalSubjects > 0): ?>
                                <!-- Progress Bar Saat Ujian Sudah Berlangsung / Selesai -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Progres Pengerjaan</span>
                                        <span class="fw-bold text-primary"><?= $progress ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="text-end mt-1">
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <?= $finishedSubjects ?> dari <?= $totalSubjects ?> Mapel Selesai
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-auto d-grid gap-2">
                                <?php if ($progress >= 100 && $totalSubjects > 0): ?>
                                    <button class="btn btn-secondary btn-start disabled" disabled>
                                        <i class='bi bi-check-circle-fill me-2'></i>Sudah Selesai
                                    </button>
                                    <?php if (!empty($exam['allow_review'])): ?>
                                        <a href="review_exam.php?exam_id=<?= $exam['id'] ?>" class="btn-review">
                                            <i class="bi bi-journal-text"></i> Lihat Pembahasan
                                        </a>
                                    <?php endif; ?>
                                <?php elseif ($now < $start): ?>
                                    <button class="btn btn-secondary btn-start disabled btn-countdown"
                                            id="btn-exam-<?= $exam['id'] ?>"
                                            data-exam-id="<?= $exam['id'] ?>"
                                            data-exam-name="<?= htmlspecialchars($exam['exam_name'], ENT_QUOTES) ?>"
                                            data-start-time="<?= date('c', strtotime($start)) ?>"
                                            data-progress="<?= $progress ?>"
                                            disabled>
                                        <i class="bi bi-clock-history me-1"></i> Menunggu Jadwal <span class="countdown-display ms-1">(--:--:--)</span>
                                    </button>
                                <?php elseif ($btnDisabled): ?>
                                    <button class="btn btn-secondary btn-start disabled" disabled>
                                        Ujian Ditutup
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-start" 
                                            onclick="openTokenModal('<?= $exam['id'] ?>', '<?= htmlspecialchars($exam['exam_name']) ?>')">
                                        <?php if ($progress > 0): ?>
                                            <i class="bi bi-play-circle me-1"></i> Lanjutkan Ujian
                                        <?php else: ?>
                                            <i class="bi bi-play-circle me-1"></i> Mulai Ujian
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Ganti Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="tokenModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-key-fill me-2"></i>Token Ujian
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="take_exam.php" method="POST"> 
                <div class="modal-body p-4 text-center">
                    <h6 class="mb-3">Masukkan Token untuk memulai:</h6>
                    <h4 class="fw-bold text-primary mb-4" id="examNameDisplay"></h4>
                    
                    <input type="hidden" name="exam_id" id="examIdInput">
                    
                    <div class="form-floating mb-3">
                        <input type="text" name="token" class="form-control text-center fw-bold fs-3 text-uppercase" 
                               id="tokenInput" placeholder="TOKEN" maxlength="6" autocomplete="off" required>
                        <label for="tokenInput">Ketik Token Di Sini</label>
                    </div>
                    <small class="text-muted d-block">Mintalah token kepada pengawas ujian.</small>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="submit" class="btn btn-lg btn-primary px-5 rounded-pill">
                        MASUK UJIAN <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openTokenModal(examId, examName) {
        document.getElementById('examIdInput').value = examId;
        document.getElementById('examNameDisplay').innerText = examName;
        document.getElementById('tokenInput').value = ''; // Reset input
        
        var myModal = new bootstrap.Modal(document.getElementById('tokenModal'));
        myModal.show();
    }

    // --- COUNTDOWN ENGINE & AUTO-ACTIVATE BUTTON ---
    // Sinkronisasi Waktu Server PHP dengan Browser Siswa
    const serverTimestamp = <?= time() * 1000 ?>;
    const clientTimestamp = Date.now();
    const timeOffset = serverTimestamp - clientTimestamp;

    function getServerNow() {
        return Date.now() + timeOffset;
    }

    function formatCountdown(distanceMs) {
        if (distanceMs <= 0) return '00:00:00';
        
        const totalSeconds = Math.floor(distanceMs / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        const pad = (n) => String(n).padStart(2, '0');
        
        if (days > 0) {
            return `${days}h ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }
        return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
    }

    function activateExam(btn) {
        const examId = btn.getAttribute('data-exam-id');
        const examName = btn.getAttribute('data-exam-name');
        const progress = parseInt(btn.getAttribute('data-progress') || '0', 10);
        
        // Aktifkan Tombol
        btn.classList.remove('btn-secondary', 'disabled', 'btn-countdown');
        btn.classList.add('btn-primary', 'btn-ready-animate');
        btn.removeAttribute('disabled');
        btn.innerHTML = `<i class="bi bi-play-circle me-1"></i> ${progress > 0 ? 'Lanjutkan Ujian' : 'Mulai Ujian'}`;
        btn.onclick = function() {
            openTokenModal(examId, examName);
        };
        
        // Update Badge Status di Header
        const badge = document.getElementById(`badge-exam-${examId}`);
        if (badge) {
            badge.className = 'badge bg-success status-badge';
            badge.textContent = progress > 0 ? 'Sedang Dikerjakan' : 'Berlangsung';
        }
        
        // Sembunyikan Info Belum Dimulai & Munculkan Progress Box jika ada
        const infoBox = document.getElementById(`info-box-${examId}`);
        const progressBox = document.getElementById(`progress-box-${examId}`);
        if (infoBox) infoBox.classList.add('d-none');
        if (progressBox) progressBox.classList.remove('d-none');
    }

    function updateCountdowns() {
        const countdownButtons = document.querySelectorAll('.btn-countdown');
        if (countdownButtons.length === 0) return;
        
        const now = getServerNow();
        
        countdownButtons.forEach(btn => {
            const startTimeStr = btn.getAttribute('data-start-time');
            if (!startTimeStr) return;
            
            const startTime = new Date(startTimeStr).getTime();
            const distance = startTime - now;
            
            if (distance <= 0) {
                activateExam(btn);
            } else {
                const displaySpan = btn.querySelector('.countdown-display');
                if (displaySpan) {
                    displaySpan.textContent = `(${formatCountdown(distance)})`;
                }
            }
        });
    }

    // Jalankan segera saat halaman selesai dimuat, lalu tiap detik
    document.addEventListener('DOMContentLoaded', function() {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    });
</script>

</body>
</html>