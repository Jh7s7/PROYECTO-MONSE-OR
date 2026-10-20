<?php
require_once 'config/database.php';

$email = 'admin@colegio.com';
$password = 'admin123';

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo "✅ Usuario encontrado:<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Status: " . $user['status'] . "<br>";
    echo "Password hash: " . $user['password'] . "<br><br>";
    
    if (password_verify($password, $user['password'])) {
        echo "✅ ¡LA CONTRASEÑA ES CORRECTA!";
    } else {
        echo "❌ LA CONTRASEÑA ES INCORRECTA";
    }
} else {
    echo "❌ Usuario no encontrado";
}
?>