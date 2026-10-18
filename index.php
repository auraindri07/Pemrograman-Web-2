<?php
session_start();
$home = ['admin' => 'admin.php', 'guru' => 'guru.php', 'siswa' => 'index.php'];
header('Location: ' . (isset($_SESSION['user_id'], $home[$_SESSION['role'] ?? ''])
    ? 'dashboard/' . $home[$_SESSION['role']] : 'auth/login.php'));
exit;
