<?php

require_once 'Middleware/auth.php';

// Verificar que el usuario esté logueado
AuthMiddleware::verificarLogin();

// Obtener datos del usuario actual
$usuario_actual = AuthMiddleware::getUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SISBEN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info span {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .welcome-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-card h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .module-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
        }
        
        .module-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .module-card p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .module-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .module-btn:hover {
            transform: translateY(-2px);
        }
        
        .admin-only {
            border-left: 4px solid #f39c12;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-admin {
            background: #e74c3c;
            color: white;
        }
        
        .badge-operador {
            background: #27ae60;
            color: white;
        }
        
        .badge-consulta {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>SISBEN - Panel de Control</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
            <span class="status-badge <?php 
                if($usuario_actual['rol'] === 'admin_sistema') echo 'badge-admin';
                elseif($usuario_actual['rol'] === 'operador_sisben') echo 'badge-operador'; 
                else echo 'badge-consulta';
            ?>">
                <?php echo str_replace('_', ' ', ucwords($usuario_actual['rol'], '_')); ?>
            </span>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Bienvenido, <?php echo htmlspecialchars($usuario_actual['nombre']); ?></h2>
            <p>Tu rol: <strong><?php echo str_replace('_', ' ', ucwords($usuario_actual['rol'], '_')); ?></strong></p>
            <p>Accede a los módulos disponibles según tu nivel de permisos.</p>
        </div>
        
        <div class="modules-grid">
            <!-- Módulos disponibles para TODOS los roles -->
            <div class="module-card">
                <h3>📊 Consulta SISBEN IV</h3>
                <p>Consultar información del SISBEN IV de los ciudadanos registrados.</p>
                <a href="modulos/consulta_sisben.php" class="module-btn">Acceder</a>
            </div>
            
            <!-- Módulos para OPERADORES y ADMINS -->
            <?php if ($usuario_actual['rol'] !== 'consulta_solo'): ?>
            <div class="module-card">
                <h3>📝 Gestión de Datos SISBEN</h3>
                <p>Crear, editar, importar y gestionar los datos de beneficiarios y encuestas SISBEN.</p>
                <a href="modulos/sisben_admin.php" class="module-btn">Acceder</a>
            </div>
            <?php endif; ?>

            <!-- Módulos SOLO para ADMINISTRADORES DEL SISTEMA -->
            <?php if ($usuario_actual['rol'] === 'admin_sistema'): ?>
            <div class="module-card admin-only">
                <h3>⚙️ Administración del Sistema</h3>
                <p>Configuración general, respaldos y mantenimiento del sistema.</p>
                <a href="modulos/admin_sistema.php" class="module-btn">Acceder</a>
            </div>
            
            <div class="module-card admin-only">
                <h3>👥 Gestión de Usuarios</h3>
                <p>Administrar usuarios del sistema, roles y permisos.</p>
                <a href="modulos/usuarios.php" class="module-btn">Acceder</a>
            </div>
            
            <div class="module-card admin-only">
                <h3>🔍 Auditoría</h3>
                <p>Logs del sistema, auditoría de cambios y actividad de usuarios.</p>
                <a href="modulos/auditoria.php" class="module-btn">Acceder</a>
            </div>
            
            <div class="module-card admin-only">
                <h3>💾 Base de Datos</h3>
                <p>Gestión directa de la base de datos y conexiones.</p>
                <a href="modulos/db_management.php" class="module-btn">Acceder</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Prevenir botón atrás después del logout
        if (performance.navigation.type === 2) {
            location.reload(true);
        }
    </script>
</body>
</html>