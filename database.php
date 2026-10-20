<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gestion_boletines";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

function registerLog($conn, $userId, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $conn->prepare("INSERT INTO access_logs (user_id, action, ip, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $ip, $userAgent);
    $stmt->execute();
}
?>