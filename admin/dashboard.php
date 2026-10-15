<?php 
require_once '../config/database.php';
require_once '../includes/auth_check.php';
include '../includes/header.php';

// Ambil parameter page
$page = $_GET['page'] ?? 'main'; 
?>

<div class="container-fluid">
    <?php 
    switch ($page) {
        case 'subjects':
            // Pastikan path ini benar sesuai lokasi file CRUD kamu
            include 'modules/subjects/index.php';
            break;
            
        case 'questions':
            include 'modules/questions/index.php';
            break;
        case 'exams':
            include 'modules/exams/index.php';
            break;
        case 'students':
            include 'modules/students/index.php';
            break;
        case 'admins':
            include 'modules/admins/index.php';
            break;
            
        case 'reports':
            include 'reports.php';
            break;
        case 'scores':
            include 'modules/scores/index.php';
            break;
        case 'settings':
            include 'modules/settings/index.php';
            break;
        case 'references':
            include 'modules/references/index.php';
            break;
        case 'references_form':
            include 'modules/references/form.php';
            break;
        case 'questions':
            include 'modules/questions/index.php';
            break;
        case 'questions_form':
            include 'modules/questions/form.php';
            break;
        case 'questions_import':
            include 'modules/questions/import.php';
            break;
        case 'main':
        default:
            // === QUERY STATISTIK DASHBOARD ===
            $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $total_subjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
            $total_exams = (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
            
            // Siswa sedang mengerjakan ujian
            $total_working = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM exam_sessions WHERE status = 'ongoing'")->fetchColumn();
            // Total siswa login
            $total_logged_in = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_logged_in = 1")->fetchColumn();
            
            // Query data monitoring siswa (status login, status ujian, mapel yang sedang dikerjakan)
            $stmtMonitor = $pdo->query("
                SELECT 
                    s.id AS student_id,
                    s.nis,
                    s.full_name,
                    s.class,
                    s.is_logged_in,
                    s.last_login,
                    es.id AS session_id,
                    es.exam_id,
                    es.subject_id,
                    es.start_time,
                    es.end_time,
                    COALESCE(es.remaining_seconds, TIMESTAMPDIFF(SECOND, NOW(), es.end_time)) AS remaining_seconds,
                    e.exam_name,
                    sb.name AS subject_name,
                    (SELECT COUNT(DISTINCT question_id) FROM student_answers WHERE session_id = es.id AND answer_data IS NOT NULL AND answer_data != '' AND answer_data != 'null') AS answered_count,
                    (SELECT COUNT(*) FROM questions WHERE subject_id = es.subject_id) AS total_questions
                FROM students s
                LEFT JOIN (
                    SELECT es1.*
                    FROM exam_sessions es1
                    INNER JOIN (
                        SELECT student_id, MAX(id) AS max_id
                        FROM exam_sessions
                        WHERE status = 'ongoing'
                        GROUP BY student_id
                    ) es2 ON es1.id = es2.max_id
                ) es ON es.student_id = s.id
                LEFT JOIN exams e ON es.exam_id = e.id
                LEFT JOIN subjects sb ON es.subject_id = sb.id
                ORDER BY 
                    CASE 
                        WHEN es.id IS NOT NULL THEN 1
                        WHEN s.is_logged_in = 1 THEN 2
                        ELSE 3
                    END ASC,
                    s.class ASC,
                    s.full_name ASC
            ");
            $monitor_students = $stmtMonitor->fetchAll(PDO::FETCH_ASSOC);

            // Hitung ringkasan status & siapkan data JSON untuk JS
            $count_working = 0;
            $count_login_only = 0;
            $students_data_for_js = [];
            foreach ($monitor_students as $st) {
                $is_working = !empty($st['session_id']);
                $is_login = ($st['is_logged_in'] == 1);
                $category = $is_working ? 'working' : ($is_login ? 'login' : 'offline');

                if ($is_working) {
                    $count_working++;
                } elseif ($is_login) {
                    $count_login_only++;
                }

                $students_data_for_js[] = [
                    'student_id'        => (int)$st['student_id'],
                    'nis'               => $st['nis'] ?? '',
                    'full_name'         => $st['full_name'] ?? '',
                    'class'             => $st['class'] ?? '-',
                    'is_logged_in'      => (int)$st['is_logged_in'],
                    'session_id'        => $st['session_id'] ? (int)$st['session_id'] : null,
                    'exam_id'           => $st['exam_id'] ? (int)$st['exam_id'] : null,
                    'exam_name'         => $st['exam_name'] ?? null,
                    'subject_id'        => $st['subject_id'] ? (int)$st['subject_id'] : null,
                    'subject_name'      => $st['subject_name'] ?? null,
                    'start_time'        => $st['start_time'] ? date('H:i', strtotime($st['start_time'])) : null,
                    'remaining_seconds' => $st['remaining_seconds'] !== null ? (int)$st['remaining_seconds'] : null,
                    'answered_count'    => $is_working ? (int)($st['answered_count'] ?? 0) : 0,
                    'total_questions'   => $is_working ? (int)($st['total_questions'] ?? 0) : 0,
                    'is_working'        => $is_working,
                    'is_login'          => $is_login,
                    'category'          => $category
                ];
            }
            $count_active = $count_working + $count_login_only;
            ?>

            <style>
            @keyframes spin-cw {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .spin-animation {
                display: inline-block;
                animation: spin-cw 0.7s linear infinite;
            }
            .monitor-table {
                font-size: 0.8125rem;
            }
            .monitor-table th {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                padding: 9px 8px;
                white-space: nowrap;
                background-color: #f8fafc;
            }
            .monitor-table td {
                padding: 6px 8px;
                vertical-align: middle;
            }
            .monitor-table .badge {
                font-size: 0.725rem;
                font-weight: 500;
            }
            .btn-action-icon {
                width: 30px;
                height: 30px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                font-size: 0.9rem;
            }
            </style>


            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Dashboard & Monitoring Ujian</h3>
                    <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> 👋</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="btnManualRefresh" onclick="fetchMonitorData(true);" title="Refresh Data Monitoring (AJAX)">
                        <i class="bi bi-arrow-clockwise" id="refreshIcon"></i> Refresh
                    </button>
                    <a href="modules/students/process.php?action=reset_all_login&redirect=dashboard" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('PERINGATAN: Reset status login SEMUA siswa?\nSemua siswa yang sedang login akan di-logout dari sistem.')">
                        <i class="bi bi-power"></i> Reset Login Semua
                    </a>
                </div>
            </div>

            <!-- KARTU STATISTIK RINGKASAN -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-people text-primary fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold" id="statTotalStudents"><?= $total_students ?></h4>
                                <small class="text-muted">Total Siswa</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-warning">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-pencil-fill text-warning fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold text-warning" id="statWorking"><?= $count_working ?></h4>
                                <small class="text-muted">Sedang Mengerjakan</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 h-100 border-start border-4 border-success">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-person-check-fill text-success fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold text-success" id="statLoginOnly"><?= $count_login_only ?></h4>
                                <small class="text-muted">Hanya Login (Standby)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-journal-check text-info fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold" id="statTotalExams"><?= $total_exams ?></h4>
                                <small class="text-muted">Jadwal Ujian</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL MONITORING STATUS SISWA & UJIAN -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1 text-primary">
                                <i class="bi bi-broadcast me-2 text-danger"></i>Monitoring Status Siswa & Ujian
                            </h5>
                            <p class="text-muted small mb-0">
                                Pantau siswa yang login atau sedang mengerjakan ujian secara langsung.
                                <span id="lastUpdatedTime" class="badge bg-light text-muted border ms-2">Diperbarui: <?= date('H:i:s') ?></span>
                            </p>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div class="form-check form-switch me-2 d-none d-sm-inline-flex align-items-center">
                                <input class="form-check-input me-1" type="checkbox" id="autoRefreshToggle">
                                <label class="form-check-label small text-muted" for="autoRefreshToggle">Auto Refresh (<span id="refreshTimer">30</span>s)</label>
                            </div>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted small"></i></span>
                                <input type="text" id="monitorSearch" class="form-control form-control-sm border-start-0" placeholder="Cari nama, NIS, mapel...">
                            </div>
                        </div>
                    </div>

                    <!-- TAB FILTER -->
                    <div class="d-flex gap-2 mt-3 flex-wrap" id="filterButtons">
                        <button type="button" class="btn btn-sm btn-primary filter-btn active" data-filter="active">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Semua Aktif 
                            <span class="badge bg-white text-dark ms-1" id="badgeActive"><?= $count_active ?></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark filter-btn" data-filter="working">
                            <i class="bi bi-pencil-fill me-1"></i> Sedang Mengerjakan 
                            <span class="badge bg-warning text-dark ms-1" id="badgeWorking"><?= $count_working ?></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="login">
                            <i class="bi bi-check-circle-fill me-1"></i> Hanya Login 
                            <span class="badge bg-success ms-1" id="badgeLogin"><?= $count_login_only ?></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="all">
                            <i class="bi bi-people me-1"></i> Semua Siswa 
                            <span class="badge bg-secondary ms-1" id="badgeAll"><?= count($monitor_students) ?></span>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 monitor-table" id="monitorTable">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="text-center" style="width: 45px;">No</th>
                                    <th scope="col" style="min-width: 170px;">Nama Siswa</th>
                                    <th scope="col" style="width: 100px;">Kelas</th>
                                    <th scope="col" style="width: 150px;">Status</th>
                                    <th scope="col" style="min-width: 160px;">Mata Pelajaran</th>
                                    <th scope="col" class="text-center" style="width: 100px;">Soal</th>
                                    <th scope="col" class="text-center" style="width: 95px;">Waktu Mulai</th>
                                    <th scope="col" class="text-center" style="width: 110px;">Sisa Durasi</th>
                                    <th scope="col" class="text-center" style="width: 85px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="monitorBody">
                                <!-- Baris tabel di-render dinamis oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 py-3">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0" id="monitorPagination"></ul>
                    </nav>
                </div>
            </div>

            <script>
            // Data Monitoring Siswa Awal
            let allStudents = <?= json_encode($students_data_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const rowsPerPage = 20;
            let currentMonitorPage = 1;
            let currentFilter = 'active';

            const monitorSearch = document.getElementById('monitorSearch');
            const monitorBody = document.getElementById('monitorBody');
            const monitorPagination = document.getElementById('monitorPagination');
            const filterBtns = document.querySelectorAll('.filter-btn');

            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function escapeJs(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/\\/g, '\\\\')
                    .replace(/'/g, "\\'")
                    .replace(/"/g, '\\"');
            }

            function renderTable() {
                const keyword = (monitorSearch ? monitorSearch.value : '').toLowerCase().trim();

                const filtered = allStudents.filter(st => {
                    const category = st.category;
                    const isActive = (category === 'working' || category === 'login');

                    // Cek filter tab
                    let matchesFilter = false;
                    if (currentFilter === 'all') {
                        matchesFilter = true;
                    } else if (currentFilter === 'active') {
                        matchesFilter = isActive;
                    } else if (currentFilter === 'working') {
                        matchesFilter = (category === 'working');
                    } else if (currentFilter === 'login') {
                        matchesFilter = (category === 'login');
                    }

                    if (!matchesFilter) return false;

                    // Cek search keyword
                    if (!keyword) return true;

                    const name = (st.full_name || '').toLowerCase();
                    const nis = (st.nis || '').toLowerCase();
                    const studentClass = (st.class || '').toLowerCase();
                    const subject = (st.subject_name || '').toLowerCase();
                    const exam = (st.exam_name || '').toLowerCase();

                    return name.includes(keyword) || nis.includes(keyword) || studentClass.includes(keyword) || subject.includes(keyword) || exam.includes(keyword);
                });

                if (filtered.length === 0) {
                    let emptyMsg = 'Siswa tidak ditemukan.';
                    if (!keyword) {
                        if (currentFilter === 'active') {
                            emptyMsg = 'Saat ini tidak ada siswa yang sedang aktif (login atau mengerjakan ujian).';
                        } else if (currentFilter === 'working') {
                            emptyMsg = 'Tidak ada siswa yang sedang mengerjakan ujian saat ini.';
                        } else if (currentFilter === 'login') {
                            emptyMsg = 'Tidak ada siswa yang berstatus login (standby) saat ini.';
                        } else {
                            emptyMsg = 'Belum ada data siswa terdaftar.';
                        }
                    }
                    monitorBody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center p-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                <span>${emptyMsg}</span>
                            </td>
                        </tr>
                    `;
                    monitorPagination.innerHTML = '';
                    return;
                }

                const totalPages = Math.ceil(filtered.length / rowsPerPage);
                if (currentMonitorPage > totalPages) currentMonitorPage = 1;

                const start = (currentMonitorPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                const pageItems = filtered.slice(start, end);

                let html = '';
                pageItems.forEach((st, idx) => {
                    const number = start + idx + 1;
                    const nameEsc = escapeHtml(st.full_name);
                    const nisEsc = escapeHtml(st.nis);
                    const classEsc = escapeHtml(st.class || '-');
                    const subjEsc = escapeHtml(st.subject_name || 'Mata Pelajaran');
                    const examEsc = escapeHtml(st.exam_name || '-');
                    
                    let statusBadge = '';
                    let mapelCol = '<span class="text-muted fst-italic">-</span>';
                    let soalCol = '<span class="text-muted small">-</span>';
                    let startTimeCol = '<span class="text-muted small">-</span>';
                    let remainingCol = '<span class="text-muted small">-</span>';
                    let actionBtns = '';

                    if (st.is_working) {
                        statusBadge = `
                            <span class="badge bg-warning text-dark px-2 py-1 d-inline-flex align-items-center">
                                <span class="spinner-grow spinner-grow-sm me-1 text-danger" role="status"></span>
                                Mengerjakan
                            </span>
                        `;
                        mapelCol = `
                            <div class="fw-bold text-primary">
                                <i class="bi bi-book me-1"></i> ${subjEsc}
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-card-checklist me-1"></i> ${examEsc}
                            </small>
                        `;

                        // Kolom Progres Soal: misal 15/40
                        const answered = st.answered_count !== undefined ? st.answered_count : 0;
                        const totalQ = st.total_questions !== undefined ? st.total_questions : 0;
                        soalCol = `
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                                ${answered} / ${totalQ}
                            </span>
                        `;

                        // Kolom Waktu Mulai
                        startTimeCol = `
                            <span class="text-secondary fw-semibold">
                                <i class="bi bi-clock me-1 text-muted small"></i>${escapeHtml(st.start_time || '-')}
                            </span>
                        `;

                        // Kolom Sisa Durasi
                        if (st.remaining_seconds !== null) {
                            if (st.remaining_seconds > 0) {
                                let timeFormatted = '';
                                if (st.remaining_seconds >= 3600) {
                                    const remH = Math.floor(st.remaining_seconds / 3600);
                                    const remM = Math.floor((st.remaining_seconds % 3600) / 60);
                                    const remS = st.remaining_seconds % 60;
                                    timeFormatted = String(remH).padStart(2, '0') + ':' + String(remM).padStart(2, '0') + ':' + String(remS).padStart(2, '0');
                                } else {
                                    const remM = Math.floor(st.remaining_seconds / 60);
                                    const remS = st.remaining_seconds % 60;
                                    timeFormatted = String(remM).padStart(2, '0') + ':' + String(remS).padStart(2, '0');
                                }
                                remainingCol = `
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-hourglass-split me-1"></i> ${timeFormatted}
                                    </span>
                                `;
                            } else {
                                remainingCol = `
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Waktu Habis
                                    </span>
                                `;
                            }
                        }

                        // Tombol Aksi: Hanya Icon tanpa Teks (Gembok Terbuka untuk Reset Login)
                        actionBtns = `
                            <button class="btn btn-sm btn-warning text-dark btn-action-icon me-1" 
                                    onclick="finishExam(${st.session_id}, '${escapeJs(st.full_name)}', '${escapeJs(st.subject_name || 'ujian')}')" 
                                    title="Selesaikan Paksa Ujian">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-action-icon" 
                                    onclick="resetLogin(${st.student_id}, '${escapeJs(st.full_name)}')" 
                                    title="Reset Sesi Login (Gembok Terbuka)">
                                <i class="bi bi-unlock-fill"></i>
                            </button>
                        `;
                    } else if (st.is_login) {
                        statusBadge = `
                            <span class="badge bg-success px-2 py-1 d-inline-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1"></i> Login (Standby)
                            </span>
                        `;
                        actionBtns = `
                            <button class="btn btn-sm btn-outline-danger btn-action-icon" 
                                    onclick="resetLogin(${st.student_id}, '${escapeJs(st.full_name)}')" 
                                    title="Reset Sesi Login (Gembok Terbuka)">
                                <i class="bi bi-unlock-fill"></i>
                            </button>
                        `;
                    } else {
                        statusBadge = `
                            <span class="badge bg-light text-muted border px-2 py-1">
                                <i class="bi bi-dash-circle me-1"></i> Offline
                            </span>
                        `;
                        actionBtns = `
                            <button class="btn btn-sm btn-outline-secondary btn-action-icon" 
                                    onclick="resetLogin(${st.student_id}, '${escapeJs(st.full_name)}')" 
                                    title="Reset Flag Login">
                                <i class="bi bi-unlock"></i>
                            </button>
                        `;
                    }

                    html += `
                        <tr>
                            <td class="text-center">${number}</td>
                            <td>
                                <div class="fw-bold text-dark">${nameEsc}</div>
                                <small class="text-muted">NIS: <code>${nisEsc}</code></small>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                                    ${classEsc}
                                </span>
                            </td>
                            <td>${statusBadge}</td>
                            <td>${mapelCol}</td>
                            <td class="text-center">${soalCol}</td>
                            <td class="text-center">${startTimeCol}</td>
                            <td class="text-center">${remainingCol}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    ${actionBtns}
                                </div>
                            </td>
                        </tr>
                    `;
                });

                monitorBody.innerHTML = html;
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                monitorPagination.innerHTML = '';
                if (totalPages <= 1) return;

                for (let i = 1; i <= totalPages; i++) {
                    const li = document.createElement('li');
                    li.className = `page-item ${i === currentMonitorPage ? 'active' : ''}`;
                    li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                    li.addEventListener('click', (e) => {
                        e.preventDefault();
                        currentMonitorPage = i;
                        renderTable();
                    });
                    monitorPagination.appendChild(li);
                }
            }

            // Fungsi Fetch Data Monitoring via AJAX (Tanpa Reload Halaman)
            let isFetching = false;
            function fetchMonitorData(isManual = false) {
                if (isFetching) return;
                isFetching = true;

                const refreshIcon = document.getElementById('refreshIcon');
                if (refreshIcon) refreshIcon.classList.add('spin-animation');

                fetch('ajax_monitor.php', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response error');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Update angka pada kartu ringkasan
                        if (data.stats) {
                            const elTotal = document.getElementById('statTotalStudents');
                            const elWork = document.getElementById('statWorking');
                            const elLog = document.getElementById('statLoginOnly');
                            const elExam = document.getElementById('statTotalExams');

                            if (elTotal) elTotal.textContent = data.stats.total_students;
                            if (elWork) elWork.textContent = data.stats.count_working;
                            if (elLog) elLog.textContent = data.stats.count_login_only;
                            if (elExam) elExam.textContent = data.stats.total_exams;

                            // 2. Update badge tab filter
                            const bActive = document.getElementById('badgeActive');
                            const bWork = document.getElementById('badgeWorking');
                            const bLog = document.getElementById('badgeLogin');
                            const bAll = document.getElementById('badgeAll');

                            if (bActive) bActive.textContent = data.stats.count_active;
                            if (bWork) bWork.textContent = data.stats.count_working;
                            if (bLog) bLog.textContent = data.stats.count_login_only;
                            if (bAll) bAll.textContent = data.stats.count_all;
                        }

                        // 3. Update data siswa & re-render tabel
                        if (Array.isArray(data.students)) {
                            allStudents = data.students;
                            renderTable();
                        }

                        // 4. Update waktu refresh
                        const lastUpdated = document.getElementById('lastUpdatedTime');
                        if (lastUpdated && data.server_time) {
                            lastUpdated.textContent = 'Diperbarui: ' + data.server_time;
                        }
                    }
                })
                .catch(err => {
                    console.error('AJAX Monitor error:', err);
                })
                .finally(() => {
                    isFetching = false;
                    if (refreshIcon) {
                        setTimeout(() => refreshIcon.classList.remove('spin-animation'), 400);
                    }
                    if (isManual) {
                        countdown = 30;
                        if (timerLabel) timerLabel.textContent = countdown;
                    }
                });
            }

            // Event listener tab filter
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    currentFilter = this.dataset.filter;
                    currentMonitorPage = 1;

                    filterBtns.forEach(b => {
                        b.classList.remove('active', 'btn-primary', 'btn-warning', 'btn-success', 'btn-secondary');
                        if (b.dataset.filter === 'active') b.classList.add('btn-outline-primary');
                        else if (b.dataset.filter === 'working') b.classList.add('btn-outline-warning');
                        else if (b.dataset.filter === 'login') b.classList.add('btn-outline-success');
                        else b.classList.add('btn-outline-secondary');
                    });

                    this.classList.add('active');
                    this.classList.remove('btn-outline-primary', 'btn-outline-warning', 'btn-outline-success', 'btn-outline-secondary');
                    if (currentFilter === 'active') this.classList.add('btn-primary');
                    else if (currentFilter === 'working') this.classList.add('btn-warning');
                    else if (currentFilter === 'login') this.classList.add('btn-success');
                    else this.classList.add('btn-secondary');

                    renderTable();
                });
            });

            if (monitorSearch) {
                monitorSearch.addEventListener('input', () => {
                    currentMonitorPage = 1;
                    renderTable();
                });
            }

            // Fungsi Aksi Reset Login
            function resetLogin(studentId, studentName) {
                if (confirm(`Reset status login untuk siswa "${studentName}"?\nSiswa akan di-logout dan dapat login kembali.`)) {
                    window.location.href = `modules/students/process.php?action=reset_login&id=${studentId}&redirect=dashboard`;
                }
            }

            // Fungsi Aksi Selesaikan Ujian
            function finishExam(sessionId, studentName, subjectName) {
                if (confirm(`Apakah Anda yakin ingin MENYELESAIKAN PAKSA ujian "${subjectName}" untuk siswa "${studentName}"?\n\nSistem akan menghitung nilai akhir berdasarkan jawaban yang sudah tersimpan.`)) {
                    window.location.href = `modules/scores/process.php?action=finish&id=${sessionId}&redirect=dashboard`;
                }
            }

            // Fitur Auto Refresh AJAX
            const autoToggle = document.getElementById('autoRefreshToggle');
            const timerLabel = document.getElementById('refreshTimer');
            let refreshInterval = null;
            let countdown = 30;

            if (localStorage.getItem('cbt_auto_refresh') === 'true') {
                if (autoToggle) autoToggle.checked = true;
                startAutoRefresh();
            }

            if (autoToggle) {
                autoToggle.addEventListener('change', function() {
                    if (this.checked) {
                        localStorage.setItem('cbt_auto_refresh', 'true');
                        startAutoRefresh();
                    } else {
                        localStorage.setItem('cbt_auto_refresh', 'false');
                        stopAutoRefresh();
                    }
                });
            }

            function startAutoRefresh() {
                countdown = 30;
                if (timerLabel) timerLabel.textContent = countdown;
                clearInterval(refreshInterval);
                refreshInterval = setInterval(() => {
                    countdown--;
                    if (timerLabel) timerLabel.textContent = countdown;
                    if (countdown <= 0) {
                        countdown = 30;
                        if (timerLabel) timerLabel.textContent = countdown;
                        fetchMonitorData(false); // Ambil data baru secara AJAX (tanpa reload halaman!)
                    }
                }, 1000);
            }

            function stopAutoRefresh() {
                clearInterval(refreshInterval);
                if (timerLabel) timerLabel.textContent = '30';
            }

            // Inisialisasi render awal tabel
            renderTable();
            </script>
            <?php
            break;
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>