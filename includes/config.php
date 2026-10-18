<?php
/**
 * EduLMS - Configuration
 * Application-wide configuration
 */

// Tahun ajaran aktif
define('TAHUN_AJARAN_AKTIF', getenv('EDULMS_TAHUN_AJARAN') ?: '2026/2027');
date_default_timezone_set('Asia/Jakarta');

// Upload configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MATERI_DIR', UPLOAD_DIR . 'materi/');
define('UPLOAD_TUGAS_DIR', UPLOAD_DIR . 'tugas/');
define('UPLOAD_PENGUMPULAN_DIR', UPLOAD_DIR . 'pengumpulan/');

// Allowed file types
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'docx', 'doc', 'pptx', 'ppt']);
define('ALLOWED_ARCHIVE_TYPES', ['zip']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);

// Session timeout (2 hours)
define('SESSION_TIMEOUT', 7200);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Application info
define('APP_NAME', 'EduLMS');
define('APP_VERSION', 'v4.2');
define('APP_YEAR', '2026');
