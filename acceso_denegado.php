<?php
// Middleware de autenticación y autorización
require_once 'Middleware/auth.php';

// Obtener los datos del usuario actual para un mensaje personalizado
$usuario = AuthMiddleware::getUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Denegado</title>
    <link rel="stylesheet" href="assets/css/estilos_modulos.css">
    <style>
        .access-denied-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
        }
        .denied-icon {
            font-size: 8rem;
            color: #d9534f;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1.5rem;
            max-width: 600px;
        }
        .btn-return {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-return:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="denied-icon">🚫</div>
        <h1>Acceso Denegado</h1>
        <p>
            Lo sentimos, **<?php echo htmlspecialchars($usuario['nombre'] ?? 'usuario'); ?>**. No tienes los permisos necesarios para ver esta página.
            Tu rol actual es **<?php echo htmlspecialchars($usuario['rol'] ?? 'no definido'); ?>**.
        </p>
        <a href="dashboard.php" class="btn-return">Volver al Dashboard</a>
    </div>
</body>
</html>