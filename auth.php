<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: index.php");
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    if (!hasRole($role) && !hasRole('admin')) {
        header("Location: index.php");
        exit;
    }
}

function requireAdmin() {
    requireRole('admin');
}

function getRoleName($role) {
    $roles = [
        'admin' => 'Administrador',
        'teacher' => 'Docente',
        'representative' => 'Representante',
        'user' => 'Usuario'
    ];
    return $roles[$role] ?? $role;
}
?>