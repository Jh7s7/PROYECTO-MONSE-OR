<?php
// Generar hash para la contraseña 'admin'
$password = 'admin';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "🔑 Contraseña: " . $password . "<br>";
echo "🔐 Hash generado: " . $hash . "<br><br>";

echo "📝 Copia y pega esto en phpMyAdmin:<br>";
echo "<code style='background:#f0f0f0;padding:10px;display:block;'>UPDATE users SET password = '" . $hash . "' WHERE email = 'admin@colegio.com';</code>";
?>