<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../config/database.php';

// Validasi otentikasi siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$studentId = $_SESSION['user_id'];
$sessionId = (int)($_GET['session_id'] ?? 0);

if ($sessionId <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Ambil info sesi ujian
$stmt = $pdo->prepare("
    SELECT qs.*, s.name as subject_name, e.exam_name, e.show_score, stu.full_name, stu.class, stu.nis
    FROM exam_sessions qs
    JOIN subjects s ON qs.subject_id = s.id
    JOIN exams e ON qs.exam_id = e.id
    JOIN students stu ON qs.student_id = stu.id
    WHERE qs.id = ? AND qs.student_id = ?
");
$stmt->execute([$sessionId, $studentId]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: dashboard.php");
    exit;
}

$subjectId = (int)$session['subject_id'];
$examId = (int)$session['exam_id'];

// Cek apakah siswa sudah pernah mengisi survei untuk subject ini
$stmtCheck = $pdo->prepare("SELECT id FROM cbt_survey_responden_local WHERE student_id = ? AND subject_id = ? LIMIT 1");
$stmtCheck->execute([$studentId, $subjectId]);
$alreadyFilled = $stmtCheck->fetch();

// Ambil status apakah semua mapel ujian sudah selesai
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE exam_id = ?");
$stmtTotal->execute([$examId]);
$totalSubjects = (int)$stmtTotal->fetchColumn();

$stmtDone = $pdo->prepare("SELECT COUNT(*) FROM exam_sessions WHERE exam_id = ? AND student_id = ? AND status = 'finished'");
$stmtDone->execute([$examId, $studentId]);
$finishedSubjects = (int)$stmtDone->fetchColumn();
$isAllDone = ($finishedSubjects >= $totalSubjects);

// Jika sudah pernah mengisi survei, langsung redirect sesuai aturan
if ($alreadyFilled) {
    if (empty($session['show_score'])) {
        if ($totalSubjects <= 1 || $isAllDone) {
            unset($_SESSION['active_exam']);
            header("Location: dashboard.php?msg=exam_finished");
            exit;
        } else {
            header("Location: take_exam.php");
            exit;
        }
    } else {
        header("Location: exam_result.php?session_id=" . $sessionId);
        exit;
    }
}

// Ambil daftar guru pengampu dan survei untuk mata pelajaran ini
$stmtTeachers = $pdo->prepare("
    SELECT ss.teacher_name, ss.survey_code, csl.judul, csl.payload_json, csl.guru_nip, csl.guru_id_cloud
    FROM subject_surveys ss
    LEFT JOIN cbt_surveys_local csl ON ss.survey_code = csl.kode_akses
    WHERE ss.subject_id = ?
    ORDER BY ss.teacher_name ASC
");
$stmtTeachers->execute([$subjectId]);
$assignedTeachers = $stmtTeachers->fetchAll();

// Jika ternyata tidak ada guru survei untuk mapel ini, langsung lanjut
if (empty($assignedTeachers)) {
    if (empty($session['show_score'])) {
        if ($totalSubjects <= 1 || $isAllDone) {
            unset($_SESSION['active_exam']);
            header("Location: dashboard.php?msg=exam_finished");
            exit;
        } else {
            header("Location: take_exam.php");
            exit;
        }
    } else {
        header("Location: exam_result.php?session_id=" . $sessionId);
        exit;
    }
}

// Ambil setting aplikasi
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
$bimbel_name = $settings['bimbel_name'] ?? 'BIMBINGAN BELAJAR LITA';
$app_name    = $settings['app_name'] ?? 'CBT Simulation';
$bimbel_logo = $settings['bimbel_logo'] ?? 'logo-bimbel.png';
$logo_path   = '../assets/img/' . $bimbel_logo;
$logo_version = (file_exists($logo_path) && is_file($logo_path)) ? '?v=' . filemtime($logo_path) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Evaluasi Pembelajaran - <?= htmlspecialchars($app_name) ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link href="../assets/vendor/fonts/fonts.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/<?= htmlspecialchars($bimbel_logo) . $logo_version ?>">

    <style>
        :root {
            --brand-primary: #1e40af;
            --brand-primary-hover: #1d4ed8;
            --brand-primary-subtle: #eff6ff;
            --brand-accent: #f59e0b;
            --canvas-bg: #f8fafc;
            --surface-card: #ffffff;
            --border-subtle: #e2e8f0;
        }
        body {
            background-color: var(--canvas-bg);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .survey-navbar {
            background: linear-gradient(135deg, #0d2347 0%, #1e3a8a 100%);
            box-shadow: 0 4px 20px rgba(13, 35, 71, 0.25);
            padding: 12px 0;
        }
        .survey-card {
            border-radius: 16px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            background: #ffffff;
            overflow: hidden;
        }
        .teacher-option-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        .teacher-option-card:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.08);
        }
        .teacher-option-card.selected {
            border-color: var(--brand-primary);
            background-color: var(--brand-primary-subtle);
            box-shadow: 0 4px 16px rgba(30, 64, 175, 0.15);
        }

        /* Scale rating 1 - 5 */
        .rating-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .rating-btn {
            flex: 1 1 50px;
            max-width: 90px;
            text-align: center;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 6px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1rem;
            color: #334155;
            background: #ffffff;
            transition: all 0.2s ease;
            user-select: none;
        }
        .rating-btn:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            background: #f0f7ff;
        }
        .rating-btn.active {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.25);
        }
        .question-box {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .scale-label-guide {
            display: flex;
            justify-content: space-between;
            max-width: 480px;
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 6px;
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="survey-navbar text-white mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="../assets/img/<?= htmlspecialchars($bimbel_logo) . $logo_version ?>" alt="Logo" style="height: 38px; object-fit: contain;">
                <div>
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($bimbel_name) ?></h6>
                    <small class="text-white-50" style="font-size: 0.75rem;">Sistem Ujian CBT Terintegrasi SiHonor</small>
                </div>
            </div>
            <div class="text-end d-none d-sm-block">
                <div class="small fw-semibold text-white"><?= htmlspecialchars($session['full_name']) ?></div>
                <small class="text-white-50">Kelas: <?= htmlspecialchars($session['class'] ?: '-') ?></small>
            </div>
        </div>
    </header>

    <main class="container mb-5" style="max-width: 800px;">
        <!-- Banner Sukses Pengerjaan Mapel -->
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center">
            <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
                <i class="bi bi-check-lg fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-success-emphasis">Ujian Selesai: <?= htmlspecialchars($session['subject_name']) ?></h6>
                <div class="small text-secondary">
                    Jawaban ujian Anda telah tersimpan dengan aman. Mohon luangkan 1 menit untuk mengisi kuesioner evaluasi guru pengampu berikut sebelum melanjutkan.
                </div>
            </div>
        </div>

        <div class="survey-card p-4 p-md-5">
            <form id="surveyForm" action="survey_submit.php" method="POST">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <input type="hidden" name="survey_code" id="inputSurveyCode" value="<?= count($assignedTeachers) === 1 ? htmlspecialchars($assignedTeachers[0]['survey_code']) : '' ?>">
                <input type="hidden" name="teacher_name" id="inputTeacherName" value="<?= count($assignedTeachers) === 1 ? htmlspecialchars($assignedTeachers[0]['teacher_name']) : '' ?>">

                <!-- Langkah 1: Pemilihan Guru Pengampu -->
                <div class="mb-4">
                    <label class="fw-bold fs-6 mb-2 text-dark">
                        <i class="bi bi-person-video3 text-primary me-2"></i>
                        <?php if (count($assignedTeachers) > 1): ?>
                            Pilih Guru yang Mengajar di Kelas Anda: <span class="text-danger">*</span>
                        <?php else: ?>
                            Guru Pengampu Mata Pelajaran:
                        <?php endif; ?>
                    </label>

                    <?php if (count($assignedTeachers) > 1): ?>
                        <div class="row g-3 mt-1" id="teacherCardsContainer">
                            <?php foreach ($assignedTeachers as $tIdx => $t): ?>
                                <div class="col-sm-6">
                                    <div class="teacher-option-card d-flex align-items-center justify-content-between" 
                                         data-code="<?= htmlspecialchars($t['survey_code']) ?>"
                                         data-name="<?= htmlspecialchars($t['teacher_name']) ?>"
                                         data-index="<?= $tIdx ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-person-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['teacher_name']) ?></div>
                                                <small class="text-muted">NIP: <?= htmlspecialchars($t['guru_nip'] ?: '-') ?></small>
                                            </div>
                                        </div>
                                        <i class="bi bi-check-circle-fill text-primary fs-5 d-none check-icon"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="teacherSelectError" class="text-danger small mt-2 d-none">
                            <i class="bi bi-exclamation-circle me-1"></i> Silakan pilih guru pengampu Anda terlebih dahulu.
                        </div>
                    <?php else: 
                        $singleTeacher = $assignedTeachers[0];
                    ?>
                        <div class="p-3 bg-light rounded-3 border d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="bi bi-person-badge fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($singleTeacher['teacher_name']) ?></h6>
                                <small class="text-muted">NIP: <?= htmlspecialchars($singleTeacher['guru_nip'] ?: '-') ?> &bull; Kuesioner: <?= htmlspecialchars($singleTeacher['judul'] ?: 'Evaluasi Pembelajaran') ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Langkah 2: Pertanyaan Kuesioner -->
                <div id="surveyQuestionsContainer">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-ui-checks text-primary me-2"></i>Butir Pertanyaan Kuesioner
                    </h5>

                    <!-- Tempat render pertanyaan dinamis berdasarkan guru yang dipilih -->
                    <div id="questionsList"></div>
                </div>

                <!-- Opsi Anonim -->
                <div class="p-3 bg-light rounded-3 border my-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_anonim" id="isAnonim" value="1">
                        <label class="form-check-label text-dark fw-semibold small" for="isAnonim">
                            <i class="bi bi-incognito me-1 text-primary"></i> Kirim sebagai penilaian anonim
                        </label>
                        <div class="text-muted small ms-4">
                            Nama dan identitas Anda tidak akan ditampilkan ke guru pada laporan hasil evaluasi.
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="d-grid mt-4">
                    <button type="submit" id="btnSubmitSurvey" class="btn btn-primary btn-lg fw-bold py-3 shadow">
                        <i class="bi bi-send-check-fill me-2"></i> Kirim Penilaian & Selesaikan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Data Survei JSON untuk dinamisasi di Client-Side -->
    <script>
    const TEACHERS_DATA = <?= json_encode($assignedTeachers, JSON_UNESCAPED_UNICODE) ?>;
    const TOTAL_TEACHERS = <?= count($assignedTeachers) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        if (TOTAL_TEACHERS === 1) {
            renderQuestionsForTeacher(0);
        } else {
            // Pasang event click kartu guru
            const cards = document.querySelectorAll('.teacher-option-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    cards.forEach(c => {
                        c.classList.remove('selected');
                        c.querySelector('.check-icon').classList.add('d-none');
                    });
                    this.classList.add('selected');
                    this.querySelector('.check-icon').classList.remove('d-none');

                    const code = this.getAttribute('data-code');
                    const name = this.getAttribute('data-name');
                    const idx = parseInt(this.getAttribute('data-index'));

                    document.getElementById('inputSurveyCode').value = code;
                    document.getElementById('inputTeacherName').value = name;
                    document.getElementById('teacherSelectError').classList.add('d-none');

                    renderQuestionsForTeacher(idx);
                });
            });

            // Tampilkan info pilih guru jika belum ada
            document.getElementById('questionsList').innerHTML = `
                <div class="text-center py-4 text-muted bg-light rounded-3 border">
                    <i class="bi bi-arrow-up-circle fs-2 d-block mb-2 text-primary"></i>
                    Silakan klik salah satu guru pengampu di atas untuk menampilkan kuesioner.
                </div>
            `;
        }

        // Validasi form saat submit
        document.getElementById('surveyForm').addEventListener('submit', function(e) {
            const code = document.getElementById('inputSurveyCode').value;
            if (!code) {
                e.preventDefault();
                document.getElementById('teacherSelectError').classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return false;
            }

            // Validasi pertanyaan wajib
            let missing = false;
            const mandatoryInputs = document.querySelectorAll('.mandatory-q');
            mandatoryInputs.forEach(box => {
                const qId = box.getAttribute('data-qid');
                const radioChecked = box.querySelector('input[type="radio"]:checked');
                const textarea = box.querySelector('textarea');
                
                if (radioChecked && radioChecked.value) {
                    box.classList.remove('border-danger');
                } else if (textarea && textarea.value.trim()) {
                    box.classList.remove('border-danger');
                } else {
                    box.classList.add('border-danger');
                    missing = true;
                }
            });

            if (missing) {
                e.preventDefault();
                alert('Mohon jawab seluruh butir pertanyaan wajib yang diberi tanda bintang (*) merah.');
                return false;
            }

            const btn = document.getElementById('btnSubmitSurvey');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan jawaban Anda...';
        });
    });

    function renderQuestionsForTeacher(index) {
        const teacher = TEACHERS_DATA[index];
        if (!teacher) return;

        let payload = null;
        try {
            payload = typeof teacher.payload_json === 'string' ? JSON.parse(teacher.payload_json) : teacher.payload_json;
        } catch(e) {
            payload = null;
        }

        const questions = payload && payload.pertanyaan ? payload.pertanyaan : [];
        const container = document.getElementById('questionsList');

        if (questions.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning py-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Kuesioner untuk guru <strong>${teacher.teacher_name}</strong> belum memiliki butir pertanyaan.
                </div>
            `;
            return;
        }

        let html = '';
        questions.forEach((q, idx) => {
            const isMandatory = q.wajib ? true : false;
            const mandatoryClass = isMandatory ? 'mandatory-q' : '';

            html += `
                <div class="question-box ${mandatoryClass}" data-qid="${q.id}">
                    <div class="fw-semibold mb-2">
                        <span class="text-primary fw-bold me-1">${idx + 1}.</span> 
                        ${escapeHtml(q.pertanyaan)}
                        ${isMandatory ? '<span class="text-danger fw-bold ms-1" title="Pertanyaan Wajib">*</span>' : ''}
                    </div>
            `;

            if (q.tipe === 'skala') {
                html += `
                    <div class="rating-group mt-3" data-qid="${q.id}">
                `;
                for (let s = 1; s <= 5; s++) {
                    html += `
                        <input type="radio" class="d-none" name="jawaban[${q.id}]" id="rating_${q.id}_${s}" value="${s}">
                        <label for="rating_${q.id}_${s}" class="rating-btn" onclick="selectRating(this, ${q.id})">
                            ${s}
                        </label>
                    `;
                }
                html += `
                    </div>
                    <div class="scale-label-guide">
                        <span>1 = Sangat Kurang</span>
                        <span>3 = Cukup</span>
                        <span>5 = Sangat Baik</span>
                    </div>
                `;
            } else if (q.tipe === 'teks') {
                html += `
                    <div class="mt-2">
                        <textarea name="jawaban[${q.id}]" class="form-control" rows="3" placeholder="Tuliskan masukan atau tanggapan Anda..." ${isMandatory ? 'required' : ''}></textarea>
                    </div>
                `;
            } else if (q.tipe === 'pilihan' && Array.isArray(q.pilihan_opsi)) {
                html += `<div class="mt-2">`;
                q.pilihan_opsi.forEach((opt, optIdx) => {
                    html += `
                        <div class="form-check py-1">
                            <input class="form-check-input" type="radio" name="jawaban[${q.id}]" id="opt_${q.id}_${optIdx}" value="${escapeHtml(opt)}">
                            <label class="form-check-label small" for="opt_${q.id}_${optIdx}">
                                ${escapeHtml(opt)}
                            </label>
                        </div>
                    `;
                });
                html += `</div>`;
            }

            html += `</div>`;
        });

        container.innerHTML = html;
    }

    function selectRating(labelEl, qId) {
        const group = labelEl.closest('.rating-group');
        group.querySelectorAll('.rating-btn').forEach(btn => btn.classList.remove('active'));
        labelEl.classList.add('active');
        const box = labelEl.closest('.question-box');
        if (box) box.classList.remove('border-danger');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    </script>
</body>
</html>
