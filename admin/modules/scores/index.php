<?php
// admin/modules/scores/index.php

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Query untuk mengambil data nilai
$query = "
    SELECT 
        es.id,
        s.full_name,
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
    ORDER BY es.start_time DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Data Nilai Siswa</h3>
    <div class="d-flex gap-2">
        <div class="input-group" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari nama siswa...">
        </div>
        <!-- Optional: Button Export -->
        <!-- <a href="#" class="btn btn-success"><i class="bi bi-file-excel"></i> Export Excel</a> -->
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
                        <th scope="col">Ujian</th>
                        <th scope="col">Mata Pelajaran</th>
                        <th scope="col">Nilai</th>
                        <th scope="col">Status</th>
                        <th scope="col">Waktu Mengerjakan</th>
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
                                <td><?= htmlspecialchars($row['exam_name']) ?></td>
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
// Logika Pencarian & Paginasi
const rowsPerPage = 20; // Sesuaikan jumlah baris per halaman
let currentPage = 1;
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('tableBody');
const allRows = Array.from(tableBody.querySelectorAll('.score-row'));
const noDataRow = document.getElementById('noData');
const paginationControls = document.getElementById('paginationControls');

function displayTable() {
    const filter = searchInput.value.toLowerCase();
    
    // Filter baris berdasarkan pencarian (Nama Siswa)
    const filteredRows = allRows.filter(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        // Bisa tambahkan kolom lain jika ingin pencarian lebih luas, misal:
        // const exam = row.children[2].textContent.toLowerCase();
        // return name.includes(filter) || exam.includes(filter);
        return name.includes(filter);
    });

    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    // Sembunyikan semua baris terlebih dahulu
    allRows.forEach(row => row.style.display = 'none');
    noDataRow.style.display = 'none'; // Sembunyikan pesan "tidak ada data"

    if (filteredRows.length === 0) {
        // Jika tidak ada hasil pencarian atau data kosong
        if (allRows.length > 0) {
             noDataRow.style.display = '';
        } else {
             // Case benar-benar table kosong dari DB, sudah dihandle di PHP sebenarnya tapi untuk search safety:
             noDataRow.style.display = '';
             noDataRow.querySelector('td').textContent = 'Data tidak ditemukan.';
        }
        paginationControls.innerHTML = '';
    } else {
        // Tampilkan baris sesuai halaman
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

// Inisialisasi tampilan awal
displayTable();
</script>
