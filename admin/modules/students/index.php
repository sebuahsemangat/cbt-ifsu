<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data siswa
$stmt = $pdo->query("SELECT * FROM students ORDER BY class ASC, full_name ASC");
$students = $stmt->fetchAll();
$nomor = 0;

// Ambil daftar kelas untuk filter export
$stmt_classes = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $stmt_classes->fetchAll(PDO::FETCH_COLUMN);

// Ambil daftar kelompok unik yang sudah ada
$stmt_kelompoks = $pdo->query("SELECT DISTINCT kelompok FROM students WHERE kelompok IS NOT NULL AND kelompok != '' ORDER BY kelompok ASC");
$existing_kelompoks = $stmt_kelompoks->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
    .pagination .page-item .page-link {
        color: #334155;
        border-radius: 6px;
        margin: 0 2px;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.825rem;
        border-color: #e2e8f0;
        font-weight: 500;
        transition: all 0.15s ease-in-out;
    }
    .pagination .page-item.active .page-link {
        background-color: var(--brand-primary, #1e40af);
        border-color: var(--brand-primary, #1e40af);
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: var(--brand-primary, #1e40af);
    }
</style>

<?php if (isset($_GET['status'])): ?>
    <div class="alert alert-<?= in_array($_GET['status'], ['success', 'updated', 'deleted', 'resetted', 'pw_resetted', 'all_resetted', 'kelompok_updated']) ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?php 
        switch($_GET['status']) {
            case 'success': echo "Data siswa berhasil ditambahkan!"; break;
            case 'updated': echo "Data siswa berhasil diperbarui!"; break;
            case 'deleted': echo "Data siswa berhasil dihapus!"; break;
            case 'resetted': echo "Status login berhasil direset!"; break;
            case 'pw_resetted': echo "Password siswa berhasil direset ke 123456!"; break;
            case 'all_resetted': echo "Status login semua siswa berhasil direset!"; break;
            case 'kelompok_updated': echo "Kelompok siswa berhasil diperbarui!"; break;
            case 'nis_exists': echo "NIS sudah terdaftar! Gunakan NIS lain."; break;
            case 'error': echo "Terjadi kesalahan dalam memproses data."; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-0">Data Siswa</h3>
        <p class="text-muted small mb-0">Kelola data siswa, kelas, dan penetapan kelompok ujian.</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="width: 200px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted small"></i></span>
            <input type="text" id="searchInput" class="form-control form-control-sm border-start-0" placeholder="Cari nama, NIS, kelas...">
        </div>
        <select id="kelompokFilter" class="form-select form-select-sm" style="width: 170px;">
            <option value="">Semua Kelompok</option>
            <option value="__empty__">Belum Ada Kelompok</option>
            <?php foreach ($existing_kelompoks as $ek): ?>
                <option value="<?= htmlspecialchars($ek) ?>"><?= htmlspecialchars($ek) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSingleModal">
            <i class="bi bi-person-plus"></i> Tambah Siswa
        </button>
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#printCardModal">
            <i class="bi bi-printer"></i> Cetak Kartu
        </button>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportModalStudents">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button class="btn btn-sm btn-outline-primary" onclick="location.href='modules/students/download_template.php'">
            <i class="bi bi-download"></i> Template
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-excel"></i> Import
        </button>
    </div>
</div>

<!-- FLOATING / STICKY BULK ACTION BAR -->
<div id="bulkActionBar" class="alert alert-primary d-none align-items-center justify-content-between p-2 mb-3 shadow-sm rounded-3 border-0 bg-primary bg-opacity-10 text-primary">
    <div class="d-flex align-items-center ps-2">
        <i class="bi bi-check2-square fs-5 text-primary me-2"></i>
        <span class="fw-bold me-2"><span id="selectedCount">0</span> siswa dipilih</span>
        <small class="text-muted d-none d-md-inline">(dari halaman ini maupun filter aktif)</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm" onclick="openBulkKelompokModal()">
            <i class="bi bi-people-fill me-1"></i> Terapkan Kelompok
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllSelections()">
            Batal Pilihan
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="studentTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih Semua di Halaman Ini">
                        </th>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>NIS</th>
                        <th class="px-3">Nama Siswa</th>
                        <th style="cursor: pointer;" id="sortClass">
                            Kelas <i class="bi bi-arrow-down-up ms-1 small text-muted"></i>
                        </th>
                        <th style="cursor: pointer;" id="sortKelompok">
                            Kelompok <i class="bi bi-arrow-down-up ms-1 small text-muted"></i>
                        </th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($students as $s): $nomor++; ?>
                    <tr class="student-row" data-id="<?= $s['id'] ?>">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input student-check" value="<?= $s['id'] ?>">
                        </td>
                        <td class="text-center nomor-urut"><?= $nomor ?></td>
                        <td><code class="student-nis"><?= htmlspecialchars($s['nis']) ?></code></td>
                        <td class="px-3 fw-bold student-name"><?= htmlspecialchars($s['full_name']) ?></td>
                        <td><span class="badge bg-primary student-class"><?= htmlspecialchars($s['class'] ?? '-') ?></span></td>
                        <td>
                            <?php if (!empty($s['kelompok'])): ?>
                                <span class="badge bg-info text-dark student-kelompok fw-semibold">
                                    <i class="bi bi-people me-1"></i><?= htmlspecialchars($s['kelompok']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border student-kelompok">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="modules/students/print_card.php?id=<?= $s['id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak Kartu Ujian Siswa Ini">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="modules/students/process.php?action=reset_password&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info" title="Reset Password ke 123456" onclick="return confirm('Reset password siswa ini menjadi 123456?')">
                                Reset Password
                            </a>
                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                    data-id="<?= $s['id'] ?>" 
                                    data-nis="<?= htmlspecialchars($s['nis']) ?>"
                                    data-name="<?= htmlspecialchars($s['full_name']) ?>" 
                                    data-class="<?= htmlspecialchars($s['class'] ?? '') ?>"
                                    data-kelompok="<?= htmlspecialchars($s['kelompok'] ?? '') ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal"
                                    title="Edit Data">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="modules/students/process.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus siswa ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noData" style="display: none;">
                        <td colspan="7" class="text-center p-4 text-muted">Siswa tidak ditemukan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
            <span>Tampilkan</span>
            <select id="rowsPerPageSelect" class="form-select form-select-sm" style="width: 75px;">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>data per halaman</span>
            <span class="mx-1 text-black-50">|</span>
            <span id="paginationInfo" class="fw-medium text-dark">Menampilkan 0 data</span>
        </div>
        <nav aria-label="Navigasi Halaman Siswa">
            <ul class="pagination pagination-sm mb-0 flex-wrap align-items-center" id="paginationControls"></ul>
        </nav>
    </div>
</div>

<!-- MODAL: BULK TERAPKAN KELOMPOK -->
<div class="modal fade" id="bulkKelompokModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="modules/students/process.php?action=bulk_set_kelompok" method="POST" id="bulkKelompokForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>Terapkan Kelompok Siswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        Mengatur kelompok untuk <strong class="text-primary fs-5" id="modalSelectedCount">0</strong> siswa yang dipilih:
                    </p>
                    
                    <!-- Container input hidden student_ids[] yang di-generate via JS -->
                    <div id="bulkStudentIdsContainer"></div>

                    <div class="mb-3" id="kelompokInputGroup">
                        <label class="form-label fw-bold">Nama Kelompok</label>
                        <input type="text" name="kelompok" id="bulkKelompokInput" class="form-control" list="kelompokDatalist" placeholder="Contoh: Kelompok 1, Sesi A, dsb...">
                        <datalist id="kelompokDatalist">
                            <?php foreach ($existing_kelompoks as $ek): ?>
                                <option value="<?= htmlspecialchars($ek) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted d-block mt-1">Ketik nama kelompok baru atau pilih salah satu dari saran input.</small>
                    </div>

                    <?php if (!empty($existing_kelompoks)): ?>
                    <div class="mb-3" id="existingKelompokGroup">
                        <label class="form-label small text-muted">Pilih cepat dari kelompok yang sudah ada:</label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($existing_kelompoks as $ek): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="setBulkInput('<?= htmlspecialchars(addslashes($ek)) ?>');">
                                    <?= htmlspecialchars($ek) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="clear_kelompok" value="1" id="clearKelompokCheck" onchange="toggleClearKelompok(this)">
                        <label class="form-check-label text-danger fw-semibold" for="clearKelompokCheck">
                            Kosongkan / hapus kelompok dari siswa-siswa yang dipilih
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan & Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: TAMBAH SISWA BARU -->
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
                        <label class="form-label">NIS (Nomor Induk Siswa)</label>
                        <input type="text" name="nis" class="form-control" required placeholder="Contoh: 1001 atau 202601001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="class" class="form-control" required placeholder="Contoh: XII IPA 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelompok (Opsional)</label>
                        <input type="text" name="kelompok" class="form-control" list="kelompokDatalist" placeholder="Contoh: Kelompok 1, Kelompok A...">
                        <small class="text-muted d-block mt-1">Password login default: <strong>123456</strong>.</small>
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

<!-- MODAL: EXPORT DATA SISWA -->
<div class="modal fade" id="exportModalStudents" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/students/export.php" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Export Data Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Kelas</label>
                        <select name="class" class="form-select">
                            <option value="all">Semua Kelas</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Download Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: CETAK KARTU UJIAN -->
<div class="modal fade" id="printCardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="modules/students/print_card.php" method="GET" target="_blank">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-printer me-2"></i>Cetak Kartu Ujian Siswa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Kelas</label>
                        <select name="class" class="form-select">
                            <option value="all">Semua Kelas (Seluruh Siswa)</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">Kelas <?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text mt-2">
                            Pilih kelas yang ingin dicetak kartu ujiannya atau pilih "Semua Kelas" untuk mencetak seluruh data siswa.
                        </div>
                    </div>
                    
                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Dokumen dicetak dalam format <strong>PDF ukuran A4</strong> (memuat 4 kartu per lembar kertas), lengkap dengan logo sekolah, nama & alamat sekolah, data siswa, serta tanda tangan kepala sekolah.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-printer-fill me-1"></i> Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: IMPORT CSV -->
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
                        <small class="text-muted d-block mt-2">
                            Format kolom: <strong>NIS ; Nama Lengkap ; Kelas ; Kelompok (Opsional)</strong>.<br>
                            Gunakan file yang diunduh dari tombol "Template" di atas. Password default otomatis <strong>123456</strong>.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Mulai Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDIT DATA SISWA -->
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
                        <label class="form-label">NIS (Nomor Induk Siswa)</label>
                        <input type="text" name="nis" id="edit_nis" class="form-control" required placeholder="Masukkan NIS">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" name="full_name" id="edit_name" class="form-control" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="class" id="edit_class" class="form-control" required placeholder="Contoh: XII IPA 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kelompok (Opsional)</label>
                        <input type="text" name="kelompok" id="edit_kelompok" class="form-control" list="kelompokDatalist" placeholder="Contoh: Kelompok 1, Kelompok A...">
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
// Logika Pencarian, Filter Kelompok, Paginasi, & Multi-Select Checkbox
let rowsPerPage = 20;
let currentPage = 1;
let currentSortClass = 'none';
let currentSortKelompok = 'none';

const searchInput = document.getElementById('searchInput');
const kelompokFilter = document.getElementById('kelompokFilter');
const tableBody = document.getElementById('tableBody');
const allRows = Array.from(tableBody.querySelectorAll('.student-row'));
const noDataRow = document.getElementById('noData');
const paginationControls = document.getElementById('paginationControls');
const paginationInfo = document.getElementById('paginationInfo');
const rowsPerPageSelect = document.getElementById('rowsPerPageSelect');
const sortClassBtn = document.getElementById('sortClass');
const sortKelompokBtn = document.getElementById('sortKelompok');

const checkAll = document.getElementById('checkAll');
const bulkActionBar = document.getElementById('bulkActionBar');
const selectedCount = document.getElementById('selectedCount');

// Set penyimpan ID siswa yang diceklis (lintas halaman)
const selectedStudentIds = new Set();

// Event listener pilihan baris per halaman
if (rowsPerPageSelect) {
    rowsPerPageSelect.addEventListener('change', function() {
        rowsPerPage = parseInt(this.value, 10) || 20;
        currentPage = 1;
        displayTable();
    });
}

function getFilteredRows() {
    const keyword = searchInput.value.toLowerCase().trim();
    const groupVal = kelompokFilter.value.trim();

    return allRows.filter(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        const nisEl = row.querySelector('.student-nis');
        const nis = nisEl ? nisEl.textContent.toLowerCase() : '';
        const clsEl = row.querySelector('.student-class');
        const cls = clsEl ? clsEl.textContent.toLowerCase() : '';
        const kelEl = row.querySelector('.student-kelompok');
        const kelText = kelEl ? kelEl.textContent.trim() : '';

        // Cek search keyword
        const matchesSearch = !keyword || (name.includes(keyword) || nis.includes(keyword) || cls.includes(keyword) || kelText.toLowerCase().includes(keyword));

        // Cek filter kelompok
        let matchesGroup = true;
        if (groupVal === '__empty__') {
            matchesGroup = (!kelText || kelText === '-');
        } else if (groupVal !== '') {
            matchesGroup = (kelText === groupVal);
        }

        return matchesSearch && matchesGroup;
    });
}

function displayTable() {
    const filteredRows = getFilteredRows();
    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / rowsPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    // Sembunyikan semua baris
    allRows.forEach(row => row.style.display = 'none');

    if (totalItems === 0) {
        noDataRow.style.display = '';
        paginationControls.innerHTML = '';
        if (paginationInfo) {
            paginationInfo.innerHTML = 'Menampilkan <strong>0</strong> siswa' + 
                (allRows.length > 0 ? ` <span class="text-muted">(dari total ${allRows.length} siswa)</span>` : '');
        }
    } else {
        noDataRow.style.display = 'none';
        const start = (currentPage - 1) * rowsPerPage;
        const end = Math.min(start + rowsPerPage, totalItems);
        
        filteredRows.slice(start, end).forEach((row, idx) => {
            row.style.display = '';
            row.querySelector('.nomor-urut').textContent = start + idx + 1;
            
            // Sinkronkan state checkbox & highlight
            const studentId = row.dataset.id;
            const cb = row.querySelector('.student-check');
            if (cb) {
                cb.checked = selectedStudentIds.has(studentId);
                row.classList.toggle('table-primary', cb.checked);
            }
        });
        
        if (paginationInfo) {
            const isFiltered = totalItems < allRows.length;
            paginationInfo.innerHTML = `Menampilkan <strong>${start + 1} - ${end}</strong> dari <strong>${totalItems}</strong> siswa` + 
                (isFiltered ? ` <span class="text-muted fst-italic">(difilter dari ${allRows.length})</span>` : '');
        }
        
        setupPagination(totalPages);
    }

    updateHeaderCheckAll(filteredRows);
    updateBulkBar();
}

// Algoritma Smart Pagination dengan sliding window & ellipsis
function getPaginationItems(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const items = [];
    if (current <= 4) {
        for (let i = 1; i <= 5; i++) items.push(i);
        items.push('...');
        items.push(total);
    } else if (current >= total - 3) {
        items.push(1);
        items.push('...');
        for (let i = total - 4; i <= total; i++) items.push(i);
    } else {
        items.push(1);
        items.push('...');
        items.push(current - 1);
        items.push(current);
        items.push(current + 1);
        items.push('...');
        items.push(total);
    }

    return items;
}

function createPageButton(content, targetPage, isActive = false, isDisabled = false, title = '') {
    const li = document.createElement('li');
    li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
    
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    if (title) a.title = title;
    a.innerHTML = content;

    if (!isDisabled && !isActive) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = targetPage;
            displayTable();
            
            // Scroll halus ke tabel jika posisi scroll di bawah tabel
            const cardEl = document.querySelector('.card.border-0.shadow-sm');
            if (cardEl && cardEl.getBoundingClientRect().top < 0) {
                cardEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
    li.appendChild(a);
    return li;
}

function setupPagination(totalPages) {
    paginationControls.innerHTML = '';
    if (totalPages <= 1) return;

    // Tombol Pertama & Sebelumnya
    paginationControls.appendChild(
        createPageButton('<i class="bi bi-chevron-double-left"></i>', 1, false, currentPage === 1, 'Halaman Pertama (1)')
    );
    paginationControls.appendChild(
        createPageButton('<i class="bi bi-chevron-left"></i>', currentPage - 1, false, currentPage === 1, 'Halaman Sebelumnya')
    );

    // Nomor Halaman dengan Ellipsis
    const items = getPaginationItems(currentPage, totalPages);
    items.forEach(item => {
        if (item === '...') {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link text-muted border-0 bg-transparent px-2">&hellip;</span>';
            paginationControls.appendChild(li);
        } else {
            paginationControls.appendChild(
                createPageButton(item, item, item === currentPage, false, `Halaman ${item}`)
            );
        }
    });

    // Tombol Selanjutnya & Terakhir
    paginationControls.appendChild(
        createPageButton('<i class="bi bi-chevron-right"></i>', currentPage + 1, false, currentPage === totalPages, 'Halaman Selanjutnya')
    );
    paginationControls.appendChild(
        createPageButton('<i class="bi bi-chevron-double-right"></i>', totalPages, false, currentPage === totalPages, `Halaman Terakhir (${totalPages})`)
    );
}

function updateHeaderCheckAll(filteredRows) {
    if (!checkAll) return;
    const start = (currentPage - 1) * rowsPerPage;
    const currentRows = filteredRows.slice(start, start + rowsPerPage);
    
    if (currentRows.length === 0) {
        checkAll.checked = false;
        checkAll.indeterminate = false;
        return;
    }

    const visibleIds = currentRows.map(r => r.dataset.id);
    const selectedVisibleCount = visibleIds.filter(id => selectedStudentIds.has(id)).length;

    if (selectedVisibleCount === visibleIds.length) {
        checkAll.checked = true;
        checkAll.indeterminate = false;
    } else if (selectedVisibleCount > 0) {
        checkAll.checked = false;
        checkAll.indeterminate = true;
    } else {
        checkAll.checked = false;
        checkAll.indeterminate = false;
    }
}

function updateBulkBar() {
    const count = selectedStudentIds.size;
    if (selectedCount) selectedCount.textContent = count;

    if (count > 0) {
        bulkActionBar.classList.remove('d-none');
        bulkActionBar.classList.add('d-flex');
    } else {
        bulkActionBar.classList.remove('d-flex');
        bulkActionBar.classList.add('d-none');
    }
}

// Event listener checkbox baris siswa
tableBody.addEventListener('change', function(e) {
    if (e.target.classList.contains('student-check')) {
        const cb = e.target;
        const row = cb.closest('.student-row');
        const id = cb.value;

        if (cb.checked) {
            selectedStudentIds.add(id);
            if (row) row.classList.add('table-primary');
        } else {
            selectedStudentIds.delete(id);
            if (row) row.classList.remove('table-primary');
        }

        updateHeaderCheckAll(getFilteredRows());
        updateBulkBar();
    }
});

// Event listener Select All di header
if (checkAll) {
    checkAll.addEventListener('change', function() {
        const filtered = getFilteredRows();
        const start = (currentPage - 1) * rowsPerPage;
        const currentRows = filtered.slice(start, start + rowsPerPage);

        currentRows.forEach(row => {
            const id = row.dataset.id;
            const cb = row.querySelector('.student-check');
            if (checkAll.checked) {
                selectedStudentIds.add(id);
                if (cb) cb.checked = true;
                row.classList.add('table-primary');
            } else {
                selectedStudentIds.delete(id);
                if (cb) cb.checked = false;
                row.classList.remove('table-primary');
            }
        });

        checkAll.indeterminate = false;
        updateBulkBar();
    });
}

function clearAllSelections() {
    selectedStudentIds.clear();
    allRows.forEach(row => {
        row.classList.remove('table-primary');
        const cb = row.querySelector('.student-check');
        if (cb) cb.checked = false;
    });
    if (checkAll) {
        checkAll.checked = false;
        checkAll.indeterminate = false;
    }
    updateBulkBar();
}

function openBulkKelompokModal() {
    if (selectedStudentIds.size === 0) {
        alert("Pilih minimal satu siswa terlebih dahulu.");
        return;
    }

    const container = document.getElementById('bulkStudentIdsContainer');
    container.innerHTML = '';

    selectedStudentIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_ids[]';
        input.value = id;
        container.appendChild(input);
    });

    document.getElementById('modalSelectedCount').textContent = selectedStudentIds.size;
    document.getElementById('bulkKelompokInput').value = '';
    const clearCheck = document.getElementById('clearKelompokCheck');
    if (clearCheck) {
        clearCheck.checked = false;
        toggleClearKelompok(clearCheck);
    }

    const modal = new bootstrap.Modal(document.getElementById('bulkKelompokModal'));
    modal.show();
}

function setBulkInput(val) {
    document.getElementById('bulkKelompokInput').value = val;
    const clearCheck = document.getElementById('clearKelompokCheck');
    if (clearCheck) {
        clearCheck.checked = false;
        toggleClearKelompok(clearCheck);
    }
}

function toggleClearKelompok(el) {
    const input = document.getElementById('bulkKelompokInput');
    if (el.checked) {
        input.value = '';
        input.disabled = true;
    } else {
        input.disabled = false;
    }
}

// Event search & filter
searchInput.addEventListener('keyup', () => {
    currentPage = 1;
    displayTable();
});

kelompokFilter.addEventListener('change', () => {
    currentPage = 1;
    displayTable();
});

// Sort Kelas
sortClassBtn.addEventListener('click', () => {
    const icon = sortClassBtn.querySelector('i');
    
    if (currentSortClass === 'asc') {
        currentSortClass = 'desc';
        icon.className = 'bi bi-sort-alpha-down-alt ms-1 small text-primary';
    } else {
        currentSortClass = 'asc';
        icon.className = 'bi bi-sort-alpha-down ms-1 small text-primary';
    }

    allRows.sort((a, b) => {
        const classA = a.querySelector('.student-class').textContent.trim();
        const classB = b.querySelector('.student-class').textContent.trim();
        return currentSortClass === 'asc' 
            ? classA.localeCompare(classB, undefined, {numeric: true, sensitivity: 'base'})
            : classB.localeCompare(classA, undefined, {numeric: true, sensitivity: 'base'});
    });

    allRows.forEach(row => tableBody.appendChild(row));
    currentPage = 1;
    displayTable();
});

// Sort Kelompok
sortKelompokBtn.addEventListener('click', () => {
    const icon = sortKelompokBtn.querySelector('i');
    
    if (currentSortKelompok === 'asc') {
        currentSortKelompok = 'desc';
        icon.className = 'bi bi-sort-alpha-down-alt ms-1 small text-primary';
    } else {
        currentSortKelompok = 'asc';
        icon.className = 'bi bi-sort-alpha-down ms-1 small text-primary';
    }

    allRows.sort((a, b) => {
        const kA = a.querySelector('.student-kelompok').textContent.trim();
        const kB = b.querySelector('.student-kelompok').textContent.trim();
        return currentSortKelompok === 'asc' 
            ? kA.localeCompare(kB, undefined, {numeric: true, sensitivity: 'base'})
            : kB.localeCompare(kA, undefined, {numeric: true, sensitivity: 'base'});
    });

    allRows.forEach(row => tableBody.appendChild(row));
    currentPage = 1;
    displayTable();
});

// Logika Edit Modal Data
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-btn')) {
        const btn = e.target.closest('.edit-btn');
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_nis').value = btn.dataset.nis;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_class').value = btn.dataset.class;
        document.getElementById('edit_kelompok').value = btn.dataset.kelompok || '';
    }
});

// Inisialisasi tampilan awal
displayTable();
</script>