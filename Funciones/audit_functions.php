<?php
// audit_functions.php
// -----------------------------------------------------------------------------
// Funciones para registrar eventos de auditoría en la base de datos
// -----------------------------------------------------------------------------
function logAuditoria($accion, $tabla = null, $registro_id = null) {
    // No registrar si el usuario no está autenticado
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }

    // Incluir la conexión a la base de datos si no está ya disponible
    if (!function_exists('ejecutarComando')) {
        require_once 'db_connect.php'; 
    }
    
    // Obtener el ID del usuario actual de la sesión
    $usuario_id = $_SESSION['usuario_id'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Consulta para insertar el registro de auditoría
    $sql = "INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, ip) VALUES (?, ?, ?, ?, ?)";
    
    // Usar la función de ayuda para ejecutar el comando
    ejecutarComando($sql, [$usuario_id, $accion, $tabla, $registro_id, $ip]);
}
?>