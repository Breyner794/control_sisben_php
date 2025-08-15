<?php
session_start();

require_once '../db_connect.php';

// Incluir la librería PHPSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Variables para mensajes de estado
$message = '';
$message_type = '';

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
// Lógica para Subir y Procesar Archivo Excel
// ----------------------------------------------------
if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == UPLOAD_ERR_OK) {

    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowedfileExtensions = array('xls', 'xlsx');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        try {
            $spreadsheet = IOFactory::load($fileTmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $duplicados = [];
            $duplicados_map = [];
            $nuevos_registros = [];
            $columnMap = [];

            // Crear mapeo de columnas desde la primera fila
            $headerRow = 1;
            $highestColumn = $sheet->getHighestColumn();
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $headerValue = strtolower(trim($sheet->getCell($col . $headerRow)->getValue()));
                
                if (in_array($headerValue, ['tid', 'tipo', 'tipo doc', 'tipo documento', 'tipo de documento'])) {
                    $columnMap['tipoDoc'] = $col;
                } elseif (in_array($headerValue, ['num_id', 'documento', 'numero documento', 'numero de documento', 'cedula', 'identificacion'])) {
                    $columnMap['documento'] = $col;
                } elseif (in_array($headerValue, ['ape1', 'primer apellido', 'apellido1', 'apellido paterno'])) {
                    $columnMap['p_apellido'] = $col;
                } elseif (in_array($headerValue, ['ap2', 'segundo apellido', 'apellido2', 'apellido materno'])) {
                    $columnMap['s_apellido'] = $col;
                } elseif (in_array($headerValue, ['nom1', 'primer nombre', 'nombre1', 'nombre'])) {
                    $columnMap['p_nombre'] = $col;
                } elseif (in_array($headerValue, ['nom2', 'segundo nombre', 'nombre2'])) {
                    $columnMap['s_nombre'] = $col;
                }
            }

            // Procesar todas las filas
            for ($row = 2; $row <= $highestRow; $row++) {
                $tipoDoc = isset($columnMap['tipoDoc']) ? trim($sheet->getCell($columnMap['tipoDoc'] . $row)->getValue()) : '';
                $documento = isset($columnMap['documento']) ? trim($sheet->getCell($columnMap['documento'] . $row)->getValue()) : '';
                $p_apellido = isset($columnMap['p_apellido']) ? trim($sheet->getCell($columnMap['p_apellido'] . $row)->getValue()) : '';
                $s_apellido = isset($columnMap['s_apellido']) ? trim($sheet->getCell($columnMap['s_apellido'] . $row)->getValue()) : '';
                $p_nombre = isset($columnMap['p_nombre']) ? trim($sheet->getCell($columnMap['p_nombre'] . $row)->getValue()) : '';
                $s_nombre = isset($columnMap['s_nombre']) ? trim($sheet->getCell($columnMap['s_nombre'] . $row)->getValue()) : '';
                
                if (empty($documento)) continue;
                
                // Si no hay primer nombre, usar el segundo
                if (empty($p_nombre) && !empty($s_nombre)) {
                    $p_nombre = $s_nombre;
                    $s_nombre = '';
                }
                
                // Si no hay primer apellido, usar el segundo
                if (empty($p_apellido) && !empty($s_apellido)) {
                    $p_apellido = $s_apellido;
                    $s_apellido = '';
                }

                // Verificar si existe en la BD
                $stmt = $conexion->prepare("SELECT id, TipoDoc, P_Apellido, S_Apellido, P_Nombre, S_Nombre FROM sisben WHERE Documento = ?");
                $stmt->bind_param("s", $documento);
                $stmt->execute();
                $result = $stmt->get_result();
                $existente = $result->fetch_assoc();
                $stmt->close();

                $registro = [
                    'fila_excel' => $row,
                    'tipoDoc' => $tipoDoc,
                    'documento' => $documento,
                    'p_apellido' => $p_apellido,
                    's_apellido' => $s_apellido,
                    'p_nombre' => $p_nombre,
                    's_nombre' => $s_nombre
                ];

                if ($existente) {
                    $cambios = [];
                    if ($tipoDoc != $existente['TipoDoc']) $cambios[] = 'Tipo de Documento';
                    if ($p_apellido != $existente['P_Apellido']) $cambios[] = 'Primer Apellido';
                    if ($s_apellido != $existente['S_Apellido']) $cambios[] = 'Segundo Apellido';
                    if ($p_nombre != $existente['P_Nombre']) $cambios[] = 'Primer Nombre';
                    if ($s_nombre != $existente['S_Nombre']) $cambios[] = 'Segundo Nombre';

                    if (!empty($cambios)) {
                        $documento_key = $registro['documento'];
                        $duplicados_map[$documento_key] = [
                            'registro' => $registro,
                            'existente' => $existente,
                            'cambios' => $cambios
                        ];
                    }
                } else {
                    $nuevos_registros[] = $registro;
                }
            }

            if (!empty($duplicados_map) || !empty($nuevos_registros)) {
                $_SESSION['excel_data'] = [
                    'duplicados' => $duplicados_map,
                    'nuevos_registros' => $nuevos_registros,
                    'columnMap' => $columnMap
                ];

                $_SESSION['decisiones_duplicados'] = [];
                foreach ($duplicados_map as $documento => $duplicado) {
                    $_SESSION['decisiones_duplicados'][$documento] = 'mantener';
                }
                
                $message = "Se encontraron " . count($nuevos_registros) . " registros nuevos y " . count($duplicados) . " duplicados con cambios. Revise la vista previa antes de confirmar.";
                $message_type = 'warning';
            } else {
                $message = "No se encontraron registros válidos para procesar.";
                $message_type = 'error';
            }

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $message = 'Error al leer el archivo Excel: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Tipo de archivo no permitido. Solo se aceptan archivos .xls y .xlsx.';
        $message_type = 'error';
    }
}

