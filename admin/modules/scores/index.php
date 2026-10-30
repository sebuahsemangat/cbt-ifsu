<?php
// admin/modules/scores/index.php

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

// === PESAN NOTIFIKASI ===
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php
// === TAMPILAN DAFTAR NILAI (DETAIL) ===
if ($exam_id):

    // Ambil info ujian
    $stmt_exam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt_exam->execute([$exam_id]);
    $exam = $stmt_exam->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        echo "<div class='alert alert-danger'>Ujian tidak ditemukan.</div>";
        exit;
    }

    // Query untuk mengambil data nilai yang telah final berdasarkan exam_id
    $query = "
        SELECT 
            es.id,
            s.nis,
            s.full_name,
            s.class,
            s.kelompok,
            e.exam_name,
            sb.name AS subject_name,
            sb.max_score,
            es.score,
            es.status,
            es.synced,
            es.synced_at,
            es.start_time,
            es.end_time
        FROM exam_sessions es
        JOIN students s ON es.student_id = s.id
        JOIN exams e ON es.exam_id = e.id
        JOIN subjects sb ON es.subject_id = sb.id
        WHERE es.exam_id = :exam_id AND es.status = 'finished'
        ORDER BY es.end_time DESC, es.start_time DESC
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute(['exam_id' => $exam_id]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil daftar kelas yang ada di ujian ini untuk filter export
        $stmt_classes = $pdo->prepare("
            SELECT DISTINCT s.class 
            FROM exam_sessions es 
            JOIN students s ON es.student_id = s.id 
            WHERE es.exam_id = ? AND es.status = 'finished' AND s.class IS NOT NULL AND s.class != ''
            ORDER BY s.class ASC
        ");
        $stmt_classes->execute([$exam_id]);
        $classes = $stmt_classes->fetchAll(PDO::FETCH_COLUMN);
        if (empty($classes)) {
            $stmt_all_classes = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
            $classes = $stmt_all_classes->fetchAll(PDO::FETCH_COLUMN);
        }

        // Cek apakah ada siswa tanpa kelas
        $stmt_empty_class = $pdo->prepare("
            SELECT 1 
            FROM exam_sessions es 
            JOIN students s ON es.student_id = s.id 
            WHERE es.exam_id = ? AND es.status = 'finished' AND (s.class IS NULL OR s.class = '')
            LIMIT 1
        ");
        $stmt_empty_class->execute([$exam_id]);
        $has_empty_class = (bool)$stmt_empty_class->fetchColumn();

        // Ambil daftar kelompok yang ada di ujian ini untuk filter export
        $stmt_groups = $pdo->prepare("
            SELECT DISTINCT s.kelompok 
            FROM exam_sessions es 
            JOIN students s ON es.student_id = s.id 
            WHERE es.exam_id = ? AND es.status = 'finished' AND s.kelompok IS NOT NULL AND s.kelompok != ''
            ORDER BY s.kelompok ASC
        ");
        $stmt_groups->execute([$exam_id]);
        $groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);
        if (empty($groups)) {
            $stmt_all_groups = $pdo->query("SELECT DISTINCT kelompok FROM students WHERE kelompok IS NOT NULL AND kelompok != '' ORDER BY kelompok ASC");
            $groups = $stmt_all_groups->fetchAll(PDO::FETCH_COLUMN);
        }

        // Cek apakah ada siswa tanpa kelompok
        $stmt_empty_group = $pdo->prepare("
            SELECT 1 
            FROM exam_sessions es 
            JOIN students s ON es.student_id = s.id 
            WHERE es.exam_id = ? AND es.status = 'finished' AND (s.kelompok IS NULL OR s.kelompok = '')
            LIMIT 1
        ");
        $stmt_empty_group->execute([$exam_id]);
        $has_empty_group = (bool)$stmt_empty_group->fetchColumn();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="dashboard.php?page=scores" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Ujian
            </a>
            <h3 class="fw-bold">Nilai: <?= htmlspecialchars($exam['exam_name']) ?></h3>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar-event me-1"></i> 
                <?= date('d M Y H:i', strtotime($exam['start_date'])) ?> - <?= date('d M Y H:i', strtotime($exam['end_date'])) ?>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-end flex-wrap">
            <!-- Tombol Upload ke Cloud -->
            <button class="btn btn-primary shadow-sm" onclick="openUploadCloudModal()" title="Upload Nilai ke CBT Cloud">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Nilai ke Cloud
            </button>

            <!-- Tombol Refresh -->
            <button class="btn btn-outline-primary" onclick="window.location.reload();" title="Refresh Data">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            
            <!-- Tombol Hapus Semua -->
            <button class="btn btn-danger" onclick="deleteAllScores()" title="Hapus Semua Nilai">
                <i class="bi bi-trash-fill"></i> Hapus Semua
            </button>

            <button type="button" class="btn btn-success text-white" onclick="openExportModal()">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </button>
            <div class="input-group" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari nama siswa...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="scoreTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center" style="width: 50px;">No</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Kelompok</th>
                            <th scope="col">Mata Pelajaran</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Waktu Selesai</th>
                            <th scope="col" class="text-center" style="width: 140px;">Status Cloud</th>
                            <th scope="col" class="text-center" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (count($scores) > 0): ?>
                            <?php foreach ($scores as $index => $row): ?>
                                <tr class="score-row">
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold student-name"><?= htmlspecialchars($row['full_name']) ?></div>
                                        <small class="text-muted">NIS: <?= htmlspecialchars($row['nis'] ?? '-') ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($row['class'] ?? '-') ?></span></td>
                                    <td>
                                        <?php if (!empty($row['kelompok'])): ?>
                                            <span class="badge bg-info text-dark fw-semibold"><i class="bi bi-people me-1"></i><?= htmlspecialchars($row['kelompok']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td>
                                        <?php 
                                        $maxScore = $row['max_score'] > 0 ? $row['max_score'] : 100;
                                        $percentage = ($row['score'] / $maxScore) * 100;
                                        $scoreClass = 'text-success';
                                        if ($percentage < 50) $scoreClass = 'text-danger';
                                        elseif ($percentage < 75) $scoreClass = 'text-warning';
                                        ?>
                                        <span class="fw-bold <?= $scoreClass ?>"><?= number_format($row['score'], 2) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= !empty($row['end_time']) ? date('d M Y H:i', strtotime($row['end_time'])) : date('d M Y H:i', strtotime($row['start_time'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['synced'])): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success" title="Tersinkron: <?= htmlspecialchars($row['synced_at'] ?? '-') ?>">
                                                <i class="bi bi-cloud-check-fill me-1"></i> Tersinkron
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border" title="Belum diupload ke cloud">
                                                <i class="bi bi-cloud-arrow-up me-1"></i> Belum Upload
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-danger btn-sm" onclick="deleteScore(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')" title="Hapus Nilai">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="noData" style="display: none;">
                            <td colspan="9" class="text-center p-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0" id="paginationControls"></ul>
            </nav>
        </div>
    </div>

    <script>
    // Logika Pencarian & Paginasi (Sama seperti sebelumnya)
    const rowsPerPage = 20; 
    let currentPage = 1;
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('tableBody');
    const allRows = Array.from(tableBody.querySelectorAll('.score-row'));
    const noDataRow = document.getElementById('noData');
    const paginationControls = document.getElementById('paginationControls');

    function displayTable() {
        const filter = searchInput.value.toLowerCase().trim();
        
        const filteredRows = allRows.filter(row => {
            return row.textContent.toLowerCase().includes(filter);
        });

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        
        allRows.forEach(row => row.style.display = 'none');
        noDataRow.style.display = 'none';

        if (filteredRows.length === 0) {
            if (allRows.length > 0) {
                noDataRow.style.display = '';
            } else {
                noDataRow.style.display = '';
                noDataRow.querySelector('td').textContent = 'Data belum tersedia untuk ujian ini.';
            }
            paginationControls.innerHTML = '';
        } else {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            
            filteredRows.slice(start, end).forEach(row => row.style.display = '');
            setupPagination(totalPages);
        }
    }

    function setupPagination(totalPages) {
        paginationControls.innerHTML = '';
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                displayTable();
            });
            paginationControls.appendChild(li);
        }
    }

    searchInput.addEventListener('keyup', () => {
        currentPage = 1;
        displayTable();
    });

    displayTable();

    // Fungsi untuk menghapus data nilai
    function deleteScore(id, studentName) {
        if (confirm(`Apakah Anda yakin ingin menghapus nilai siswa "${studentName}"?\n\nData yang dihapus tidak dapat dikembalikan!`)) {
            // Redirect ke process.php dengan parameter delete
            window.location.href = 'modules/scores/process.php?action=delete&id=' + id + '&exam_id=<?= $exam_id ?>';
        }
    }


    // Fungsi untuk menghapus semua data nilai pada ujian ini
    function deleteAllScores() {
        const confirmInput = document.getElementById('confirmDeleteInput');
        const btnConfirm = document.getElementById('btnConfirmDeleteAll');
        
        confirmInput.value = '';
        btnConfirm.disabled = true;
        
        // Pastikan event listener terpasang (hanya sekali)
        if (!confirmInput.getAttribute('data-listener')) {
            confirmInput.addEventListener('input', function() {
                btnConfirm.disabled = (this.value.toLowerCase() !== 'hapus');
            });
            confirmInput.setAttribute('data-listener', 'true');
        }

        const myModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
        myModal.show();
    }

    function executeDeleteAll() {
        window.location.href = 'modules/scores/process.php?action=delete_all&exam_id=<?= $exam_id ?>';
    }

    // Fungsi untuk membuka modal export
    function openExportModal() {
        const myModal = new bootstrap.Modal(document.getElementById('exportModal'));
        myModal.show();
    }

    function toggleExportType(type) {
        const classContainer = document.getElementById('filter_class_container');
        const kelompokContainer = document.getElementById('filter_kelompok_container');
        const classSelect = document.getElementById('class_select');
        const kelompokSelect = document.getElementById('kelompok_select');
        
        if (type === 'class') {
            classContainer.style.display = 'block';
            kelompokContainer.style.display = 'none';
            classSelect.disabled = false;
            kelompokSelect.disabled = true;
        } else {
            classContainer.style.display = 'none';
            kelompokContainer.style.display = 'block';
            classSelect.disabled = true;
            kelompokSelect.disabled = false;
        }
    }

    // Fungsi membuka modal upload nilai ke cloud
    function openUploadCloudModal() {
        const modalEl = document.getElementById('uploadCloudModal');
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();

        const loadingDiv = document.getElementById('cloudCheckLoading');
        const contentDiv = document.getElementById('cloudCheckContent');
        const alertContainer = document.getElementById('cloudEligibilityAlert');
        const btnUpload = document.getElementById('btnStartCloudUpload');
        const summaryDiv = document.getElementById('cloudSummaryDetails');

        loadingDiv.style.display = 'block';
        contentDiv.style.display = 'none';
        btnUpload.disabled = true;

        fetch('modules/scores/sync_process.php?action=check_eligibility&exam_id=<?= $exam_id ?>')
            .then(async res => {
                const rawText = await res.text();
                try {
                    return JSON.parse(rawText);
                } catch (e) {
                    const cleanMsg = rawText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                    throw new Error(cleanMsg ? cleanMsg.substring(0, 300) : "Respon server kosong");
                }
            })
            .then(data => {
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';

                if (!data.success) {
                    alertContainer.className = 'alert alert-danger';
                    alertContainer.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + (data.message || 'Gagal memeriksa status.');
                    btnUpload.disabled = true;
                    return;
                }

                if (!data.can_upload) {
                    // Blokir upload
                    let reasonsHtml = '<ul class="mb-0 mt-2 ps-3">';
                    (data.reasons || []).forEach(r => {
                        reasonsHtml += `<li><strong>${r}</strong></li>`;
                    });
                    reasonsHtml += '</ul>';

                    let actionLinks = '<div class="mt-3 d-flex flex-wrap gap-2">';
                    if (data.ongoing_count > 0) {
                        actionLinks += '<a href="dashboard.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-check-circle me-1"></i> Buka Dashboard (Selesaikan Ujian)</a>';
                    }
                    if (data.logged_in_count > 0) {
                        actionLinks += '<a href="dashboard.php?page=students" class="btn btn-sm btn-outline-warning text-dark"><i class="bi bi-box-arrow-right me-1"></i> Buka Halaman Siswa (Reset Login)</a>';
                    }
                    actionLinks += '</div>';

                    alertContainer.className = 'alert alert-danger border-2';
                    alertContainer.innerHTML = `
                        <div class="d-flex align-items-start">
                            <i class="bi bi-shield-x fs-2 text-danger me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-danger">Upload Nilai Diblokir!</h6>
                                <p class="mb-0 small">Nilai belum dapat diunggah ke cloud karena beberapa syarat belum terpenuhi:</p>
                                ${reasonsHtml}
                                ${actionLinks}
                            </div>
                        </div>
                    `;
                    btnUpload.disabled = true;
                } else {
                    // Boleh upload
                    alertContainer.className = 'alert alert-success border-2';
                    alertContainer.innerHTML = `
                        <div class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill fs-2 text-success me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-success">Syarat Terpenuhi & Siap Diupload!</h6>
                                <p class="mb-0 small">Seluruh siswa sudah logout dan seluruh sesi ujian telah selesai (status <code>finished</code>). Data siap disatukan ke server pusat.</p>
                            </div>
                        </div>
                    `;
                    btnUpload.disabled = false;
                }

                // Render ringkasan
                summaryDiv.innerHTML = `
                    <div class="list-group list-group-flush border rounded-3 small">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-globe me-2"></i>Server Cloud Target:</span>
                            <span class="fw-bold text-primary">${data.cloud_url || 'http://cbt.page.gd'}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-people me-2"></i>Siswa Sedang Login:</span>
                            <span class="badge ${data.logged_in_count > 0 ? 'bg-danger' : 'bg-success'}">${data.logged_in_count} Siswa</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-hourglass-split me-2"></i>Sesi Masih Berlangsung:</span>
                            <span class="badge ${data.ongoing_count > 0 ? 'bg-danger' : 'bg-success'}">${data.ongoing_count} Sesi</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-file-earmark-check me-2"></i>Nilai Selesai (Siap Upload):</span>
                            <span class="badge bg-primary fs-6">${data.finished_count} Nilai</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-cloud-arrow-up me-2"></i>Nilai Belum Pernah Upload:</span>
                            <span class="badge ${data.pending_sync_count > 0 ? 'bg-warning text-dark' : 'bg-secondary'}">${data.pending_sync_count} Nilai</span>
                        </div>
                    </div>
                `;
            })
            .catch(err => {
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
                alertContainer.className = 'alert alert-danger';
                alertContainer.innerHTML = '<i class="bi bi-x-circle me-2"></i> Terjadi kesalahan: ' + err.message;
                btnUpload.disabled = true;
            });
    }

    // Fungsi eksekusi upload nilai ke server cloud
    function startUploadScores() {
        const btnUpload = document.getElementById('btnStartCloudUpload');
        const btnCancel = document.getElementById('btnCancelCloudUpload');
        const progressArea = document.getElementById('cloudUploadProgress');
        const alertContainer = document.getElementById('cloudEligibilityAlert');

        btnUpload.disabled = true;
        btnCancel.disabled = true;
        btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah Nilai...';
        progressArea.style.display = 'block';

        const formData = new FormData();
        formData.append('action', 'upload_scores');
        formData.append('exam_id', '<?= $exam_id ?>');

        fetch('modules/scores/sync_process.php', {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const rawText = await res.text();
            try {
                return JSON.parse(rawText);
            } catch (e) {
                const cleanMsg = rawText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(cleanMsg ? cleanMsg.substring(0, 300) : "Respon server kosong");
            }
        })
        .then(data => {
            progressArea.style.display = 'none';
            btnUpload.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Mulai Upload Sekarang';

            if (data.success) {
                alertContainer.className = 'alert alert-success border-2';
                alertContainer.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-2 text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-success">Upload Berhasil!</h6>
                            <p class="mb-0 small">${data.message}</p>
                        </div>
                    </div>
                `;
                btnUpload.style.display = 'none';
                btnCancel.disabled = false;
                btnCancel.textContent = 'Tutup & Segarkan Halaman';
                btnCancel.className = 'btn btn-success px-4';
                btnCancel.onclick = function() {
                    window.location.reload();
                };

                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                btnUpload.disabled = false;
                btnCancel.disabled = false;
                alertContainer.className = 'alert alert-danger border-2';
                alertContainer.innerHTML = `
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Upload Gagal</h6>
                            <p class="mb-0 small">${data.message}</p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            progressArea.style.display = 'none';
            btnUpload.disabled = false;
            btnCancel.disabled = false;
            btnUpload.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Mulai Upload Sekarang';
            alertContainer.className = 'alert alert-danger';
            alertContainer.innerHTML = '<i class="bi bi-x-circle me-2"></i> Error: ' + err.message;
        });
    }
    </script>

    <!-- Modal Konfirmasi Hapus Semua -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-trash-fill me-2"></i>Konfirmasi Hapus Semua</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-3">Tindakan Berbahaya!</h5>
                    <p class="text-muted">
                        Anda akan menghapus <strong>SELURUH</strong> data nilai untuk ujian ini. 
                        Data yang sudah dihapus tidak dapat dikembalikan lagi.
                    </p>
                    <div class="mt-4 text-start">
                        <label class="form-label small fw-bold">Silakan ketik <span class="text-danger italic">"hapus"</span> untuk mengonfirmasi:</label>
                        <input type="text" id="confirmDeleteInput" class="form-control border-2" placeholder="Ketik di sini..." autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 pt-0 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnConfirmDeleteAll" class="btn btn-danger px-4" disabled onclick="executeDeleteAll()">Iya, Hapus Semua Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Export (Berdasarkan Kelas atau Kelompok) -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel me-2"></i>Export Nilai Siswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="modules/scores/export.php" method="GET">
                    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                    <div class="modal-body p-4">
                        <label class="form-label fw-bold mb-2">Pilih Kategori Export:</label>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="export_by" id="export_by_class" value="class" onchange="toggleExportType('class')">
                                <label class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3 rounded-3" for="export_by_class">
                                    <i class="bi bi-mortarboard fs-2 mb-1"></i>
                                    <span class="fw-bold">Berdasarkan Kelas</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="export_by" id="export_by_kelompok" value="kelompok" checked onchange="toggleExportType('kelompok')">
                                <label class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3 rounded-3" for="export_by_kelompok">
                                    <i class="bi bi-people fs-2 mb-1"></i>
                                    <span class="fw-bold">Berdasarkan Kelompok</span>
                                </label>
                            </div>
                        </div>

                        <!-- Filter Kelas -->
                        <div id="filter_class_container" class="mb-3" style="display: none;">
                            <label class="form-label fw-bold">Pilih Kelas</label>
                            <select name="class" id="class_select" class="form-select border-2" disabled>
                                <option value="all">Semua Kelas</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?= htmlspecialchars($cls) ?>">Kelas: <?= htmlspecialchars($cls) ?></option>
                                <?php endforeach; ?>
                                <?php if ($has_empty_class): ?>
                                    <option value="none">Tanpa Kelas (Belum Ditentukan)</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Filter Kelompok -->
                        <div id="filter_kelompok_container" class="mb-3">
                            <label class="form-label fw-bold">Pilih Kelompok</label>
                            <select name="kelompok" id="kelompok_select" class="form-select border-2">
                                <option value="all">Semua Kelompok</option>
                                <?php foreach ($groups as $grp): ?>
                                    <option value="<?= htmlspecialchars($grp) ?>">Kelompok: <?= htmlspecialchars($grp) ?></option>
                                <?php endforeach; ?>
                                <?php if ($has_empty_group): ?>
                                    <option value="none">Tanpa Kelompok (Belum Ditentukan)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 pt-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success px-4 fw-bold">Export Excel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Upload Nilai ke CBT Cloud (cbt.page.gd) -->
    <div class="modal fade" id="uploadCloudModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload Nilai ke CBT Cloud
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btnCloseCloudModalHeader"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading Status -->
                    <div id="cloudCheckLoading" class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                        <h6 class="fw-bold text-dark">Memeriksa Status Siswa & Sesi Ujian...</h6>
                        <p class="text-muted small mb-0">Memastikan seluruh siswa sudah logout dan tidak ada ujian yang masih berjalan.</p>
                    </div>

                    <!-- Content Area -->
                    <div id="cloudCheckContent" style="display: none;">
                        <!-- Alert Kelayakan -->
                        <div id="cloudEligibilityAlert" class="alert alert-info"></div>

                        <!-- Progress Bar saat upload -->
                        <div id="cloudUploadProgress" class="my-3" style="display: none;">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Mengunggah paket data nilai & lembar jawaban siswa...</span>
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                            </div>
                        </div>

                        <!-- Ringkasan Info -->
                        <div class="mt-3">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle me-1"></i> Ringkasan Ujian</h6>
                            <div id="cloudSummaryDetails"></div>
                        </div>

                        <div class="alert alert-light border small text-muted mt-3 mb-0">
                            <i class="bi bi-shield-lock me-1"></i> <strong>Keamanan Multi-Lab:</strong>
                            Data nilai dan jawaban diidentifikasi berdasarkan NIS siswa dan nama ujian. Jika diunggah ulang, data di server cloud akan diperbarui (update) tanpa membuat duplikasi baris.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 pt-0 justify-content-between">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" id="btnCancelCloudUpload">Tutup</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" id="btnStartCloudUpload" onclick="startUploadScores()" disabled>
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Mulai Upload Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php 
// === TAMPILAN PILIH UJIAN (UTAMA) ===
else: 
    // Ambil daftar ujian beserta statistik nilai selesai dan status sinkronisasi cloud
    $stmt = $pdo->query("
        SELECT 
            e.*,
            (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id AND status = 'finished') AS total_finished,
            (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id AND status = 'finished' AND (synced = 0 OR synced IS NULL)) AS total_pending_sync
        FROM exams e 
        ORDER BY e.created_at DESC
    ");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Pilih Ujian</h3>
    </div>

    <div class="row g-4">
        <?php foreach ($exams as $exam): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title fw-bold text-primary mb-0"><?= htmlspecialchars($exam['exam_name']) ?></h5>
                            <?php if ($exam['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non-Aktif</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="card-text text-muted small mb-3">
                            <i class="bi bi-clock me-1"></i> Mulai: <?= date('d M Y H:i', strtotime($exam['start_date'])) ?><br>
                            <i class="bi bi-clock-history me-1"></i> Selesai: <?= date('d M Y H:i', strtotime($exam['end_date'])) ?>
                        </p>

                        <div class="d-flex justify-content-between align-items-center mb-4 p-2 bg-light rounded-3 small">
                            <span class="fw-semibold text-dark"><i class="bi bi-person-check me-1"></i> <?= (int)$exam['total_finished'] ?> Selesai</span>
                            <?php if ($exam['total_pending_sync'] > 0): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-cloud-arrow-up me-1"></i><?= $exam['total_pending_sync'] ?> Belum Upload</span>
                            <?php elseif ($exam['total_finished'] > 0): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-cloud-check-fill me-1"></i>Tersinkron</span>
                            <?php else: ?>
                                <span class="text-muted small">Belum ada nilai</span>
                            <?php endif; ?>
                        </div>

                        <a href="dashboard.php?page=scores&exam_id=<?= $exam['id'] ?>" class="btn btn-primary w-100">
                            <i class="bi bi-eye me-2"></i> Lihat Nilai
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($exams) == 0): ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3 text-muted opacity-50">
                    <i class="bi bi-journal-x" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted fw-semibold">Belum ada ujian yang dibuat.</h5>
                <p class="text-muted small">Data nilai akan ditampilkan di sini setelah jadwal ujian dibuat.</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .transition {
            transition: all 0.3s ease;
        }
    </style>

<?php endif; ?>
