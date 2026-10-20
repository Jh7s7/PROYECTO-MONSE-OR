<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// ============================================================
// VERIFICAR ACCESO - SOLO REPRESENTANTES
// ============================================================
if (!isset($_SESSION['user_id']) && !isset($_SESSION['temp_student_id'])) {
    header("Location: index.php");
    exit;
}

// Verificar que el usuario sea representante (si está logueado)
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] !== 'representative' && $user['role'] !== 'admin') {
        header("Location: index.php");
        exit;
    }
}

$mensaje = "";
$boletin_id_ver = isset($_GET['ver']) ? intval($_GET['ver']) : 0;

// ============================================================
// CONFIRMAR LECTURA (sin representante_id)
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_lectura'])) {
    $obs = htmlspecialchars($_POST['observacion'] ?? '');
    $bulletin_id = intval($_POST['bulletin_id']);
    
    // Verificar que el boletín existe y está publicado
    $check = $conn->prepare("SELECT id, student_id FROM bulletins WHERE id = ? AND status = 'published'");
    $check->bind_param("i", $bulletin_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Guardar confirmación sin representante (NULL)
        $stmt = $conn->prepare("
            INSERT INTO confirmations (bulletin_id, representative_id, observation) 
            VALUES (?, NULL, ?)
        ");
        $stmt->bind_param("is", $bulletin_id, $obs);
        if ($stmt->execute()) {
            $update = $conn->prepare("UPDATE bulletins SET status = 'confirmed' WHERE id = ?");
            $update->bind_param("i", $bulletin_id);
            $update->execute();
            $mensaje = "✅ Lectura confirmada exitosamente!";
        }
    }
}

// ============================================================
// OBTENER TODOS LOS BOLETINES PUBLICADOS
// ============================================================
$query = "
    SELECT 
        b.*,
        s.full_name as student_name,
        s.student_number,
        s.grade,
        s.section,
        t.full_name as docente_nombre,
        c.confirmed_at,
        c.observation as rep_observation
    FROM bulletins b
    JOIN students s ON b.student_id = s.id
    LEFT JOIN teachers t ON b.teacher_id = t.id
    LEFT JOIN confirmations c ON b.id = c.bulletin_id
    WHERE b.status = 'published' OR b.status = 'confirmed'
    ORDER BY b.period DESC, b.created_at DESC
";

$result = $conn->query($query);
$bulletins = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bulletins[] = $row;
    }
}

// Boletín seleccionado
$boletin_seleccionado = null;
if ($boletin_id_ver > 0) {
    foreach ($bulletins as $b) {
        if ($b['id'] == $boletin_id_ver) {
            $boletin_seleccionado = $b;
            break;
        }
    }
}
// Si no hay seleccionado, tomar el primero
if (!$boletin_seleccionado && count($bulletins) > 0) {
    $boletin_seleccionado = $bulletins[0];
}

