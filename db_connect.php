<?php
// db_connect.php
// -----------------------------------------------------------------------------
// Este script establece la conexión a la base de datos MySQL usando mysqli.
// -----------------------------------------------------------------------------

// --- Configuración de la base de datos ---
$servidor = 'localhost';
$usuario = 'root';
$clave = '';
$base_de_datos = 'sisben_db';

// --- Establecer la conexión ---
$conn = mysqli_connect($servidor, $usuario, $clave, $base_de_datos);

// --- Verificar si la conexión fue exitosa ---
if (!$conn) {
    die('Error al conectar con la base de datos: ' . mysqli_connect_error());
}

// --- Configuración adicional (charset) ---
mysqli_set_charset($conn, 'utf8');

// NOTA: La conexión queda abierta para ser utilizada por los otros scripts.
// Para usar la conexión en otros archivos, usa la variable $conn.
?>