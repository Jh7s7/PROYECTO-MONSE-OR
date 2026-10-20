<?php
session_start();
require_once 'config/database.php';

// Solo Admin y Docente pueden registrar
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: index.php");
    exit;
}

$msg = "";
$status = "";

// Obtener representantes y docentes
$representatives = $conn->query("SELECT id, full_name, identification FROM representatives WHERE status = 'active'");
$teachers = $conn->query("SELECT id, full_name, employee_number FROM teachers WHERE status = 'active'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $grade = $_POST['grade'];
    $section = $_POST['section'];
    $representative_id = $_POST['representative_id'] ? intval($_POST['representative_id']) : 'NULL';
    $teacher_id = $_POST['teacher_id'] ? intval($_POST['teacher_id']) : 'NULL';
    
    if (empty($full_name) || empty($grade) || empty($section)) {
        $msg = "Completa todos los campos obligatorios.";
        $status = "error";
    } else {
        $year = date('Y');
        $count_result = $conn->query("SELECT COUNT(*) as total FROM students WHERE YEAR(created_at) = $year");
        $count = 1;
        if ($count_result && $count_result->num_rows > 0) {
            $row = $count_result->fetch_assoc();
            $count = $row['total'] + 1;
        }
        $student_number = 'E' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO students (student_number, full_name, grade, section, representative_id, teacher_id) 
                VALUES ('$student_number', '$full_name', '$grade', '$section', $representative_id, $teacher_id)";
        
        if ($conn->query($sql)) {
            $msg = "Estudiante registrado. Número: <strong>" . $student_number . "</strong>";
            $status = "success";
            registerLog($conn, $_SESSION['user_id'] ?? null, 'Registró estudiante: ' . $student_number);
        } else {
            $msg = "Error al registrar estudiante: " . $conn->error;
            $status = "error";
        }
    }
}

// Determinar a dónde regresar según el rol
$rol = $_SESSION['role'] ?? '';
$destino_regreso = ($rol === 'admin') ? 'admin_dashboard.php' : 'docente_dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiante</title>
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

        .form-group input,
        .form-group select {
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

        .form-group input:focus,
        .form-group select:focus {
            border-color: #1a365d;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 54, 93, 0.08);
        }

        .form-group input::placeholder {
            color: #a0aec0;
        }

        .form-group select {
            padding: 10px 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a5568' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ===== BOTONES ===== */
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #48bb78, #38a169);
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        /* ===== BOTÓN DE REGRESAR ===== */
        .btn-regresar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #718096, #4a5568);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-regresar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(113, 128, 150, 0.3);
        }

        .btn-regresar .icon {
            font-size: 18px;
        }

        .acciones {
            display: flex;
            gap: 12px;
            margin-top: 18px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 18px;
            flex-wrap: wrap;
            justify-content: center;
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

        .info-box {
            font-family: 'Inter', sans-serif;
            background: #f0fff4;
            border: 1px solid #48bb78;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
            color: #276749;
            font-size: 13px;
            text-align: center;
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
            .acciones {
                flex-direction: column;
            }
            .btn-regresar {
                justify-content: center;
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
                <h2>Registrar <span>Estudiante</span></h2>
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
                    <h4 style="color:#1a365d;margin-bottom:12px;font-size:14px;font-weight:700;">Datos del Estudiante</h4>

                    <div class="form-group">
                        <label>Nombre completo <span class="required">*</span></label>
                        <input type="text" name="full_name" placeholder="Ej: Juan Perez" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Grado <span class="required">*</span></label>
                            <select name="grade" required>
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
                            <label>Sección <span class="required">*</span></label>
                            <select name="section" required>
                                <option value="">Seleccionar...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Representante (opcional)</label>
                            <select name="representative_id">
                                <option value="">Seleccionar...</option>
                                <?php if($representatives && $representatives->num_rows > 0): ?>
                                    <?php while($row = $representatives->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No hay representantes activos</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Docente (opcional)</label>
                            <select name="teacher_id">
                                <option value="">Seleccionar...</option>
                                <?php if($teachers && $teachers->num_rows > 0): ?>
                                    <?php while($row = $teachers->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No hay docentes activos</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    [i] El <strong>número de estudiante</strong> se generará automáticamente al registrar.
                </div>

                <button type="submit" class="btn">💾 Registrar Estudiante</button>
            </form>

            <!-- ===== BOTÓN DE REGRESAR ===== -->
            <div class="acciones">
                <a href="<?php echo $destino_regreso; ?>" class="btn-regresar">
                    <span class="icon">←</span> Regresar
                </a>
                <a href="logout.php" class="btn-regresar" style="background:linear-gradient(135deg,#fc8181,#e53e3e);">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>