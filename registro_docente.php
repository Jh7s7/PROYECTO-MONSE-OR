<?php
session_start();
require_once 'config/database.php';
$msg = "";
$status = "";

// Verificar si las tablas existen
$tablas_ok = true;
$check_users = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users->num_rows == 0) {
    $tablas_ok = false;
    $msg = "La tabla 'users' no existe. Contacta al administrador.";
}
$check_teachers = $conn->query("SHOW TABLES LIKE 'teachers'");
if ($check_teachers->num_rows == 0) {
    $tablas_ok = false;
    $msg = "La tabla 'teachers' no existe. Contacta al administrador.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $tablas_ok) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $grade = $_POST['grade'] ?? '';
    $section = $_POST['section'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($email) || empty($password) || empty($full_name)) {
        $msg = "Completa todos los campos obligatorios.";
        $status = "error";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check && $check->num_rows > 0) {
            $msg = "Este email ya está registrado.";
            $status = "error";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql1 = "INSERT INTO users (email, password, full_name, role, status) VALUES ('$email', '$hashed', '$full_name', 'teacher', 'pending')";
            if ($conn->query($sql1)) {
                $user_id = $conn->insert_id;
                $year = date('Y');
                $count_result = $conn->query("SELECT COUNT(*) as total FROM teachers WHERE YEAR(created_at) = $year");
                $count = 1;
                if ($count_result && $count_result->num_rows > 0) {
                    $row = $count_result->fetch_assoc();
                    $count = $row['total'] + 1;
                }
                $employee_number = 'DOC-' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
                
                // Verificar que la tabla teachers existe antes de insertar
                $check_teachers2 = $conn->query("SHOW TABLES LIKE 'teachers'");
                if ($check_teachers2->num_rows > 0) {
                    $sql2 = "INSERT INTO teachers (user_id, employee_number, full_name, grade, section, phone, status) VALUES ($user_id, '$employee_number', '$full_name', '$grade', '$section', '$phone', 'pending')";
                    if ($conn->query($sql2)) {
                        $msg = "Docente registrado. Número: <strong>" . $employee_number . "</strong>";
                        $status = "success";
                        registerLog($conn, null, 'Nuevo registro de docente: ' . $email);
                    } else {
                        $conn->query("DELETE FROM users WHERE id = $user_id");
                        $msg = "Error al crear docente: " . $conn->error;
                        $status = "error";
                    }
                } else {
                    $conn->query("DELETE FROM users WHERE id = $user_id");
                    $msg = "Error: La tabla 'teachers' no existe.";
                    $status = "error";
                }
            } else {
                $msg = "Error al crear usuario: " . $conn->error;
                $status = "error";
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
    <title>Registro de Docente</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(26,54,93,0.92), rgba(26,54,93,0.82)), url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1920&q=80');
            background-size: cover;
            background-position: center;
        }

        .register-wrapper { max-width: 560px; width: 100%; margin: 0 auto; }
        .register-card {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
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
        @keyframes gradientMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        .encabezado { text-align: center; padding-bottom: 15px; margin-bottom: 18px; border-bottom: 2px solid rgba(0,0,0,0.05); }
        .encabezado h2 { font-size: 22px; color: #1a365d; }
        .encabezado h2 span { color: #e8c84c; }
        .encabezado p { color: #718096; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .logo-institucion { display: block; max-width: 80px; margin: 0 auto 10px; border-radius: 50%; background: white; padding: 6px; border: 3px solid #e8c84c; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #2d3748; margin-bottom: 4px; }
        .form-group label .required { color: #e53e3e; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f7fafc;
        }
        .form-group input:focus, .form-group select:focus { border-color: #1a365d; outline: none; }
        .form-group select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a5568' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 12px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; color: white; background: linear-gradient(135deg, #1a365d, #2d4a7a); cursor: pointer; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(26,54,93,0.3); }

        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-weight: 500; font-size: 13px; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #48bb78; }
        .alert-error { background: #fff5f5; color: #c53030; border-left: 4px solid #fc8181; }

        .info-box { background: #f0fff4; border: 1px solid #48bb78; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; color: #276749; font-size: 13px; }
        .links { text-align: center; margin-top: 18px; border-top: 1px dashed #e2e8f0; padding-top: 16px; font-size: 13px; }
        .links a { color: #1a365d; text-decoration: none; font-weight: 600; margin: 0 8px; }
        .links a:hover { color: #e8c84c; }

        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .register-card { padding: 20px; } }
        @media (max-width: 480px) { .register-card { padding: 16px; } }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-card">
            <div class="encabezado">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
                <h2>Registro de <span>Docente</span></h2>
                <p>Sistema de Boletines - U.E.E. Monseñor Rafael Arias Blanco</p>
            </div>

            <?php if($msg && $status == 'error'): ?>
                <div class="alert alert-error">[!] <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if($msg && $status == 'success'): ?>
                <div class="alert alert-success">[OK] <?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if($tablas_ok): ?>
            <form method="POST">
                <div style="background:#f7fafc;padding:16px;border-radius:12px;margin-bottom:16px;">
                    <h4 style="color:#1a365d;margin-bottom:12px;font-size:14px;">Datos del Docente</h4>
                    
                    <div class="form-group">
                        <label>Nombre completo <span class="required">*</span></label>
                        <input type="text" name="full_name" placeholder="Ej: Prof. Maria Perez" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" placeholder="admin@colegio.com" required>
                        </div>
                        <div class="form-group">
                            <label>Contraseña <span class="required">*</span></label>
                            <input type="password" name="password" minlength="6" placeholder="********" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Grado (opcional)</label>
                            <select name="grade">
                                <option value="">Seleccionar...</option>
                                <option value="1er Grado">1er Grado</option>
                                <option value="2do Grado">2do Grado</option>
                                <option value="3er Grado">3er Grado</option>
                                <option value="4to Grado">4to Grado</option>
                                <option value="5to Grado">5to Grado</option>
                                <option value="6to Grado">6to Grado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sección (opcional)</label>
                            <select name="section">
                                <option value="">Seleccionar...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Teléfono (opcional)</label>
                        <input type="text" name="phone" placeholder="0424-1234567">
                    </div>
                </div>

                <div class="info-box">[i] El número de empleado se generará automáticamente.</div>

                <button type="submit" class="btn">Registrarse como Docente</button>
            </form>
            <?php else: ?>
                <div style="text-align:center;padding:20px;color:#718096;">
                    <p>⚠️ El sistema no está configurado correctamente.</p>
                    <p style="font-size:13px;margin-top:8px;">Contacta al administrador.</p>
                </div>
            <?php endif; ?>

            <div class="links">
                <a href="index.php">Volver al Inicio</a> | <a href="registro_usuario.php">Registrar Representante</a>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>