$year = date('Y');
$next_year = date('Y') + 1;
$user_name = $_SESSION['full_name'] ?? 'Representante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Boletines</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
            background: 
                linear-gradient(135deg, rgba(26, 54, 93, 0.92) 0%, rgba(26, 54, 93, 0.82) 100%),
                url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0f2440, #e8c84c, #0f2440);
            background-size: 200% 100%;
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
            background: linear-gradient(135deg, #0f2440, #1a365d);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 25px rgba(15, 36, 64, 0.4);
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
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
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

        .rep-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }

        .rep-header .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rep-header .header-left .logo-mini {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e8c84c;
            padding: 3px;
            background: white;
        }

        .rep-header .header-left h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .rep-header .header-left h1 span {
            color: #e8c84c;
        }

        .rep-header .header-left p {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            margin: 0;
        }

        .rep-user {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 6px 12px 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .rep-user .user-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e8c84c, #d4a830);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #0f2440;
        }

        .rep-user .user-name {
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
        }

        .rep-user .user-role {
            font-family: 'Inter', sans-serif;
            background: rgba(232, 200, 76, 0.15);
            color: #e8c84c;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-logout {
            font-family: 'Inter', sans-serif;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            padding: 6px 14px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s;
            border: 1px solid rgba(239, 68, 68, 0.1);
        }

        .btn-logout:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-print {
            font-family: 'Inter', sans-serif;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 6px 14px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-print:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
        }

        .menu-lateral {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            height: fit-content;
        }

        .menu-lateral .menu-header {
            background: linear-gradient(135deg, #0f2440, #1a365d);
            color: white;
            padding: 14px 18px;
            font-family: 'Playfair Display', 'Georgia', serif;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-lateral .menu-header .badge-count {
            background: #e8c84c;
            color: #0f2440;
            padding: 0 10px;
            border-radius: 20px;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            text-decoration: none;
            color: #2d3748;
            gap: 10px;
            transition: all 0.3s;
        }

        .menu-item:hover {
            background: #f7fafc;
        }

        .menu-item.active {
            background: #dbeafe;
            border-left: 3px solid #1a365d;
        }

        .menu-item .info {
            flex: 1;
        }

        .menu-item .info .titulo {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 13px;
        }

        .menu-item .info .detalle {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            color: #718096;
        }

        .status-badge {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .status-badge.leido {
            background: #c6f6d5;
            color: #276749;
        }

        .status-badge.pendiente {
            background: #fefcbf;
            color: #975a16;
        }

        .menu-empty {
            padding: 20px;
            text-align: center;
            color: #a0aec0;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .boletin-paper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .boletin-paper .boletin-header {
            text-align: center;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .boletin-paper .boletin-header h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 18px;
            color: #1a365d;
            font-weight: 800;
        }

        .boletin-paper .boletin-header h2 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 15px;
            color: #1a365d;
            font-weight: 700;
        }

        .boletin-paper .boletin-header p {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #4a5568;
        }

        .boletin-paper .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 16px;
            background: #f7fafc;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
        }

        .boletin-paper .info-grid .label {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: #2d3748;
            font-size: 12px;
        }

        .boletin-paper .info-grid .value {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #1a202c;
        }

        .boletin-paper .observacion-texto {
            font-family: 'Inter', sans-serif;
            background: #f7fafc;
            padding: 12px 14px;
            border-radius: 8px;
            border-left: 4px solid #1a365d;
            margin: 12px 0;
            line-height: 1.6;
            font-size: 13px;
            white-space: pre-wrap;
        }

        .boletin-paper .literal-box {
            text-align: center;
            padding: 14px;
            margin: 12px 0;
            background: #ebf8ff;
            border-radius: 8px;
            border: 2px solid #90cdf4;
        }

        .boletin-paper .literal-box .literal {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 38px;
            font-weight: 900;
            color: #1a365d;
        }

        .boletin-paper .literal-box .descripcion {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #2a69ac;
        }

        .boletin-paper .promocion {
            text-align: center;
            padding: 10px;
            background: #f0fff4;
            border-radius: 8px;
            border: 2px solid #48bb78;
            margin: 12px 0;
        }

        .boletin-paper .promocion .si {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #276749;
            font-weight: 800;
            font-size: 18px;
        }

        .boletin-paper .promocion .no {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #e53e3e;
            font-weight: 800;
            font-size: 18px;
        }

        .boletin-paper .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
        }

        .boletin-paper .firma-box {
            text-align: center;
        }

        .boletin-paper .firma-box .linea {
            border-bottom: 2px solid #a0aec0;
            margin: 4px 0;
            min-height: 25px;
        }

        .boletin-paper .firma-box .label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 600;
            color: #4a5568;
        }

        .estado-boletin {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado-published {
            background: #bee3f8;
            color: #2a69ac;
        }

        .estado-confirmed {
            background: #c6f6d5;
            color: #276749;
        }

        .confirmed-box {
            background: #f0fff4;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #c6f6d5;
            color: #276749;
            margin-top: 10px;
        }

        .form-confirmacion {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-confirmacion input {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            min-width: 150px;
        }

        .btn-confirmar {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-confirmar:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 500;
            font-size: 13px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .empty-boletin {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
            font-family: 'Inter', sans-serif;
        }

        .no-print { display: block; }

        @media print {
            .no-print { display: none !important; }
            .dashboard-grid { display: block; }
            .menu-lateral { display: none; }
            .boletin-paper { border: none; box-shadow: none; }
            .container { padding: 0; }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .rep-header {
                flex-direction: column;
                text-align: center;
            }
            .rep-header .header-left {
                flex-direction: column;
            }
            .rep-user {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            .boletin-paper .info-grid {
                grid-template-columns: 1fr;
            }
            .boletin-paper .firmas {
                grid-template-columns: 1fr 1fr;
            }
            .form-confirmacion {
                flex-direction: column;
            }
            .main-card {
                padding: 20px 16px;
            }
        }

        @media (max-width: 480px) {
            .boletin-paper .firmas {
                grid-template-columns: 1fr;
            }
            .boletin-paper .literal-box .literal {
                font-size: 28px;
            }
            .main-card {
                padding: 14px 12px;
                border-radius: 16px;
            }
            .encabezado h2 {
                font-size: 17px;
            }
            .encabezado .logo-institucion {
                max-width: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="encabezado">
            <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
            <h2>Mis <span>Boletines</span></h2>
            <p>U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <header class="rep-header">
            <div class="header-left">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-mini" onerror="this.style.display='none'">
                <div>
                    <h1>📚 Mis <span>Boletines</span></h1>
                    <p><?php echo htmlspecialchars($user_name); ?></p>
                </div>
            </div>
            <div class="rep-user">
                <div class="user-icon"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="user-role">Representante</span>
                </div>
                <button onclick="window.print()" class="btn-print" title="Imprimir">🖨️</button>
                <a href="logout.php" class="btn-logout">🚪 Salir</a>
            </div>
        </header>

        <div class="main-card">

            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="dashboard-grid">

                <div class="menu-lateral no-print">
                    <div class="menu-header">
                        📋 Boletines
                        <span class="badge-count"><?php echo count($bulletins); ?></span>
                    </div>
                    <?php if(count($bulletins) > 0): ?>
                        <?php foreach($bulletins as $b): ?>
                            <a href="?ver=<?php echo $b['id']; ?>" class="menu-item <?php echo ($boletin_seleccionado && $boletin_seleccionado['id'] == $b['id']) ? 'active' : ''; ?>">
                                <span><?php echo $b['status'] === 'confirmed' ? '✅' : '📄'; ?></span>
                                <div class="info">
                                    <div class="titulo"><?php echo htmlspecialchars($b['student_name']); ?></div>
                                    <div class="detalle"><?php echo $b['period']; ?>° Lapso - <?php echo date('d/m/Y', strtotime($b['published_at'] ?? $b['created_at'])); ?></div>
                                </div>
                                <span class="status-badge <?php echo $b['status'] === 'confirmed' ? 'leido' : 'pendiente'; ?>">
                                    <?php echo $b['status'] === 'confirmed' ? 'Leído' : 'Pendiente'; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="menu-empty">📭 No hay boletines publicados.</div>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if($boletin_seleccionado): ?>
                        <div class="boletin-paper">
                            <div class="boletin-header">
                                <h1>U.E.E. MONSEÑOR RAFAEL ARIAS BLANCO</h1>
                                <h2>EVALUACIÓN FINAL DE LA ACTUACIÓN DEL ALUMNO</h2>
                                <p>Santa Eulalia - Los Teques - Edo. Miranda</p>
                                <div style="font-size:12px;color:#718096;">AÑO ESCOLAR: <?php echo $year . ' - ' . $next_year; ?></div>
                            </div>

                            <div class="info-grid">
                                <div><span class="label">ALUMNO(A):</span> <span class="value"><strong><?php echo htmlspecialchars($boletin_seleccionado['student_name']); ?></strong></span></div>
                                <div><span class="label">C.E. ó C.I.:</span> <span class="value"><?php echo htmlspecialchars($boletin_seleccionado['student_number']); ?></span></div>
                                <div><span class="label">GRADO:</span> <span class="value"><strong><?php echo htmlspecialchars($boletin_seleccionado['grade']); ?></strong></span></div>
                                <div><span class="label">SECCIÓN:</span> <span class="value"><strong><?php echo htmlspecialchars($boletin_seleccionado['section']); ?></strong></span></div>
                                <div><span class="label">LAPSO:</span> <span class="value"><strong><?php echo $boletin_seleccionado['period']; ?>° Lapso</strong></span></div>
                                <div><span class="label">DOCENTE:</span> <span class="value"><strong><?php echo htmlspecialchars($boletin_seleccionado['docente_nombre'] ?? 'N/A'); ?></strong></span></div>
                                <div style="grid-column: span 2;"><span class="label">ESTADO:</span> 
                                    <span class="estado-boletin estado-<?php echo $boletin_seleccionado['status']; ?>">
                                        <?php echo ucfirst($boletin_seleccionado['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="font-size:10px;color:#718096;text-align:center;padding:6px;background:#f7fafc;border-radius:6px;margin-bottom:10px;border:1px solid #e2e8f0;">
                                Artículo 44: Ley Orgánica de Educación Gaceta Oficial Nº 5.929 Extraordinario del 15 de Agosto de 2009. 
                                Artículos 99, 108 y 118: Reglamento General de la Ley Orgánica de Educación.
                            </div>

                            <?php if($boletin_seleccionado['period'] <= 2): ?>
                                <div class="observacion-texto">
                                    <?php echo nl2br(htmlspecialchars($boletin_seleccionado['apreciacion'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="literal-box">
                                    <div class="literal"><?php echo htmlspecialchars($boletin_seleccionado['nota_literal']); ?></div>
                                    <div class="descripcion">
                                        <?php 
                                            $desc = [
                                                'A' => 'El alumno alcanzó todas las competencias y en algunos casos superó las expectativas previstas para el grado.',
                                                'B' => 'El alumno alcanzó todas las competencias previstas para el grado.',
                                                'C' => 'El alumno alcanzó la mayoría de las competencias previstas para el grado.',
                                                'D' => 'El alumno alcanzó algunas de las competencias previstas para el grado, pero requiere de un proceso de nivelación al inicio del nuevo año escolar para alcanzar las restantes.',
                                                'E' => 'El alumno no logró adquirir las competencias mínimas requeridas para ser promovido al grado inmediato superior.'
                                            ];
                                            echo $desc[$boletin_seleccionado['nota_literal']] ?? '';
                                        ?>
                                    </div>
                                </div>
                                <?php if(!empty($boletin_seleccionado['apreciacion'])): ?>
                                    <div class="observacion-texto" style="border-left-color:#48bb78;">
                                        <strong>Observaciones Complementarias:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($boletin_seleccionado['apreciacion'])); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="promocion">
                                <?php if($boletin_seleccionado['promovido'] === 'si'): ?>
                                    <div class="si">✅ PROMOVIDO(A)</div>
                                    <div>al <strong><?php echo htmlspecialchars($boletin_seleccionado['grado_promovido'] ?? 'Grado Superior'); ?></strong>, 
                                    con el literal <strong><?php echo htmlspecialchars($boletin_seleccionado['nota_literal']); ?></strong></div>
                                <?php else: ?>
                                    <div class="no">❌ NO PROMOVIDO(A)</div>
                                    <div>con el literal <strong><?php echo htmlspecialchars($boletin_seleccionado['nota_literal']); ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <div style="text-align:center;margin:8px 0;font-size:13px;color:#4a5568;">
                                FECHA DE ENTREGA: <strong><?php echo $boletin_seleccionado['fecha_entrega'] ? date('d/m/Y', strtotime($boletin_seleccionado['fecha_entrega'])) : '__________'; ?></strong>
                            </div>

                            <div class="firmas">
                                <div class="firma-box"><div class="linea">&nbsp;</div><div class="label">REPRESENTANTE</div></div>
                                <div class="firma-box"><div class="linea">&nbsp;</div><div class="label">DOCENTE</div></div>
                                <div class="firma-box"><div class="linea">&nbsp;</div><div class="label">COORDINADOR PEDAGÓGICO</div></div>
                                <div class="firma-box"><div class="linea">&nbsp;</div><div class="label">DIRECCIÓN</div></div>
                            </div>

                            <div style="margin-top:12px;" class="no-print">
                                <?php if($boletin_seleccionado['status'] === 'confirmed'): ?>
                                    <div class="confirmed-box">
                                        ✅ Confirmado el <?php echo date('d/m/Y H:i', strtotime($boletin_seleccionado['confirmed_at'] ?? 'now')); ?>
                                        <?php if($boletin_seleccionado['rep_observation']): ?>
                                            <br><small>📝 Observación: <?php echo htmlspecialchars($boletin_seleccionado['rep_observation']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif($boletin_seleccionado['status'] === 'published'): ?>
                                    <form method="POST" class="form-confirmacion">
                                        <input type="hidden" name="bulletin_id" value="<?php echo $boletin_seleccionado['id']; ?>">
                                        <input type="text" name="observacion" placeholder="📝 Observación (opcional)">
                                        <button type="submit" name="confirmar_lectura" class="btn-confirmar">✅ Confirmar Lectura</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top:12px;padding:8px;background:#f7fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:10px;color:#718096;">
                                <strong>ESCALA ALFABÉTICA:</strong> 
                                A: Excelente | B: Muy Bueno | C: Bueno | D: Regular | E: Insuficiente
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-boletin">
                            📭 No hay boletines publicados disponibles.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>