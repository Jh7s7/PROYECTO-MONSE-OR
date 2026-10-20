<?php
session_start();
require_once 'config/database.php';
$msg = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $identification = trim($_POST['identification'] ?? '');
    
    if (empty($email) || empty($password) || empty($full_name) || empty($identification)) {
        $msg = "Completa todos los campos obligatorios.";
        $status = "error";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check && $check->num_rows > 0) {
            $msg = "Este email ya está registrado.";
            $status = "error";
        } else {
            $check_id = $conn->query("SELECT id FROM representatives WHERE identification = '$identification'");
            if ($check_id && $check_id->num_rows > 0) {
                $msg = "Esta cédula/identificación ya está registrada.";
                $status = "error";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $sql1 = "INSERT INTO users (email, password, full_name, role, status) VALUES ('$email', '$hashed', '$full_name', 'representative', 'pending')";
                if ($conn->query($sql1)) {
                    $user_id = $conn->insert_id;
                    $sql2 = "INSERT INTO representatives (user_id, identification, full_name, phone, address, status) VALUES ($user_id, '$identification', '$full_name', '$phone', '$address', 'pending')";
                    if ($conn->query($sql2)) {
                        $msg = "Representante registrado exitosamente.";
                        $status = "success";
                        registerLog($conn, null, 'Nuevo registro de representante: ' . $email);
                    } else {
                        $conn->query("DELETE FROM users WHERE id = $user_id");
                        $msg = "Error al crear representante. Verifica que la tabla 'representatives' tenga las columnas: phone, address.";
                        $status = "error";
                    }
                } else {
                    $msg = "Error al crear usuario.";
                    $status = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Representante</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
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

        .register-wrapper {
            max-width: 560px;
            width: 100%;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a365d, #e8c84c, #1a365d);
            animation: gradientMove 4s ease-in-out infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .encabezado {
            text-align: center;
            padding: 15px 15px 12px 15px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #1a365d, #2d4a7a);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 20px rgba(26, 54, 93, 0.3);
        }

        .encabezado .logo-institucion {
            display: block;
            max-width: 65px;
            height: auto;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            border: 3px solid #e8c84c;
        }

        .encabezado h2 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin: 0;
        }

        .encabezado h2 span {
            color: #e8c84c;
        }

        .encabezado p {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.7);
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            font-family: 'Inter', sans-serif;
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .form-group label .required {
            color: #e53e3e;
        }

        .form-group input {
            font-family: 'Inter', sans-serif;
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: #f7fafc;
            color: #1a202c;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #1a365d;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 54, 93, 0.08);
        }

        .form-group input::placeholder {
            color: #a0aec0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #a0aec0;
            padding: 4px;
            font-weight: 700;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #4a5568;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #1a365d, #2d4a7a);
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 54, 93, 0.3);
        }

        .alert {
            font-family: 'Inter', sans-serif;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 500;
            font-size: 13px;
        }

        .alert-success {
            background: #f0fff4;
            color: #276749;
            border-left: 4px solid #48bb78;
        }

        .alert-error {
            background: #fff5f5;
            color: #c53030;
            border-left: 4px solid #fc8181;
        }

        .warning-box {
            font-family: 'Inter', sans-serif;
            background: #fffbeb;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 10px 0;
            color: #975a16;
            font-size: 12px;
            text-align: center;
        }

        .links {
            font-family: 'Inter', sans-serif;
            text-align: center;
            margin-top: 18px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
            font-size: 13px;
        }

        .links a {
            color: #1a365d;
            text-decoration: none;
            font-weight: 600;
            margin: 0 8px;
            transition: color 0.3s;
        }

        .links a:hover {
            color: #e8c84c;
        }

        .links .separator {
            color: #cbd5e1;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .register-card {
                padding: 20px 16px;
            }
            .register-wrapper {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 16px 14px;
                border-radius: 16px;
            }
            .encabezado h2 {
                font-size: 17px;
            }
            .encabezado .logo-institucion {
                max-width: 50px;
            }
            .btn {
                font-size: 14px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-card">
            <div class="encabezado">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
                <h2>Registro de <span>Representante</span></h2>
                <p>U.E.E. Monseñor Rafael Arias Blanco</p>
            </div>

            <?php if($msg && $status == 'error'): ?>
                <div class="alert alert-error">[!] <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if($msg && $status == 'success'): ?>
                <div class="alert alert-success">[OK] <?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div style="background:#f7fafc;padding:16px;border-radius:12px;margin-bottom:16px;">
                    <h4 style="color:#1a365d;margin-bottom:12px;font-size:14px;font-weight:700;">Datos del Representante</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre completo <span class="required">*</span></label>
                            <input type="text" name="full_name" placeholder="Ej: Maria Perez" required>
                        </div>
                        <div class="form-group">
                            <label>Cédula / Identificación <span class="required">*</span></label>
                            <input type="text" name="identification" placeholder="Ej: V12345678" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" placeholder="admin@colegio.com" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="phone" placeholder="0412-1234567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="address" placeholder="Santa Eulalia, Los Teques">
                    </div>

                    <div class="form-group">
                        <label>Contraseña <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" minlength="6" placeholder="********" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">[O]</button>
                        </div>
                    </div>
                </div>

                <div class="warning-box">[!] Tu cuenta estará <strong>pendiente de aprobación</strong> por el administrador.</div>

                <button type="submit" class="btn">Registrarse</button>
            </form>

            <div class="links">
                <a href="index.php">Volver al Inicio</a>
                <span class="separator">|</span>
                <a href="registro_alumno.php">Registrar Estudiante</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            var input = document.getElementById(inputId);
            var btn = input.parentElement.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '[X]';
            } else {
                input.type = 'password';
                btn.textContent = '[O]';
            }
        }
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>