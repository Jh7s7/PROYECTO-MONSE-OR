<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE users SET status = 'active' WHERE id = $user_id");
        registerLog($conn, $_SESSION['user_id'], 'Aprobó usuario ID: ' . $user_id);
    } elseif ($action === 'reject') {
        $conn->query("UPDATE users SET status = 'inactive' WHERE id = $user_id");
        registerLog($conn, $_SESSION['user_id'], 'Rechazó usuario ID: ' . $user_id);
    }
}

header("Location: admin_dashboard.php");
exit;
?>