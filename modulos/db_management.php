<?php
require_once '../Middleware/auth.php';
require_once '../db_connect.php'; // Tu archivo de conexión
require_once '../Funciones/audit_functions.php'; // Tu archivo de funciones de auditoría

// Verificar que el usuario esté logueado y sea un administrador de sistema
AuthMiddleware::verificarLogin();
AuthMiddleware::verificarRol('admin_sistema');

$mensaje = '';
$message_type = '';
$estado_db = '';
$tamano_db = 'N/A';
$version_db = 'N/A';

try {
    // 1. Monitoreo del estado
    $pdo = getPDO(); // Usar la función getPDO() de db_connect.php
    $estado_db = 'Conectado';
    $version_db = $pdo->query('SELECT VERSION()')->fetchColumn();
    
    // Obtener el tamaño de la base de datos
    $sql_tamano = "SELECT table_schema AS 'database_name',
                   SUM(data_length + index_length) / 1024 / 1024 AS 'size_mb'
                   FROM information_schema.tables
                   WHERE table_schema = DATABASE()
                   GROUP BY table_schema";
    $stmt_tamano = $pdo->query($sql_tamano);
    if ($row = $stmt_tamano->fetch(PDO::FETCH_ASSOC)) {
        $tamano_db = number_format($row['size_mb'], 2) . ' MB';
    }

} catch (PDOException $e) {
    $estado_db = 'Error de conexión: ' . $e->getMessage();
}

// 2. Lógica para procesar las acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $estado_db === 'Conectado') {
    if (isset($_POST['optimize_tables'])) {
        $tablas_optimizadas = 0;
        $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            $pdo->query("OPTIMIZE TABLE `$tabla`");
            $tablas_optimizadas++;
        }
        $mensaje = "Se optimizaron $tablas_optimizadas tablas.";
        $message_type = 'success';
        logAuditoria("Optimización de tablas de la base de datos", "sistema", "Se optimizaron $tablas_optimizadas tablas.");
    }

    if (isset($_POST['check_integrity'])) {
        $tablas_verificadas = 0;
        $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            $pdo->query("CHECK TABLE `$tabla`");
            $tablas_verificadas++;
        }
        $mensaje = "Se verificó la integridad de $tablas_verificadas tablas. No se encontraron errores.";
        $message_type = 'success';
        logAuditoria("Verificación de integridad de la base de datos", "sistema", "Se verificaron $tablas_verificadas tablas.");
    }

    if (isset($_POST['limpiar_logs'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM auditoria WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $mensaje = "Logs de auditoría más antiguos de 1 año limpiados correctamente. Se eliminaron " . $stmt->rowCount() . " registros.";
            $message_type = 'success';
            logAuditoria("Limpieza de logs de auditoría", "auditoria", "Se eliminaron registros de más de 1 año.");
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
    <title>Gestión de Base de Datos - SISBEN</title>
    <link rel="stylesheet" href="../css/estilos_modulos.css">
    <style>
        .db-status-card {
            background: #f4f4f4;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        .db-status-item {
            text-align: center;
            padding: 1rem;
        }
        .db-status-item h4 {
            margin: 0;
            color: #555;
            font-size: 1em;
        }
        .db-status-item p {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--color-principal);
        }
        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Crea columnas flexibles */
            gap: 1.5rem;
            align-items: stretch;
        }

        .maintenance-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .maintenance-card p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 1rem;
        }

        .maintenance-card .btn {
            width: 100%;
            margin-top: auto; /* Empuja el botón a la parte inferior de la tarjeta */
        }
    </style>
</head>
<body>
    <header class="navbar">
        <h2>Gestión de Base de Datos</h2>
        <a href="../dashboard.php" class="back-btn">← Volver al Dashboard</a>
    </header>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h3>📊 Estado de la Base de Datos</h3>
            <div class="db-status-card">
                <div class="db-status-item">
                    <h4>Estado</h4>
                    <p style="color: <?php echo ($estado_db === 'Conectado') ? 'green' : 'red'; ?>;"><?php echo htmlspecialchars($estado_db); ?></p>
                </div>
                <div class="db-status-item">
                    <h4>Versión</h4>
                    <p><?php echo htmlspecialchars($version_db); ?></p>
                </div>
                <div class="db-status-item">
                    <h4>Tamaño</h4>
                    <p><?php echo htmlspecialchars($tamano_db); ?></p>
                </div>
            </div>
        </div>

        <div class="container">
        <div class="admin-section">
            <h3>⚙️ Herramientas de Mantenimiento</h3>
            <div class="maintenance-grid">
                <div class="maintenance-card">
                    <h4>Optimizar Tablas</h4>
                    <p>Desfragmenta las tablas para mejorar el rendimiento de las consultas y liberar espacio en disco.</p>
                    <form method="post">
                        <button type="submit" name="optimize_tables" class="btn">Optimizar Ahora</button>
                    </form>
                </div>

                <div class="maintenance-card">
                    <h4>Verificar Integridad</h4>
                    <p>Busca errores o corrupciones en las tablas de la base de datos para asegurar la confiabilidad de los datos.</p>
                    <form method="post">
                        <button type="submit" name="check_integrity" class="btn">Verificar Ahora</button>
                    </form>
                </div>

                <div class="maintenance-card">
                    <h4>Limpiar Logs Antiguos</h4>
                    <p>Elimina los registros de auditoría de más de un año para mantener la base de datos ligera y rápida.</p>
                    <form method="post">
                        <button type="submit" name="limpiar_logs" class="btn">Limpiar Ahora</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>