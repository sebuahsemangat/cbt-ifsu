<?php
/**
 * Action Controller: Sinkronisasi Survei Guru dengan Cloud SiHonor
 */

require_once '../../../config/database.php';
require_once '../../../includes/auth_check.php';
require_once '../../../includes/CbtSurveySync.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Helper simpan / update setting
function save_or_update_setting(PDO $pdo, string $key, string $val): void {
    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute([$key, $val]);
}

// 1. Simpan Konfigurasi Koneksi SiHonor
if ($action === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $syncUrl = rtrim(trim($_POST['sync_url'] ?? ''), '/');
    $apiKey  = trim($_POST['api_key'] ?? '');

    if (empty($syncUrl)) {
        echo json_encode(['success' => false, 'message' => 'URL Server SiHonor wajib diisi.']);
        exit;
    }

    try {
        save_or_update_setting($pdo, 'cbt_survey_sync_url', $syncUrl);
        save_or_update_setting($pdo, 'cbt_survey_api_key', $apiKey);

        echo json_encode(['success' => true, 'message' => 'Pengaturan koneksi SiHonor berhasil disimpan.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()]);
    }
    exit;
}

// 2. Test Koneksi ke Server SiHonor
if ($action === 'test_connection') {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('cbt_survey_sync_url', 'cbt_survey_api_key')");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

    $syncUrl = rtrim(trim($_POST['sync_url'] ?? ($settings['cbt_survey_sync_url'] ?? 'http://sihonor.page.gd')), '/');
    $apiKey  = trim($_POST['api_key'] ?? ($settings['cbt_survey_api_key'] ?? ''));

    if (empty($syncUrl)) {
        echo json_encode(['success' => false, 'message' => 'URL Server SiHonor belum diatur.']);
        exit;
    }

    $res = CbtSurveySync::testConnection($syncUrl, $apiKey);
    echo json_encode($res);
    exit;
}

// 3. Tarik (Pull) Data Survei Aktif dari SiHonor
if ($action === 'pull_surveys') {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('cbt_survey_sync_url', 'cbt_survey_api_key')");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

    $syncUrl = rtrim(trim($settings['cbt_survey_sync_url'] ?? 'http://sihonor.page.gd'), '/');
    $apiKey  = trim($settings['cbt_survey_api_key'] ?? '');

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'API Key SiHonor belum dikonfigurasi. Silakan isi di form konfigurasi koneksi.']);
        exit;
    }

    $res = CbtSurveySync::pullSurveys($pdo, $syncUrl, $apiKey);
    echo json_encode($res);
    exit;
}

// 4. Kirim (Push) Hasil Respon Siswa ke SiHonor
if ($action === 'push_responses') {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('cbt_survey_sync_url', 'cbt_survey_api_key')");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

    $syncUrl = rtrim(trim($settings['cbt_survey_sync_url'] ?? 'http://sihonor.page.gd'), '/');
    $apiKey  = trim($settings['cbt_survey_api_key'] ?? '');

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'API Key SiHonor belum dikonfigurasi.']);
        exit;
    }

    $res = CbtSurveySync::pushResponses($pdo, $syncUrl, $apiKey);
    echo json_encode($res);
    exit;
}

// 5. Hapus respon siswa lokal (opsional untuk reset/bersihkan data lama)
if ($action === 'delete_response' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM cbt_survey_responden_local WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Respon berhasil dihapus.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
exit;
