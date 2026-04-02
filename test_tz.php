<?php
require_once 'config/database.php';
$php_now = date('Y-m-d H:i:s');
$php_time = time();
$stmt = $pdo->query("SELECT NOW() as mysql_now, UNIX_TIMESTAMP(NOW()) as mysql_ts");
$res = $stmt->fetch();
echo "PHP Now: $php_now\n";
echo "PHP TS: $php_time\n";
echo "MySQL Now: {$res['mysql_now']}\n";
echo "MySQL TS: {$res['mysql_ts']}\n";
echo "Diff: " . ($php_time - $res['mysql_ts']) . " seconds\n";
