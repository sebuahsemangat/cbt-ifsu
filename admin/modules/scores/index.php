<?php
// admin/modules/scores/index.php

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

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

    // Query untuk mengambil data nilai berdasarkan exam_id
    $query = "
        SELECT 
            es.id,
            s.full_name,
            s.class,
            e.exam_name,
            sb.name AS subject_name,
            es.score,
            es.status,
            es.start_time,
            es.end_time
        FROM exam_sessions es
        JOIN students s ON es.student_id = s.id
        JOIN exams e ON es.exam_id = e.id
        JOIN subjects sb ON es.subject_id = sb.id
        WHERE es.exam_id = :exam_id
        ORDER BY es.start_time DESC
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute(['exam_id' => $exam_id]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="d-flex gap-2 align-items-end">
            <a href="modules/scores/export.php?exam_id=<?= $exam_id ?>" class="btn btn-success text-white">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
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
                            <th scope="col" class="text-center" style="width: 60px;">No</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Mata Pelajaran</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Status</th>
                            <th scope="col">Waktu Mengerjakan</th>
                            <th scope="col" class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (count($scores) > 0): ?>
                            <?php foreach ($scores as $index => $row): ?>
                                <tr class="score-row">
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold student-name"><?= htmlspecialchars($row['full_name']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['class']) ?></td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td>
                                        <?php 
                                        $scoreClass = 'text-success';
                                        if ($row['score'] < 50) $scoreClass = 'text-danger';
                                        elseif ($row['score'] < 75) $scoreClass = 'text-warning';
                                        ?>
                                        <span class="fw-bold <?= $scoreClass ?>"><?= number_format($row['score'], 2) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'finished'): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sedang Mengerjakan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d M Y H:i', strtotime($row['start_time'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-danger btn-sm" onclick="deleteScore(<?= $row['id'] ?>, '<?= htmlspecialchars($row['full_name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="noData" style="display: none;">
                            <td colspan="7" class="text-center p-4 text-muted">
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
        const filter = searchInput.value.toLowerCase();
        
        const filteredRows = allRows.filter(row => {
            const name = row.querySelector('.student-name').textContent.toLowerCase();
            return name.includes(filter);
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
    </script>

<?php 
// === TAMPILAN PILIH UJIAN (UTAMA) ===
else: 
    // Ambil daftar ujian yang aktif maupun tidak
    $stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC");
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
                        
                        <p class="card-text text-muted small mb-4">
                            <i class="bi bi-clock me-1"></i> Mulai: <?= date('d M Y H:i', strtotime($exam['start_date'])) ?><br>
                            <i class="bi bi-clock-history me-1"></i> Selesai: <?= date('d M Y H:i', strtotime($exam['end_date'])) ?>
                        </p>

                        <a href="dashboard.php?page=scores&exam_id=<?= $exam['id'] ?>" class="btn btn-primary w-100">
                            <i class="bi bi-eye me-2"></i> Lihat Nilai
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($exams) == 0): ?>
            <div class="col-12 text-center py-5">
                <img src="../assets/img/empty.svg" alt="No Data" class="mb-3" style="max-width: 200px; opacity: 0.5;"> <!-- Placeholder image -->
                <h5 class="text-muted">Belum ada ujian yang dibuat.</h5>
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
