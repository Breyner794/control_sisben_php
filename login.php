<?php
require_once 'db_connect.php';
require_once 'Middleware/auth.php';

// Si ya está logueado, redirigir al dashboard
if (AuthMiddleware::yaLogueado()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $pdo = getPDO();
            
            $query = "SELECT id, usuario, password, nombre, rol FROM usuarios WHERE usuario = :usuario AND activo = 1";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar password (usando password_verify para mayor seguridad)
                if (password_verify($password, $user['password'])) {
                    // Iniciar sesión
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['usuario'] = $user['usuario'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['rol'] = $user['rol'];
                    
                    // Actualizar último acceso
                    $update_query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->bindParam(':id', $user['id']);
                    $update_stmt->execute();
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error en el servidor. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SISBEN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .demo-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #e7f3ff;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        
        .demo-info h4 {
            color: #1976D2;
            margin-bottom: 0.5rem;
        }
        
        .demo-info p {
            font-size: 0.85rem;
            color: #666;
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>SISBEN</h1>
            <p>Sistema de Control</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>
        
        <div class="demo-info">
            <h4>Credenciales de Prueba:</h4>
            <p><strong>Admin Sistema:</strong> usuario: admin_sistema, password: 123456</p>
            <p><strong>Operador SISBEN:</strong> usuario: operador1, password: 123456</p>
            <p><strong>Solo Consultas:</strong> usuario: consulta1, password: 123456</p>
        </div>
    </div>
</body>
</html>