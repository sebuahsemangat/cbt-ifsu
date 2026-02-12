<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data siswa
$stmt = $pdo->query("SELECT * FROM students ORDER BY full_name ASC");
$students = $stmt->fetchAll();
$nomor = 0;
?>


<?php if (isset($_GET['status'])): ?>
    <div class="alert alert-<?= in_array($_GET['status'], ['success', 'updated', 'deleted', 'resetted']) ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?php 
        switch($_GET['status']) {
            case 'success': echo "Data siswa berhasil ditambahkan!"; break;
            case 'updated': echo "Data siswa berhasil diperbarui!"; break;
            case 'deleted': echo "Data siswa berhasil dihapus!"; break;
            case 'resetted': echo "Status login berhasil direset!"; break;
            case 'error': echo "Terjadi kesalahan dalam memproses data."; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Data Siswa</h3>
    <div class="d-flex gap-2">
        <div class="input-group" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari nama siswa...">
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSingleModal">
            <i class="bi bi-person-plus"></i> Tambah Siswa
        </button>
        <button class="btn btn-outline-primary" onclick="location.href='modules/students/download_template.php'">
            <i class="bi bi-download"></i> Template
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-excel"></i> Import
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="studentTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th class="px-4">Nama Siswa</th>
                        <th style="cursor: pointer;" id="sortClass">
                            Kelas <i class="bi bi-arrow-down-up ms-1 small text-muted"></i>
                        </th>
                        <th>Username</th>
                        <th>Status Login</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($students as $s): $nomor++; ?>
                    <tr class="student-row">
                        <td class="text-center nomor-urut"><?= $nomor ?></td>
                        <td class="px-4 fw-bold student-name"><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><span class="badge bg-primary student-class"><?= htmlspecialchars($s['class'] ?? '-') ?></span></td>
                        <td><code><?= htmlspecialchars($s['username']) ?></code></td>
                        <td>
                            <?php if ($s['is_logged_in']): ?>
                                <span class="badge bg-warning text-dark">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Offline</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                    data-id="<?= $s['id'] ?>" 
                                    data-name="<?= htmlspecialchars($s['full_name']) ?>" 
                                    data-class="<?= htmlspecialchars($s['class'] ?? '') ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal"
                                    title="Edit Data">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="modules/students/process.php?action=reset_login&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Reset Login">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                            <a href="modules/students/process.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus siswa ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noData" style="display: none;">
                        <td colspan="6" class="text-center p-4 text-muted">Siswa tidak ditemukan.</td>
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

<div class="modal fade" id="addSingleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/students/process.php?action=add_single" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="class" class="form-control" required placeholder="Contoh: XII IPA 1">
                        <small class="text-muted">Username & password otomatis digenerate sistem.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/students/process.php?action=import" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Import Data Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih File Template (.csv)</label>
                        <input type="file" name="file_excel" class="form-control" required accept=".csv">
                        <small class="text-muted">Gunakan file yang diunduh dari tombol "Template" di atas.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Mulai Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/students/process.php?action=edit" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Data Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" name="full_name" id="edit_name" class="form-control" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="class" id="edit_class" class="form-control" required placeholder="Contoh: XII IPA 1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Logika Pencarian & Paginasi Ringan
const rowsPerPage = 10;
let currentPage = 1;
let currentSort = 'none'; // 'asc', 'desc', 'none'
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('tableBody');
const allRows = Array.from(tableBody.querySelectorAll('.student-row'));
const noDataRow = document.getElementById('noData');
const paginationControls = document.getElementById('paginationControls');
const sortClassBtn = document.getElementById('sortClass');

function displayTable() {
    const filter = searchInput.value.toLowerCase();
    const filteredRows = allRows.filter(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        return name.includes(filter);
    });

    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    // Sembunyikan semua baris terlebih dahulu
    allRows.forEach(row => row.style.display = 'none');

    if (filteredRows.length === 0) {
        noDataRow.style.display = '';
        paginationControls.innerHTML = '';
    } else {
        noDataRow.style.display = 'none';
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

sortClassBtn.addEventListener('click', () => {
    const icon = sortClassBtn.querySelector('i');
    
    if (currentSort === 'asc') {
        currentSort = 'desc';
        icon.className = 'bi bi-sort-alpha-down-alt ms-1 small text-primary';
    } else {
        currentSort = 'asc';
        icon.className = 'bi bi-sort-alpha-down ms-1 small text-primary';
    }

    allRows.sort((a, b) => {
        const classA = a.querySelector('.student-class').textContent.trim();
        const classB = b.querySelector('.student-class').textContent.trim();
        
        if (currentSort === 'asc') {
            return classA.localeCompare(classB, undefined, {numeric: true, sensitivity: 'base'});
        } else {
            return classB.localeCompare(classA, undefined, {numeric: true, sensitivity: 'base'});
        }
    });

    // Sesuaikan nomor urut setelah di-sort
    allRows.forEach((row, index) => {
        row.querySelector('.nomor-urut').textContent = index + 1;
        tableBody.appendChild(row); // Re-order in DOM
    });

    currentPage = 1;
    displayTable();
});

// Logika Edit Modal Data
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-btn')) {
        const btn = e.target.closest('.edit-btn');
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_class').value = btn.dataset.class;
    }
});

// Inisialisasi tampilan awal
displayTable();
</script>