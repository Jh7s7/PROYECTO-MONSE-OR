<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Contraseña: " . $password . "<br>";
echo "Hash: " . $hash;
?>