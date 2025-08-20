<?php
require_once '../Middleware/auth.php';
require_once '../db_connect.php';
require_once '../Funciones/audit_functions.php';

// Verificar que el usuario esté logueado y sea un administrador
AuthMiddleware::verificarLogin();
AuthMiddleware::verificarRol('admin_sistema');

$mensaje = '';
$tipo_mensaje = '';

// Lógica para procesar el formulario de usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_usuario'])) {
        $usuario = trim($_POST['usuario']);
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            // Verificar si el usuario ya existe
            $exist_user = obtenerRegistro("SELECT id FROM usuarios WHERE usuario = ?", [$usuario]);
            if ($exist_user) {
                $mensaje = "Error: El nombre de usuario ya existe.";
                $tipo_mensaje = 'error';
            } else {
                $sql = "INSERT INTO usuarios (usuario, password, nombre, email, rol) VALUES (?, ?, ?, ?, ?)";
                ejecutarComando($sql, [$usuario, $password, $nombre, $email, $rol]);
                $mensaje = "Usuario creado exitosamente.";
                $tipo_mensaje = 'success';

                // --- REGISTRO DE AUDITORÍA ---
                $nuevo_usuario_id = obtenerRegistro("SELECT id FROM usuarios WHERE usuario = ?", [$usuario])['id'];
                logAuditoria("Creación de usuario", "usuarios", $nuevo_usuario_id);
                }
        } catch (PDOException $e) {
            $mensaje = "Error al crear el usuario: " . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }

    if (isset($_POST['editar_usuario'])) {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = $_POST['rol'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // No permitir que un usuario se desactive a sí mismo
        if ($id == AuthMiddleware::getUsuarioActual()['id'] && $activo == 0) {
            $mensaje = "Error: No puedes desactivar tu propio usuario.";
            $tipo_mensaje = 'error';
        } else {
            try {
                $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?";
                ejecutarComando($sql, [$nombre, $email, $rol, $activo, $id]);
                $mensaje = "Usuario actualizado exitosamente.";
                $tipo_mensaje = 'success';
                logAuditoria("Actualización de usuario", "usuarios", $id);
            } catch (PDOException $e) {
                $mensaje = "Error al actualizar el usuario: " . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['cambiar_estado'])) {
        $id = $_POST['id'];
        $nuevo_estado = $_POST['activo'];
        
        // No permitir que un usuario se desactive a sí mismo
        if ($id == AuthMiddleware::getUsuarioActual()['id'] && $nuevo_estado == 0) {
            $mensaje = "Error: No puedes desactivar tu propio usuario.";
            $tipo_mensaje = 'error';
        } else {
            try {
                $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
                ejecutarComando($sql, [$nuevo_estado, $id]);
                $accion = $nuevo_estado ? "Activación" : "Desactivación";
                $mensaje = "Usuario " . ($nuevo_estado ? "activado" : "desactivado") . " exitosamente.";
                $tipo_mensaje = 'success';
                logAuditoria($accion . " de usuario", "usuarios", $id);
            } catch (PDOException $e) {
                $mensaje = "Error al cambiar el estado del usuario: " . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
    
    if (isset($_POST['eliminar_usuario'])) {
        $id = $_POST['id'];
        
        // No permitir que un usuario se elimine a sí mismo
        if ($id == AuthMiddleware::getUsuarioActual()['id']) {
            $mensaje = "Error: No puedes eliminar tu propio usuario.";
            $tipo_mensaje = 'error';
        } else {
            try {
                $sql = "DELETE FROM usuarios WHERE id = ?";
                ejecutarComando($sql, [$id]);
                $mensaje = "Usuario eliminado exitosamente.";
                $tipo_mensaje = 'success';
                logAuditoria("Eliminación de usuario", "usuarios", $id);
            } catch (PDOException $e) {
                $mensaje = "Error al eliminar el usuario: " . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
}

// Obtener la lista de usuarios para mostrar
$usuarios = obtenerRegistros("SELECT id, usuario, nombre, email, rol, activo, fecha_creacion, ultimo_acceso FROM usuarios");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - SISBEN</title>
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
        <h2>Gestión de Usuarios</h2>
        <a href="../dashboard.php" class="back-btn">← Volver al Dashboard</a>
    </header>

    <div class="container mx-auto p-4 md:p-8">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border border-gray-200">
            <h1 class="text-3xl md:text-4xl font-bold text-center text-indigo-600 mb-6">Gestión de Usuarios</h1>
            <p class="text-center text-gray-600 mb-8">Administración de usuarios del sistema SISBEN.</p>

            <!-- Mensajes de estado -->
            <?php if ($mensaje): ?>
            <div class="p-4 mb-6 text-sm font-medium rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <?php endif; ?>

            <!-- Formulario para Crear Usuario -->
            <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Crear Nuevo Usuario</h2>
                <form action="" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                        <input type="text" name="usuario" required 
                               class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Nombre de usuario">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                        <input type="text" name="nombre" required 
                               class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Nombre completo">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" 
                               class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="correo@ejemplo.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" required 
                               class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Contraseña">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="rol" required 
                                class="w-full p-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Seleccionar rol...</option>
                            <option value="admin_sistema">Administrador del Sistema</option>
                            <option value="operador_sisben">Operador SISBEN</option>
                            <option value="consulta_solo">Consulta</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" name="crear_usuario" 
                                class="w-full bg-green-600 text-white p-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Usuarios -->
            <div class="p-6 bg-gray-50 rounded-xl border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Lista de Usuarios</h2>
                
                <div class="overflow-x-auto rounded-lg shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach ($usuarios as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td data-label="TipoDoc" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['id']); ?>
                                    </td>
                                    <td data-label="Usuario" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['usuario']); ?>
                                    </td>
                                    <td data-label="Nombre" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['nombre']); ?>
                                    </td>
                                    <td data-label="Email" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td data-label="Rol del usuario" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                            switch($user['rol']) {
                                                case 'admin_sistema':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'operador_sisben':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'consulta_solo':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($user['rol'], '_'))); ?>
                                        </span>
                                    </td>
                                    <td data-label="Estado" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td data-label="Acciones" class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center space-x-2">
                                            <button type="button" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <?php if ($user['id'] != AuthMiddleware::getUsuarioActual()['id']): ?>
                                             <!-- Botón de activar/desactivar -->
                                             <form action="" method="post" style="display:inline;" onsubmit="return confirmarCambioEstado('<?php echo $user['activo'] ? 'desactivar' : 'activar'; ?>')">
                                                 <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                 <input type="hidden" name="activo" value="<?php echo $user['activo'] ? '0' : '1'; ?>">
                                                 <button type="submit" name="cambiar_estado" 
                                                         class="<?php echo $user['activo'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900'; ?>"
                                                         title="<?php echo $user['activo'] ? 'Desactivar usuario' : 'Activar usuario'; ?>">
                                                                                                           <?php if ($user['activo']): ?>
                                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                      </svg>
                                                      <?php else: ?>
                                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                      </svg>
                                                      <?php endif; ?>
                                                 </button>
                                             </form>
                                             
                                             <!-- Botón de eliminar -->
                                             <form action="" method="post" style="display:inline;" onsubmit="return confirmarEliminacion()">
                                                 <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                 <button type="submit" name="eliminar_usuario" class="text-red-600 hover:text-red-900">
                                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                     </svg>
                                                 </button>
                                             </form>
                                             <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        No se encontraron usuarios.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4 text-center">Editar Usuario</h3>
                <form id="editForm" method="post" class="space-y-4">
                    <input type="hidden" id="editId" name="id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                        <input type="text" id="editUsuario" class="w-full p-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                        <input type="text" id="editNombre" name="nombre" required 
                               class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="editEmail" name="email" 
                               class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                                         <div>
                         <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                         <select id="editRol" name="rol" required 
                                 class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                             <option value="admin_sistema">Administrador del Sistema</option>
                             <option value="operador_sisben">Operador SISBEN</option>
                             <option value="consulta_solo">Consulta</option>
                         </select>
                     </div>
                     
                     <div>
                         <label class="block text-sm font-medium text-gray-700 mb-1">Estado del Usuario</label>
                         <div class="flex items-center space-x-3">
                             <label class="relative inline-flex items-center cursor-pointer">
                                 <input type="checkbox" id="editActivo" name="activo" class="sr-only peer">
                                 <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                 <span class="ml-3 text-sm font-medium text-gray-900" id="editActivoLabel">Inactivo</span>
                             </label>
                         </div>
                     </div>
                    
                    <div class="flex justify-center space-x-4 mt-6">
                        <button type="submit" name="editar_usuario" 
                                class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            Guardar
                        </button>
                        <button type="button" onclick="cerrarModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editarUsuario(user) {
            // Poblar el formulario con los datos del usuario
            document.getElementById('editId').value = user.id;
            document.getElementById('editUsuario').value = user.usuario;
            document.getElementById('editNombre').value = user.nombre;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRol').value = user.rol;
            
            // Configurar el toggle de estado
            const toggleActivo = document.getElementById('editActivo');
            const labelActivo = document.getElementById('editActivoLabel');
            
            toggleActivo.checked = user.activo == 1;
            labelActivo.textContent = user.activo == 1 ? 'Activo' : 'Inactivo';
            
            // Deshabilitar el toggle si es el usuario actual
            if (user.id == <?php echo AuthMiddleware::getUsuarioActual()['id']; ?>) {
                toggleActivo.disabled = true;
                labelActivo.textContent += ' (No puedes desactivar tu propio usuario)';
                labelActivo.classList.add('text-gray-500');
            } else {
                toggleActivo.disabled = false;
                labelActivo.classList.remove('text-gray-500');
            }
            
            // Mostrar la modal
            document.getElementById('editModal').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function confirmarEliminacion() {
            return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción no se puede deshacer.');
        }

        function confirmarCambioEstado(accion) {
            return confirm('¿Estás seguro de que quieres ' + accion + ' a este usuario?');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                cerrarModal();
            }
        }

        // Event listener para el toggle de estado
        document.addEventListener('DOMContentLoaded', function() {
            const toggleActivo = document.getElementById('editActivo');
            const labelActivo = document.getElementById('editActivoLabel');
            
            toggleActivo.addEventListener('change', function() {
                if (!this.disabled) {
                    labelActivo.textContent = this.checked ? 'Activo' : 'Inactivo';
                }
            });
        });
    </script>
</body>
</html>