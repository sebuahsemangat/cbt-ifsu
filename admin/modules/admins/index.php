<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Ambil data admin
$stmt = $pdo->query("SELECT * FROM admins ORDER BY full_name ASC");
$admins = $stmt->fetchAll();
$nomor = 0;
?>

<?php if (isset($_GET['status'])): ?>
    <div class="alert alert-<?= in_array($_GET['status'], ['success', 'updated', 'deleted']) ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?php 
        switch($_GET['status']) {
            case 'success': echo "Admin baru berhasil ditambahkan!"; break;
            case 'updated': echo "Data admin berhasil diperbarui!"; break;
            case 'deleted': echo "Admin berhasil dihapus!"; break;
            case 'error': echo "Terjadi kesalahan dalam memproses data."; break;
            case 'username_exists': echo "Username sudah digunakan!"; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Manajemen Admin</h3>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
        <i class="bi bi-person-plus"></i> Tambah Admin
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th class="px-4">Nama Lengkap</th>
                        <th>Username</th>
                        <th>Tgl Dibuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): $nomor++; ?>
                    <tr>
                        <td class="text-center"><?= $nomor ?></td>
                        <td class="px-4 fw-bold"><?= htmlspecialchars($a['full_name']) ?></td>
                        <td><code><?= htmlspecialchars($a['username']) ?></code></td>
                        <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary edit-admin-btn" 
                                    data-id="<?= $a['id'] ?>" 
                                    data-name="<?= htmlspecialchars($a['full_name']) ?>" 
                                    data-username="<?= htmlspecialchars($a['username']) ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editAdminModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($a['id'] != $_SESSION['user_id']): ?>
                            <a href="modules/admins/process.php?action=delete&id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus admin ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Admin -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/admins/process.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Admin Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
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

<!-- Modal Edit Admin -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="modules/admins/process.php?action=edit" method="POST">
                <input type="hidden" name="id" id="edit_admin_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Data Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="edit_admin_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="edit_admin_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
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
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-admin-btn')) {
        const btn = e.target.closest('.edit-admin-btn');
        document.getElementById('edit_admin_id').value = btn.dataset.id;
        document.getElementById('edit_admin_name').value = btn.dataset.name;
        document.getElementById('edit_admin_username').value = btn.dataset.username;
    }
});
</script>
