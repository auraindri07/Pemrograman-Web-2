<?php
date_default_timezone_set('Asia/Jakarta');
$host = getenv('EDULMS_DB_HOST') ?: 'localhost';
$dbname = getenv('EDULMS_DB_NAME') ?: 'edulms_db';
$username = getenv('EDULMS_DB_USER') ?: 'root';
$password = getenv('EDULMS_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('EduLMS database: ' . $e->getMessage());
    if (PHP_SAPI !== 'cli') http_response_code(503);
    exit('Database EduLMS belum tersedia. Periksa konfigurasi koneksi dan panduan docs/PANDUAN_MENJALANKAN.md.');
}
?>
