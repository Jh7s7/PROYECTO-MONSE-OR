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

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($action === 'delete' && $user_id > 0) {
        if ($user_id == $_SESSION['user_id']) {
            $mensaje = "No puedes eliminar tu propia cuenta.";
        } else {
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $mensaje = "Usuario eliminado correctamente.";
            registerLog($conn, $_SESSION['user_id'], 'Eliminó usuario ID: ' . $user_id);
        }
    }
    
    if ($action === 'toggle_status' && $user_id > 0) {
        $new_status = $_POST['status'] ?? 'active';
        $conn->query("UPDATE users SET status = '$new_status' WHERE id = $user_id");
        $mensaje = "Estado actualizado correctamente.";
        registerLog($conn, $_SESSION['user_id'], 'Cambió estado de usuario ID: ' . $user_id);
    }
    
    if ($action === 'reset_password' && $user_id > 0) {
        $new_password = $_POST['new_password'] ?? '12345678';
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $conn->query("UPDATE users SET password = '$hashed' WHERE id = $user_id");
        $mensaje = "Contraseña restablecida a: <strong>" . htmlspecialchars($new_password) . "</strong>";
        registerLog($conn, $_SESSION['user_id'], 'Restableció contraseña de usuario ID: ' . $user_id);
    }
}

$users = $conn->query("SELECT id, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios</title>
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

        /* ===== HEADER ADMIN CON MEJOR CONTRASTE ===== */
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

        .btn-regresar {
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            padding: 6px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .btn-regresar:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* ===== ALERTAS ===== */
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

        /* ===== CADA ROL CON SU COLOR ===== */
        .badge {
            font-family: 'Inter', sans-serif;
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin { background: #1a365d; color: white; box-shadow: 0 2px 8px rgba(26, 54, 93, 0.3); }
        .badge-teacher { background: #6b46c1; color: white; box-shadow: 0 2px 8px rgba(107, 70, 193, 0.3); }
        .badge-representative { background: #276749; color: white; box-shadow: 0 2px 8px rgba(39, 103, 73, 0.3); }
        .badge-user { background: #718096; color: white; box-shadow: 0 2px 8px rgba(113, 128, 150, 0.3); }

        .badge-active { background: #c6f6d5; color: #276749; }
        .badge-pending { background: #fefcbf; color: #975a16; }
        .badge-inactive { background: #fed7d7; color: #9b2c2c; }

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

        .btn-sm-custom:hover { transform: scale(1.05); }

        .btn-active { background: #48bb78; color: white; }
        .btn-active:hover { background: #38a169; }
        .btn-inactive { background: #fc8181; color: white; }
        .btn-inactive:hover { background: #e53e3e; }
        .btn-delete { background: #e53e3e; color: white; }
        .btn-delete:hover { background: #c53030; }
        .btn-reset { background: #ecc94b; color: #1a365d; }
        .btn-reset:hover { background: #d4a830; }

        .actions-cell { display: flex; gap: 4px; flex-wrap: wrap; }
        .text-muted { color: #a0aec0; font-size: 12px; }

        /* ===== MODAL ===== */
        .password-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .password-modal.active { display: flex; }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 { font-family: 'Playfair Display', 'Georgia', serif; color: #1a365d; margin-bottom: 16px; }
        .modal-content p { font-family: 'Inter', sans-serif; color: #4a5568; margin-bottom: 16px; }

        .modal-content .form-group { margin-bottom: 14px; }
        .modal-content .form-group label { font-family: 'Inter', sans-serif; display: block; font-size: 13px; font-weight: 600; color: #2d3748; margin-bottom: 4px; }
        .modal-content .form-group input { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; }

        .modal-content .btn-submit-modal { width: 100%; padding: 12px; background: #4299e1; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-family: 'Inter', sans-serif; transition: all 0.3s; }
        .modal-content .btn-submit-modal:hover { background: #2b6cb0; }
        .modal-content .btn-cancel-modal { width: 100%; padding: 12px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-family: 'Inter', sans-serif; margin-top: 8px; transition: all 0.3s; }
        .modal-content .btn-cancel-modal:hover { background: #cbd5e1; }

        .password-input-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .password-input-group input { flex: 1; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-width: 150px; font-family: 'Inter', sans-serif; }
        .password-input-group .btn-generate { background: #e2e8f0; border: none; padding: 10px 14px; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; white-space: nowrap; transition: all 0.3s; }
        .password-input-group .btn-generate:hover { background: #cbd5e1; }

        @media (max-width: 768px) {
            .actions-cell { flex-direction: column; }
            .admin-header { flex-direction: column; text-align: center; }
            .admin-user { flex-wrap: wrap; justify-content: center; width: 100%; }
            .main-card { padding: 20px 16px; }
            .password-input-group { flex-direction: column; }
            .password-input-group input { width: 100%; }
            .header-actions { justify-content: center; }
        }

        @media (max-width: 480px) {
            .main-card { padding: 14px 12px; border-radius: 16px; }
            .table { font-size: 11px; }
            .table th, .table td { padding: 6px 8px; }
            .admin-container { padding: 10px; }
            .modal-content { padding: 20px; }
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
            <h2>Gestionar <span>Usuarios</span></h2>
            <p>U.E.E. Monseñor Rafael Arias Blanco</p>
        </div>

        <!-- ===== HEADER ADMIN CON MEJOR CONTRASTE ===== -->
        <header class="admin-header">
            <div class="header-left">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-mini" onerror="this.style.display='none'">
                <h1>📋 Lista de <span>Usuarios</span></h1>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn-regresar">← Regresar</a>
                <div class="admin-user">
                    <div class="user-icon"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <span class="user-role">Administrador</span>
                    </div>
                    <a href="logout.php" class="btn-logout">🚪 Salir</a>
                </div>
            </div>
        </header>

        <!-- ===== TARJETA PRINCIPAL ===== -->
        <div class="main-card">

            <?php if($mensaje): ?>
                <div class="alert <?php echo strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users && $users->num_rows > 0): ?>
                            <?php while($user = $users->fetch_assoc()): 
                                $roleClass = 'badge-user';
                                $roleName = 'Usuario';
                                if ($user['role'] === 'admin') { $roleClass = 'badge-admin'; $roleName = 'Administrador'; }
                                elseif ($user['role'] === 'teacher') { $roleClass = 'badge-teacher'; $roleName = 'Docente'; }
                                elseif ($user['role'] === 'representative') { $roleClass = 'badge-representative'; $roleName = 'Representante'; }

                                $statusClass = 'badge-active';
                                $statusName = 'Activo';
                                if ($user['status'] === 'pending') { $statusClass = 'badge-pending'; $statusName = 'Pendiente'; }
                                elseif ($user['status'] === 'inactive') { $statusClass = 'badge-inactive'; $statusName = 'Inactivo'; }
                            ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge <?php echo $roleClass; ?>"><?php echo $roleName; ?></span></td>
                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusName; ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <?php if($user['status'] === 'active'): ?>
                                                    <input type="hidden" name="status" value="inactive">
                                                    <button type="submit" class="btn-sm-custom btn-inactive" title="Desactivar">🔴</button>
                                                <?php elseif($user['status'] === 'inactive'): ?>
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn-sm-custom btn-active" title="Activar">🟢</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn-sm-custom btn-active" title="Aprobar">✅</button>
                                                <?php endif; ?>
                                            </form>
                                            <button class="btn-sm-custom btn-reset" onclick="abrirModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" title="Restablecer contraseña">🔑</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ¿Estás seguro de eliminar este usuario?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-sm-custom btn-delete" title="Eliminar">🗑️</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Tú (no puedes)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:30px; color:#a0aec0;">
                                    📭 No hay usuarios registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- ===== MODAL ===== -->
    <div class="password-modal" id="passwordModal">
        <div class="modal-content">
            <h3>🔑 Restablecer Contraseña</h3>
            <p>Usuario: <strong id="modalUserName"></strong></p>
            <form method="POST" id="resetForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="form-group">
                    <label>Nueva contraseña:</label>
                    <div class="password-input-group">
                        <input type="text" name="new_password" id="newPassword" value="12345678" required>
                        <button type="button" class="btn-generate" onclick="generarPassword()">🎲 Generar</button>
                    </div>
                </div>
                <small style="font-family:'Inter',sans-serif;color:#718096;">La contraseña debe tener al menos 8 caracteres.</small>
                <button type="submit" class="btn-submit-modal">✅ Restablecer</button>
                <button type="button" class="btn-cancel-modal" onclick="cerrarModal()">❌ Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModal(userId, userName) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('newPassword').value = '12345678';
            document.getElementById('passwordModal').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }

        function generarPassword() {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            var password = '';
            for (var i = 0; i < 10; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('newPassword').value = password;
        }

        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>