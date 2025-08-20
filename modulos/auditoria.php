<?php
require_once '../Middleware/auth.php';
require_once '../db_connect.php';

// Verificar que el usuario esté logueado y sea un administrador
AuthMiddleware::verificarLogin();
AuthMiddleware::verificarRol('admin_sistema');

// ----------------------------------------------------
// Configuración de Paginación
// ----------------------------------------------------
$registros_por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$opciones_registros = [10, 20, 50, 100];
if (!in_array($registros_por_pagina, $opciones_registros)) {
    $registros_por_pagina = 20;
}

// ----------------------------------------------------
// Filtros
// ----------------------------------------------------
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_tabla = $_GET['tabla'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// ----------------------------------------------------
// Construir consulta con filtros
// ----------------------------------------------------
$sql_base = "FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id";
$sql_conditions = [];
$sql_params = [];
$sql_types = '';

// Filtro por usuario
if (!empty(trim($filtro_usuario))) {
    $sql_conditions[] = "u.nombre LIKE ?";
    $sql_params[] = "%" . trim($filtro_usuario) . "%";
    $sql_types .= 's';
}

// Filtro por acción
if (!empty(trim($filtro_accion))) {
    $sql_conditions[] = "a.accion LIKE ?";
    $sql_params[] = "%" . trim($filtro_accion) . "%";
    $sql_types .= 's';
}

// Filtro por tabla
if (!empty(trim($filtro_tabla))) {
    $sql_conditions[] = "a.tabla LIKE ?";
    $sql_params[] = "%" . trim($filtro_tabla) . "%";
    $sql_types .= 's';
}

// Filtro por fecha desde
if (!empty($filtro_fecha_desde)) {
    $sql_conditions[] = "DATE(a.fecha_hora) >= ?";
    $sql_params[] = $filtro_fecha_desde;
    $sql_types .= 's';
}

// Filtro por fecha hasta
if (!empty($filtro_fecha_hasta)) {
    $sql_conditions[] = "DATE(a.fecha_hora) <= ?";
    $sql_params[] = $filtro_fecha_hasta;
    $sql_types .= 's';
}

// Unir todas las condiciones con AND si existen
$sql_where = count($sql_conditions) > 0 ? " WHERE " . implode(' AND ', $sql_conditions) : "";

// Contar registros totales
$sql_count = "SELECT COUNT(*) as total " . $sql_base . $sql_where;
$total_registros = 0;

if ($stmt = $conexion->prepare($sql_count)) {
    if (!empty($sql_params)) {
        $stmt->bind_param($sql_types, ...$sql_params);
    }
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_registros = $count_result->fetch_assoc()['total'];
    $stmt->close();
}

$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros para la página actual
$sql_select = "SELECT 
    a.accion, 
    a.tabla, 
    a.registro_id, 
    a.ip, 
    a.fecha_hora, 
    u.nombre AS nombre_usuario
    " . $sql_base . $sql_where . " 
    ORDER BY a.fecha_hora DESC 
    LIMIT ? OFFSET ?";

$registros_auditoria = [];

if ($stmt = $conexion->prepare($sql_select)) {
    // Agregar los parámetros de paginación al final de la lista
    $sql_params_final = array_merge($sql_params, [(int)$registros_por_pagina, (int)$offset]);
    $sql_types_final = $sql_types . 'ii';

    $stmt->bind_param($sql_types_final, ...$sql_params_final);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $registros_auditoria[] = $row;
    }
    $stmt->close();
}

