<?php
require_once '../Middleware/auth.php';
require_once '../db_connect.php'; // Tu archivo de conexión a la base de datos
require_once '../Funciones/audit_functions.php'; // Tu función de auditoría

AuthMiddleware::verificarLogin();

$registro = null;
$mensaje = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultar'])) {
    $documento = trim($_POST['documento']);
    
    if (empty($documento)) {
        $mensaje = "Por favor, ingrese un número de documento válido.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $conexion->prepare("SELECT TipoDoc, Documento, P_Apellido, S_Apellido, P_Nombre, S_Nombre FROM sisben WHERE Documento = ?");
            if ($stmt) {
                $stmt->bind_param("s", $documento);
                $stmt->execute();
                $result = $stmt->get_result();
                $registro = $result->fetch_assoc();
                $stmt->close();

                // Llama a la función de auditoría para registrar la consulta
                logAuditoria("Consulta pública de registro", "sisben", "Documento: " . $documento);

                if ($registro) {
                    $mensaje = "Registro encontrado con éxito.";
                    $message_type = 'success';
                } else {
                    $mensaje = "No se encontró ningún registro con el documento proporcionado.";
                    $message_type = 'info';
                }
            } else {
                $mensaje = 'Error en la preparación de la consulta: ' . $conexion->error;
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $mensaje = 'Ocurrió un error inesperado al consultar la base de datos.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta SISBEN</title>
    <link rel="stylesheet" href="../css/estilos_modulos.css">
    <style>
        .consulta-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-consultar {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .btn-consultar:hover {
            background-color: #0056b3;
        }
        .resultado-card {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-left: 5px solid #007bff;
            border-radius: 4px;
        }
        .resultado-card h4 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
        }
        .resultado-card p {
            margin: 0.5rem 0;
            font-size: 1.1em;
        }
        .resultado-card span {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <h2>Consulta SISBEN</h2>
        <a href="../dashboard.php" class="back-btn">← Volver al Dashboard</a>
    </header>

    <div class="consulta-container">
        <header>
            <h2 style="text-align: center;">Consulta de Registro SISBEN</h2>
            <p style="text-align: center; color: #666;">Ingrese el número de documento para consultar el registro.</p>
        </header>

        <?php if ($mensaje): ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="documento">Número de Documento:</label>
                <input type="text" id="documento" name="documento" required>
            </div>
            <button type="submit" name="consultar" class="btn-consultar">Consultar</button>
        </form>

        <?php if ($registro): ?>
            <div class="resultado-card">
                <h4>Detalles del Registro</h4>
                <p><span>Tipo de Documento:</span> <?php echo htmlspecialchars($registro['TipoDoc']); ?></p>
                <p><span>Documento:</span> <?php echo htmlspecialchars($registro['Documento']); ?></p>
                <p><span>Nombres:</span> <?php echo htmlspecialchars($registro['P_Nombre'] . ' ' . $registro['S_Nombre']); ?></p>
                <p><span>Apellidos:</span> <?php echo htmlspecialchars($registro['P_Apellido'] . ' ' . $registro['S_Apellido']); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>