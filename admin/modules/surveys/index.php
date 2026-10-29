<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil pengaturan sinkronisasi
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'cbt_survey_%'");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$syncUrl   = $settings['cbt_survey_sync_url'] ?? 'http://sihonor.page.gd';
$apiKey    = $settings['cbt_survey_api_key'] ?? '';
$lastPull  = $settings['cbt_survey_last_pull'] ?? '';
$lastPush  = $settings['cbt_survey_last_push'] ?? '';

// Statistik Survei
$totalSurveys = (int)$pdo->query("SELECT COUNT(*) FROM cbt_surveys_local")->fetchColumn();
$totalResponses = (int)$pdo->query("SELECT COUNT(*) FROM cbt_survey_responden_local")->fetchColumn();
$pendingResponses = (int)$pdo->query("SELECT COUNT(*) FROM cbt_survey_responden_local WHERE synced = 0")->fetchColumn();
$syncedResponses = (int)$pdo->query("SELECT COUNT(*) FROM cbt_survey_responden_local WHERE synced = 1")->fetchColumn();

// Ambil data kuesioner survei lokal
$stmtSurveys = $pdo->query("SELECT * FROM cbt_surveys_local ORDER BY guru_nama ASC, judul ASC");
$surveysList = $stmtSurveys->fetchAll();

