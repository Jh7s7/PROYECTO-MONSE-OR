<?php<?php
require_once 'config/database.php';

$email = 'admin@colegio.com';
$password = 'admin'; // <-- LA CONTRASEÑA QUE QUIERES

echo "🔍 Verificando credenciales:<br>";
echo "Email: " . $email . "<br>";
echo "Contraseña: " . $password . "<br><br>";

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo "✅ Usuario encontrado<br>";
    echo "📧 Email: " . $user['email'] . "<br>";
    echo "👤 Nombre: " . $user['full_name'] . "<br>";
    echo "🎯 Rol: " . $user['role'] . "<br>";
    echo "📊 Status: " . $user['status'] . "<br>";
    echo "🔑 Hash guardado: " . $user['password'] . "<br><br>";
    
    // Verificar con password_verify
    if (password_verify($password, $user['password'])) {
        echo "✅✅✅ ¡CONTRASEÑA CORRECTA! ✅✅✅<br>";
        echo "🔑 Ahora puedes iniciar sesión con:<br>";
        echo "Email: admin@colegio.com<br>";
        echo "Contraseña: admin";
    } else {
        echo "❌❌❌ LA CONTRASEÑA ES INCORRECTA ❌❌❌<br><br>";
        
        // Listar contraseñas comunes para probar
        $passwords = ['admin', 'admin123', '123456', 'password', 'Admin', 'ADMIN', 'administrador'];
        echo "🔍 Probando contraseñas comunes:<br>";
        foreach ($passwords as $pwd) {
            if (password_verify($pwd, $user['password'])) {
                echo "✅ ¡La contraseña es: '" . $pwd . "'!<br>";
                break;
            }
        }
        
        // Generar nuevo hash para 'admin'
        $newHash = password_hash('admin', PASSWORD_BCRYPT);
        echo "<br>🔑 Para cambiar la contraseña a 'admin', ejecuta en phpMyAdmin:<br>";
        echo "<code style='background:#f0f0f0;padding:8px;display:block;'>UPDATE users SET password = '" . $newHash . "' WHERE email = 'admin@colegio.com';</code>";
    }
} else {
    echo "❌ Usuario no encontrado.<br>";
    echo "📝 Para crearlo, ejecuta en phpMyAdmin:<br>";
    echo "<code style='background:#f0f0f0;padding:8px;display:block;'>INSERT INTO users (email, password, full_name, role, status) VALUES ('admin@colegio.com', '" . password_hash('admin', PASSWORD_BCRYPT) . "', 'Administrador', 'admin', 'active');</code>";
}
?>