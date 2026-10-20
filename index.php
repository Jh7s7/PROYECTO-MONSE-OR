<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

$error = "";

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($role === 'teacher') {
        header("Location: docente_dashboard.php");
        exit;
    } elseif ($role === 'representative') {
        header("Location: ver_boletin.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Por favor, ingresa tu email y contraseña.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Tu cuenta está pendiente de aprobación o ha sido desactivada.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    registerLog($conn, $user['id'], 'Login exitoso');
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'teacher') {
                        header("Location: docente_dashboard.php");
                        exit;
                    } elseif ($user['role'] === 'representative') {
                        header("Location: ver_boletin.php");
                        exit;
                    }
                }
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "No existe una cuenta con ese email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U.E.E. Monseñor Rafael Arias Blanco</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           LOGIN EMBELLECIDO - DISEÑO PREMIUM
           ============================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: 
                linear-gradient(135deg, rgba(26, 54, 93, 0.92) 0%, rgba(26, 54, 93, 0.82) 100%),
                url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(25px);
            border-radius: 28px;
            padding: 42px 36px 36px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #1a365d, #e8c84c, #d4a830, #1a365d);
            background-size: 300% 100%;
            animation: gradientMove 5s ease-in-out infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ===== DECORACIÓN ===== */
        .deco-bg {
            position: absolute;
            border-radius: 50%;
            opacity: 0.03;
            pointer-events: none;
        }
        .deco-bg.d1 {
            width: 300px;
            height: 300px;
            background: #1a365d;
            top: -100px;
            right: -100px;
        }
        .deco-bg.d2 {
            width: 200px;
            height: 200px;
            background: #e8c84c;
            bottom: -80px;
            left: -80px;
        }

        /* ===== LOGO ===== */
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .login-logo .logo-circle {
            width: 90px;
            height: 90px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: white;
            padding: 6px;
            border: 3px solid #e8c84c;
            box-shadow: 0 8px 35px rgba(232, 200, 76, 0.25);
            transition: transform 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-logo .logo-circle:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .login-logo .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .login-logo .institution-name {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 20px;
            font-weight: 800;
            color: #1a365d;
            line-height: 1.2;
        }

        .login-logo .institution-name span {
            color: #e8c84c;
        }

        .login-logo .institution-sub {
            font-size: 11px;
            color: #718096;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* ===== TÍTULO ===== */
        .login-title {
            text-align: center;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .login-title h2 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 28px;
            font-weight: 900;
            color: #1a365d;
            letter-spacing: -0.5px;
        }

        .login-title h2 span {
            color: #e8c84c;
            position: relative;
        }

        .login-title h2 span::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e8c84c;
            border-radius: 2px;
            opacity: 0.3;
        }

        .login-title p {
            color: #718096;
            font-size: 14px;
            margin-top: 6px;
            font-weight: 400;
        }

        /* ===== ERROR ===== */
        .error-msg {
            background: #fff5f5;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 500;
            font-size: 13px;
            border-left: 4px solid #fc8181;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        .error-msg .error-icon {
            font-size: 20px;
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #a0aec0;
            transition: color 0.3s;
            z-index: 2;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            background: #f7fafc;
            color: #1a202c;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus {
            border-color: #1a365d;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 54, 93, 0.08);
        }

        .input-wrapper input:focus ~ .input-icon {
            color: #1a365d;
        }

        .input-wrapper input::placeholder {
            color: #a0aec0;
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #a0aec0;
            padding: 4px;
            transition: color 0.3s;
            z-index: 2;
        }

        .input-wrapper .toggle-password:hover {
            color: #4a5568;
        }

        /* ===== OPCIONES ===== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 8px;
            position: relative;
            z-index: 1;
        }

        .form-options .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #4a5568;
            cursor: pointer;
        }

        .form-options .remember input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a365d;
            cursor: pointer;
            border-radius: 4px;
        }

        .form-options .forgot-link {
            font-size: 13px;
            color: #1a365d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-options .forgot-link:hover {
            color: #e8c84c;
        }

        /* ===== BOTÓN ===== */
        .btn-login {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #1a365d, #2d4a7a);
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(26, 54, 93, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login .btn-icon {
            margin-right: 8px;
        }

        /* ===== DIVISOR ===== */
        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }

        .divider span {
            font-size: 12px;
            color: #a0aec0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            white-space: nowrap;
        }

        /* ===== REGISTRO ===== */
        .register-section {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .register-section p {
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .register-links {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .register-links a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #718096;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 50px;
            background: rgba(247, 250, 252, 0.8);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .register-links a:hover {
            color: #1a365d;
            border-color: #1a365d;
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .register-links a .icon-link {
            font-size: 16px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .login-card {
                padding: 28px 20px 24px;
                border-radius: 20px;
            }
            .login-title h2 {
                font-size: 22px;
            }
            .login-logo .logo-circle {
                width: 70px;
                height: 70px;
            }
            .login-logo .institution-name {
                font-size: 17px;
            }
            .input-wrapper input {
                padding: 12px 14px 12px 44px;
                font-size: 14px;
            }
            .form-options {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .register-links {
                flex-direction: column;
                align-items: center;
            }
            .register-links a {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 380px) {
            .login-card {
                padding: 20px 14px 18px;
            }
            .login-title h2 {
                font-size: 18px;
            }
            .btn-login {
                font-size: 14px;
                padding: 14px;
            }
        }

        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1a365d, #e8c84c);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2d4a7a, #d4a830);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Decoración de fondo -->
            <div class="deco-bg d1"></div>
            <div class="deco-bg d2"></div>

            <!-- ===== LOGO ===== -->
            <div class="login-logo">
                <div class="logo-circle">
                    <img src="img/logo_colegio.png" alt="Logo" onerror="this.style.display='none'">
                </div>
                <div class="institution-name">
                    U.E.E. <span>Monseñor</span><br>Rafael Arias Blanco
                </div>
                <div class="institution-sub">✦ Santa Eulalia - Los Teques - Edo. Miranda ✦</div>
            </div>

            <!-- ===== TÍTULO ===== -->
            <div class="login-title">
                <h2>📋 Control de <span>Boletines</span></h2>
                <p>🔐 Ingresa a tu cuenta para continuar</p>
            </div>

            <!-- ===== ERROR ===== -->
            <?php if($error): ?>
                <div class="error-msg">
                    <span class="error-icon">⚠️</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- ===== FORMULARIO ===== -->
            <form method="POST">
                <div class="form-group">
                    <label>📧 Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="ejemplo@colegio.com" required autofocus>
                        <span class="input-icon">✉️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>🔑 Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <span class="input-icon">🔒</span>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember"> ✅ Recordarme
                    </label>
                    <a href="recuperar.php" class="forgot-link">🔓 ¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-login">
                    <span class="btn-icon">🚀</span> Iniciar Sesión
                </button>
            </form>

            <!-- ===== DIVISOR ===== -->
            <div class="divider">
                <span>✨ ¿Nuevo usuario?</span>
            </div>

            <!-- ===== REGISTRO ===== -->
            <div class="register-section">
                <p>📝 Regístrate según tu rol:</p>
                <div class="register-links">
                    <a href="registro_usuario.php">
                        <span class="icon-link">👩‍👧</span> Representante
                    </a>
                    <a href="registro_docente.php">
                        <span class="icon-link">👨‍🏫</span> Docente
                    </a>
                    <a href="ayuda.php">
                        <span class="icon-link">❓</span> Ayuda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            var input = document.getElementById(inputId);
            var btn = document.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>