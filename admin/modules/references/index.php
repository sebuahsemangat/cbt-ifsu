<?php
// Ambil query parameter
$filter_subject = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int) $_GET['subject_id'] : null;
$filter_type = isset($_GET['type']) && in_array($_GET['type'], ['text', 'image', 'sound']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page_num = max(1, (int) ($_GET['p'] ?? 1));
$per_page = 10; // Jumlah item per halaman

// Ambil semua daftar Mapel lengkap dengan nama Ujian untuk dropdown filter
$stmt_subs = $pdo->query("
    SELECT s.id, s.name, s.exam_id, COALESCE(e.exam_name, 'Tanpa Ujian') AS exam_name 
    FROM subjects s 
    LEFT JOIN exams e ON s.exam_id = e.id 
    ORDER BY e.exam_name ASC, s.name ASC
");
$all_subjects = $stmt_subs->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan mapel berdasarkan ujian
$grouped_subjects = [];
foreach ($all_subjects as $s) {
    $examName = $s['exam_name'] ?: 'Tanpa Ujian';
    $grouped_subjects[$examName][] = $s;
}

// Statistik umum untuk summary cards
$stats_total = (int) $pdo->query("SELECT COUNT(*) FROM `references`")->fetchColumn();
$stats_text = (int) $pdo->query("SELECT COUNT(*) FROM `references` WHERE type = 'text'")->fetchColumn();
$stats_image = (int) $pdo->query("SELECT COUNT(*) FROM `references` WHERE type = 'image'")->fetchColumn();
$stats_used = (int) $pdo->query("SELECT COUNT(DISTINCT reference_id) FROM questions WHERE reference_id IS NOT NULL")->fetchColumn();

// Bangun WHERE clause dinamis
$where = [];
$params = [];

if ($filter_subject !== null) {
    $where[] = "r.subject_id = ?";
    $params[] = $filter_subject;
}

if ($filter_type !== '') {
    $where[] = "r.type = ?";
    $params[] = $filter_type;
}

if ($search !== '') {
    $where[] = "(r.title LIKE ? OR r.content LIKE ? OR s.name LIKE ? OR e.exam_name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// Hitung total item yang sesuai filter
$count_sql = "SELECT COUNT(*) FROM `references` r 
              JOIN subjects s ON r.subject_id = s.id 
              LEFT JOIN exams e ON s.exam_id = e.id" . $where_sql;
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_items = (int) $stmt_count->fetchColumn();

// Hitung total halaman & offset
$total_pages = max(1, (int) ceil($total_items / $per_page));
if ($page_num > $total_pages) {
    $page_num = $total_pages;
}
$offset = ($page_num - 1) * $per_page;

// Query data referensi dengan pagination
$data_sql = "SELECT r.*, s.name as subject_name, COALESCE(e.exam_name, 'Tanpa Ujian') as exam_name,
             (SELECT COUNT(*) FROM questions q WHERE q.reference_id = r.id) as question_count
             FROM `references` r 
             JOIN subjects s ON r.subject_id = s.id 
             LEFT JOIN exams e ON s.exam_id = e.id
             $where_sql
             ORDER BY r.id DESC 
             LIMIT $per_page OFFSET $offset";
$stmt_data = $pdo->prepare($data_sql);
$stmt_data->execute($params);
$references = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// Helper function untuk menjaga parameter query saat pagination / filter
function refQueryUrl($newParams = [])
{
    $params = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return 'dashboard.php?' . http_build_query($params);
}

// Algoritma sliding window pagination agar tidak menampilkan semua angka
function getSlidingPagination($current, $total, $delta = 2)
{
    if ($total <= 1)
        return [];
    if ($total <= 7)
        return range(1, $total);

    $range = [];
    $left = max(2, $current - $delta);
    $right = min($total - 1, $current + $delta);

    $range[] = 1;
    if ($left > 2) {
        $range[] = '...';
    }

    for ($i = $left; $i <= $right; $i++) {
        $range[] = $i;
    }

    if ($right < $total - 1) {
        $range[] = '...';
    }
    $range[] = $total;

    return $range;
}

$pagination_pages = getSlidingPagination($page_num, $total_pages, 2);
?>

<div class="references-module">
    <!-- Header Page -->
    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Bank Referensi Soal
            </h3>
            <p class="text-muted mb-0 small">
                Kelola stimulus teks bacaan, infografis, dan gambar pendukung untuk soal ujian.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php?page=references_form"
                class="btn btn-primary d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-plus-circle-fill me-2"></i> Tambah Referensi
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4"
            role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success me-2"></i>
            <div>Data referensi berhasil disimpan dan diperbarui!</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4"
            role="alert">
            <i class="bi bi-trash-fill fs-5 text-warning me-2"></i>
            <div>Referensi berhasil dihapus dari sistem.</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_unique'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4"
            role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-danger me-2"></i>
            <div>Nama referensi sudah pernah digunakan pada mapel tersebut! Silakan gunakan nama lain.</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="dashboard.php" id="filterForm">
                <input type="hidden" name="page" value="references">
                <div class="row g-2 align-items-center">
                    <!-- Search Input -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0"
                                placeholder="Cari judul, isi teks, mapel, atau ujian..."
                                value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                            <?php if ($search !== ''): ?>
                                <a href="<?= refQueryUrl(['search' => null, 'p' => 1]) ?>"
                                    class="input-group-text bg-white text-muted text-decoration-none border-start-0"
                                    title="Hapus pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Filter Mata Pelajaran dengan Info Nama Ujian -->
                    <div class="col-12 col-md-4">
                        <select name="subject_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Mata Pelajaran & Ujian</option>
                            <?php foreach ($grouped_subjects as $examName => $subs): ?>
                                <optgroup label="📋 Ujian: <?= htmlspecialchars($examName) ?>">
                                    <?php foreach ($subs as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $filter_subject === (int) $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['name']) ?> — (<?= htmlspecialchars($examName) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Tipe -->
                    <div class="col-6 col-md-2">
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="" <?= $filter_type === '' ? 'selected' : '' ?>>Semua Tipe</option>
                            <option value="text" <?= $filter_type === 'text' ? 'selected' : '' ?>>Teks Bacaan</option>
                            <option value="image" <?= $filter_type === 'image' ? 'selected' : '' ?>>Gambar</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-1"></i> Terapkan
                        </button>
                        <?php if ($search !== '' || $filter_subject !== null || $filter_type !== ''): ?>
                            <a href="dashboard.php?page=references" class="btn btn-outline-secondary"
                                title="Reset Semua Filter">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Filter Tags (if any) -->
                <?php if ($search !== '' || $filter_subject !== null || $filter_type !== ''): ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                        <small class="text-muted fw-semibold me-1"><i class="bi bi-funnel me-1"></i>Filter Aktif:</small>

                        <?php if ($search !== ''): ?>
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center py-1 px-2">
                                Kata Kunci: "<?= htmlspecialchars($search) ?>"
                                <a href="<?= refQueryUrl(['search' => null, 'p' => 1]) ?>"
                                    class="text-secondary ms-2 text-decoration-none">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            </span>
                        <?php endif; ?>

                        <?php if ($filter_subject !== null):
                            $matchedSub = null;
                            foreach ($all_subjects as $sub) {
                                if ((int) $sub['id'] === $filter_subject) {
                                    $matchedSub = $sub;
                                    break;
                                }
                            }
                            ?>
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center py-1 px-2">
                                Mapel:
                                <?= htmlspecialchars($matchedSub ? $matchedSub['name'] . ' (' . $matchedSub['exam_name'] . ')' : 'ID ' . $filter_subject) ?>
                                <a href="<?= refQueryUrl(['subject_id' => null, 'p' => 1]) ?>"
                                    class="text-secondary ms-2 text-decoration-none">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            </span>
                        <?php endif; ?>

                        <?php if ($filter_type !== ''): ?>
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center py-1 px-2">
                                Tipe: <?= $filter_type === 'text' ? 'Teks Bacaan' : 'Gambar/Diagram' ?>
                                <a href="<?= refQueryUrl(['type' => null, 'p' => 1]) ?>"
                                    class="text-secondary ms-2 text-decoration-none">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            </span>
                        <?php endif; ?>

                        <a href="dashboard.php?page=references"
                            class="badge bg-secondary text-white text-decoration-none py-1 px-2">
                            Reset Semua
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Info Bar / Counter -->
    <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
        <div>
            <?php if ($total_items > 0): ?>
                Menampilkan <strong><?= $offset + 1 ?></strong> -
                <strong><?= min($offset + $per_page, $total_items) ?></strong> dari total
                <strong><?= number_format($total_items) ?></strong> referensi
            <?php else: ?>
                Menampilkan 0 referensi
            <?php endif; ?>
        </div>
        <div class="d-none d-md-block text-muted">
            Halaman <?= $page_num ?> dari <?= $total_pages ?>
        </div>
    </div>

    <!-- References Cards Grid -->
    <div class="row g-4 mb-4">
        <?php foreach ($references as $r): ?>
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-3 reference-card">
                    <!-- Card Top Header -->
                    <div class="card-header bg-white border-0 pt-3 px-3 pb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span
                                    class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1"
                                    style="font-size: 0.75rem;">
                                    <i class="bi bi-book me-1"></i><?= htmlspecialchars($r['subject_name']) ?>
                                </span>
                                <span class="badge bg-light text-secondary border px-2 py-1 text-truncate"
                                    style="font-size: 0.72rem; max-width: 170px;"
                                    title="Ujian: <?= htmlspecialchars($r['exam_name']) ?>">
                                    <i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($r['exam_name']) ?>
                                </span>
                            </div>
                            <div>
                                <?php if ($r['type'] === 'text'): ?>
                                    <span class="badge bg-light text-primary border" style="font-size: 0.72rem;">
                                        <i class="bi bi-file-earmark-text me-1"></i>Teks
                                    </span>
                                <?php elseif ($r['type'] === 'image'): ?>
                                    <span class="badge bg-light text-info border" style="font-size: 0.72rem;">
                                        <i class="bi bi-image me-1"></i>Gambar
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">
                                        <i class="bi bi-file-earmark me-1"></i>Media
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reference Title -->
                        <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?= htmlspecialchars($r['title']) ?>">
                            <?= htmlspecialchars($r['title']) ?>
                        </h6>
                    </div>

                    <!-- Card Body Content Preview -->
                    <div class="card-body px-3 py-2">
                        <?php if ($r['type'] === 'text'): ?>
                            <div class="ref-text-preview rounded-3 p-3 bg-light border position-relative"
                                style="max-height: 140px; overflow: hidden;">
                                <div class="small text-muted line-clamp-preview">
                                    <?= $r['content'] ?>
                                </div>
                                <div class="preview-gradient-overlay"></div>
                            </div>
                            <button type="button"
                                class="btn btn-sm btn-link text-decoration-none text-primary px-0 py-1 small fw-semibold mt-1 d-inline-flex align-items-center"
                                data-bs-toggle="modal" data-bs-target="#previewModal<?= $r['id'] ?>">
                                <i class="bi bi-arrows-fullscreen me-1"></i> Baca Selengkapnya
                            </button>
                        <?php elseif ($r['type'] === 'image'): ?>
                            <div class="ref-image-preview rounded-3 bg-light border text-center p-2 d-flex align-items-center justify-content-center"
                                style="height: 140px; cursor: pointer; overflow: hidden;" data-bs-toggle="modal"
                                data-bs-target="#previewModal<?= $r['id'] ?>" title="Klik untuk memperbesar gambar">
                                <?php if (!empty($r['image_path']) && file_exists("../assets/uploads/references/" . $r['image_path'])): ?>
                                    <img src="../assets/uploads/references/<?= htmlspecialchars($r['image_path']) ?>"
                                        class="img-fluid rounded mh-100 object-fit-contain"
                                        alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="text-muted small">
                                        <i class="bi bi-image text-secondary fs-3 d-block mb-1"></i>
                                        File gambar tidak ditemukan
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button"
                                class="btn btn-sm btn-link text-decoration-none text-primary px-0 py-1 small fw-semibold mt-1 d-inline-flex align-items-center"
                                data-bs-toggle="modal" data-bs-target="#previewModal<?= $r['id'] ?>">
                                <i class="bi bi-zoom-in me-1"></i> Perbesar Gambar
                            </button>
                        <?php else: ?>
                            <div class="p-3 bg-light rounded-3 text-muted small text-center">
                                Format media tidak didukung untuk pratinjau langsung.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="card-footer bg-white border-0 pt-1 pb-3 px-3">
                        <div class="d-flex justify-content-between align-items-center border-top pt-2">
                            <!-- Left: Usage Status -->
                            <div class="d-flex align-items-center">
                                <?php if ((int) $r['question_count'] > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"
                                        style="font-size: 0.72rem;" title="Jumlah soal yang menggunakan stimulus ini">
                                        <i class="bi bi-link-45deg me-1"></i><?= $r['question_count'] ?> Soal Terhubung
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"
                                        style="font-size: 0.72rem;" title="Belum ada soal yang memakai referensi ini">
                                        <i class="bi bi-dash-circle me-1"></i>Belum Terpakai
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Action Buttons -->
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                                    data-bs-target="#previewModal<?= $r['id'] ?>" title="Pratinjau Stimulus">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="dashboard.php?page=references_form&id=<?= $r['id'] ?>"
                                    class="btn btn-outline-primary" title="Edit Referensi">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="modules/references/process.php?action=delete&id=<?= $r['id'] ?>"
                                    class="btn btn-outline-danger" title="Hapus Referensi"
                                    onclick="return confirm('<?= (int) $r['question_count'] > 0 ? 'PERINGATAN: Referensi ini sedang terhubung dengan ' . $r['question_count'] . ' butir soal! Menghapusnya akan memutus kaitan soal dengan stimulus ini. Lanjutkan hapus?' : 'Apakah Anda yakin ingin menghapus referensi ini?' ?>')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Pratinjau Lengkap -->
            <div class="modal fade" id="previewModal<?= $r['id'] ?>" tabindex="-1"
                aria-labelledby="previewModalLabel<?= $r['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom pb-3">
                            <div class="me-2">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                        <?= htmlspecialchars($r['subject_name']) ?>
                                    </span>
                                    <span class="badge bg-light text-secondary border">
                                        <?= htmlspecialchars($r['exam_name']) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <?= $r['type'] === 'text' ? 'Teks Bacaan' : 'Gambar / Diagram' ?>
                                    </span>
                                </div>
                                <h5 class="modal-title fw-bold text-dark" id="previewModalLabel<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['title']) ?>
                                </h5>
                            </div>
                            <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 bg-light bg-opacity-50">
                            <?php if ($r['type'] === 'text'): ?>
                                <div class="bg-white p-4 rounded-3 border shadow-xs fs-6 text-dark leading-relaxed content-math-render"
                                    style="line-height: 1.75;">
                                    <?= $r['content'] ?>
                                </div>
                            <?php elseif ($r['type'] === 'image'): ?>
                                <div class="text-center bg-white p-3 rounded-3 border shadow-xs">
                                    <?php if (!empty($r['image_path']) && file_exists("../assets/uploads/references/" . $r['image_path'])): ?>
                                        <img src="../assets/uploads/references/<?= htmlspecialchars($r['image_path']) ?>"
                                            class="img-fluid rounded" alt="<?= htmlspecialchars($r['title']) ?>"
                                            style="max-height: 550px;">
                                        <div class="mt-3">
                                            <a href="../assets/uploads/references/<?= htmlspecialchars($r['image_path']) ?>"
                                                target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Gambar di Tab Baru
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-danger py-4 mb-0">File gambar tidak ditemukan pada server.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3 d-flex justify-content-between align-items-center text-muted small">
                                <div>
                                    <i class="bi bi-link-45deg me-1"></i>
                                    Digunakan oleh <strong><?= (int) $r['question_count'] ?></strong> soal
                                </div>
                                <div>
                                    <i class="bi bi-clock me-1"></i> Dibuat:
                                    <?= date('d M Y, H:i', strtotime($r['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-white">
                            <a href="dashboard.php?page=references_form&id=<?= $r['id'] ?>"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit Referensi Ini
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Empty State jika tidak ada data -->
        <?php if (empty($references)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 py-5 text-center bg-white">
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="rounded-circle p-3 bg-light text-muted d-inline-flex">
                                <i class="bi bi-journal-x fs-1"></i>
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark">Data Referensi Tidak Ditemukan</h5>
                        <?php if ($search !== '' || $filter_subject !== null || $filter_type !== ''): ?>
                            <p class="text-muted small mb-3">
                                Tidak ada referensi yang cocok dengan kriteria pencarian atau filter yang dipilih.
                            </p>
                            <a href="dashboard.php?page=references" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Semua Filter
                            </a>
                        <?php else: ?>
                            <p class="text-muted small mb-3">
                                Belum ada stimulus atau referensi bacaan yang ditambahkan ke sistem.
                            </p>
                            <a href="dashboard.php?page=references_form" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Referensi Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination (Sliding Window / Tidak menampilkan semua angka) -->
    <?php if ($total_pages > 1): ?>
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="text-muted small order-2 order-md-1">
                    Menampilkan data ke-<strong><?= $offset + 1 ?></strong> hingga
                    <strong><?= min($offset + $per_page, $total_items) ?></strong> dari total
                    <strong><?= number_format($total_items) ?></strong>
                </div>

                <nav aria-label="Pagination Referensi" class="order-1 order-md-2">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <!-- First & Previous Page Button -->
                        <li class="page-item <?= ($page_num <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= refQueryUrl(['p' => 1]) ?>" title="Halaman Pertama">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page_num <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= refQueryUrl(['p' => max(1, $page_num - 1)]) ?>"
                                title="Halaman Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Sliding Window Page Numbers -->
                        <?php foreach ($pagination_pages as $p): ?>
                            <?php if ($p === '...'): ?>
                                <li class="page-item disabled">
                                    <span class="page-link border-0 text-muted">…</span>
                                </li>
                            <?php elseif ($p == $page_num): ?>
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link fw-bold"><?= $p ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= refQueryUrl(['p' => $p]) ?>"><?= $p ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Next & Last Page Button -->
                        <li class="page-item <?= ($page_num >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= refQueryUrl(['p' => min($total_pages, $page_num + 1)]) ?>"
                                title="Halaman Selanjutnya">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page_num >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= refQueryUrl(['p' => $total_pages]) ?>" title="Halaman Terakhir">
                                <i class="bi bi-chevron-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .reference-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
    }

    .reference-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
    }

    .ref-text-preview {
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .ref-text-preview img {
        max-width: 100% !important;
        height: auto !important;
    }

    .preview-gradient-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(180deg, rgba(248, 249, 250, 0) 0%, rgba(248, 249, 250, 0.95) 100%);
        pointer-events: none;
    }

    .pagination-sm .page-link {
        padding: 0.35rem 0.65rem;
        color: #495057;
        border-radius: 6px;
        margin: 0 2px;
    }

    .pagination-sm .page-item.active .page-link {
        background-color: var(--brand-primary, #1e40af);
        border-color: var(--brand-primary, #1e40af);
        color: #fff;
    }

    .pagination-sm .page-item.disabled .page-link {
        background-color: transparent;
        color: #adb5bd;
    }
</style>

<script>
    // Trigger MathJax render when preview modal is opened so any math formulas render crisp
    document.addEventListener('DOMContentLoaded', function () {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                if (window.MathJax && window.MathJax.typesetPromise) {
                    const mathContainers = modal.querySelectorAll('.content-math-render');
                    if (mathContainers.length > 0) {
                        MathJax.typesetPromise(Array.from(mathContainers)).catch(function (err) {
                            console.error('MathJax modal render error:', err);
                        });
                    }
                }
            });
        });
    });
</script>