<?php
require_once __DIR__ . '/ui.php';
/**
 * EduLMS - Helper Functions
 * Common utility functions for the application
 */

/**
 * Sanitize output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a page
 */
function redirect($path) {
    header("Location: $path");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user ID
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function currentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check if current user has specific role
 */
function hasRole($role) {
    return currentUserRole() === $role;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

/** Display an optional SQL TIME without date parsing or timezone conversion. */
function formatJam(?string $time): string {
    if ($time === null || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
        return '-';
    }
    return substr($time, 0, 5);
}

/**
 * Format tanggal Indonesia
 */
function formatTanggalIndo($date, $format = 'd M Y') {
    if (empty($date)) return '-';
    
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    $timestamp = strtotime($date);
    $d = date('d', $timestamp);
    $m = $bulan[(int)date('m', $timestamp)];
    $y = date('Y', $timestamp);
    $h = date('H:i', $timestamp);
    
    if ($format === 'd M Y H:i') {
        return "$d $m $y, $h";
    }
    return "$d $m $y";
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'aktif' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'tepat_waktu' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'sakit' => 'bg-amber-50 text-amber-700 border-amber-200',
        'izin' => 'bg-blue-50 text-blue-700 border-blue-200',
        'alfa' => 'bg-rose-50 text-rose-700 border-rose-200',
        'terlambat' => 'bg-rose-50 text-rose-700 border-rose-200',
        'belum_dinilai' => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
    
    return $classes[$status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = ['pdf', 'docx', 'zip'], $maxSize = 10485760) {
    $errors = [];
    
    // Check if file exists
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'File harus diupload';
        return $errors;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Terjadi kesalahan saat upload file';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'Ukuran file terlalu besar. Maksimal ' . formatFileSize($maxSize);
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        $errors[] = 'Format file tidak diizinkan. Hanya: ' . implode(', ', $allowedTypes);
    }
    
    // Check MIME type
    if (!is_file($file['tmp_name'] ?? '')) return ['Berkas upload tidak ditemukan.'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedMimes = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'zip' => 'application/zip',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
    
    $validMime = false;
    if (isset($allowedMimes[$ext]) && $mimeType === $allowedMimes[$ext]) $validMime = true;
    // OOXML is a ZIP container. Verify the internal document family, not just ZIP MIME.
    if (in_array($ext, ['docx','pptx']) && in_array($mimeType, ['application/zip','application/octet-stream']) && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) === true) {
            $part = $ext === 'docx' ? 'word/document.xml' : 'ppt/presentation.xml';
            $validMime = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($part) !== false;
            $zip->close();
        }
    }
    
    if (!$validMime) {
        $errors[] = 'Tipe file tidak valid';
    }
    
    return $errors;
}

/**
 * Generate unique filename
 */
function generateUniqueFileName($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $ext;
}

/**
 * Calculate persentase
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 1);
}

/**
 * Check if deadline is close (within 24 hours)
 */
function isDeadlineClose($deadline) {
    $now = time();
    $deadlineTime = strtotime($deadline);
    $diff = $deadlineTime - $now;
    
    // Deadline dalam 24 jam
    return $diff > 0 && $diff <= 86400;
}

/**
 * Check if deadline is passed
 */
function isDeadlinePassed($deadline) {
    return strtotime($deadline) < time();
}

/**
 * Get time remaining text
 */
function getTimeRemaining($deadline) {
    $now = time();
    $deadlineTime = strtotime($deadline);
    $diff = $deadlineTime - $now;
    
    if ($diff < 0) {
        return 'Terlambat';
    }
    
    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    
    if ($days > 0) {
        return "$days hari lagi";
    } elseif ($hours > 0) {
        return "$hours jam lagi";
    } else {
        return "Kurang dari 1 jam";
    }
}
