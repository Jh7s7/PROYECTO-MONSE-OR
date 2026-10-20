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

$filter_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_date = isset($_GET['date']) ? trim($_GET['date']) : '';

$where = array();
$params = array();
$types = "";

if ($filter_user > 0) {
    $where[] = "l.user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}
if (!empty($filter_action)) {
    $where[] = "l.action LIKE ?";
    $params[] = "%" . $filter_action . "%";
    $types .= "s";
}
if (!empty($filter_date)) {
    $where[] = "DATE(l.created_at) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT l.*, u.full_name as user_name, u.email as user_email FROM access_logs l LEFT JOIN users u ON l.user_id = u.id $where_sql ORDER BY l.created_at DESC LIMIT 200";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

$users = $conn->query("SELECT id, full_name, email FROM users ORDER BY full_name");
$total = $conn->query("SELECT COUNT(*) as count FROM access_logs")->fetch_assoc()['count'];
$hoy = $conn->query("SELECT COUNT(*) as count FROM access_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Accesos</title>
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
            background: linear-gradient(90deg, #059669, #fcd34d, #059669);
            background-size: 200% 100%;
            animation: gradientMove 4s ease-in-out infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ===== ENCABEZADO VERDE ===== */
        .encabezado {
            text-align: center;
            padding: 18px 20px 14px 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #059669, #10b981);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 25px rgba(5, 150, 105, 0.35);
        }

        .encabezado .logo-institucion {
            display: block;
            max-width: 65px;
            height: auto;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            border: 3px solid #fcd34d;
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
            color: #fcd34d;
        }

        .encabezado p {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.8);
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
            padding: 14px 20px;
            margin-bottom: 20px;
            background: rgba(5, 150, 105, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 14px;
            border: 1px solid rgba(5, 150, 105, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }

        .admin-header h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .admin-header h1 span {
            color: #fcd34d;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-user .user-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #fcd34d, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .admin-user .user-name {
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
        }

        .admin-user .user-role {
            font-family: 'Inter', sans-serif;
            background: rgba(252, 211, 77, 0.2);
            color: #fcd34d;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-logout {
            font-family: 'Inter', sans-serif;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 6px 14px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-logout:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-back {
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        /* ===== ESTADÍSTICAS ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-mini {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-mini:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-mini .num {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 28px;
            font-weight: 900;
            color: #1e3a5f;
        }

        .stat-mini .label {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* ===== FILTROS ===== */
        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .filters select,
        .filters input,
        .filters button {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            font-family: 'Inter', sans-serif;
        }

        .filters select:focus,
        .filters input:focus {
            border-color: #059669;
            outline: none;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .filters button {
            background: #059669;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filters button:hover {
            background: #047857;
        }

        .filters .btn-clear {
            background: #718096;
        }

        .filters .btn-clear:hover {
            background: #4a5568;
        }

        .filters .btn-export {
            background: #10b981;
        }

        .filters .btn-export:hover {
            background: #059669;
        }

        /* ===== TABLA ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table thead {
            background: linear-gradient(135deg, #059669, #10b981);
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

        .badge {
            font-family: 'Inter', sans-serif;
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .empty-message {
            text-align: center;
            color: #a0aec0;
            padding: 40px 0;
            font-family: 'Inter', sans-serif;
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }
            .filters {
                flex-direction: column;
            }
            .filters select,
            .filters input,
            .filters button {
                width: 100%;
            }
            .admin-header {
                flex-direction: column;
                text-align: center;
            }
            .admin-user {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            .main-card {
                padding: 20px 16px;
            }
            .table {
                font-size: 12px;
            }
            .table th,
            .table td {
                padding: 6px 8px;
            }
        }

        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            .main-card {
                padding: 14px 12px;
                border-radius: 16px;
            }
            .admin-container {
                padding: 10px;
            }
            .encabezado h2 {
                font-size: 17px;
            }
            .encabezado .logo-institucion {
                max-width: 50px;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #059669, #fcd34d);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #10b981, #f59e0b);
        }
    </style>
</head>
<body>
    <div class="admin-container">

        <!-- ===== ENCABEZADO VERDE ===== -->
        <div class="encabezado">
            <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
            <h2>Bitácora de <span>Accesos</span></h2>
            <p>U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <!-- ===== HEADER ADMIN ===== -->
        <header class="admin-header">
            <h1>📊 Registro de <span>Actividad</span></h1>
            <div class="admin-user" style="display:flex; align-items:center; gap:12px;">
                <div class="user-icon"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <span class="user-role">Administrador</span>
                </div>
                <a href="admin_dashboard.php" class="btn-back">← Volver</a>
                <a href="logout.php" class="btn-logout">🚪 Salir</a>
            </div>
        </header>

        <!-- ===== TARJETA PRINCIPAL ===== -->
        <div class="main-card">

            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="num"><?php echo $total; ?></div>
                    <div class="label">Total Accesos</div>
                </div>
                <div class="stat-mini">
                    <div class="num"><?php echo $hoy; ?></div>
                    <div class="label">Accesos Hoy</div>
                </div>
                <div class="stat-mini">
                    <div class="num"><?php echo $logs->num_rows; ?></div>
                    <div class="label">Mostrando</div>
                </div>
                <div class="stat-mini">
                    <?php 
                        $ultima = $conn->query("SELECT created_at FROM access_logs ORDER BY created_at DESC LIMIT 1")->fetch_assoc(); 
                    ?>
                    <div class="num" style="font-size:16px;"><?php echo $ultima ? date('d/m/Y H:i', strtotime($ultima['created_at'])) : 'Sin registros'; ?></div>
                    <div class="label">Último Acceso</div>
                </div>
            </div>

            <!-- ===== FILTROS ===== -->
            <form method="GET" class="filters">
                <select name="user">
                    <option value="0">Todos los usuarios</option>
                    <?php while($u = $users->fetch_assoc()): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $filter_user == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['full_name'] . ' (' . $u['email'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="action" placeholder="Buscar acción..." value="<?php echo htmlspecialchars($filter_action); ?>">
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                <button type="submit">🔍 Filtrar</button>
                <a href="admin_bitacora.php" class="btn-clear" style="padding:8px 20px;background:#718096;color:white;border-radius:8px;text-decoration:none;font-weight:600;text-align:center;">🔄 Limpiar</a>
                <?php if($logs->num_rows > 0): ?>
                    <button type="button" class="btn-export" onclick="exportarCSV()">📊 Exportar CSV</button>
                <?php endif; ?>
            </form>

            <!-- ===== TABLA ===== -->
            <?php if($logs && $logs->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table" id="logsTable">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Acción</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo $log['user_name'] ? htmlspecialchars($log['user_name']) : '<span style="color:#a0aec0;">Anónimo</span>'; ?></td>
                                    <td style="font-size:12px;color:#718096;"><?php echo $log['user_email'] ? htmlspecialchars($log['user_email']) : '—'; ?></td>
                                    <td>
                                        <?php 
                                            $badge = 'badge-info';
                                            if (strpos($log['action'], 'Login') !== false) $badge = 'badge-success';
                                            if (strpos($log['action'], 'error') !== false || strpos($log['action'], 'incorrecta') !== false) $badge = 'badge-danger';
                                            if (strpos($log['action'], 'Aprobó') !== false || strpos($log['action'], 'Rechazó') !== false) $badge = 'badge-warning';
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                                    </td>
                                    <td style="font-size:12px;color:#718096;"><?php echo htmlspecialchars($log['ip'] ?: '—'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-message">📭 No hay registros de actividad.</div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function exportarCSV() {
            var table = document.getElementById('logsTable');
            if (!table) return;
            
            var csv = '';
            var rows = table.querySelectorAll('tr');
            rows.forEach(function(row) {
                var cols = row.querySelectorAll('th, td');
                var rowData = [];
                cols.forEach(function(col) {
                    var text = col.innerText.replace(/,/g, ';');
                    rowData.push('"' + text + '"');
                });
                csv += rowData.join(',') + '\n';
            });
            
            var blob = new Blob([csv], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'bitacora_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>