// Función para generar enlaces de paginación
function generarEnlacesPaginacion($pagina_actual, $total_paginas, $parametros_adicionales = []) {
    $enlaces = [];
    $parametros = [];

    // Construir la cadena de parámetros adicionales
    foreach ($parametros_adicionales as $key => $value) {
        if (!empty($value) || $value === '0') {
            $parametros[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    
    $parametros_string = !empty($parametros) ? '&' . implode('&', $parametros) : '';

    // Enlace "Anterior"
    if ($pagina_actual > 1) {
        $enlaces[] = '<a href="?pagina=' . ($pagina_actual - 1) . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">Anterior</a>';
    } else {
        $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">Anterior</span>';
    }
    
    $inicio = max(1, $pagina_actual - 2);
    $fin = min($total_paginas, $pagina_actual + 2);
    
    if ($inicio > 1) {
        $enlaces[] = '<a href="?pagina=1' . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">1</a>';
        if ($inicio > 2) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300">...</span>';
        }
    }
    
    for ($i = $inicio; $i <= $fin; $i++) {
        if ($i == $pagina_actual) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-500">' . $i . '</span>';
        } else {
            $enlaces[] = '<a href="?pagina=' . $i . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    if ($fin < $total_paginas) {
        if ($fin < $total_paginas - 1) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300">...</span>';
        }
        $enlaces[] = '<a href="?pagina=' . $total_paginas . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">' . $total_paginas . '</a>';
    }

    // Enlace "Siguiente"
    if ($pagina_actual < $total_paginas) {
        $enlaces[] = '<a href="?pagina=' . ($pagina_actual + 1) . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">Siguiente</a>';
    } else {
        $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">Siguiente</span>';
    }
    
    return $enlaces;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría del Sistema - SISBEN</title>
    <link rel="stylesheet" href="../css/estilos_modulos.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <header class="navbar">
        <h2>Auditoría del Sistema</h2>
        <a href="../dashboard.php" class="back-btn">← Volver al Dashboard</a>
    </header>

    <div class="container mx-auto p-4 md:p-8">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border border-gray-200">
            <h1 class="text-3xl md:text-4xl font-bold text-center text-indigo-600 mb-6">Auditoría del Sistema</h1>
            <p class="text-center text-gray-600 mb-8">Registro de todas las actividades realizadas en el sistema.</p>

            <!-- Filtros -->
            <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Filtros de Búsqueda</h2>
                <form action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                        <input type="text" name="usuario" placeholder="Buscar por usuario..." 
                               class="w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Acción</label>
                        <input type="text" name="accion" placeholder="Buscar por acción..." 
                               class="w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($filtro_accion); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tabla</label>
                        <input type="text" name="tabla" placeholder="Buscar por tabla..." 
                               class="w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($filtro_tabla); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                        <input type="date" name="fecha_desde" 
                               class="w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" 
                               class="w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition duration-300">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Buscar
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($filtro_usuario) || !empty($filtro_accion) || !empty($filtro_tabla) || !empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta)): ?>
                <div class="mt-4">
                    <a href="?" class="text-indigo-600 hover:text-indigo-800 text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Limpiar filtros
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Información de paginación y controles -->
            <div class="mb-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-600">
                    Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> a <?php echo min($offset + $registros_por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> registros
                    <?php if (!empty($filtro_usuario) || !empty($filtro_accion) || !empty($filtro_tabla) || !empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta)): ?>
                        (filtrados)
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-2">
                    <label for="por_pagina" class="text-sm font-medium text-gray-700">Registros por página:</label>
                    <select id="por_pagina" name="por_pagina" onchange="cambiarRegistrosPorPagina(this.value)" class="text-sm border border-gray-300 rounded-md px-3 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <?php foreach ($opciones_registros as $opcion): ?>
                            <option value="<?php echo $opcion; ?>" <?php echo $registros_por_pagina == $opcion ? 'selected' : ''; ?>>
                                <?php echo $opcion; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Paginación superior -->
            <?php if ($total_paginas > 1): ?>
            <div class="mb-4 flex items-center justify-center">
                <nav class="flex items-center space-x-1" aria-label="Paginación Superior">
                    <?php 
                    $enlaces = generarEnlacesPaginacion(
                        $pagina_actual, 
                        $total_paginas, 
                        [
                            'usuario' => $filtro_usuario,
                            'accion' => $filtro_accion,
                            'tabla' => $filtro_tabla,
                            'fecha_desde' => $filtro_fecha_desde,
                            'fecha_hasta' => $filtro_fecha_hasta,
                            'por_pagina' => $registros_por_pagina
                        ]
                    );
                    foreach ($enlaces as $enlace) {
                        echo $enlace;
                    }
                    ?>
                </nav>
            </div>
            <?php endif; ?>

            <!-- Tabla de Auditoría -->
            <div class="overflow-x-auto rounded-lg shadow-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha y Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabla</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Registro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($registros_auditoria)): ?>
                            <?php foreach ($registros_auditoria as $registro): ?>
                            <tr class="hover:bg-gray-50">
                                <td data-label="Fecha y Hora" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_hora'])); ?>
                                </td>
                                <td data-label="Nombre" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($registro['nombre_usuario']); ?>
                                </td>
                                <td data-label="Acción" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch(strtolower($registro['accion'])) {
                                            case 'creación':
                                            case 'creacion':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'actualización':
                                            case 'actualizacion':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'eliminación':
                                            case 'eliminacion':
                                            case 'eliminado':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($registro['accion']); ?>
                                    </span>
                                </td>
                                <td data-label="Tabla" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($registro['tabla']); ?>
                                </td>
                                <td data-label="ID Registro" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($registro['registro_id']); ?>
                                </td>
                                <td data-label="IP" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($registro['ip']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    No se encontraron registros de auditoría.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación inferior -->
            <?php if ($total_paginas > 1): ?>
            <div class="mt-6 flex items-center justify-center">
                <nav class="flex items-center space-x-1" aria-label="Paginación">
                    <?php 
                    $enlaces = generarEnlacesPaginacion(
                        $pagina_actual, 
                        $total_paginas, 
                        [
                            'usuario' => $filtro_usuario,
                            'accion' => $filtro_accion,
                            'tabla' => $filtro_tabla,
                            'fecha_desde' => $filtro_fecha_desde,
                            'fecha_hasta' => $filtro_fecha_hasta,
                            'por_pagina' => $registros_por_pagina
                        ]
                    );
                    foreach ($enlaces as $enlace) {
                        echo $enlace;
                    }
                    ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function cambiarRegistrosPorPagina(valor) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('por_pagina', valor);
            urlParams.delete('pagina');
            window.location.href = window.location.pathname + '?' + urlParams.toString();
        }
    </script>
</body>
</html>

<?php
$conexion->close();
?>