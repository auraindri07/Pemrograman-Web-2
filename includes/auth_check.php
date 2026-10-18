<?php
require_once __DIR__.'/ui.php';
/**
 * EduLMS - Authentication Middleware
 * Check user authentication and authorization
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require authentication
 * Redirect to login if not authenticated
 */
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    // Revalidate active account and role; admin changes take effect on the next request.
    global $pdo;
    if (isset($pdo)) {
        $account = $pdo->prepare('SELECT role, nama_lengkap FROM users WHERE id = ? AND is_active = 1');
        $account->execute([$_SESSION['user_id']]);
        $active = $account->fetch();
        if (!$active) {
            $_SESSION = [];
            session_destroy();
            header('Location: ../auth/login.php?error=inactive');
            exit;
        }
        $_SESSION['role'] = $active['role'];
        $_SESSION['nama_lengkap'] = $active['nama_lengkap'];
    }
}

/**
 * Require specific role
 * Show 403 error if user doesn't have required role
 */
function requireRole($allowedRoles) {
    requireAuth();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        die(('
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Akses Ditolak - EduLMS</title>
            <link rel="stylesheet" href="../assets/css/tailwind.css">
        </head>
        <body class="bg-slate-50 min-h-screen flex items-center justify-center">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 max-w-md text-center">
                <div class="text-6xl mb-4">'.uiIcon('shield',18).'</div>
                <h1 class="text-xl font-bold text-slate-900 mb-2">Akses Ditolak</h1>
                <p class="text-sm text-slate-600 mb-6">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href="../dashboard/" class="inline-block px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold rounded-xl transition">
                    Kembali ke Dashboard
                </a>
            </div>
        </body>
        </html>
        '));
    }
}

/**
 * Check if user is student
 */
function requireStudent() {
    requireRole('siswa');
}

/**
 * Check if user is teacher
 */
function requireTeacher() {
    requireRole('guru');
}

/**
 * Check if user is admin
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Check if user is teacher or admin
 */
function requireTeacherOrAdmin() {
    requireRole(['guru', 'admin']);
}

/**
 * Get current user from database
 */
function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, nama_lengkap, email, role, nisn, nip, foto_profil FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Refresh session data from database
 */
function refreshSessionData($pdo) {
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit();
    }
    
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
}