// ----------------------------------------------------
// Lógica para Procesar Excel Confirmado (CORREGIDO)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmarExcel'])) {
    // Verifica que ambas variables de sesión cruciales existan
    if (isset($_SESSION['excel_data']) && isset($_SESSION['decisiones_duplicados'])) {
        $excel_data = $_SESSION['excel_data'];
        $insertCount = 0;
        $updateCount = 0;

        // Procesar registros nuevos
        if (isset($excel_data['nuevos_registros'])) {
            foreach ($excel_data['nuevos_registros'] as $registro) {
                $stmt = $conexion->prepare("INSERT INTO sisben (TipoDoc, Documento, P_Apellido, S_Apellido, P_Nombre, S_Nombre) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $registro['tipoDoc'], $registro['documento'], $registro['p_apellido'], $registro['s_apellido'], $registro['p_nombre'], $registro['s_nombre']);
                    if ($stmt->execute()) {
                        $insertCount++;
                    }
                    $stmt->close();
                }
            }
        }
        
            foreach ($_SESSION['decisiones_duplicados'] as $documento => $accion) {
            if ($accion === 'actualizar') {
                // Acceso directo a los datos del duplicado usando el documento como clave
                if (isset($excel_data['duplicados'][$documento])) {
                    $registro_a_actualizar = $excel_data['duplicados'][$documento]['registro'];

                    $stmt = $conexion->prepare("UPDATE sisben SET TipoDoc=?, P_Apellido=?, S_Apellido=?, P_Nombre=?, S_Nombre=? WHERE Documento=?");
                    if ($stmt) {
                        $stmt->bind_param("ssssss", 
                            $registro_a_actualizar['tipoDoc'],
                            $registro_a_actualizar['p_apellido'],
                            $registro_a_actualizar['s_apellido'],
                            $registro_a_actualizar['p_nombre'],
                            $registro_a_actualizar['s_nombre'],
                            $documento
                        );
                        if ($stmt->execute()) {
                            $updateCount++;
                        }
                        $stmt->close();
                    }
                }
            }
        }
        
        
        $message = "Procesamiento completado. Se insertaron {$insertCount} registros nuevos y se actualizaron {$updateCount} registros existentes.";
        $message_type = 'success';
        
        // Limpia las variables de sesión
        unset($_SESSION['excel_data']);
        unset($_SESSION['decisiones_duplicados']);

        // Puedes usar una redirección o simplemente mostrar un mensaje
        // header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message) . '&type=success');
        // exit;
    }
}