// Ambil data respon siswa (terbaru 100)
$stmtResp = $pdo->query("
    SELECT csr.*, s.name as subject_name 
    FROM cbt_survey_responden_local csr
    LEFT JOIN subjects s ON csr.subject_id = s.id
    ORDER BY csr.id DESC 
    LIMIT 100
");
$responsesList = $stmtResp->fetchAll();
?>

<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-clipboard2-check text-primary me-2"></i>Survei Evaluasi Guru (SiHonor)
            </h3>
            <p class="text-muted small mb-0">
                Tarik data kuesioner dari server <strong>sihonor.page.gd</strong>, hubungkan dengan mata pelajaran, dan upload jawaban siswa dalam 1-klik.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-gear-fill me-1"></i> Konfigurasi API
            </button>
            <button id="btnPullSurveys" class="btn btn-outline-primary fw-semibold">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Tarik Data Survei
            </button>
            <button id="btnPushResponses" class="btn btn-success fw-bold position-relative shadow-sm">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Respon ke SiHonor
                <?php if ($pendingResponses > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                        <?= $pendingResponses ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Alert Status Koneksi -->
    <div id="syncAlertArea"></div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-card-checklist fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Kuesioner Guru</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalSurveys) ?></h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Tersedia di Server Lokal
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Respon</small>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($totalResponses) ?></h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Diisi oleh Siswa
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Belum Diupload</small>
                        <h4 class="fw-bold mb-0 <?= $pendingResponses > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($pendingResponses) ?>
                        </h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <?= $pendingResponses > 0 ? 'Menunggu dikirim ke Cloud' : 'Semua telah tersinkron' ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check2-all fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Terkirim ke Cloud</small>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($syncedResponses) ?></h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <?= $lastPush ? 'Terakhir: ' . date('d/m H:i', strtotime($lastPush)) : 'Belum pernah sync' ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tabSurveys">
                        <i class="bi bi-journal-text me-1"></i> Data Kuesioner Guru (<?= count($surveysList) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tabResponses">
                        <i class="bi bi-chat-left-quote-fill me-1"></i> Hasil Respon Siswa (<?= count($responsesList) ?>)
                        <?php if ($pendingResponses > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= $pendingResponses ?> Pending</span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content">
                <!-- TAB 1: Kuesioner Survei Lokal -->
                <div class="tab-pane fade show active p-0" id="tabSurveys">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4" style="width: 50px;">#</th>
                                    <th>Nama Guru</th>
                                    <th>Judul Survei</th>
                                    <th>Mata Pelajaran (SiHonor)</th>
                                    <th class="text-center">Jml Soal</th>
                                    <th>Kode Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($surveysList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-cloud-download fs-1 d-block mb-2 text-secondary"></i>
                                            Belum ada data survei di server lokal CBT.<br>
                                            Klik tombol <strong>"Tarik Data Survei"</strong> di atas untuk menyedot kuesioner dari <code>sihonor.page.gd</code>.
                                        </td>
                                    </tr>
                                <?php else: foreach ($surveysList as $idx => $srv): 
                                    $payload = json_decode($srv['payload_json'], true) ?: [];
                                    $questions = $payload['pertanyaan'] ?? [];
                                ?>
                                    <tr>
                                        <td class="px-4 text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($srv['guru_nama']) ?></div>
                                            <small class="text-muted">NIP: <?= htmlspecialchars($srv['guru_nip'] ?: '-') ?></small>
                                        </td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($srv['judul']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($srv['mata_pelajaran'] ?: 'Semua Mapel') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                                                <?= count($questions) ?> Butir
                                            </span>
                                        </td>
                                        <td>
                                            <code class="px-2 py-1 rounded bg-light border fw-bold text-dark">
                                                <?= htmlspecialchars($srv['kode_akses']) ?>
                                            </code>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#previewModal<?= $srv['id'] ?>" title="Lihat Pertanyaan">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Preview Kuesioner -->
                                    <div class="modal fade" id="previewModal<?= $srv['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title fw-bold">
                                                        <i class="bi bi-journal-check me-2"></i><?= htmlspecialchars($srv['judul']) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="p-3 bg-light rounded-3 mb-3 border">
                                                        <div class="row g-2 small">
                                                            <div class="col-sm-6"><strong>Guru:</strong> <?= htmlspecialchars($srv['guru_nama']) ?></div>
                                                            <div class="col-sm-6"><strong>Kode Akses:</strong> <code><?= htmlspecialchars($srv['kode_akses']) ?></code></div>
                                                            <div class="col-sm-6"><strong>Mata Pelajaran:</strong> <?= htmlspecialchars($srv['mata_pelajaran'] ?: '-') ?></div>
                                                            <div class="col-sm-6"><strong>Kelas Sasaran:</strong> <?= htmlspecialchars($srv['kelas_sasaran'] ?: 'Semua Kelas') ?></div>
                                                        </div>
                                                    </div>
                                                    <h6 class="fw-bold mb-3">Daftar Pertanyaan:</h6>
                                                    <?php if (empty($questions)): ?>
                                                        <p class="text-muted">Tidak ada butir pertanyaan pada kuesioner ini.</p>
                                                    <?php else: foreach ($questions as $qidx => $q): ?>
                                                        <div class="card mb-2 border shadow-none bg-white">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div class="fw-semibold">
                                                                        <?= ($qidx + 1) ?>. <?= htmlspecialchars($q['pertanyaan']) ?>
                                                                        <?php if (!empty($q['wajib'])): ?><span class="text-danger">*</span><?php endif; ?>
                                                                    </div>
                                                                    <span class="badge bg-secondary ms-2 text-uppercase" style="font-size: 0.65rem;">
                                                                        <?= htmlspecialchars($q['tipe']) ?>
                                                                    </span>
                                                                </div>
                                                                <?php if ($q['tipe'] === 'skala'): ?>
                                                                    <div class="text-muted small mt-2">
                                                                        <span class="badge bg-light text-dark border">Skala 1 - 5 (Kurang s.d. Sangat Baik)</span>
                                                                    </div>
                                                                <?php elseif ($q['tipe'] === 'pilihan' && !empty($q['pilihan_opsi'])): ?>
                                                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                                                        <?php foreach ($q['pilihan_opsi'] as $opsi): ?>
                                                                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($opsi) ?></span>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Respon Siswa Lokal -->
                <div class="tab-pane fade p-0" id="tabResponses">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4" style="width: 50px;">#</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Guru yang Dinilai</th>
                                    <th>Mata Pelajaran Ujian</th>
                                    <th>Waktu Isi</th>
                                    <th class="text-center">Status Sync</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($responsesList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                            Belum ada siswa yang mengisi survei.<br>
                                            Ketika siswa selesai ujian pada mapel yang memiliki guru pengampu, respon mereka akan muncul di sini.
                                        </td>
                                    </tr>
                                <?php else: foreach ($responsesList as $idx => $resp): ?>
                                    <tr>
                                        <td class="px-4 text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <?php if ($resp['is_anonim']): ?>
                                                <span class="badge bg-secondary me-1"><i class="bi bi-incognito me-1"></i>Anonim</span>
                                                <small class="text-muted">(<?= htmlspecialchars($resp['nama_siswa']) ?>)</small>
                                            <?php else: ?>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($resp['nama_siswa']) ?></span>
                                                <?php if ($resp['nisn']): ?>
                                                    <br><small class="text-muted">NISN: <?= htmlspecialchars($resp['nisn']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($resp['kelas_siswa'] ?: '-') ?></span></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($resp['teacher_name'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($resp['subject_name'] ?: '-') ?></td>
                                        <td><small class="text-muted"><?= date('d M Y H:i', strtotime($resp['waktu_isi'])) ?></small></td>
                                        <td class="text-center">
                                            <?php if ($resp['synced']): ?>
                                                <span class="badge bg-success" title="Sudah diupload ke SiHonor pada <?= $resp['synced_at'] ?>">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Terkirim ke Cloud
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock-history me-1"></i> Belum Diupload
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfigurasi Koneksi SiHonor -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formSyncSettings">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-sliders me-2 text-primary"></i>Konfigurasi API SiHonor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Masukkan URL server SiHonor dan Secret API Key yang ada pada menu <strong>Pengaturan</strong> web SiHonor.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL Server Cloud SiHonor</label>
                        <input type="url" name="sync_url" id="inputSyncUrl" class="form-control" required value="<?= htmlspecialchars($syncUrl) ?>" placeholder="http://sihonor.page.gd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Secret API Key CBT (X-API-KEY)</label>
                        <div class="input-group">
                            <input type="password" name="api_key" id="inputApiKey" class="form-control font-monospace" required value="<?= htmlspecialchars($apiKey) ?>" placeholder="cbt_xxxxxxxxxxxxxxxx">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility()">
                                <i class="bi bi-eye" id="passToggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div id="testConnStatus" class="mt-2"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" id="btnTestConn" class="btn btn-outline-info">
                        <i class="bi bi-broadcast me-1"></i> Uji Koneksi
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassVisibility() {
    const input = document.getElementById('inputApiKey');
    const icon = document.getElementById('passToggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Handler Simpan Pengaturan
document.getElementById('formSyncSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const formData = new FormData(this);
    formData.append('action', 'save_settings');

    fetch('modules/surveys/process.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            showAlert('success', res.message);
            const modalEl = document.getElementById('settingsModal');
            bootstrap.Modal.getInstance(modalEl).hide();
        } else {
            showAlert('danger', res.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        showAlert('danger', 'Gagal menyimpan pengaturan: ' . err);
    });
});

// Handler Uji Koneksi
document.getElementById('btnTestConn').addEventListener('click', function() {
    const btn = this;
    const statusDiv = document.getElementById('testConnStatus');
    const syncUrl = document.getElementById('inputSyncUrl').value;
    const apiKey = document.getElementById('inputApiKey').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menguji...';
    statusDiv.innerHTML = '<div class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Menghubungi sihonor.page.gd & bypass bot check...</div>';

    const formData = new FormData();
    formData.append('action', 'test_connection');
    formData.append('sync_url', syncUrl);
    formData.append('api_key', apiKey);

    fetch('modules/surveys/process.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-broadcast me-1"></i> Uji Koneksi';
        if (res.success) {
            statusDiv.innerHTML = '<div class="alert alert-success py-2 small mb-0"><i class="bi bi-check-circle me-1"></i>' + res.message + ' (Ditemukan: ' + (res.total_surveys || 0) + ' survei aktif)</div>';
        } else {
            statusDiv.innerHTML = '<div class="alert alert-danger py-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>' + res.message + '</div>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-broadcast me-1"></i> Uji Koneksi';
        statusDiv.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Error: ' + err + '</div>';
    });
});

// Handler Tarik Survei dari SiHonor
document.getElementById('btnPullSurveys').addEventListener('click', function() {
    if (!confirm('Tarik seluruh kuesioner survei aktif dari server SiHonor ke CBT lokal?')) return;

    const btn = this;
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menarik data...';

    fetch('modules/surveys/process.php?action=pull_surveys')
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert('Gagal: ' + res.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        alert('Terjadi error saat menarik survei: ' + err);
    });
});

// Handler Push Respon ke SiHonor
document.getElementById('btnPushResponses').addEventListener('click', function() {
    const pending = <?= $pendingResponses ?>;
    if (pending === 0) {
        alert('Tidak ada respon siswa yang menunggu upload. Semua sudah tersinkronisasi.');
        return;
    }

    if (!confirm('Upload ' + pending + ' respon kuesioner siswa ke cloud SiHonor sekarang?')) return;

    const btn = this;
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengunggah ke Cloud...';

    fetch('modules/surveys/process.php?action=push_responses')
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = origText;
        if (res.success) {
            alert(res.message);
            location.reload();
        } else {
            alert('Gagal: ' + res.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = origText;
        alert('Terjadi error saat upload: ' + err);
    });
});

function showAlert(type, msg) {
    const area = document.getElementById('syncAlertArea');
    area.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}
</script>
