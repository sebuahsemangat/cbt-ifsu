<?php
/**
 * FTP Deployment Script for PHP Projects
 * Usage:
 *   php deploy_ftp.php --test             # Test connection only
 *   php deploy_ftp.php                    # Auto: upload uncommitted git changes
 *   php deploy_ftp.php --git              # Upload uncommitted git changes
 *   php deploy_ftp.php --commit           # Upload files from latest git commit
 *   php deploy_ftp.php --files="f1,f2"    # Upload specific files
 *   php deploy_ftp.php --all              # Upload whole project (respects ignore list)
 *   php deploy_ftp.php --dry-run          # Preview without uploading
 */

declare(strict_types=1);

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'ftp_config.json';

// Parse CLI options
$options = getopt('', ['test', 'git', 'commit::', 'files::', 'all', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  php deploy_ftp.php --test             Test FTP connection\n";
    echo "  php deploy_ftp.php                    Upload uncommitted git changes (default)\n";
    echo "  php deploy_ftp.php --git              Upload uncommitted git changes\n";
    echo "  php deploy_ftp.php --commit           Upload files changed in the latest commit\n";
    echo "  php deploy_ftp.php --commit=HEAD~1    Upload files changed in specific commit/range\n";
    echo "  php deploy_ftp.php --files=\"a.php,b\"  Upload specific files\n";
    echo "  php deploy_ftp.php --all              Upload entire project (excluding ignores)\n";
    echo "  php deploy_ftp.php --dry-run          Show files without uploading\n";
    exit(0);
}

if (!file_exists($configFile)) {
    echo "[\033[31mERROR\033[0m] File konfigurasi 'ftp_config.json' tidak ditemukan!\n";
    echo "Silakan buat file tersebut atau salin dari 'ftp_config.example.json'.\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config || empty($config['host']) || empty($config['username'])) {
    echo "[\033[31mERROR\033[0m] Format 'ftp_config.json' tidak valid atau belum diisi dengan benar.\n";
    exit(1);
}

if ($config['host'] === 'ftp.yourdomain.com' || $config['username'] === 'your_username') {
    echo "[\033[33mPERINGATAN\033[0m] File 'ftp_config.json' masih menggunakan contoh bawaan.\n";
    echo "Silakan edit file 'ftp_config.json' dengan host, user, dan password FTP server Anda terlebih dahulu.\n";
    exit(1);
}


$isDryRun = isset($options['dry-run']);
$isTest = isset($options['test']);

echo "\n=========================================\n";
echo "   \033[36mFTP DEPLOYMENT TOOL\033[0m\n";
echo "=========================================\n";
echo "Host        : {$config['host']}:" . ($config['port'] ?? 21) . "\n";
echo "User        : {$config['username']}\n";
echo "Remote Root : " . ($config['remote_root'] ?? '/') . "\n";
if ($isDryRun) {
    echo "\033[33mMode        : DRY-RUN (Simulasi saja, tidak ada file di-upload)\033[0m\n";
}
echo "-----------------------------------------\n";

// Connect to FTP
$port = (int)($config['port'] ?? 21);
$timeout = (int)($config['timeout'] ?? 30);
$useSsl = !empty($config['ssl']);

echo "Menghubungkan ke FTP server... ";
if ($useSsl && function_exists('ftp_ssl_connect')) {
    $conn = @ftp_ssl_connect($config['host'], $port, $timeout);
} else {
    $conn = @ftp_connect($config['host'], $port, $timeout);
}

if (!$conn) {
    echo "\033[31mGAGAL!\033[0m\n";
    echo "Tidak dapat terhubung ke host {$config['host']}:{$port}.\n";
    exit(1);
}
echo "\033[32mTERHUBUNG!\033[0m\n";

echo "Melakukan autentikasi login... ";
$login = @ftp_login($conn, $config['username'], $config['password']);
if (!$login) {
    echo "\033[31mGAGAL!\033[0m\n";
    echo "Username atau password salah.\n";
    @ftp_close($conn);
    exit(1);
}
echo "\033[32mBERHASIL!\033[0m\n";

// Passive mode
$passive = $config['passive'] ?? true;
@ftp_pasv($conn, (bool)$passive);

if ($isTest) {
    echo "\n\033[32mTes koneksi berhasil! Siap digunakan untuk upload.\033[0m\n";
    @ftp_close($conn);
    exit(0);
}

// Determine files to upload
$filesToUpload = [];
$ignoreList = $config['ignore'] ?? [
    '.git',
    '.gitignore',
    'ftp_config.json',
    'ftp_config.example.json',
    'deploy_ftp.php',
];

function isIgnored(string $relPath, array $ignoreList): bool {
    $normalized = str_replace('\\', '/', $relPath);
    foreach ($ignoreList as $pattern) {
        $pattern = str_replace('\\', '/', trim($pattern, '/'));
        if ($pattern === '') continue;

        if ($normalized === $pattern || strpos($normalized, $pattern . '/') === 0) {
            return true;
        }
        if (fnmatch($pattern, basename($normalized))) {
            return true;
        }
    }
    return false;
}

if (isset($options['files'])) {
    $rawList = explode(',', (string)$options['files']);
    foreach ($rawList as $item) {
        $item = trim($item);
        if ($item !== '' && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $item)) {
            $filesToUpload[] = $item;
        }
    }
} elseif (isset($options['commit'])) {
    $rev = $options['commit'] !== false && $options['commit'] !== '' ? $options['commit'] : 'HEAD';
    echo "Mencari file dari git commit: $rev\n";
    $output = [];
    exec("git diff-tree --no-commit-id --name-only -r " . escapeshellarg($rev), $output);
    foreach ($output as $file) {
        $file = trim($file);
        if ($file !== '' && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $file) && !isIgnored($file, $ignoreList)) {
            $filesToUpload[] = $file;
        }
    }
} elseif (isset($options['all'])) {
    echo "Memindai seluruh direktori proyek...\n";
    $dirIter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($dirIter as $item) {
        if ($item->isFile()) {
            $rel = substr($item->getPathname(), strlen(__DIR__) + 1);
            if (!isIgnored($rel, $ignoreList)) {
                $filesToUpload[] = $rel;
            }
        }
    }
} else {
    // Default: upload git modified / untracked files
    echo "Mendeteksi perubahan git lokal (uncommitted)...\n";
    $output = [];
    exec("git status --short", $output);
    foreach ($output as $line) {
        $status = substr($line, 0, 2);
        $file = trim(substr($line, 3));
        // Handle renamed files like "old -> new"
        if (strpos($file, '->') !== false) {
            $parts = explode('->', $file);
            $file = trim(end($parts));
        }
        $file = trim($file, '"');
        if ($file !== '' && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $file) && !isIgnored($file, $ignoreList)) {
            $filesToUpload[] = $file;
        }
    }

    // Jika tidak ada perubahan uncommitted, fallback otomatis ke file commit terakhir (HEAD)
    if (empty($filesToUpload)) {
        $commitOutput = [];
        exec("git diff-tree --no-commit-id --name-only -r HEAD", $commitOutput);
        if (!empty($commitOutput)) {
            echo "Tidak ada perubahan uncommitted, mendeteksi file dari commit terakhir (HEAD)...\n";
            foreach ($commitOutput as $file) {
                $file = trim($file);
                if ($file !== '' && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $file) && !isIgnored($file, $ignoreList)) {
                    $filesToUpload[] = $file;
                }
            }
        }
    }
}


