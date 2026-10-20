<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ============================================================
// MARCAR NOTIFICACIÓN COMO LEÍDA
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marcar_leida'])) {
    $id = intval($_POST['marcar_leida']);
    $conn->query("UPDATE notificaciones_admin SET leida = 1 WHERE id = $id");
    header("Location: admin_dashboard.php");
    exit;
}

// ============================================================
// ELIMINAR NOTIFICACIÓN
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_notificacion'])) {
    $id = intval($_POST['eliminar_notificacion']);
    $conn->query("DELETE FROM notificaciones_admin WHERE id = $id");
    header("Location: admin_dashboard.php");
    exit;
}

// ============================================================
// ESTADÍSTICAS
// ============================================================
$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$stats['pending_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'];
$stats['active_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$stats['inactive_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")->fetch_assoc()['count'];
$stats['total_teachers'] = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$stats['total_representatives'] = $conn->query("SELECT COUNT(*) as count FROM representatives")->fetch_assoc()['count'];
$stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$stats['total_bulletins'] = $conn->query("SELECT COUNT(*) as count FROM bulletins")->fetch_assoc()['count'];
$stats['published_bulletins'] = $conn->query("SELECT COUNT(*) as count FROM bulletins WHERE status = 'published'")->fetch_assoc()['count'];
$stats['confirmed_bulletins'] = $conn->query("SELECT COUNT(*) as count FROM bulletins WHERE status = 'confirmed'")->fetch_assoc()['count'];

// ============================================================
// NOTIFICACIONES
// ============================================================
$notificaciones = $conn->query("SELECT * FROM notificaciones_admin ORDER BY created_at DESC LIMIT 10");
$notificaciones_no_leidas = $conn->query("SELECT COUNT(*) as count FROM notificaciones_admin WHERE leida = 0")->fetch_assoc()['count'];

$pending_users = $conn->query("SELECT id, email, full_name, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           ADMIN DASHBOARD - ESTILOS COMPLETOS
           ============================================================ */
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

        .admin-container {
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

        /* ===== HEADER ADMIN ===== */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 14px 24px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #0f2440, #1a365d);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .admin-header .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-header .header-left .logo-mini {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e8c84c;
            padding: 3px;
            background: white;
        }

        .admin-header .header-left h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .admin-header .header-left h1 span {
            color: #e8c84c;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 6px 12px 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-user .user-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #e8c84c, #d4a830);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            color: #0f2440;
        }

        .admin-user .user-name {
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
        }

        .admin-user .user-role {
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

        /* ===== NOTIFICACIONES ===== */
        .notificacion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s;
        }

        .notificacion-item:hover {
            background: #f7fafc;
        }

        .notificacion-item.nueva {
            background: #dbeafe;
            border-left: 3px solid #1a365d;
        }

        .notificacion-item .texto {
            flex: 1;
        }

        .notificacion-item .texto .mensaje {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #2d3748;
        }

        .notificacion-item .texto .fecha {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            color: #718096;
            margin-top: 2px;
        }

        .notificacion-item .accion {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-marcar-leida {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-marcar-leida:hover {
            background: #e2e8f0;
        }

        .btn-ir-accion {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #0f2440;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-ir-accion:hover {
            background: #1a365d;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(15, 36, 64, 0.3);
        }

        .leida-texto {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #10b981;
            font-weight: 600;
        }

        .badge-nuevas {
            background: #ef4444;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
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
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0f2440, #e8c84c);
            opacity: 0.6;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-icon {
            font-size: 30px;
            margin-bottom: 4px;
            display: block;
        }

        .stat-card .stat-number {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 32px;
            font-weight: 900;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
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

        /* ===== COLORES DE ESTADÍSTICAS ===== */
        .stat-card.blue .stat-number { color: #1e40af; }
        .stat-card.blue { border-left: 4px solid #3b82f6; }
        .stat-card.blue::after { background: #3b82f6; }

        .stat-card.green .stat-number { color: #065f46; }
        .stat-card.green { border-left: 4px solid #10b981; }
        .stat-card.green::after { background: #10b981; }

        .stat-card.gold .stat-number { color: #92400e; }
        .stat-card.gold { border-left: 4px solid #f59e0b; }
        .stat-card.gold::after { background: #f59e0b; }

        .stat-card.purple .stat-number { color: #5b21b6; }
        .stat-card.purple { border-left: 4px solid #8b5cf6; }
        .stat-card.purple::after { background: #8b5cf6; }

        .stat-card.red .stat-number { color: #991b1b; }
        .stat-card.red { border-left: 4px solid #ef4444; }
        .stat-card.red::after { background: #ef4444; }

        .stat-card.teal .stat-number { color: #134e4a; }
        .stat-card.teal { border-left: 4px solid #14b8a6; }
        .stat-card.teal::after { background: #14b8a6; }

        .stat-card.orange .stat-number { color: #9a3412; }
        .stat-card.orange { border-left: 4px solid #f97316; }
        .stat-card.orange::after { background: #f97316; }

        .stat-card.pink .stat-number { color: #831843; }
        .stat-card.pink { border-left: 4px solid #ec4899; }
        .stat-card.pink::after { background: #ec4899; }

        /* ===== ADMIN CARD ===== */
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

        /* ===== BOTONES ===== */
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

        .btn-approve {
            background: #10b981;
            color: white;
        }
        .btn-approve:hover { background: #059669; }

        .btn-reject {
            background: #ef4444;
            color: white;
        }
        .btn-reject:hover { background: #dc2626; }

        /* ===== ACCIONES RÁPIDAS ===== */
        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .action-btn {
            font-family: 'Inter', sans-serif;
            background: rgba(247, 250, 252, 0.8);
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            text-decoration: none;
            color: #2d3748;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .action-btn:hover {
            background: #0f2440;
            color: white;
            border-color: #0f2440;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(15, 36, 64, 0.2);
        }

        .empty-message {
            text-align: center;
            color: #10b981;
            padding: 20px 0;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .admin-grid { grid-template-columns: 1fr; }
            .action-grid { grid-template-columns: 1fr; }
            .admin-header { flex-direction: column; text-align: center; }
            .admin-header .header-left { flex-direction: column; }
            .admin-user { flex-wrap: wrap; justify-content: center; width: 100%; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .main-card { padding: 20px 16px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .admin-card { padding: 16px; }
            .admin-container { padding: 10px; }
            .main-card { padding: 14px 12px; border-radius: 16px; }
            .encabezado h2 { font-size: 17px; }
            .encabezado .logo-institucion { max-width: 50px; }
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #0f2440, #e8c84c); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: linear-gradient(135deg, #1a365d, #d4a830); }
    </style>
</head>
<body>
    <div class="admin-container">

        <!-- ===== ENCABEZADO ===== -->
        <div class="encabezado">
            <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
            <h2>Panel de <span>Administración</span></h2>
            <p>U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <!-- ===== HEADER ADMIN ===== -->
        <header class="admin-header">
            <div class="header-left">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-mini" onerror="this.style.display='none'">
                <h1>📊 Panel de <span>Administración</span></h1>
            </div>
            <div class="admin-user">
                <div class="user-icon"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <span class="user-role">Administrador</span>
                </div>
                <a href="logout.php" class="btn-logout">🚪 Salir</a>
            </div>
        </header>

        <!-- ===== TARJETA PRINCIPAL ===== -->
        <div class="main-card">

            <!-- ========================================================== -->
            <!-- NOTIFICACIONES                                              -->
            <!-- ========================================================== -->
            <div class="admin-card" style="margin-bottom:24px;">
                <h2>
                    🔔 Notificaciones
                    <?php if($notificaciones_no_leidas > 0): ?>
                        <span class="badge-nuevas"><?php echo $notificaciones_no_leidas; ?> nuevas</span>
                    <?php endif; ?>
                </h2>
                <?php if($notificaciones && $notificaciones->num_rows > 0): ?>
                    <div style="max-height:250px;overflow-y:auto;">
                        <?php while($noti = $notificaciones->fetch_assoc()): 
                            $enlace = '#';
                            $mensaje_lower = strtolower($noti['mensaje']);
                            
                            if (strpos($mensaje_lower, 'recuperar') !== false || strpos($mensaje_lower, 'contraseña') !== false) {
                                $enlace = 'admin_usuarios.php';
                            } elseif (strpos($mensaje_lower, 'docente') !== false) {
                                $enlace = 'admin_docentes.php';
                            } elseif (strpos($mensaje_lower, 'representante') !== false) {
                                $enlace = 'admin_representantes.php';
                            } elseif (strpos($mensaje_lower, 'estudiante') !== false) {
                                $enlace = 'admin_estudiantes.php';
                            }
                        ?>
                            <div class="notificacion-item <?php echo $noti['leida'] ? '' : 'nueva'; ?>">
                                <div class="texto">
                                    <div class="mensaje"><?php echo htmlspecialchars($noti['mensaje']); ?></div>
                                    <div class="fecha">📅 <?php echo date('d/m/Y H:i', strtotime($noti['created_at'])); ?></div>
                                </div>
                                <div class="accion">
                                    <?php if(!$noti['leida']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="marcar_leida" value="<?php echo $noti['id']; ?>">
                                            <button type="submit" class="btn-marcar-leida" title="Marcar como leída">✅</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="leida-texto">✓ Leída</span>
                                    <?php endif; ?>
                                    <a href="<?php echo $enlace; ?>" class="btn-ir-accion" title="Ir a la acción">➜</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-message">📭 No hay notificaciones</div>
                <?php endif; ?>
            </div>

            <!-- ========================================================== -->
            <!-- ESTADÍSTICAS (8 TARJETAS)                                    -->
            <!-- ========================================================== -->
            <div class="stats-grid">

                <!-- Total Usuarios -->
                <div class="stat-card blue">
                    <span class="stat-icon">👥</span>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Usuarios</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($stats['total_users'] * 10, 100); ?>%;"></div></div>
                </div>

                <!-- Usuarios Activos -->
                <div class="stat-card green">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0; ?>%;"></div></div>
                </div>

                <!-- Pendientes -->
                <div class="stat-card gold">
                    <span class="stat-icon">⏳</span>
                    <div class="stat-number"><?php echo $stats['pending_users']; ?></div>
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $stats['total_users'] > 0 ? round(($stats['pending_users'] / $stats['total_users']) * 100) : 0; ?>%;"></div></div>
                </div>

                <!-- Estudiantes -->
                <div class="stat-card purple">
                    <span class="stat-icon">🎓</span>
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Estudiantes</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($stats['total_students'] * 5, 100); ?>%;"></div></div>
                </div>

                <!-- Docentes -->
                <div class="stat-card teal">
                    <span class="stat-icon">👨‍🏫</span>
                    <div class="stat-number"><?php echo $stats['total_teachers']; ?></div>
                    <div class="stat-label">Docentes</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($stats['total_teachers'] * 10, 100); ?>%;"></div></div>
                </div>

                <!-- Representantes -->
                <div class="stat-card orange">
                    <span class="stat-icon">👩‍👧</span>
                    <div class="stat-number"><?php echo $stats['total_representatives']; ?></div>
                    <div class="stat-label">Representantes</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo min($stats['total_representatives'] * 10, 100); ?>%;"></div></div>
                </div>

                <!-- Publicados -->
                <div class="stat-card blue">
                    <span class="stat-icon">📄</span>
                    <div class="stat-number"><?php echo $stats['published_bulletins']; ?></div>
                    <div class="stat-label">Publicados</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $stats['total_bulletins'] > 0 ? round(($stats['published_bulletins'] / max($stats['total_bulletins'], 1)) * 100) : 0; ?>%;"></div></div>
                </div>

                <!-- Confirmados -->
                <div class="stat-card pink">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $stats['confirmed_bulletins']; ?></div>
                    <div class="stat-label">Confirmados</div>
                    <div class="stat-bar"><div class="fill" style="width: <?php echo $stats['total_bulletins'] > 0 ? round(($stats['confirmed_bulletins'] / max($stats['total_bulletins'], 1)) * 100) : 0; ?>%;"></div></div>
                </div>

            </div>

            <!-- ========================================================== -->
            <!-- GRID ADMIN                                                  -->
            <!-- ========================================================== -->
            <div class="admin-grid">

                <!-- Usuarios Pendientes -->
                <div class="admin-card">
                    <h2>
                        👥 Usuarios Pendientes
                        <span class="badge-count"><?php echo $stats['pending_users']; ?></span>
                    </h2>
                    <?php if($pending_users && $pending_users->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($user = $pending_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst($user['role']); ?></td>
                                        <td>
                                            <form method="POST" action="admin_aprobar.php" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-sm-custom btn-approve">✅ Aprobar</button>
                                            </form>
                                            <form method="POST" action="admin_aprobar.php" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-sm-custom btn-reject">❌ Rechazar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-message">✅ No hay usuarios pendientes de aprobación.</div>
                    <?php endif; ?>
                </div>

                <!-- Acciones Rápidas -->
                <div class="admin-card">
                    <h2>⚡ Acciones Rápidas</h2>
                    <div class="action-grid">
                        <a href="registro_alumno.php" class="action-btn">🎓 Registrar Estudiante</a>
                        <a href="registro_docente.php" class="action-btn">👨‍🏫 Registrar Docente</a>
                        <a href="registro_usuario.php" class="action-btn">📝 Registrar Representante</a>
                        <a href="admin_usuarios.php" class="action-btn">👥 Gestionar Usuarios</a>
                        <a href="admin_bitacora.php" class="action-btn">📋 Bitácora</a>
                        <a href="admin_dashboard.php" class="action-btn">📊 Reportes</a>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>