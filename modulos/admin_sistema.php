<?php
// Incluir archivos necesarios y verificar permisos de admin
require_once '../Middleware/auth.php';
require_once '../db_connect.php';

// Verificar que el usuario esté logueado y sea un administrador
AuthMiddleware::verificarLogin();
AuthMiddleware::verificarRol('admin_sistema');

// Obtener los últimos 50 registros de auditoría
$registros_auditoria = obtenerRegistros("
    SELECT 
        a.accion, 
        a.tabla, 
        a.registro_id, 
        a.ip, 
        a.fecha_hora, 
        u.nombre AS nombre_usuario
    FROM auditoria a
    JOIN usuarios u ON a.usuario_id = u.id
    ORDER BY a.fecha_hora DESC
    LIMIT 50
");

// Lógica para manejar acciones administrativas
$mensaje = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['limpiar_logs'])) {
        try {
            // Eliminar registros de auditoría muy antiguos
            $stmt = getPDO()->prepare("DELETE FROM auditoria WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $mensaje = "Logs de auditoría más antiguos de 1 año limpiados correctamente.";
            $message_type = 'success';
        } catch (PDOException $e) {
            $mensaje = 'Error al limpiar los logs: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración del Sistema - SISBEN</title>
    <link rel="stylesheet" href="../css/estilos_modulos.css">
    <style>
        .admin-section {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .admin-section h3 {
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .admin-section table th, .admin-section table td {
            font-size: 0.9em;
        }
        .action-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        @media screen and (max-width: 768px) {
            .action-form {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <h2>Administración del Sistema</h2>
        <a href="../dashboard.php" class="back-btn">← Volver al Dashboard</a>
    </header>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h3>📈 Log de Actividad Reciente</h3>
            <p>Se muestran los últimos 50 eventos registrados.</p>
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla</th>
                        <th>ID de Registro</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros_auditoria as $registro): ?>
                        <tr>
                            <td data-label="Fecha"><?php echo htmlspecialchars($registro['fecha_hora']); ?></td>
                            <td data-label="Usuario"><?php echo htmlspecialchars($registro['nombre_usuario']); ?></td>
                            <td data-label="Acción"><?php echo htmlspecialchars($registro['accion']); ?></td>
                            <td data-label="Tabla"><?php echo htmlspecialchars($registro['tabla']); ?></td>
                            <td data-label="Registro ID"><?php echo htmlspecialchars($registro['registro_id']); ?></td>
                            <td data-label="IP"><?php echo htmlspecialchars($registro['ip']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-section">
            <h3>⚙️ Herramientas de Mantenimiento</h3>
            <div class="action-form">
                <p>Limpiar registros de auditoría antiguos para optimizar el rendimiento de la base de datos.</p>
                <form method="post" onsubmit="return confirm('¿Estás seguro de que deseas limpiar los logs antiguos? Esta acción es irreversible.');">
                    <button type="submit" name="limpiar_logs" class="btn">Limpiar Logs</button>
                </form>
            </div>
            
            <hr>

            <div class="action-form">
                <p>Genera un respaldo completo de la base de datos para prevenir pérdida de datos.</p>
                <a href="backup.php" class="btn">Crear Respaldo Ahora</a>
            </div>
        </div>
    </div>
</body>
</html>