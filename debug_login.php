<?php
// debug_login.php - Para diagnosticar problemas de login
echo "<h2>🔍 Diagnóstico del Sistema de Login</h2>";

// Incluir la conexión
require_once 'db_connect.php';

echo "<h3>1. Conexión a la base de datos:</h3>";
if ($conexion) {
    echo "✅ Conexión MySQLi: OK<br>";
} else {
    echo "❌ Conexión MySQLi: ERROR<br>";
}

try {
    $pdo = getPDO();
    echo "✅ Conexión PDO: OK<br>";
} catch (Exception $e) {
    echo "❌ Conexión PDO: ERROR - " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h3>2. Verificar tabla usuarios:</h3>";
$sql_tabla = "SHOW TABLES LIKE 'usuarios'";
$resultado = mysqli_query($conexion, $sql_tabla);
if (mysqli_num_rows($resultado) > 0) {
    echo "✅ Tabla 'usuarios' existe<br>";
    
    // Mostrar estructura
    echo "<h4>Estructura de la tabla:</h4>";
    $estructura = mysqli_query($conexion, "DESCRIBE usuarios");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($estructura)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "❌ Tabla 'usuarios' NO existe<br>";
    echo "<strong>SOLUCIÓN:</strong> Ejecuta el script SQL para crear la tabla<br>";
}

echo "<hr>";

echo "<h3>3. Verificar usuarios registrados:</h3>";
$sql_usuarios = "SELECT id, usuario, nombre, rol, activo, fecha_creacion FROM usuarios";
$resultado = mysqli_query($conexion, $sql_usuarios);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Activo</th><th>Fecha</th></tr>";
    while ($row = mysqli_fetch_assoc($resultado)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['usuario'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['rol'] . "</td>";
        echo "<td>" . ($row['activo'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td>" . $row['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No hay usuarios registrados<br>";
    echo "<strong>SOLUCIÓN:</strong> Ejecuta los INSERT del script SQL<br>";
}

echo "<hr>";

echo "<h3>4. Probar hash de contraseñas:</h3>";
$password_prueba = '123456';
$hash_correcto = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Contraseña de prueba: <strong>$password_prueba</strong><br>";
echo "Hash almacenado: <code>$hash_correcto</code><br>";

if (password_verify($password_prueba, $hash_correcto)) {
    echo "✅ Verificación de password: OK<br>";
} else {
    echo "❌ Verificación de password: ERROR<br>";
    echo "<strong>Generando nuevo hash...</strong><br>";
    $nuevo_hash = password_hash($password_prueba, PASSWORD_DEFAULT);
    echo "Nuevo hash: <code>$nuevo_hash</code><br>";
    
    echo "<h4>SQL para actualizar passwords:</h4>";
    echo "<textarea rows='6' cols='80'>
UPDATE usuarios SET password = '$nuevo_hash' WHERE usuario = 'admin_sistema';
UPDATE usuarios SET password = '$nuevo_hash' WHERE usuario = 'operador1';  
UPDATE usuarios SET password = '$nuevo_hash' WHERE usuario = 'consulta1';
</textarea>";
}

echo "<hr>";

echo "<h3>5. Probar consulta de login:</h3>";
$usuario_test = 'admin_sistema';
echo "Probando login para: <strong>$usuario_test</strong><br>";

// Usar PDO como en el login real
try {
    $pdo = getPDO();
    $query = "SELECT id, usuario, password, nombre, rol FROM usuarios WHERE usuario = :usuario AND activo = 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':usuario', $usuario_test);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        echo "✅ Usuario encontrado:<br>";
        echo "- ID: " . $user['id'] . "<br>";
        echo "- Usuario: " . $user['usuario'] . "<br>";
        echo "- Nombre: " . $user['nombre'] . "<br>";
        echo "- Rol: " . $user['rol'] . "<br>";
        echo "- Password hash: <code>" . substr($user['password'], 0, 30) . "...</code><br>";
        
        // Probar password
        if (password_verify('123456', $user['password'])) {
            echo "✅ Password correcto<br>";
        } else {
            echo "❌ Password incorrecto<br>";
        }
    } else {
        echo "❌ Usuario no encontrado o inactivo<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error en consulta: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>6. Estado de sesiones:</h3>";
session_start();
if (isset($_SESSION['usuario_id'])) {
    echo "✅ Hay sesión activa:<br>";
    echo "- Usuario ID: " . $_SESSION['usuario_id'] . "<br>";
    echo "- Usuario: " . $_SESSION['usuario'] . "<br>";
    echo "- Nombre: " . $_SESSION['nombre'] . "<br>";
    echo "- Rol: " . $_SESSION['rol'] . "<br>";
    echo "<a href='../proyecto_sisben/Dashboard/logout.php'>Cerrar sesión</a>";
} else {
    echo "ℹ️ No hay sesión activa<br>";
}

mysqli_close($conexion);
?>