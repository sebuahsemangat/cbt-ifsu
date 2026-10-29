<?php
/**
 * Class CbtSurveySync
 * Helper cURL cerdas untuk sinkronisasi Survei Guru antara CBT Offline dan Cloud SiHonor (InfinityFree)
 * Mendukung bypass otomatis Anti-Bot InfinityFree (AES-128-CBC testcookie)
 */

class CbtSurveySync {
    private static function getCookiePath(): string {
        $dir = __DIR__ . '/../uploads/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir . '/sihonor_session.txt';
    }

    /**
     * Eksekusi HTTP Request ke server SiHonor dengan penanganan bypass Anti-Bot InfinityFree
     */
    public static function request(string $url, string $method = 'GET', ?array $postData = null, string $apiKey = ''): array {
        $cookieFile = self::getCookiePath();
        $ch = curl_init();

        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept: application/json, text/html, */*'
        ];
        if (!empty($apiKey)) {
            $defaultHeaders[] = 'X-API-KEY: ' . $apiKey;
        }
        if ($method === 'POST') {
            $defaultHeaders[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? json_encode($postData) : ($postData ?? ''));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            curl_close($ch);
            return [
                'success' => false,
                'http_code' => 0,
                'message' => 'Koneksi ke server gagal: ' . $curlError
            ];
        }

        // Deteksi challenge JavaScript InfinityFree Anti-Bot
        if (is_string($response) && preg_match('/a=toNumbers\("([0-9a-fA-F]+)"\),b=toNumbers\("([0-9a-fA-F]+)"\),c=toNumbers\("([0-9a-fA-F]+)"\)/', $response, $matches)) {
            $keyHex = $matches[1];
            $ivHex  = $matches[2];
            $ctHex  = $matches[3];

            $key = hex2bin($keyHex);
            $iv  = hex2bin($ivHex);
            $ct  = hex2bin($ctHex);

            $decrypted = openssl_decrypt($ct, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
            $cookieVal = bin2hex($decrypted);

            // Simpan cookie ke cURL dan lakukan handshake ?i=1 ke domain root
            $parsedUrl = parse_url($url);
            $rootUrl = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'sihonor.page.gd') . '/?i=1';

            curl_setopt($ch, CURLOPT_URL, $rootUrl);
            curl_setopt($ch, CURLOPT_COOKIE, "__test=" . $cookieVal);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_exec($ch); // Selesaikan handshake

            // Ulangi request target asli dengan menyertakan cookie bypass
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_COOKIE, "__test=" . $cookieVal);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? json_encode($postData) : ($postData ?? ''));
            } else {
                curl_setopt($ch, CURLOPT_HTTPGET, true);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);

        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return array_merge(['http_code' => $httpCode], $json);
        }

        return [
            'success' => false,
            'http_code' => $httpCode,
            'message' => 'Respon dari server bukan JSON yang valid. Excerpt: ' . substr(strip_tags((string)$response), 0, 150),
            'raw' => $response
        ];
    }

    /**
     * Uji koneksi dan validasi API Key ke SiHonor
     */
    public static function testConnection(string $baseUrl, string $apiKey): array {
        $baseUrl = rtrim($baseUrl, '/');
        $endpoint = $baseUrl . '/api/survey/export_all_active.php';
        
        $res = self::request($endpoint, 'GET', null, $apiKey);
        if (!empty($res['success'])) {
            return [
                'success' => true,
                'message' => 'Koneksi ke SiHonor berhasil! ' . ($res['message'] ?? ''),
                'total_surveys' => $res['total'] ?? 0
            ];
        }

        return [
            'success' => false,
            'message' => $res['message'] ?? 'Gagal menghubungi server SiHonor.'
        ];
    }

    /**
     * Tarik (Pull) data seluruh survei aktif dari SiHonor ke database lokal CBT
     */
    public static function pullSurveys(PDO $pdo, string $baseUrl, string $apiKey): array {
        $baseUrl = rtrim($baseUrl, '/');
        $endpoint = $baseUrl . '/api/survey/export_all_active.php';

        $res = self::request($endpoint, 'GET', null, $apiKey);
        if (empty($res['success'])) {
            return [
                'success' => false,
                'message' => $res['message'] ?? 'Gagal menarik data survei dari server cloud.'
            ];
        }

        $surveys = $res['data'] ?? [];
        if (!is_array($surveys)) {
            return [
                'success' => false,
                'message' => 'Format data survei yang diterima tidak sesuai.'
            ];
        }

        $savedCount = 0;
        try {
            $pdo->beginTransaction();

            $stmtUpsert = $pdo->prepare("
                INSERT INTO `cbt_surveys_local` 
                (`survey_id_cloud`, `kode_akses`, `judul`, `guru_id_cloud`, `guru_nama`, `guru_nip`, `mata_pelajaran`, `kelas_sasaran`, `total_pertanyaan`, `payload_json`, `updated_at`)
                VALUES (:sid, :k, :judul, :gid, :gnama, :gnip, :mapel, :kelas, :tot_q, :payload, NOW())
                ON DUPLICATE KEY UPDATE
                    `survey_id_cloud` = VALUES(`survey_id_cloud`),
                    `judul` = VALUES(`judul`),
                    `guru_id_cloud` = VALUES(`guru_id_cloud`),
                    `guru_nama` = VALUES(`guru_nama`),
                    `guru_nip` = VALUES(`guru_nip`),
                    `mata_pelajaran` = VALUES(`mata_pelajaran`),
                    `kelas_sasaran` = VALUES(`kelas_sasaran`),
                    `total_pertanyaan` = VALUES(`total_pertanyaan`),
                    `payload_json` = VALUES(`payload_json`),
                    `updated_at` = NOW()
            ");

            foreach ($surveys as $s) {
                $kode = trim($s['kode_akses'] ?? '');
                if (empty($kode)) continue;

                $stmtUpsert->execute([
                    ':sid'     => (int)($s['survey_id'] ?? 0),
                    ':k'       => $kode,
                    ':judul'   => $s['judul'] ?? 'Survei Guru',
                    ':gid'     => !empty($s['guru']['id']) ? (int)$s['guru']['id'] : null,
                    ':gnama'   => $s['guru']['nama'] ?? 'Guru',
                    ':gnip'    => $s['guru']['nip'] ?? '-',
                    ':mapel'   => $s['mata_pelajaran'] ?? null,
                    ':kelas'   => $s['kelas_sasaran'] ?? null,
                    ':tot_q'   => (int)($s['total_pertanyaan'] ?? count($s['pertanyaan'] ?? [])),
                    ':payload' => json_encode($s, JSON_UNESCAPED_UNICODE)
                ]);
                $savedCount++;
            }

            // Update waktu last pull
            $stmtSet = $pdo->prepare("REPLACE INTO `settings` (`setting_key`, `setting_value`) VALUES ('cbt_survey_last_pull', :val)");
            $stmtSet->execute([':val' => date('Y-m-d H:i:s')]);

            $pdo->commit();

            return [
                'success' => true,
                'message' => "Berhasil menarik $savedCount kuesioner survei guru dari SiHonor.",
                'count' => $savedCount
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Gagal menyimpan survei ke database lokal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Kirim (Push) hasil respon siswa yang belum tersinkron (synced = 0) ke SiHonor
     */
    public static function pushResponses(PDO $pdo, string $baseUrl, string $apiKey, int $limit = 500): array {
        $baseUrl = rtrim($baseUrl, '/');
        $endpoint = $baseUrl . '/api/survey/bulk_submit.php';

        // Ambil data respon lokal yang belum synced
        $stmt = $pdo->prepare("
            SELECT id, kode_akses, teacher_name, nama_siswa, kelas_siswa, nisn, is_anonim, jawaban_json, waktu_isi
            FROM `cbt_survey_responden_local`
            WHERE `synced` = 0
            ORDER BY id ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [
                'success' => true,
                'message' => 'Semua respon siswa sudah tersinkronisasi ke Cloud. Tidak ada antrean baru.',
                'total_saved' => 0
            ];
        }

        $items = [];
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int)$r['id'];
            $items[] = [
                'kode_akses'  => $r['kode_akses'],
                'nama_siswa'  => $r['nama_siswa'],
                'kelas_siswa' => $r['kelas_siswa'],
                'nisn'        => $r['nisn'],
                'is_anonim'   => (bool)$r['is_anonim'],
                'waktu_isi'   => $r['waktu_isi'],
                'jawaban'     => json_decode($r['jawaban_json'], true) ?: []
            ];
        }

        // Kirim batch payload ke SiHonor
        $res = self::request($endpoint, 'POST', ['items' => $items], $apiKey);
        if (empty($res['success'])) {
            return [
                'success' => false,
                'message' => $res['message'] ?? 'Gagal mengunggah respon siswa ke server SiHonor.'
            ];
        }

        // Tandai data lokal sebagai sudah tersinkron (synced = 1)
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmtUpdate = $pdo->prepare("
                UPDATE `cbt_survey_responden_local` 
                SET `synced` = 1, `synced_at` = NOW() 
                WHERE `id` IN ($placeholders)
            ");
            $stmtUpdate->execute($ids);

            // Update waktu last push
            $stmtSet = $pdo->prepare("REPLACE INTO `settings` (`setting_key`, `setting_value`) VALUES ('cbt_survey_last_push', :val)");
            $stmtSet->execute([':val' => date('Y-m-d H:i:s')]);
        }

        return [
            'success' => true,
            'message' => $res['message'] ?? 'Berhasil mengunggah hasil survei.',
            'total_saved' => $res['total_saved'] ?? count($ids),
            'total_skipped' => $res['total_skipped'] ?? 0
        ];
    }
}
