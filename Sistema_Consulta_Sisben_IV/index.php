<?php
// sisben_consulta.php
// -----------------------------------------------------------------------------
// Este script maneja la conexión a la base de datos, la lógica de consulta
// y la interfaz de usuario para el sistema de consulta Sisbén IV.
// -----------------------------------------------------------------------------

// --- Incluye el archivo de conexión a la base de datos ---
// La ruta '../' le indica al script que suba un nivel desde la carpeta actual.
require_once '../db_connect.php';

// --- Creación de tablas si no existen ---
// Creación de la tabla sisben para almacenar los registros de Sisbén.
// Los nombres de las columnas coinciden con la estructura de tu tabla.
$sql_sisben_data = "
CREATE TABLE IF NOT EXISTS `sisben` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `TipoDoc` VARCHAR(5) NOT NULL,
  `Documento` VARCHAR(20) NOT NULL,
  `P_Apellido` VARCHAR(50) NOT NULL,
  `S_Apellido` VARCHAR(50) DEFAULT NULL,
  `P_Nombre` VARCHAR(50) NOT NULL,
  `S_Nombre` VARCHAR(50) DEFAULT NULL,
  `requiere_actualizacion` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Documento` (`Documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($conexion, $sql_sisben_data);

// Creación de la tabla consultas para llevar un registro del conteo.
$sql_consultas = "
CREATE TABLE IF NOT EXISTS `consultas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `documento_consultado` VARCHAR(20) NOT NULL,
  `fecha_consulta` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($conexion, $sql_consultas);

// --- Lógica del conteo de consultas ---
function contarConsulta($conexion, $documento) {
    $stmt = mysqli_prepare($conexion, "INSERT INTO consultas (documento_consultado) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $documento);
    mysqli_stmt_execute($stmt);
}

function obtenerTotalConsultas($conexion) {
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM consultas");
    return mysqli_fetch_assoc($resultado)['total'];
}

// --- Inicialización de variables ---
$nombre_completo = "";
$documento_encontrado = "";
$mensaje = "";
$total_consultas = obtenerTotalConsultas($conexion);

// --- Lógica principal del formulario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['documento'])) {
    $documento_digitado = trim($_POST['documento']);

    if (!empty($documento_digitado)) {
        // Incrementa el contador de consultas
        contarConsulta($conexion, $documento_digitado);
        $total_consultas = obtenerTotalConsultas($conexion);

        // Prepara y ejecuta la consulta para buscar el documento
        $stmt = mysqli_prepare($conexion, "SELECT * FROM sisben WHERE Documento = ?");
        mysqli_stmt_bind_param($stmt, "s", $documento_digitado);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $registro = mysqli_fetch_assoc($resultado);

        if ($registro) {
            $nombre_completo = $registro['P_Nombre'] . " " . $registro['S_Nombre'] . " " . $registro['P_Apellido'] . " " . $registro['S_Apellido'];
            $documento_encontrado = $registro['Documento'];
            $mensaje_actualizacion = $registro['requiere_actualizacion'] ?
                "<h3>Estado: <span style='color: red;'>Debe realizar la actualización al Sisbén IV.</span></h3>" :
                "<h3>Estado: <span style='color: green;'>Su registro está actualizado.</span></h3>";
        } else {
            $mensaje = "<p style='color: red;'>El número de documento <strong>" . htmlspecialchars($documento_digitado) . "</strong> no se encuentra registrado.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Actualización Sisbén IV</title>
    <!-- Incluye Tailwind CSS para un diseño moderno y responsive -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .card {
            background-color: #ffffff;
            border-radius: 1.5rem; /* rounded-3xl */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .submit-btn {
            background-color: #10b981; /* Esmeralda 500 */
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #059669; /* Esmeralda 600 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="card p-8 md:p-12 max-w-lg mx-auto w-full text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Consulta de Actualización Sisbén IV</h1>
        <p class="text-gray-600 mb-6">
            <strong>Instrucciones:</strong> Digite su número de documento de identidad en el campo de abajo para verificar si debe realizar la actualización de su registro en el Sisbén IV.
        </p>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
            <input
                type="text"
                name="documento"
                placeholder="Número de Documento"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
            <button
                type="submit"
                class="w-full submit-btn text-white font-semibold py-2 px-4 rounded-lg"
            >
                Consultar
            </button>
        </form>

        <!-- Sección de resultados de la consulta -->
        <?php if (!empty($mensaje)): ?>
            <div class="mt-6 text-sm text-center">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($documento_encontrado)): ?>
            <div class="mt-6 p-4 bg-gray-100 rounded-lg text-left">
                <h2 class="text-xl font-semibold mb-2">Registro Encontrado</h2>
                <p><strong>Documento:</strong> <?php echo htmlspecialchars($documento_encontrado); ?></p>
                <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($nombre_completo); ?></p>
                <?php echo $mensaje_actualizacion; ?>
            </div>
        <?php endif; ?>

        <!-- Contador de consultas -->
        <div class="mt-6 text-sm text-gray-500 text-center">
            Total de consultas realizadas: <?php echo $total_consultas; ?>
        </div>
    </div>
</body>
</html>
