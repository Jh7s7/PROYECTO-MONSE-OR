<?php
require_once 'config/database.php';
$resultado = "";
$email_encontrado = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT full_name, email, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $email_encontrado = true;
        
        // ============================================================
        // GUARDAR NOTIFICACIÓN PARA EL ADMIN
        // ============================================================
        $mensaje = "El usuario " . $user['full_name'] . " (" . $user['email'] . ") solicita recuperar su contraseña.";
        $conn->query("INSERT INTO notificaciones_admin (mensaje) VALUES ('$mensaje')");
        
        $resultado = "
            <div class='alert alert-success'>
                ✅ Usuario encontrado.<br>
                📧 " . htmlspecialchars($user['email']) . "<br>
                👤 " . htmlspecialchars($user['full_name']) . "<br><br>
                📨 Se ha notificado al administrador.
            </div>
        ";
    } else {
        $resultado = "<div class='alert alert-error'>❌ No se encontró ningún usuario con ese email.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .recuperar-wrapper {
            max-width: 440px;
            width: 100%;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .recuperar-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px 30px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .recuperar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a365d, #e8c84c, #1a365d);
            background-size: 200% 100%;
            animation: gradientMove 4s ease-in-out infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .encabezado {
            text-align: center;
            padding-bottom: 15px;
            margin-bottom: 18px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        .encabezado h2 {
            font-family: 'Georgia', serif;
            font-size: 22px;
            font-weight: 700;
            color: #1a365d;
        }

        .encabezado h2 span {
            color: #e8c84c;
        }

        .encabezado p {
            color: #718096;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .logo-institucion {
            display: block;
            max-width: 80px;
            height: auto;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: white;
            padding: 6px;
            border: 3px solid #e8c84c;
            box-shadow: 0 8px 30px rgba(232, 200, 76, 0.2);
            transition: transform 0.3s ease;
        }

        .logo-institucion:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 54, 93, 0.3);
        }

        .links-inferiores {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
        }

        .links-inferiores a {
            color: #1a365d;
            text-decoration: none;
            margin: 0 6px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .links-inferiores a:hover {
            color: #e8c84c;
        }

        .alert {
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

        @media (max-width: 480px) {
            .recuperar-card {
                padding: 20px 16px;
            }
            .encabezado h2 {
                font-size: 18px;
            }
            .logo-institucion {
                max-width: 65px;
            }
        }
    </style>
</head>
<body>
    <div class="recuperar-wrapper">
        <div class="recuperar-card">
            <div class="encabezado">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
                <h2>Recuperar <span>Acceso</span></h2>
                <p>U.E.E. Monseñor Rafael Arias Blanco</p>
            </div>

            <?php echo $resultado; ?>

            <?php if(!$email_encontrado): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Ingresa tu email:</label>
                        <input type="email" name="email" placeholder="ejemplo@colegio.com" required>
                    </div>
                    <button type="submit" class="btn">🔍 Buscar</button>
                </form>
            <?php endif; ?>

            <div class="links-inferiores">
                <a href="index.php">← Volver al Inicio</a>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>