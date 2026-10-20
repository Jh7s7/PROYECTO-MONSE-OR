<?php
require_once 'config/database.php';

$mensaje = "";
$error = "";
$exito = false;
$user_id = null;
$token_valido = false;

// Verificar token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $conn->prepare("
        SELECT pr.*, u.full_name, u.email 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? AND pr.used = FALSE AND pr.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($reset = $result->fetch_assoc()) {
        $token_valido = true;
        $user_id = $reset['user_id'];
        $user_name = $reset['full_name'];
        $user_email = $reset['email'];
    } else {
        $error = "❌ El enlace de recuperación no es válido o ha expirado.";
    }
}

// Procesar cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password']) && isset($_POST['token'])) {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "❌ Las contraseñas no coinciden.";
    } elseif (strlen($new_password) < 8) {
        $error = "❌ La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Verificar token nuevamente
        $stmt = $conn->prepare("
            SELECT pr.*, u.id as user_id
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.used = FALSE AND pr.expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($reset = $result->fetch_assoc()) {
            // Actualizar contraseña
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $reset['user_id']);
            $stmt->execute();
            
            // Marcar token como usado
            $stmt = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            // Registrar en bitácora
            registerLog($conn, $reset['user_id'], 'Contraseña restablecida exitosamente');
            
            $mensaje = "
                <div class='success-alert'>
                    ✅ <strong>¡Contraseña actualizada exitosamente!</strong><br>
                    Ya puedes iniciar sesión con tu nueva contraseña.
                </div>
            ";
            $exito = true;
        } else {
            $error = "❌ El enlace de recuperación no es válido o ha expirado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .reset-container { max-width: 480px; margin: 0 auto; }
        .info-user {
            background: #f7fafc;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }
        .info-user p { margin: 4px 0; font-size: 14px; }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 48px; }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #a0aec0;
            padding: 4px;
        }
        .toggle-password:hover { color: #4a5568; }
        .strength-bar {
            display: flex;
            gap: 6px;
            height: 6px;
            margin-top: 6px;
        }
        .strength-bar .bar {
            flex: 1;
            border-radius: 4px;
            background: #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
        }
        .strength-bar .bar.active { background: #e53e3e; }
        .strength-bar .bar.active.medium { background: #ecc94b; }
        .strength-bar .bar.active.strong { background: #48bb78; }
        .strength-text { font-size: 12px; font-weight: 600; margin-top: 4px; }
        .strength-text.weak { color: #e53e3e; }
        .strength-text.medium { color: #ecc94b; }
        .strength-text.strong { color: #48bb78; }
    </style>
</head>
<body>
    <div class="login-container reset-container">
        <div class="encabezado">
            <img src="img/logo_colegio.png" alt="Escudo" class="logo-institucion" onerror="this.style.display='none'">
            <h2>🔑 Restablecer <span>Contraseña</span></h2>
            <p>Sistema de Boletines - U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($mensaje): ?>
            <?php echo $mensaje; ?>
            <?php if($exito): ?>
                <div class="links-inferiores">
                    <a href="index.php">← Volver al Inicio</a>
                </div>
            <?php endif; ?>
        <?php elseif($token_valido): ?>
            <div class="info-user">
                <p><strong>👤 Usuario:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <label>🔑 Nueva Contraseña <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" minlength="8" 
                           placeholder="Mínimo 8 caracteres" required
                           oninput="evaluarContraseña(this.value)">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
                </div>
                
                <div class="strength-bar" id="strengthBar">
                    <div class="bar" id="bar1"></div>
                    <div class="bar" id="bar2"></div>
                    <div class="bar" id="bar3"></div>
                    <div class="bar" id="bar4"></div>
                </div>
                <div class="strength-text" id="strengthText">Ingresa una contraseña</div>

                <label>🔁 Confirmar Contraseña <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" minlength="8" 
                           placeholder="Repite la contraseña" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>

                <button type="submit" class="btn-submit btn-green" style="margin-top: 16px;">
                    🔄 Actualizar Contraseña
                </button>
            </form>
        <?php else: ?>
            <div class="error-msg" style="margin-bottom: 16px;">
                <?php echo $error ?: "❌ Enlace no válido. Solicita un nuevo enlace de recuperación."; ?>
            </div>
            <div class="links-inferiores">
                <a href="recuperar.php">📤 Solicitar nuevo enlace</a> | 
                <a href="index.php">← Volver al Inicio</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const btn = input.parentElement.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }

        function evaluarContraseña(password) {
            const bar1 = document.getElementById('bar1');
            const bar2 = document.getElementById('bar2');
            const bar3 = document.getElementById('bar3');
            const bar4 = document.getElementById('bar4');
            const strengthText = document.getElementById('strengthText');
            const bars = [bar1, bar2, bar3, bar4];
            
            bars.forEach(bar => { bar.className = 'bar'; });
            
            if (password.length === 0) {
                strengthText.textContent = 'Ingresa una contraseña';
                strengthText.className = 'strength-text';
                return;
            }
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            let score = 0;
            if (hasLength) score++;
            if (hasUpper) score++;
            if (hasLower) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            let level = '';
            let message = '';
            
            if (score <= 2) {
                level = 'weak';
                message = '🔴 Débil - Mejora tu contraseña';
                strengthText.className = 'strength-text weak';
            } else if (score <= 3) {
                level = 'medium';
                message = '🟡 Media - Buena contraseña';
                strengthText.className = 'strength-text medium';
            } else if (score <= 4) {
                level = 'strong';
                message = '🟢 Fuerte - Excelente contraseña';
                strengthText.className = 'strength-text strong';
            } else {
                level = 'strong';
                message = '🟢 Muy Fuerte - Contraseña segura';
                strengthText.className = 'strength-text strong';
            }
            
            const activeBars = Math.min(score, 4);
            for (let i = 0; i < activeBars; i++) {
                bars[i].classList.add('active');
                if (level === 'strong') bars[i].classList.add('strong');
                else if (level === 'medium') bars[i].classList.add('medium');
            }
            
            strengthText.textContent = message;
        }
    </script>
</body>
</html>