// Normalize and remove duplicates
$filesToUpload = array_values(array_unique(array_filter($filesToUpload, function($f) {
    return is_file(__DIR__ . DIRECTORY_SEPARATOR . $f);
})));

if (empty($filesToUpload)) {
    echo "\n\033[33mTidak ada file yang perlu di-upload!\033[0m\n";
    if (!isset($options['all']) && !isset($options['files'])) {
        echo "(Tip: Tidak ada perubahan git yang terdeteksi. Gunakan --all untuk upload semua file atau --files=\"nama_file\" untuk file spesifik)\n";
    }
    @ftp_close($conn);
    exit(0);
}

echo "Ditemukan \033[32m" . count($filesToUpload) . "\033[0m file untuk di-upload:\n";
foreach ($filesToUpload as $f) {
    echo "  - " . str_replace('\\', '/', $f) . "\n";
}
echo "-----------------------------------------\n";

function ftpMkdirRecursive($conn, string $remoteDir): bool {
    $remoteDir = trim(str_replace('\\', '/', $remoteDir), '/');
    if ($remoteDir === '') return true;

    $parts = explode('/', $remoteDir);
    $path = '';
    foreach ($parts as $part) {
        $path .= '/' . $part;
        if (!@ftp_chdir($conn, $path)) {
            if (!@ftp_mkdir($conn, $path)) {
                return false;
            }
        }
    }
    return true;
}

$remoteRoot = '/' . trim(str_replace('\\', '/', $config['remote_root'] ?? '/'), '/');
if ($remoteRoot !== '/') {
    $remoteRoot .= '/';
}

$successCount = 0;
$failCount = 0;

foreach ($filesToUpload as $index => $relPath) {
    $normRel = str_replace('\\', '/', $relPath);
    $localFile = __DIR__ . DIRECTORY_SEPARATOR . $relPath;
    $remoteFile = $remoteRoot . $normRel;
    $remoteDir = dirname($remoteFile);

    $num = $index + 1;
    echo "[$num/" . count($filesToUpload) . "] $normRel ... ";

    if ($isDryRun) {
        echo "\033[33m[DRY-RUN OK]\033[0m -> $remoteFile\n";
        $successCount++;
        continue;
    }

    // Ensure remote directory exists
    if (!ftpMkdirRecursive($conn, $remoteDir)) {
        echo "\033[31m[GAGAL BIKIN FOLDER]\033[0m\n";
        $failCount++;
        continue;
    }

    // Upload file
    if (@ftp_put($conn, $remoteFile, $localFile, FTP_BINARY)) {
        echo "\033[32m[OK]\033[0m\n";
        $successCount++;
    } else {
        echo "\033[31m[GAGAL UPLOAD]\033[0m\n";
        $failCount++;
    }
}

@ftp_close($conn);

echo "=========================================\n";
echo "Selesai: \033[32m{$successCount} berhasil\033[0m, ";
if ($failCount > 0) {
    echo "\033[31m{$failCount} gagal\033[0m\n";
} else {
    echo "0 gagal\n";
}
echo "=========================================\n\n";

exit($failCount > 0 ? 1 : 0);
