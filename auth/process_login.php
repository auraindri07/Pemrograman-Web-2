<?php
/** Authenticate only accounts matching the explicitly selected portal role. */
session_start();
require_once '../includes/db.php';
require_once '../includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php'); exit;
}
if (isset($_POST['csrf_token']) && !is_string($_POST['csrf_token'])) {
    http_response_code(403); exit('CSRF token tidak valid.');
}
checkCsrfToken();
// An explicit login attempt replaces any previous authenticated account.
foreach (['user_id','nama_lengkap','role','email','login_time'] as $key) unset($_SESSION[$key]);
$selectedRole = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
if (!in_array($selectedRole, ['siswa','guru','admin'], true)) {
    header('Location: login.php?error=role'); exit;
}
$identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$failureUrl = 'login.php?role='.rawurlencode($selectedRole).'&error=';
if ($identifier === '' || $password === '') {
    header('Location: '.$failureUrl.'empty'); exit;
}
// The selected role narrows the query; the authenticated role still comes from DB.
$column = match ($selectedRole) { 'siswa' => 'nisn', 'guru' => 'nip', default => null };
$sql = 'SELECT * FROM users WHERE role = ? AND is_active = 1 AND (email = ?';
$params = [$selectedRole, $identifier];
if ($column !== null) { $sql .= ' OR '.$column.' = ?'; $params[] = $identifier; }
$sql .= ') LIMIT 1';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password'])) {
    header('Location: '.$failureUrl.'invalid'); exit;
}
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['role'] = $user['role'];
$_SESSION['email'] = $user['email'];
$_SESSION['login_time'] = time();
$destination = match ($user['role']) {
    'admin' => '../dashboard/admin.php',
    'guru' => '../dashboard/guru.php',
    default => '../dashboard/index.php'
};
header('Location: '.$destination); exit;
