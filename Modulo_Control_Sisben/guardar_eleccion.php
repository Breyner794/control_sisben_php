<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejar decisiones masivas
    if (isset($_POST['decisiones_masivas'])) {
        $decisiones_masivas = json_decode($_POST['decisiones_masivas'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach ($decisiones_masivas as $documento => $accion) {
                $_SESSION['decisiones_duplicados'][$documento] = $accion;
            }
            echo "Decisiones masivas guardadas correctamente.";
        } else {
            http_response_code(400); // Bad Request
            echo "Error al decodificar JSON.";
        }
    } 
    // Manejar decisión individual
    elseif (isset($_POST['documento']) && isset($_POST['accion'])) {
        $documento = $_POST['documento'];
        $accion = $_POST['accion'];
        $_SESSION['decisiones_duplicados'][$documento] = $accion;
        echo "Decisión para el documento " . htmlspecialchars($documento) . " guardada: " . htmlspecialchars($accion);
    } else {
        http_response_code(400); // Bad Request
        echo "Datos no válidos.";
    }
}
?>