
<?php
require_once 'Middleware/auth.php';

// Cerrar sesión usando el middleware
AuthMiddleware::logout();
?>