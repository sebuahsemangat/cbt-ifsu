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
            
        case 'reports':
            include 'reports.php';
            break;
        case 'scores':
            include 'modules/scores/index.php';
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
        case 'main':
        default:
            // Tampilan Statistik Utama (Konten Dashboard Asli)
            $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
            $total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
            $total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Dashboard</h3>
                <p class="text-muted">Halo, <?= $_SESSION['full_name']; ?> 👋</p>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-people text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?= $total_students ?></h5>
                                <small class="text-muted">Siswa</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-book text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?= $total_subjects ?></h5>
                                <small class="text-muted">Mapel</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-file-earmark-text text-info fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?= $total_questions ?></h5>
                                <small class="text-muted">Total Soal</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-file-earmark-text text-warning fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?= $total_exams ?></h5>
                                <small class="text-muted">Total Ujian</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-4">
                        <h5>Selamat Datang di Panel Admin</h5>
                        <p class="text-muted">Gunakan menu di samping untuk mengelola data ujian secara terpusat.</p>
                    </div>
                </div>
            </div>
            <?php
            break;
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>