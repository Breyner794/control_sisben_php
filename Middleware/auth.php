<?php
// Middleware/auth.php
// ----------------------------------------------------
// Middleware de autenticación y autorización
// ----------------------------------------------------
session_start();

class AuthMiddleware {
    
    // Ruta de redirección centralizada
    const LOGIN_PAGE = '/proyecto_sisben/login.php';
    const DENIED_PAGE = '/proyecto_sisben/acceso_denegado.php';
    
    // Verificar si el usuario está logueado
    public static function verificarLogin() {
        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario'])) {
            header('Location: ' . self::LOGIN_PAGE);
            exit();
        }
    }
    
    // Verificar si el usuario tiene un rol necesario
    public static function verificarRol($roles_requeridos) {
        self::verificarLogin();
        
        // Convertir a array si solo se pasa un rol (string)
        if (!is_array($roles_requeridos)) {
            $roles_requeridos = [$roles_requeridos];
        }

        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $roles_requeridos)) {
            header('Location: ' . self::DENIED_PAGE);
            exit();
        }
    }
    
    // Verificar si ya está logueado (para redirigir desde login.php)
    public static function yaLogueado() {
        return isset($_SESSION['usuario_id']);
    }

    // Cerrar sesión
    public static function logout() {
        session_start();
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Obtener datos del usuario actual
    public static function getUsuarioActual() {
        if (!self::yaLogueado()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['usuario_id'],
            'usuario' => $_SESSION['usuario'],
            'nombre' => $_SESSION['nombre'],
            'rol' => $_SESSION['rol']
        ];
    }
}
?>