<div align="left" style="position: relative;">
<img src="https://img.icons8.com/external-tal-revivo-filled-tal-revivo/96/external-markdown-a-lightweight-markup-language-with-plain-text-formatting-syntax-logo-filled-tal-revivo.png" align="right" width="30%" style="margin: -20px 0 0 20px;">
<h1>SISTEMA SISBEN PHP</h1>
<p align="left">
	<em>Sistema completo de gestión y consulta para el programa SISBEN desarrollado en PHP con panel administrativo</em>
</p>
<p align="left">Built with the tools and technologies:</p>
<p align="left">
	<a href="https://skillicons.dev">
		<img src="https://skillicons.dev/icons?i=md,php,mysql,html,css,js">
	</a></p>
</div>
<br clear="right">

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Project Structure](#-project-structure)
  - [Project Index](#-project-index)
- [Getting Started](#-getting-started)
  - [Prerequisites](#-prerequisites)
  - [Installation](#-installation)
  - [Configuration](#-configuration)
  - [Usage](#-usage)
- [Modules](#-modules)
- [Security](#-security)

---

## 🎯 Overview

Este proyecto es un **Sistema Completo de Gestión SISBEN** que proporciona herramientas administrativas avanzadas para el control de beneficiarios del Sistema de Identificación de Potenciales Beneficiarios de Programas Sociales (SISBEN).

El sistema incluye:

- **Panel de Administración** con gestión de usuarios y auditoría
- **Módulo de Control SISBEN** para administración de beneficiarios
- **Sistema de Consulta SISBEN IV** para búsquedas rápidas
- **Sistema de Autenticación** con roles y permisos
- **Auditoría completa** de todas las acciones del sistema

---

## ✨ Features

### 🔐 **Sistema de Autenticación y Seguridad**

- **Login seguro** con validación de credenciales
- **Sistema de roles**: Administrador del Sistema, Operador SISBEN, Consulta
- **Middleware de autenticación** para proteger rutas
- **Auditoría completa** de todas las acciones de usuarios
- **Gestión de sesiones** segura

### 📊 **Panel de Administración**

- **Dashboard principal** con acceso a todos los módulos
- **Gestión de usuarios** (crear, editar, activar/desactivar, eliminar)
- **Log de auditoría** con filtros y búsqueda
- **Herramientas de mantenimiento** del sistema
- **Interfaz responsive** y moderna

### 🎛️ **Módulo de Control SISBEN**

- **Administración completa** de beneficiarios
- **Importación/exportación** de datos con PHPSpreadsheet
- **Gestión de elecciones** y selecciones
- **Búsqueda avanzada** y filtros
- **Edición y eliminación** de registros

### 🔍 **Sistema de Consulta SISBEN IV**

- **Consulta rápida** de beneficiarios
- **Búsqueda por múltiples criterios**
- **Resultados detallados** con información completa
- **Interfaz intuitiva** para consultas

---

## 📁 Project Structure

```sh
└── PROYECTO_SISBEN/
    ├── modulos/
    │   ├── admin_sistema.php          # Administración del sistema
    │   ├── auditoria.php              # Log de auditoría
    │   ├── consulta_sisben.php        # Sistema de consulta SISBEN
    │   ├── db_management.php          # Gestión de base de datos
    │   ├── sisben_admin.php           # Panel administrativo SISBEN
    │   └── usuarios.php               # Gestión de usuarios
    ├── CSS/
    │   └── estilos_modulos.css        # Estilos del sistema
    ├── Funciones/
    │   ├── audit_functions.php        # Funciones de auditoría
    │   └── guardar_eleccion.php       # Procesamiento de elecciones
    ├── includes/
    │   ├── vendor/                    # Librerías de Composer
    │   └── leer.txt                   # Documentación adicional
    ├── Middleware/
    │   └── auth.php                   # Middleware de autenticación
    ├── dashboard.php                  # Panel principal
    ├── login.php                      # Sistema de login
    ├── logout.php                     # Cerrar sesión
    ├── db_connect.php                 # Conexión principal DB
    ├── debug_login.php                # Debug de autenticación
    ├── acceso_denegado.php            # Página de acceso denegado
    ├── composer.json                  # Dependencias del proyecto
    ├── composer.lock                  # Lock de dependencias
    ├── composer.phar                  # Ejecutable de Composer
    ├── .gitignore                     # Archivos ignorados por Git
    └── README.md                      # Documentación
```

### 📋 Project Index

<details open>
	<summary><b><code>PROYECTO_SISBEN/</code></b></summary>
	
	<details> <!-- Core Files -->
		<summary><b>Archivos Principales</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='dashboard.php'>dashboard.php</a></b></td>
				<td>Panel principal de administración con acceso a todos los módulos</td>
			</tr>
			<tr>
				<td><b><a href='login.php'>login.php</a></b></td>
				<td>Sistema de autenticación seguro con validación de roles</td>
			</tr>
			<tr>
				<td><b><a href='logout.php'>logout.php</a></b></td>
				<td>Cerrar sesión y limpiar datos de autenticación</td>
			</tr>
			<tr>
				<td><b><a href='db_connect.php'>db_connect.php</a></b></td>
				<td>Configuración principal de conexión a la base de datos</td>
			</tr>
			<tr>
				<td><b><a href='debug_login.php'>debug_login.php</a></b></td>
				<td>Herramienta de debug para el sistema de autenticación</td>
			</tr>
			<tr>
				<td><b><a href='acceso_denegado.php'>acceso_denegado.php</a></b></td>
				<td>Página de acceso denegado para usuarios sin permisos</td>
			</tr>
			</table>
		</blockquote>
	</details>
	
	<details> <!-- Módulos -->
		<summary><b>Módulos del Sistema</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='modulos/admin_sistema.php'>admin_sistema.php</a></b></td>
				<td>Administración del sistema, logs de auditoría y herramientas de mantenimiento</td>
			</tr>
			<tr>
				<td><b><a href='modulos/auditoria.php'>auditoria.php</a></b></td>
				<td>Log completo de auditoría con filtros y paginación</td>
			</tr>
			<tr>
				<td><b><a href='modulos/consulta_sisben.php'>consulta_sisben.php</a></b></td>
				<td>Sistema de consulta SISBEN con búsqueda avanzada</td>
			</tr>
			<tr>
				<td><b><a href='modulos/db_management.php'>db_management.php</a></b></td>
				<td>Gestión y administración de la base de datos</td>
			</tr>
			<tr>
				<td><b><a href='modulos/sisben_admin.php'>sisben_admin.php</a></b></td>
				<td>Panel administrativo principal del sistema SISBEN con gestión de beneficiarios</td>
			</tr>
			<tr>
				<td><b><a href='modulos/usuarios.php'>usuarios.php</a></b></td>
				<td>Gestión completa de usuarios del sistema</td>
			</tr>
			</table>
		</blockquote>
	</details>
	
	<details> <!-- Carpetas de Soporte -->
		<summary><b>Carpetas de Soporte</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='Middleware/auth.php'>Middleware/auth.php</a></b></td>
				<td>Middleware de autenticación y verificación de roles</td>
			</tr>
			<tr>
				<td><b><a href='Funciones/audit_functions.php'>Funciones/audit_functions.php</a></b></td>
				<td>Funciones para el sistema de auditoría</td>
			</tr>
			<tr>
				<td><b><a href='Funciones/guardar_eleccion.php'>Funciones/guardar_eleccion.php</a></b></td>
				<td>Procesamiento y almacenamiento de elecciones de beneficiarios</td>
			</tr>
			<tr>
				<td><b><a href='CSS/estilos_modulos.css'>CSS/estilos_modulos.css</a></b></td>
				<td>Estilos CSS personalizados para todos los módulos</td>
			</tr>
			<tr>
				<td><b><a href='includes/vendor/'>includes/vendor/</a></b></td>
				<td>Librerías de Composer y dependencias</td>
			</tr>
			</table>
		</blockquote>
	</details>
</details>

---

## 🚀 Getting Started

### 📋 Prerequisites

Antes de comenzar con el proyecto, asegúrate de que tu entorno cumpla con los siguientes requisitos:

- **PHP**: Versión 8.1 o posterior
- **Servidor Web**: Apache, Nginx o XAMPP
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.2+
- **Composer**: Para gestión de dependencias
- **Extensiones PHP**: GD, ZIP, PDO, MySQLi

### ⚙️ Installation

#### 1. Clonar el repositorio

```sh
git clone https://github.com/tu-usuario/PROYECTO_SISBEN.git
cd PROYECTO_SISBEN
```

#### 2. Configurar el servidor web

**Para XAMPP:**

1. Copia el proyecto a `C:\xampp\htdocs\PROYECTO_SISBEN\`
2. Inicia Apache y MySQL desde el Panel de Control de XAMPP

**Para otros servidores:**

- Configura el DocumentRoot apuntando a la carpeta del proyecto
- Asegúrate de que PHP esté habilitado

#### 3. Configurar PHP

**Verificar versión de PHP:**

```sh
php -v
```

**Habilitar extensiones necesarias en php.ini:**

```ini
extension=gd
extension=zip
extension=pdo_mysql
extension=mysqli
```

#### 4. Instalar Composer y dependencias

**Instalar Composer (si no lo tienes):**

```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

**Instalar dependencias del proyecto:**

```sh
php composer-setup.php
```

**Instalar PHPSpreadsheet:**

```sh
INSTALAR en la raiz del proyecto
php composer.phar require phpoffice/phpspreadsheet
```

### 🔧 Configuration

#### 1. Configurar conexión a la base de datos

Edita el archivo `db_connect.php` con los datos de tu servidor:

```php
<?php
$servername = "localhost";
$username = "tu_usuario";
$password = "tu_contraseña";
$database = "sisben_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
```

#### 2. Crear la base de datos

```sql
CREATE DATABASE sisben_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sisben_db;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol ENUM('admin_sistema', 'operador_sisben', 'consulta_solo') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL
);

-- Tabla de auditoría
CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla VARCHAR(50),
    registro_id INT,
    ip VARCHAR(45),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (usuario, password, nombre, email, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@sisben.com', 'admin_sistema');
-- Contraseña: password
```

### 🎯 Usage

#### Acceso al sistema:

1. **Inicia tu servidor web** (Apache en XAMPP)
2. **Accede al login**: `http://localhost/PROYECTO_SISBEN/login.php`
3. **Credenciales por defecto**:
   - Usuario: `admin`
   - Contraseña: `password`

#### Módulos disponibles:

- **Dashboard**: `http://localhost/PROYECTO_SISBEN/dashboard.php`
- **Gestión de Usuarios**: `http://localhost/PROYECTO_SISBEN/modulos/usuarios.php`
- **Auditoría**: `http://localhost/PROYECTO_SISBEN/modulos/auditoria.php`
- **Admin Sistema**: `http://localhost/PROYECTO_SISBEN/modulos/admin_sistema.php`
- **Control SISBEN**: `http://localhost/PROYECTO_SISBEN/modulos/sisben_admin.php`
- **Consulta SISBEN**: `http://localhost/PROYECTO_SISBEN/modulos/consulta_sisben.php`
- **Gestión DB**: `http://localhost/PROYECTO_SISBEN/modulos/db_management.php`

---

## 📦 Modules

### 🔐 **Dashboard y Autenticación**

- **Login seguro** con validación de roles
- **Panel principal** con navegación a todos los módulos
- **Sistema de logout** seguro

### 👥 **Gestión de Usuarios**

- **Crear usuarios** con diferentes roles
- **Editar información** de usuarios
- **Activar/desactivar** usuarios
- **Eliminar usuarios** con confirmación
- **Protección** para evitar auto-eliminación

### 📊 **Auditoría del Sistema**

- **Log completo** de todas las acciones
- **Filtros avanzados** por usuario, acción, tabla y fecha
- **Paginación** para mejor rendimiento
- **Exportación** de logs

### ⚙️ **Administración del Sistema**

- **Herramientas de mantenimiento**
- **Limpieza de logs** antiguos
- **Respaldo de base de datos**
- **Monitoreo** del sistema

### 🎛️ **Control SISBEN**

- **Gestión de beneficiarios**
- **Importación/exportación** Excel
- **Búsqueda avanzada**
- **Edición y eliminación** de registros

### 🔍 **Consulta SISBEN IV**

- **Búsqueda rápida** de beneficiarios
- **Múltiples criterios** de búsqueda
- **Resultados detallados**

---

## 🔒 Security

### **Características de Seguridad:**

- ✅ **Autenticación segura** con hash de contraseñas
- ✅ **Middleware de protección** en todas las rutas
- ✅ **Validación de roles** y permisos
- ✅ **Auditoría completa** de acciones
- ✅ **Protección contra SQL Injection**
- ✅ **Escape de datos** en todas las salidas
- ✅ **Sesiones seguras** con timeout

### **Roles del Sistema:**

- **admin_sistema**: Acceso completo a todos los módulos
- **operador_sisben**: Acceso al módulo de control SISBEN
- **consulta_solo**: Solo acceso al sistema de consulta

---

## 🐛 Troubleshooting

### **Problemas comunes:**

**Error de conexión a la base de datos:**

- Verifica las credenciales en `db_connect.php`
- Asegúrate de que MySQL esté ejecutándose
- Verifica que la base de datos exista

**Error de extensiones PHP:**

- Habilita las extensiones GD, ZIP, PDO en php.ini
- Reinicia el servidor web después de los cambios

**Error de Composer:**

- Verifica que tengas PHP 8.1+ instalado
- Ejecuta `composer install` en la raíz del proyecto
- Para PHPSpreadsheet: `php composer.phar require phpoffice/phpspreadsheet`

**Error de permisos:**

- Asegúrate de que el servidor web tenga permisos de lectura/escritura
- Verifica los permisos de la carpeta `vendor/`

---

## 📝 License

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 🤝 Contributing

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📞 Support

Si tienes alguna pregunta o necesitas ayuda:

- 📧 Email: jhoanquilindo@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/tu-usuario/proyecto_sisben/issues)
- 📖 Wiki: [Documentación completa](https://github.com/tu-usuario/proyecto_sisben/wiki)
