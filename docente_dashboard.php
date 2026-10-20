<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit;
}

$msg = "";

// Obtener ID del docente
$user_id = $_SESSION['user_id'];
$teacher_query = $conn->query("SELECT id FROM teachers WHERE user_id = $user_id");
if ($teacher_query && $teacher_query->num_rows > 0) {
    $teacher_row = $teacher_query->fetch_assoc();
    $teacher_id = $teacher_row['id'];
} else {
    $full_name = $_SESSION['full_name'];
    $employee_number = 'DOC-' . date('Y') . '-' . $user_id;
    $conn->query("INSERT INTO teachers (user_id, employee_number, full_name, status) VALUES ($user_id, '$employee_number', '$full_name', 'active')");
    $teacher_id = $conn->insert_id;
}

// ============================================================
// PROCESAR ACCIONES
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    // ELIMINAR boletín
    if ($action === 'delete' && isset($_POST['bulletin_id'])) {
        $bulletin_id = intval($_POST['bulletin_id']);
        $conn->query("DELETE FROM bulletins WHERE id = $bulletin_id AND teacher_id = $teacher_id");
        $msg = "✅ Boletín eliminado correctamente.";
    }
    
    // GUARDAR boletín
    if ($action === 'save_bulletin') {
        $student_id = intval($_POST['student_id']);
        $period = intval($_POST['period']);
        $nota_literal = $_POST['nota_literal'] ?? '';
        $apreciacion = trim($_POST['apreciacion'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $bulletin_id = isset($_POST['bulletin_id']) ? intval($_POST['bulletin_id']) : 0;
        $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
        $promovido = $_POST['promovido'] ?? 'si';
        $grado_promovido = trim($_POST['grado_promovido'] ?? '');
        
        $check = $conn->query("SELECT id FROM students WHERE id = $student_id");
        if ($check->num_rows == 0) {
            $msg = "❌ Estudiante no encontrado.";
        } else {
            if ($period == 3 && empty($nota_literal)) {
                $msg = "❌ El 3er Lapso requiere calificación literal (A, B, C, D, E).";
            } elseif ($period <= 2 && empty($apreciacion)) {
                $msg = "❌ El 1er y 2do Lapso requieren observaciones de desempeño.";
            } else {
                if ($bulletin_id > 0) {
                    $sql = "UPDATE bulletins SET period=$period, nota_literal='$nota_literal', apreciacion='$apreciacion', status='$status', fecha_entrega='$fecha_entrega', promovido='$promovido', grado_promovido='$grado_promovido' WHERE id=$bulletin_id AND teacher_id=$teacher_id";
                    if ($conn->query($sql)) { 
                        $msg = "✅ Boletín actualizado correctamente."; 
                    }
                } else {
                    $sql = "INSERT INTO bulletins (student_id, teacher_id, period, nota_literal, apreciacion, status, fecha_entrega, promovido, grado_promovido) 
                            VALUES ($student_id, $teacher_id, $period, '$nota_literal', '$apreciacion', '$status', '$fecha_entrega', '$promovido', '$grado_promovido')";
                    if ($conn->query($sql)) { 
                        $bulletin_id = $conn->insert_id;
                        $msg = "✅ Boletín creado correctamente."; 
                    }
                }
            }
        }
    }
    
    // PUBLICAR boletín
    if ($action === 'publish' && isset($_POST['bulletin_id'])) {
        $bulletin_id = intval($_POST['bulletin_id']);
        $token = bin2hex(random_bytes(32));
        $conn->query("UPDATE bulletins SET status='published', token='$token', published_at=NOW() WHERE id=$bulletin_id AND teacher_id=$teacher_id");
        $msg = "✅ Boletín publicado exitosamente. Enlace generado.";
    }
}

// ============================================================
// OBTENER DATOS
// ============================================================
$students = $conn->query("SELECT id, student_number, full_name, grade, section FROM students ORDER BY grade, section, full_name");
$bulletins = $conn->query("SELECT b.*, s.full_name as student_name FROM bulletins b JOIN students s ON b.student_id = s.id WHERE b.teacher_id = $teacher_id ORDER BY b.created_at DESC");
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_bulletins = $conn->query("SELECT COUNT(*) as count FROM bulletins WHERE teacher_id = $teacher_id")->fetch_assoc()['count'];
$published_bulletins = $conn->query("SELECT COUNT(*) as count FROM bulletins WHERE teacher_id = $teacher_id AND status = 'published'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Docente</title>
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
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== ENCABEZADO ===== */
        .encabezado {
            text-align: center;
            padding: 18px 20px 14px 20px;
            margin-bottom: 20px;
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

        /* ===== HEADER ===== */
        .docente-header {
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

        .docente-header .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .docente-header .header-left .logo-mini {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e8c84c;
            padding: 3px;
            background: white;
        }

        .docente-header .header-left h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .docente-header .header-left h1 span {
            color: #e8c84c;
        }

        .docente-header .header-left p {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            margin: 0;
        }

        .docente-user {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 6px 12px 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .docente-user .user-icon {
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

        .docente-user .user-name {
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
        }

        .docente-user .user-role {
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

        .btn-registrar {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-registrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        /* ===== TARJETA PRINCIPAL ===== */
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

        /* ===== ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 18px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-icon {
            font-size: 28px;
            margin-bottom: 4px;
            display: block;
        }

        .stat-card .stat-number {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 28px;
            font-weight: 900;
            color: #0f2440;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #718096;
            font-weight: 600;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .stat-card .stat-bar {
            height: 3px;
            border-radius: 4px;
            margin-top: 10px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .stat-card .stat-bar .fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #0f2440, #e8c84c);
            transition: width 1.5s ease;
        }

        .stat-card.blue { border-left: 4px solid #3b82f6; }
        .stat-card.green { border-left: 4px solid #10b981; }
        .stat-card.gold { border-left: 4px solid #f59e0b; }
        .stat-card.purple { border-left: 4px solid #8b5cf6; }

        /* ===== ADMIN GRID ===== */
        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            padding: 20px 24px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
        }

        .admin-card h2 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 18px;
            color: #0f2440;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-card h2 .badge-count {
            font-family: 'Inter', sans-serif;
            background: #e2e8f0;
            color: #4a5568;
            font-size: 12px;
            padding: 0 10px;
            border-radius: 20px;
            margin-left: auto;
        }

        /* ===== FORM ===== */
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

        .form-group select,
        .form-group input,
        .form-group textarea {
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

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #0f2440;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(15, 36, 64, 0.08);
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group select {
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

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }

        /* ===== BOTONES ===== */
        .btn {
            font-family: 'Inter', sans-serif;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-block {
            width: 100%;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        /* ===== BOTONES PEQUEÑOS ===== */
        .btn-sm-custom {
            font-family: 'Inter', sans-serif;
            padding: 4px 10px;
            font-size: 11px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-sm-custom:hover {
            transform: scale(1.05);
        }

        .btn-edit { background: #f59e0b; color: #0f2440; }
        .btn-edit:hover { background: #d97706; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        .btn-publish { background: #10b981; color: white; }
        .btn-publish:hover { background: #059669; }
        .btn-share { background: #25D366; color: white; }
        .btn-share:hover { background: #128C7E; }
        .btn-email { background: #D44638; color: white; }
        .btn-email:hover { background: #B23121; }
        .btn-copy { background: #3b82f6; color: white; }
        .btn-copy:hover { background: #2563eb; }

        /* ===== BADGES ===== */
        .badge {
            font-family: 'Inter', sans-serif;
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-draft { background: #e2e8f0; color: #475569; }
        .badge-published { background: #dbeafe; color: #1e40af; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }

        /* ===== TABLA ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table thead {
            background: linear-gradient(135deg, #0f2440, #1a365d);
            color: white;
        }

        .table th {
            font-family: 'Inter', sans-serif;
            padding: 10px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            font-family: 'Inter', sans-serif;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }

        .table tbody tr:hover td {
            background: #f7fafc;
        }

        /* ===== LAPSO ===== */
        .lapso-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .lapso-btn {
            flex: 1;
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: #f7fafc;
            cursor: pointer;
            font-weight: 700;
            text-align: center;
            min-width: 80px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        .lapso-btn:hover {
            border-color: #0f2440;
            background: #edf2f7;
        }

        .lapso-btn.active {
            border-color: #0f2440;
            background: #0f2440;
            color: white;
        }

        .lapso-btn .icon {
            font-size: 18px;
            display: block;
        }

        .lapso-btn .label {
            font-size: 11px;
        }

        .formato-descriptivo {
            background: #f7fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 14px;
        }

        .formato-literal {
            background: #f7fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 14px;
        }

        .info-box {
            font-family: 'Inter', sans-serif;
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #1e40af;
        }

        .info-box strong {
            color: #0f2440;
        }

        .hidden { display: none; }
        .visible { display: block; }

        .alert {
            font-family: 'Inter', sans-serif;
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

        .empty-message {
            text-align: center;
            color: #a0aec0;
            padding: 20px 0;
            font-family: 'Inter', sans-serif;
        }

        .acciones-cell {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .docente-header {
                flex-direction: column;
                text-align: center;
            }
            .docente-header .header-left {
                flex-direction: column;
            }
            .docente-user {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            .main-card {
                padding: 20px 16px;
            }
            .lapso-selector {
                flex-direction: column;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-row-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .admin-card {
                padding: 16px;
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

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #0f2440, #e8c84c); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: linear-gradient(135deg, #1a365d, #d4a830); }
    </style>
</head>
<body>
    <div class="container">

        <!-- ===== ENCABEZADO ===== -->
        <div class="encabezado">
            <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
            <h2>Gestión de <span>Boletines</span></h2>
            <p>U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <!-- ===== HEADER ===== -->
        <header class="docente-header">
            <div class="header-left">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-mini" onerror="this.style.display='none'">
                <div>
                    <h1>Panel del <span>Docente</span></h1>
                    <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <a href="registro_alumno.php" class="btn-registrar">🎓 Registrar Estudiante</a>
                <div class="docente-user">
                    <div class="user-icon"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1)); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">🚪 Salir</a>
                </div>
            </div>
        </header>

        <!-- ===== TARJETA PRINCIPAL ===== -->
        <div class="main-card">

            <!-- ===== MENSAJES ===== -->
            <?php if($msg): ?>
                <div class="alert <?php echo strpos($msg, '✅') !== false ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <span class="stat-icon">🎓</span>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Estudiantes</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($total_students * 10, 100); ?>%;"></div></div>
                </div>
                <div class="stat-card green">
                    <span class="stat-icon">📄</span>
                    <div class="stat-number"><?php echo $total_bulletins; ?></div>
                    <div class="stat-label">Mis Boletines</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($total_bulletins * 20, 100); ?>%;"></div></div>
                </div>
                <div class="stat-card gold">
                    <span class="stat-icon">📤</span>
                    <div class="stat-number"><?php echo $published_bulletins; ?></div>
                    <div class="stat-label">Publicados</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $total_bulletins > 0 ? round(($published_bulletins / max($total_bulletins, 1)) * 100) : 0; ?>%;"></div></div>
                </div>
                <div class="stat-card purple">
                    <span class="stat-icon">📝</span>
                    <div class="stat-number"><?php echo $total_bulletins - $published_bulletins; ?></div>
                    <div class="stat-label">Borradores</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $total_bulletins > 0 ? round((($total_bulletins - $published_bulletins) / max($total_bulletins, 1)) * 100) : 0; ?>%;"></div></div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- ADMIN GRID                                                  -->
            <!-- ========================================================== -->
            <div class="admin-grid">

                <!-- ========================================================== -->
                <!-- FORMULARIO                                                -->
                <!-- ========================================================== -->
                <div class="admin-card">
                    <h2 id="formTitle">📝 Crear Boletín</h2>

                    <div class="info-box">
                        <strong>📋 Formato según lapso:</strong><br>
                        • <strong>1er y 2do Lapso:</strong> Formato <strong>Descriptivo</strong><br>
                        • <strong>3er Lapso:</strong> Formato <strong>Literal</strong> (A, B, C, D, E)
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_bulletin">
                        <input type="hidden" name="bulletin_id" id="edit_bulletin_id" value="">

                        <div class="form-group">
                            <label>🎓 Estudiante *</label>
                            <select name="student_id" id="edit_student_id" required>
                                <option value="">-- Seleccionar --</option>
                                <?php 
                                $students->data_seek(0);
                                while($row = $students->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['student_number'] . ' - ' . $row['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>📅 Lapso *</label>
                            <div class="lapso-selector">
                                <button type="button" class="lapso-btn active" data-lapso="1" onclick="seleccionarLapso(1)">
                                    <span class="icon">📝</span>
                                    <span class="label">1er</span>
                                </button>
                                <button type="button" class="lapso-btn" data-lapso="2" onclick="seleccionarLapso(2)">
                                    <span class="icon">📝</span>
                                    <span class="label">2do</span>
                                </button>
                                <button type="button" class="lapso-btn" data-lapso="3" onclick="seleccionarLapso(3)">
                                    <span class="icon">📊</span>
                                    <span class="label">3er</span>
                                </button>
                            </div>
                            <input type="hidden" name="period" id="edit_period" value="1">
                        </div>

                        <!-- Descriptivo (1er y 2do) -->
                        <div id="formato_descriptivo" class="formato-descriptivo visible">
                            <div class="form-group">
                                <label>📝 Observaciones de Desempeño *</label>
                                <textarea name="apreciacion" id="edit_apreciacion" rows="4" placeholder="Describe el desempeño del estudiante en este lapso..."></textarea>
                            </div>
                        </div>

                        <!-- Literal (3er) -->
                        <div id="formato_literal" class="formato-literal hidden">
                            <div class="form-group">
                                <label>📊 Calificación Literal *</label>
                                <select name="nota_literal" id="edit_nota_literal">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="A">A - Excelente (20-18)</option>
                                    <option value="B">B - Muy Bueno (17-15)</option>
                                    <option value="C">C - Bueno (14-12)</option>
                                    <option value="D">D - Regular (11-09)</option>
                                    <option value="E">E - Insuficiente (08-00)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>📝 Observaciones Complementarias</label>
                                <textarea name="apreciacion" id="edit_apreciacion_literal" rows="3" placeholder="Observaciones adicionales sobre el desempeño..."></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>🎯 Promovido</label>
                                <select name="promovido" id="edit_promovido">
                                    <option value="si">✅ Sí, Promovido</option>
                                    <option value="no">❌ No Promovido</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>📚 Grado al que promueve</label>
                                <input type="text" name="grado_promovido" id="edit_grado_promovido" placeholder="Ej: 3er Grado">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>📅 Fecha de Entrega</label>
                            <input type="date" name="fecha_entrega" id="edit_fecha_entrega">
                        </div>

                        <div class="form-group">
                            <label>📌 Estado</label>
                            <select name="status" id="edit_status">
                                <option value="draft">📝 Borrador</option>
                                <option value="published">📤 Publicar ahora</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success btn-block" id="submitBtn">💾 Guardar</button>
                    </form>
                </div>

                <!-- ========================================================== -->
                <!-- LISTA DE BOLETINES                                        -->
                <!-- ========================================================== -->
                <div class="admin-card">
                    <h2>📋 Mis Boletines</h2>
                    <?php if($bulletins && $bulletins->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Lapso</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $bulletins->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                        <td><?php echo $row['period']; ?>°</td>
                                        <td>
                                            <span class="badge badge-<?php echo $row['status']; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                            <?php if($row['status'] === 'published'): ?>
                                                <span style="font-size:10px;color:#10b981;display:block;">🔗 Enlace generado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="acciones-cell">
                                                <?php if($row['status'] !== 'published'): ?>
                                                    <button class="btn-sm-custom btn-edit" onclick="editarBoletin(<?php echo $row['id']; ?>)">✏️</button>
                                                    <button class="btn-sm-custom btn-publish" onclick="publicarBoletin(<?php echo $row['id']; ?>)">📤</button>
                                                <?php else: ?>
                                                    <button class="btn-sm-custom btn-share" onclick="compartirWhatsApp('<?php echo $row['token']; ?>', '<?php echo htmlspecialchars($row['student_name']); ?>')" title="Compartir WhatsApp">💬</button>
                                                    <button class="btn-sm-custom btn-email" onclick="compartirCorreo('<?php echo $row['token']; ?>', '<?php echo htmlspecialchars($row['student_name']); ?>')" title="Compartir Correo">📧</button>
                                                    <button class="btn-sm-custom btn-copy" onclick="copiarEnlace('<?php echo $row['token']; ?>')" title="Copiar enlace">📋</button>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ¿Eliminar este boletín?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="bulletin_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="btn-sm-custom btn-delete">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-message">📭 No tienes boletines creados aún.</div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- ========================================================== -->
    <!-- SCRIPTS                                                    -->
    <!-- ========================================================== -->
    <script>
        // ============================================================
        // SELECCIONAR LAPSO
        // ============================================================
        function seleccionarLapso(lapso) {
            document.querySelectorAll('.lapso-btn').forEach(function(btn) {
                btn.classList.remove('active');
                if (parseInt(btn.dataset.lapso) === lapso) btn.classList.add('active');
            });
            document.getElementById('edit_period').value = lapso;
            
            var desc = document.getElementById('formato_descriptivo');
            var lit = document.getElementById('formato_literal');
            
            if (lapso <= 2) {
                desc.classList.remove('hidden');
                desc.classList.add('visible');
                lit.classList.remove('visible');
                lit.classList.add('hidden');
            } else {
                lit.classList.remove('hidden');
                lit.classList.add('visible');
                desc.classList.remove('visible');
                desc.classList.add('hidden');
            }
        }

        // ============================================================
        // EDITAR BOLETÍN
        // ============================================================
        function editarBoletin(id) {
            fetch('get_bulletin.php?id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    document.getElementById('edit_bulletin_id').value = data.id;
                    document.getElementById('edit_student_id').value = data.student_id;
                    document.getElementById('edit_apreciacion').value = data.apreciacion || '';
                    document.getElementById('edit_nota_literal').value = data.nota_literal || '';
                    document.getElementById('edit_fecha_entrega').value = data.fecha_entrega || '';
                    document.getElementById('edit_status').value = data.status || 'draft';
                    document.getElementById('edit_promovido').value = data.promovido || 'si';
                    document.getElementById('edit_grado_promovido').value = data.grado_promovido || '';
                    
                    seleccionarLapso(parseInt(data.period) || 1);
                    
                    document.getElementById('formTitle').textContent = '✏️ Editar Boletín';
                    document.getElementById('submitBtn').textContent = '💾 Actualizar';
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
        }

        // ============================================================
        // PUBLICAR BOLETÍN
        // ============================================================
        function publicarBoletin(id) {
            if (confirm('⚠️ ¿Publicar este boletín?\n\nLos representantes podrán verlo y confirmar lectura.')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="publish"><input type="hidden" name="bulletin_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ============================================================
        // COMPARTIR POR WHATSAPP
        // ============================================================
        function compartirWhatsApp(token, estudiante) {
            var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
            var mensaje = '📚 *Boletín de Calificaciones*\n\n' +
                          '🏫 U.E.E. Monseñor Rafael Arias Blanco\n' +
                          '👤 Estudiante: ' + estudiante + '\n\n' +
                          '🔗 ' + url + '\n\n' +
                          '📋 Por favor, revisa el boletín.';
            window.open('https://wa.me/?text=' + encodeURIComponent(mensaje), '_blank');
        }

        // ============================================================
        // COMPARTIR POR CORREO
        // ============================================================
        function compartirCorreo(token, estudiante) {
            var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
            var asunto = encodeURIComponent('📚 Boletín de Calificaciones - ' + estudiante);
            var cuerpo = encodeURIComponent(
                'Estimado Representante,\n\n' +
                'Se encuentra disponible el boletín de calificaciones de su representado(a):\n\n' +
                '👤 Estudiante: ' + estudiante + '\n' +
                '🔗 Enlace: ' + url + '\n\n' +
                'Por favor, ingrese al enlace para revisar el boletín.\n\n' +
                'Saludos cordiales,\n' +
                'U.E.E. Monseñor Rafael Arias Blanco'
            );
            window.open('mailto:?subject=' + asunto + '&body=' + cuerpo, '_blank');
        }

        // ============================================================
        // COPIAR ENLACE
        // ============================================================
        function copiarEnlace(token) {
            var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('✅ Enlace copiado al portapapeles:\n\n' + url);
                });
            } else {
                var input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('✅ Enlace copiado al portapapeles:\n\n' + url);
            }
        }

        // ============================================================
        // INICIALIZAR
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            seleccionarLapso(1);
        });
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>