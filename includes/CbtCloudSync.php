<?php
/**
 * Class CbtCloudSync
 * Helper cURL cerdas untuk sinkronisasi nilai CBT Lab Offline ke Server Cloud (cbt.page.gd)
 * Mendukung bypass otomatis Anti-Bot InfinityFree (AES-128-CBC testcookie)
 */

declare(strict_types=1);

class CbtCloudSync {
    /**
     * Lokasi file penyimpanan session/cookie cURL
     */
    private static function getCookiePath(): string {
        $dir = __DIR__ . '/../uploads/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $dir . '/cbt_cloud_session.txt';
        }
        return sys_get_temp_dir() . '/cbt_cloud_session.txt';
    }

    /**
     * Eksekusi HTTP Request ke server CBT Cloud dengan penanganan otomatis Anti-Bot InfinityFree
     */
    public static function request(string $url, string $method = 'GET', ?array $postData = null, string $syncToken = ''): array {
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'http_code' => 0,
                'message' => 'Ekstensi PHP cURL belum aktif di php.ini pada komputer ini. Silakan aktifkan extension=curl.'
            ];
        }

        $cookieFile = self::getCookiePath();
        $ch = curl_init();

        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept: application/json, text/html, */*'
        ];
        if (!empty($syncToken)) {
            $defaultHeaders[] = 'X-SYNC-TOKEN: ' . $syncToken;
            $defaultHeaders[] = 'Authorization: Bearer ' . $syncToken;
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
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
                'message' => 'Gagal menghubungkan ke server cloud: ' . $curlError
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
            $rootUrl = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'cbt.page.gd') . '/?i=1';

            curl_setopt($ch, CURLOPT_URL, $rootUrl);
            curl_setopt($ch, CURLOPT_COOKIE, "__test=" . $cookieVal);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_exec($ch); // Jalankan handshake

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

        $json = json_decode((string)$response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return array_merge([
                'success'   => ($httpCode >= 200 && $httpCode < 300) && ($json['success'] ?? true),
                'http_code' => $httpCode,
                'data'      => $json['data'] ?? $json,
                'message'   => $json['message'] ?? 'Berhasil menerima respon server'
            ], $json);
        }

        return [
            'success'   => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'message'   => ($httpCode >= 200 && $httpCode < 300) ? 'Respon server bukan JSON: ' . substr(strip_tags((string)$response), 0, 200) : 'Server merespon kode ' . $httpCode,
            'raw'       => substr((string)$response, 0, 500)
        ];
    }

    /**
     * Tes koneksi dan validasi token ke server cloud
     */
    public static function testConnection(string $baseUrl, string $syncToken): array {
        $cleanUrl = rtrim($baseUrl, '/') . '/api/sync/upload_scores.php?test=1';
        return self::request($cleanUrl, 'GET', null, $syncToken);
    }

    /**
     * Upload paket data nilai dan jawaban ke server cloud
     */
    public static function uploadScores(string $baseUrl, string $syncToken, array $payload): array {
        $cleanUrl = rtrim($baseUrl, '/') . '/api/sync/upload_scores.php';
        return self::request($cleanUrl, 'POST', $payload, $syncToken);
    }
}