// ----------------------------------------------------
// Lógica para Cancelar la Carga
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelarExcel'])) {
    session_start();
    // Limpia los datos de la vista previa de la sesión
    unset($_SESSION['excel_data']);

    // Redirige al usuario de vuelta a la página de carga de archivos
    // Puedes usar una URL fija o la misma página.
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------------------------
// Lógica para Actualizar Estado de Encuestado
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStatus'])) {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conexion->prepare("UPDATE sisben SET actualizado = TRUE WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            $message = 'Estado de ' . $stmt->affected_rows . ' registros actualizado correctamente.';
            $message_type = 'success';
        } else {
            $message = 'Error al actualizar el estado: ' . $conexion->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ----------------------------------------------------
// CRUD Individual - Editar Registro (CORREGIDO)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editarRegistro'])) {
    $id = (int)$_POST['id'];
    $tipoDoc = $_POST['tipoDoc'];
    $documento = $_POST['documento']; 
    $p_apellido = $_POST['p_apellido'];
    $s_apellido = $_POST['s_apellido'];
    $p_nombre = $_POST['p_nombre'];
    $s_nombre = $_POST['s_nombre'];

    //Debugin necesario por si algun dia no se pueda actualizar dicho registro, tener encuenta que esta parte necesito un id AI, Auto-Incremental
    //Practicamente es una llave primaria que no tenia porque si no la encuentra entonces como va a saber que es ese usuario...
    // echo "Documento que se busca: " . $documento . "<br>";
    // echo "ID del registro a ignorar: " . $id . "<br>";
    
    // Verificar que el documento no exista en otro registro
    $stmt = $conexion->prepare("SELECT id FROM sisben WHERE Documento = ? AND id != ?");
    $stmt->bind_param("si", $documento, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "Error: El número de documento ya existe en otro registro.";
        $message_type = 'error';
    } else {
        $stmt = $conexion->prepare("UPDATE sisben SET TipoDoc=?, Documento=?, P_Apellido=?, S_Apellido=?, P_Nombre=?, S_Nombre=? WHERE id=?");
        $stmt->bind_param("ssssssi", $tipoDoc, $documento, $p_apellido, $s_apellido, $p_nombre, $s_nombre, $id);
        
        // Ejecutamos la consulta y luego verificamos si se afectó alguna fila
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Registro actualizado correctamente.";
                $message_type = 'success';
            } else {
                $message = "No se realizaron cambios en el registro. Es posible que el ID no exista o los datos sean los mismos.";
                $message_type = 'info';
            }
        } else {
            $message = "Error al actualizar el registro: " . $conexion->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ----------------------------------------------------
// CRUD Individual - Eliminar Registro
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminarRegistro'])) {
    $id = (int)$_POST['id'];
    
    $stmt = $conexion->prepare("DELETE FROM sisben WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Registro eliminado correctamente.";
        $message_type = 'success';
    } else {
        $message = "Error al eliminar el registro: " . $conexion->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// ----------------------------------------------------
// CRUD Individual - Crear Registro
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crearRegistro'])) {
    $tipoDoc = $_POST['tipoDoc'];
    $documento = $_POST['documento'];
    $p_apellido = $_POST['p_apellido'];
    $s_apellido = $_POST['s_apellido'];
    $p_nombre = $_POST['p_nombre'];
    $s_nombre = $_POST['s_nombre'];
    
    // Verificar que el documento no exista
    $stmt = $conexion->prepare("SELECT id FROM sisben WHERE Documento = ?");
    $stmt->bind_param("s", $documento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "Error: El número de documento ya existe.";
        $message_type = 'error';
    } else {
        $stmt = $conexion->prepare("INSERT INTO sisben (TipoDoc, Documento, P_Apellido, S_Apellido, P_Nombre, S_Nombre) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $tipoDoc, $documento, $p_apellido, $s_apellido, $p_nombre, $s_nombre);
        
        if ($stmt->execute()) {
            $message = "Registro creado correctamente.";
            $message_type = 'success';
        } else {
            $message = "Error al crear el registro: " . $conexion->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ----------------------------------------------------
// Obtener y Filtrar Datos con Paginación
// ----------------------------------------------------
$search_query = $_GET['search'] ?? '';
$tipoDoc_filter = $_GET['tipoDoc'] ?? '';
$encuestado_filter = $_GET['encuestado'] ?? '';
$registros_por_pagina = $_GET['por_pagina'] ?? 20; // Valor por defecto
$pagina_actual = $_GET['pagina'] ?? 1;

$result = null;
$total_registros = 0;

$sql_base = "FROM sisben";
$sql_conditions = [];
$sql_params = [];
$sql_types = '';

// Construir la condición de búsqueda de texto
// Usa !empty() para asegurar que la cadena no esté vacía.
if (!empty(trim($search_query))) {
    $search_param = "%" . trim($search_query) . "%";
    $sql_conditions[] = "(Documento LIKE ? OR P_Apellido LIKE ? OR S_Apellido LIKE ? OR P_Nombre LIKE ? OR S_Nombre LIKE ?)";
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
    $sql_types .= 'sssss';
}

// Agregar filtro por Tipo de Documento
if (!empty(trim($tipoDoc_filter))) {
    $sql_conditions[] = "TipoDoc = ?";
    $sql_params[] = trim($tipoDoc_filter);
    $sql_types .= 's';
}

// Agregar filtro por estado de Encuestado
// Se usa is_numeric() y no empty(), porque '0' se considera vacío
if ($encuestado_filter !== '' && is_numeric($encuestado_filter)) {
    $sql_conditions[] = "actualizado = ?";
    $sql_params[] = (int)$encuestado_filter;
    $sql_types .= 'i';
}

// Unir todas las condiciones con AND si existen
$sql_where = count($sql_conditions) > 0 ? " WHERE " . implode(' AND ', $sql_conditions) : "";

// Contar registros
$sql_count = "SELECT COUNT(*) as total " . $sql_base . $sql_where;
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
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener registros para la página actual
$sql_select = "SELECT id, TipoDoc, Documento, P_Apellido, S_Apellido, P_Nombre, S_Nombre, actualizado " . $sql_base . $sql_where . " ORDER BY id DESC LIMIT ? OFFSET ?";

if ($stmt = $conexion->prepare($sql_select)) {
    // Agregar los parámetros de paginación al final de la lista
    $sql_params_final = array_merge($sql_params, [(int)$registros_por_pagina, (int)$offset]);
    $sql_types_final = $sql_types . 'ii';

    $stmt->bind_param($sql_types_final, ...$sql_params_final);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

// Función para generar enlaces de paginación
function generarEnlacesPaginacion($pagina_actual, $total_paginas, $parametros_adicionales = [], $parametro_pagina = 'pagina') {
    $enlaces = [];
    $parametros = [];

    // Construir la cadena de parámetros adicionales
    foreach ($parametros_adicionales as $key => $value) {
        if (!empty($value) || $value === '0' || $value === '1') {
            $parametros[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    
    $parametros_string = !empty($parametros) ? '&' . implode('&', $parametros) : '';

    // Enlace "Anterior"
    if ($pagina_actual > 1) {
        $enlaces[] = '<a href="?' . $parametro_pagina . '=' . ($pagina_actual - 1) . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">Anterior</a>';
    } else {
        $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">Anterior</span>';
    }
    
    // ... (El resto de la lógica para los enlaces numéricos es la misma, solo cambia 'pagina' por $parametro_pagina) ...
    $inicio = max(1, $pagina_actual - 2);
    $fin = min($total_paginas, $pagina_actual + 2);
    
    if ($inicio > 1) {
        $enlaces[] = '<a href="?' . $parametro_pagina . '=1' . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">1</a>';
        if ($inicio > 2) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300">...</span>';
        }
    }
    
    for ($i = $inicio; $i <= $fin; $i++) {
        if ($i == $pagina_actual) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-500">' . $i . '</span>';
        } else {
            $enlaces[] = '<a href="?' . $parametro_pagina . '=' . $i . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    if ($fin < $total_paginas) {
        if ($fin < $total_paginas - 1) {
            $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300">...</span>';
        }
        $enlaces[] = '<a href="?' . $parametro_pagina . '=' . $total_paginas . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50">' . $total_paginas . '</a>';
    }

    // Enlace "Siguiente"
    if ($pagina_actual < $total_paginas) {
        $enlaces[] = '<a href="?' . $parametro_pagina . '=' . ($pagina_actual + 1) . $parametros_string . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">Siguiente</a>';
    } else {
        $enlaces[] = '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">Siguiente</span>';
    }
    
    return $enlaces;
}

// Verificar si hay datos de Excel en sesión para mostrar vista previa
$mostrar_vista_previa = isset($_SESSION['excel_data']) && (!empty($_SESSION['excel_data']['duplicados']) || !empty($_SESSION['excel_data']['nuevos_registros']));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Control Sisbén</title>
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
      .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
      }
      .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #fff;
        border-bottom-color: transparent;
        border-radius: 50%;
        animation: rotation 1s linear infinite;
      }
      @keyframes rotation {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto p-4 md:p-8">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border border-gray-200">
            <h1 class="text-3xl md:text-4xl font-bold text-center text-indigo-600 mb-6">Módulo de Control Sisbén</h1>
            <p class="text-center text-gray-600 mb-8">Administración y gestión de personas que requieren actualización del Sisbén IV.</p>

            <!-- Mensajes de estado -->
            <?php if ($message): ?>
            <div id="message-box" class="p-4 mb-6 text-sm font-medium rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Vista Previa de Excel -->
            <?php if ($mostrar_vista_previa): ?>
    <div class="mb-8 p-6 bg-orange-50 rounded-xl border border-orange-200">
        <h2 class="text-2xl font-semibold text-orange-700 mb-4">
            Vista Previa - Confirmar Procesamiento
        </h2>
        
        <form action="" method="post">
            <?php if (!empty($_SESSION['excel_data']['nuevos_registros'])): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-green-700 mb-3">
                        Registros Nuevos (<?php echo count($_SESSION['excel_data']['nuevos_registros']); ?>)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-green-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">Tipo Doc</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">Documento</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">P. Apellido</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">S. Apellido</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">P. Nombre</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-700">S. Nombre</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach (array_slice($_SESSION['excel_data']['nuevos_registros'], 0, 10) as $registro): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['tipoDoc']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['documento']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['p_apellido']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['s_apellido']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['p_nombre']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($registro['s_nombre']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($_SESSION['excel_data']['nuevos_registros']) > 10): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-2 text-center text-gray-500">
                                        ... y <?php echo count($_SESSION['excel_data']['nuevos_registros']) - 10; ?> más
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['excel_data']['duplicados'])): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-red-700 mb-3">
                        Duplicados con Cambios (<?php echo count($_SESSION['excel_data']['duplicados']); ?>)
                    </h3>
                    
                    <?php
                        // Lógica de paginación para los duplicados
                        $duplicados_por_pagina = 10;
                        $total_duplicados = count($_SESSION['excel_data']['duplicados']);
                        $total_paginas_duplicados = ceil($total_duplicados / $duplicados_por_pagina);
                        $pagina_actual_duplicados = $_GET['pagina_duplicados'] ?? 1;
                        $offset_duplicados = ($pagina_actual_duplicados - 1) * $duplicados_por_pagina;
                        $duplicados_a_mostrar = array_slice($_SESSION['excel_data']['duplicados'], $offset_duplicados, $duplicados_por_pagina);
                    ?>

                    <div class="flex items-center space-x-4 mb-4">
                        <span class="text-sm font-medium text-gray-700">Acciones masivas:</span>
                        <button type="button" id="seleccionar-todos-mantener" class="px-3 py-1 text-xs bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition duration-300">
                            Mantener todos
                        </button>
                        <button type="button" id="seleccionar-todos-actualizar" class="px-3 py-1 text-xs bg-red-200 text-red-800 rounded-lg hover:bg-red-300 transition duration-300">
                            Actualizar todos
                        </button>
                    </div>

                    <?php foreach ($duplicados_a_mostrar as $duplicado): ?>
                        <?php
                        // Obtiene la elección previa de la sesión, por defecto 'mantener'
                        $documento = $duplicado['registro']['documento']; 
                        $eleccion_previa = $_SESSION['decisiones_duplicados'][$documento] ?? 'mantener';
                        ?>
                        <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-semibold text-red-800">Documento: <?php echo htmlspecialchars($documento); ?></h4>
                                <div class="space-x-2 flex items-center">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="duplicados[<?php echo $documento; ?>]" value="mantener" 
                                               <?php echo ($eleccion_previa === 'mantener') ? 'checked' : ''; ?> 
                                               class="mr-1 duplicados-radio">
                                        <span class="text-sm">Mantener existente</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="duplicados[<?php echo $documento; ?>]" value="actualizar" 
                                               <?php echo ($eleccion_previa === 'actualizar') ? 'checked' : ''; ?> 
                                               class="mr-1 duplicados-radio">
                                        <span class="text-sm text-red-600">Actualizar con nuevo</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row md:space-x-8">
                                <div class="mb-4 md:mb-0">
                                    <h5 class="font-medium text-gray-700 mb-1">Datos Existentes:</h5>
                                    <p><span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($duplicado['existente']['TipoDoc']); ?></p>
                                    <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($duplicado['existente']['P_Nombre'] . ' ' . $duplicado['existente']['S_Nombre']); ?></p>
                                    <p><span class="font-medium">Apellidos:</span> <?php echo htmlspecialchars($duplicado['existente']['P_Apellido'] . ' ' . $duplicado['existente']['S_Apellido']); ?></p>
                                </div>
                                
                                <div>
                                    <h5 class="font-medium text-gray-700 mb-1">Datos Nuevos (del Excel):</h5>
                                    <p><span class="font-medium">Tipo:</span> <?php echo htmlspecialchars($duplicado['registro']['tipoDoc']); ?></p>
                                    <p><span class="font-medium">Nombre:</span> <?php echo htmlspecialchars($duplicado['registro']['p_nombre'] . ' ' . $duplicado['registro']['s_nombre']); ?></p>
                                    <p><span class="font-medium">Apellidos:</span> <?php echo htmlspecialchars($duplicado['registro']['p_apellido'] . ' ' . $duplicado['registro']['s_apellido']); ?></p>
                                </div>
                            </div>
                            
                            <p class="mt-4 text-xs text-red-600">
                                <span class="font-medium">Cambios detectados en:</span> <?php echo implode(', ', $duplicado['cambios']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                    
                    <nav class="flex justify-center mt-4">
                        <?php
                        // Crea un array con los parámetros adicionales que quieres mantener en la URL
                        $parametros_duplicados = [
                            'search' => $_GET['search'] ?? '',
                            'tipoDoc' => $_GET['tipoDoc'] ?? '',
                            'encuestado' => $_GET['encuestado'] ?? '',
                            'por_pagina' => $_GET['por_pagina'] ?? 20,
                        ];
                        
                        // Llama a la función, especificando el parámetro de página como 'pagina_duplicados'
                        $enlaces_duplicados = generarEnlacesPaginacion(
                            $pagina_actual_duplicados,
                            $total_paginas_duplicados,
                            $parametros_duplicados,
                            'pagina_duplicados'
                        );

                        foreach ($enlaces_duplicados as $enlace) {
                            echo $enlace;
                        }
                        ?>
                    </nav>
                </div>
            <?php endif; ?>

            <div class="flex justify-center space-x-4">
                <button type="submit" name="confirmarExcel" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition duration-300">
                    Confirmar y Procesar
                </button>
                <button type="submit" name="cancelarExcel" class="px-6 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition duration-300">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

            <!-- Sección de Carga de Archivo -->
            <?php if (!$mostrar_vista_previa): ?>
                <div class="mb-8 p-6 bg-gray-50 shadow-inner border border-gray-200">
                    <h2 class="text-xl md:text-2xl font-semibold text-gray-700 mb-4">Cargar Archivo Excel</h2>
                    <form action="" method="post" enctype="multipart/form-data" onsubmit="showLoading()">
                        <div class="flex flex-col md:flex-row md:items-center gap-4">
                            <div class="flex-1">
                                <input type="file" name="excelFile" id="excelFile" class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0
                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100 cursor-pointer">
                            </div>
                            
                            <button type="submit" class="w-full md:w-auto px-6 py-2 bg-indigo-600 text-white font-semibold rounded-full
                                shadow-lg hover:bg-indigo-700 transition duration-300 ease-in-out transform hover:scale-105">
                                Subir y Procesar
                            </button>
                        </div>
                    </form>
                </div>

            <!-- Botón para Crear Nuevo Registro -->
            <!-- <div class="mb-6 text-center">
                <button onclick="openCreateModal()" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition duration-300">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Crear Nuevo Registro
                </button>
            </div> -->
            <?php endif; ?>

            <!-- Sección de Búsqueda y Tabla de Datos -->
            <?php if (!$mostrar_vista_previa): ?>
            <div class="p-6 bg-gray-50 rounded-xl shadow-inner border border-gray-200">
            <div class="flex flex-col md:flex-row items-center justify-between mb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-semibold text-gray-700">Registros de Sisbén</h2>
                    <form action="" method="get" class="w-full md:w-auto flex flex-col md:flex-row items-center gap-3">
                            <input type="text" name="search" placeholder="Buscar por documento, nombre o apellido..."
                                class="flex-1 p-2 rounded-full border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo htmlspecialchars($search_query); ?>">
                            
                            <select name="tipoDoc" class="p-2 rounded-full border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Todo Tipo de Doc.</option>
                                <option value="CC" <?php echo ($tipoDoc_filter === 'CC') ? 'selected' : ''; ?>>CC</option>
                                <option value="TI" <?php echo ($tipoDoc_filter === 'TI') ? 'selected' : ''; ?>>TI</option>
                                <option value="RC" <?php echo ($tipoDoc_filter === 'RC') ? 'selected' : ''; ?>>RC</option>
                                <option value="CE" <?php echo ($tipoDoc_filter === 'CE') ? 'selected' : ''; ?>>CE</option>
                                <option value="PA" <?php echo ($tipoDoc_filter === 'PA') ? 'selected' : ''; ?>>PA</option>
                            </select>

                            <select name="encuestado" class="p-2 rounded-full border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Todos</option>
                                <option value="1" <?php echo ($encuestado_filter === '1') ? 'selected' : ''; ?>>Encuestados</option>
                                <option value="0" <?php echo ($encuestado_filter === '0') ? 'selected' : ''; ?>>No Encuestados</option>
                            </select>
                            
                            <button type="submit" class="bg-indigo-600 text-white p-2 rounded-full shadow-md hover:bg-indigo-700 transition duration-300 ease-in-out">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                </div>

                <!-- Información de paginación y controles -->
                <div class="mb-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        Mostrando <?php echo $total_registros > 0 ? ($offset + 1) : 0; ?> a <?php echo min($offset + $registros_por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> registros
                        <?php if (!empty($search_query)): ?>
                            (filtrados por: "<?php echo htmlspecialchars($search_query); ?>")
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
                    // Dentro de la sección de paginación superior y la inferior
                    $enlaces = generarEnlacesPaginacion(
                        $pagina_actual, 
                        $total_paginas, 
                        [
                            'search' => $search_query, 
                            'tipoDoc' => $tipoDoc_filter, 
                            'encuestado' => $encuestado_filter, 
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

                <form action="" method="post" id="updateForm">
                    <div class="overflow-x-auto rounded-lg shadow-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-indigo-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TipoDoc</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">P. Apellido</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S. Apellido</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">P. Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S. Nombre</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Encuestado</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="<?php echo $row['actualizado'] ? 'bg-green-50' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['TipoDoc']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['Documento']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['P_Apellido']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['S_Apellido']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['P_Nombre']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['S_Nombre']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            
                                            <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" <?php echo $row['actualizado'] ? 'checked disabled' : ''; ?> class="h-4 w-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex justify-center space-x-2">
                                                <button type="button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="text-indigo-600 hover:text-indigo-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">No se encontraron registros.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="mt-6 flex items-center justify-center">
                        <nav class="flex items-center space-x-1" aria-label="Paginación">
                            <?php 
                            $enlaces = generarEnlacesPaginacion(
                                $pagina_actual, 
                                $total_paginas, 
                                [
                                    'search' => $search_query, 
                                    'tipoDoc' => $tipoDoc_filter, 
                                    'encuestado' => $encuestado_filter, 
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
                    
                    <div class="mt-6 text-center">
                        <button type="submit" name="updateStatus" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-full shadow-lg hover:bg-indigo-700 transition duration-300 ease-in-out transform hover:scale-105 disabled:opacity-50" id="saveButton">
                            Marcar como Encuestados
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Crear/Editar Registro -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Editar Registro</h3>
                <form id="editForm" method="post">
                    <input type="hidden" id="editId" name="id" value="">
                    <div class="grid grid-cols-1 gap-4 text-left">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Documento</label>
                            <select id="editTipoDoc" name="tipoDoc" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">Seleccione...</option>
                                <option value="CC">Cédula de Ciudadanía</option>
                                <option value="TI">Tarjeta de Identidad</option>
                                <option value="RC">Registro Civil</option>
                                <option value="CE">Cédula de Extranjería</option>
                                <option value="PA">Pasaporte</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Documento</label>
                            <input type="text" id="editDocumento" name="documento" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Primer Apellido</label>
                            <input type="text" id="editPApellido" name="p_apellido" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Segundo Apellido</label>
                            <input type="text" id="editSApellido" name="s_apellido" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Primer Nombre</label>
                            <input type="text" id="editPNombre" name="p_nombre" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Segundo Nombre</label>
                            <input type="text" id="editSNombre" name="s_nombre" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="flex justify-center space-x-4 mt-6">
                        <button type="submit" name="editarRegistro" id="submitBtn" class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            Guardar
                        </button>
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmar Eliminación</h3>
                <p class="text-sm text-gray-500 mb-4">¿Está seguro que desea eliminar el registro con documento <span id="deleteDocumento" class="font-semibold"></span>?</p>
                <p class="text-xs text-red-600 mb-4">Esta acción no se puede deshacer.</p>
                <form id="deleteForm" method="post" class="flex justify-center space-x-4">
                    <input type="hidden" id="deleteId" name="id" value="">
                    <button type="submit" name="eliminarRegistro" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Eliminar
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancelar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Overlay de Carga -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function cambiarRegistrosPorPagina(valor) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('por_pagina', valor);
            urlParams.delete('pagina');
            window.location.href = window.location.pathname + '?' + urlParams.toString();
        }

        // function openCreateModal() {
        //     document.getElementById('modalTitle').textContent = 'Crear Nuevo Registro';
        //     document.getElementById('editForm').reset();
        //     document.getElementById('editId').value = '';
        //     document.getElementById('submitBtn').name = 'crearRegistro';
        //     document.getElementById('submitBtn').textContent = 'Crear';
        //     document.getElementById('editModal').classList.remove('hidden');
        // }

        function openEditModal(registro) {
            document.getElementById('modalTitle').textContent = 'Editar Registro';
            document.getElementById('editId').value = registro.id;
            document.getElementById('editTipoDoc').value = registro.TipoDoc;
            document.getElementById('editDocumento').value = registro.Documento;
            document.getElementById('editPApellido').value = registro.P_Apellido;
            document.getElementById('editSApellido').value = registro.S_Apellido;
            document.getElementById('editPNombre').value = registro.P_Nombre;
            document.getElementById('editSNombre').value = registro.S_Nombre;
            document.getElementById('submitBtn').name = 'editarRegistro';
            document.getElementById('submitBtn').textContent = 'Guardar';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function confirmDelete(id, documento) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteDocumento').textContent = documento;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
    const btnMantener = document.getElementById('seleccionar-todos-mantener');
    const btnActualizar = document.getElementById('seleccionar-todos-actualizar');
    const radios = document.querySelectorAll('input[type="radio"].duplicados-radio');
    
    // Carga las decisiones de la sesión desde el PHP
    const decisionesGuardadas = <?php echo json_encode($_SESSION['decisiones_duplicados'] ?? []); ?>;

    for (const documento in decisionesGuardadas) {
        if (decisionesGuardadas.hasOwnProperty(documento)) {
            const accion = decisionesGuardadas[documento];
            const radioBtn = document.querySelector(`input[name="duplicados[${documento}]"][value="${accion}"]`);
            if (radioBtn) {
                radioBtn.checked = true;
            }
        }
    }

    // Listener para los radio buttons individuales
    document.addEventListener('change', function(event) {
        if (event.target.classList.contains('duplicados-radio')) {
            const documento = event.target.name.match(/\[(.*?)\]/)[1];
            const accion = event.target.value;
            saveUserChoice(documento, accion);
        }
    });

    // Listener para el botón "Actualizar todos"
    if (btnActualizar) {
        btnActualizar.addEventListener('click', function() {
            // Creamos un array para guardar todas las decisiones antes de enviarlas
            const decisiones = {};
            radios.forEach(radio => {
                if (radio.value === 'actualizar') {
                    radio.checked = true;
                    const documento = radio.name.match(/\[(.*?)\]/)[1];
                    decisiones[documento] = 'actualizar';
                }
            });
            // Enviamos un solo objeto con todas las decisiones masivas
            saveMassiveChoices(decisiones);
        });
    }

    // Listener para el botón "Mantener todos"
    if (btnMantener) {
        btnMantener.addEventListener('click', function() {
            const decisiones = {};
            radios.forEach(radio => {
                if (radio.value === 'mantener') {
                    radio.checked = true;
                    const documento = radio.name.match(/\[(.*?)\]/)[1];
                    decisiones[documento] = 'mantener';
                }
            });
            saveMassiveChoices(decisiones);
        });
    }

    // Función para enviar una única decisión al servidor
    function saveUserChoice(documento, accion) {
        const formData = new FormData();
        formData.append('documento', documento);
        formData.append('accion', accion);

        fetch('<?php echo htmlspecialchars('guardar_eleccion.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            console.log('Respuesta del servidor:', result);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // NUEVA FUNCIÓN: Para enviar múltiples decisiones en una sola petición
    function saveMassiveChoices(decisiones) {
        const formData = new FormData();
        formData.append('decisiones_masivas', JSON.stringify(decisiones));

        fetch('<?php echo htmlspecialchars('guardar_eleccion.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            console.log('Respuesta del servidor (masivo):', result);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});
        
        // Habilitar/deshabilitar botón de guardar
        document.getElementById('updateForm').addEventListener('change', function() {
            const checkboxes = this.querySelectorAll('input[type="checkbox"]:not(:disabled)');
            const hasChecked = Array.from(checkboxes).some(cb => cb.checked);
            document.getElementById('saveButton').disabled = !hasChecked;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:not(:disabled)');
            const hasChecked = Array.from(checkboxes).some(cb => cb.checked);
            document.getElementById('saveButton').disabled = !hasChecked;
        });

        window.addEventListener('load', function() {
            const messageBox = document.getElementById('message-box');
            if (messageBox) {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        });
    </script>
</body>
</html>

<?php
$conexion->close();
?>