<?php
/**
 * EduLMS - CSRF Protection
 * Generate and validate CSRF tokens
 */

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token field for forms
 */
function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Check CSRF token from POST request
 */
function checkCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Halaman telah kedaluwarsa, silakan refresh dan coba lagi.');
        }
    }
}
