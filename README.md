<div align="left" style="position: relative;">
<img src="https://img.icons8.com/external-tal-revivo-filled-tal-revivo/96/external-markdown-a-lightweight-markup-language-with-plain-text-formatting-syntax-logo-filled-tal-revivo.png" align="right" width="30%" style="margin: -20px 0 0 20px;">
<h1>CONTROL_SISBEN_PHP</h1>
<p align="left">
	<em>Sistema de control y consulta para el programa SISBEN desarrollado en PHP</em>
</p>
<p align="left">Built with the tools and technologies:</p>
<p align="left">
	<a href="https://skillicons.dev">
		<img src="https://skillicons.dev/icons?i=md,php">
	</a></p>
</div>
<br clear="right">

##  Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Project Structure](#-project-structure)
  - [Project Index](#-project-index)
- [Getting Started](#-getting-started)
  - [Prerequisites](#-prerequisites)
  - [Installation](#-installation)
  - [Configuration](#-configuration)
  - [Usage](#-usage)

---

##  Overview

Este proyecto fue creado para facilitar la gestión y consulta de datos del Sistema de Identificación de Potenciales Beneficiarios de Programas Sociales (SISBEN). Proporciona herramientas administrativas para el control de beneficiarios y un sistema de consulta eficiente para acceder a la información del SISBEN IV.

---

##  Features

- **Módulo de Control SISBEN**: Administración completa de beneficiarios
- **Sistema de Consulta SISBEN IV**: Interface para consultas rápidas y eficientes
- **Gestión de datos con PHPSpreadsheet**: Manejo de archivos Excel para importación/exportación
- **Interface administrativa**: Panel de control para gestionar elecciones y beneficiarios

---

##  Project Structure

```sh
└── control_sisben_php/
    ├── Modulo_Control_Sisben/
    │   ├── composer.json
    │   ├── guardar_eleccion.php
    │   └── sisben_admin.php
    ├── README.md
    ├── Sistema_Consulta_Sisben_IV/
    │   └── index.php
    ├── db_connect.php
    └── teste.php
```

###  Project Index
<details open>
	<summary><b><code>CONTROL_SISBEN_PHP/</code></b></summary>
	<details> <!-- __root__ Submodule -->
		<summary><b>__root__</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/teste.php'>teste.php</a></b></td>
				<td>Archivo de pruebas para validar conexiones y funcionalidades</td>
			</tr>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/db_connect.php'>db_connect.php</a></b></td>
				<td>Configuración de conexión a la base de datos</td>
			</tr>
			</table>
		</blockquote>
	</details>
	<details> <!-- Modulo_Control_Sisben Submodule -->
		<summary><b>Modulo_Control_Sisben</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/Modulo_Control_Sisben/sisben_admin.php'>sisben_admin.php</a></b></td>
				<td>Panel administrativo principal del sistema SISBEN</td>
			</tr>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/Modulo_Control_Sisben/composer.json'>composer.json</a></b></td>
				<td>Configuración de dependencias de Composer</td>
			</tr>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/Modulo_Control_Sisben/guardar_eleccion.php'>guardar_eleccion.php</a></b></td>
				<td>Procesamiento y almacenamiento de elecciones de beneficiarios</td>
			</tr>
			</table>
		</blockquote>
	</details>
	<details> <!-- Sistema_Consulta_Sisben_IV Submodule -->
		<summary><b>Sistema_Consulta_Sisben_IV</b></summary>
		<blockquote>
			<table>
			<tr>
				<td><b><a href='https://github.com/Breyner794/control_sisben_php/blob/master/Sistema_Consulta_Sisben_IV/index.php'>index.php</a></b></td>
				<td>Interface principal del sistema de consulta SISBEN IV</td>
			</tr>
			</table>
		</blockquote>
	</details>
</details>

---

##  Getting Started

###  Prerequisites

Antes de comenzar con el proyecto, asegúrate de que tu entorno cumpla con los siguientes requisitos:

- **PHP**: Versión 8.1 o posterior
- **Servidor Web**: Apache, Nginx o XAMPP
- **Base de Datos**: MySQL o MariaDB
- **Composer**: Para gestión de dependencias

###  Installation

#### 1. Clonar el repositorio

```sh
git clone https://github.com/Breyner794/control_sisben_php
cd control_sisben_php
```

#### 2. Configurar variable de entorno PHP

**¿Por qué es importante Composer?**
Composer es fundamental para este proyecto ya que gestiona la librería PHPSpreadsheet, que permite el manejo de archivos Excel para la importación y exportación de datos del SISBEN.

**Configuración de la variable de entorno:**

Si no tienes configurada la variable de entorno para PHP, sigue estos pasos:

1. **Para XAMPP** (ejemplo con instalación en D:\xampp):
   - Agrega `D:\xampp\php` al PATH de tu sistema
   - En Windows: Panel de Control → Sistema → Configuración avanzada del sistema → Variables de entorno → PATH

2. **Verificar configuración**:
   ```sh
   php -v
   ```

#### 3. Instalar Composer

Navega a la carpeta donde se usará la librería:
```sh
cd D:\xampp\htdocs\tu_proyecto\Modulo_Control_Sisben
```

Descarga el instalador de Composer:
```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
```

Instala Composer:
```sh
php composer-setup.php
```

Elimina el archivo de instalación:
```sh
php -r "unlink('composer-setup.php');"
```

#### 4. Instalar PHPSpreadsheet

Instala la librería necesaria:
```sh
php composer.phar require phpoffice/phpspreadsheet
```

**Nota importante**: Este paso requiere PHP 8.1 o posterior. Si la instalación es exitosa, significa que tienes la versión correcta.

**Posibles errores y soluciones:**

Si encuentras alguno de estos errores durante la instalación:

- `...requires php ^8.1 which is not satisfied by your platform.` (Recordar que este paso es por que no tiene la version admitida el php.)
- `...requires ext-gd * but it is not present.`
- `...requires ext-zip* but it is not present.`

**Solución al problema de la extensión ext-gd**

Para solucionar el segundo error, debes habilitar la extensión GD en el archivo de configuración de PHP (php.ini):

- 1. Abre el Panel de control de XAMPP.

- 2. Haz clic en el botón Config al lado de Apache y selecciona php.ini.

- 3. Busca la siguiente línea en el archivo (puedes usar Ctrl + F para buscar):

```Ini, TOML

;extension=gd
```
- 4. Elimina el punto y coma (;) al principio de la línea para descomentarla y habilitar la extensión. La línea debe quedar así:

```Ini, TOML

extension=gd
```
5. Guarda el archivo y reinicia el servidor web Apache desde el panel de control de XAMPP.

**Solución: Habilitar la Extensión Zip en php.ini**

Para que Composer pueda instalar la librería, primero debes habilitar la extensión zip en tu configuración de PHP.

- 1. Abre el Panel de control de XAMPP.

- 2. Haz clic en el botón Config al lado de Apache y selecciona php.ini.

- 3. Una vez que el archivo php.ini esté abierto, busca la siguiente línea. Puedes usar Ctrl + F para encontrarla fácilmente:

```Ini, TOML

;extension=zip
```
- 4. Elimina el punto y coma (;) al principio de la línea para descomentarla y activarla. La línea debe verse así:

```Ini, TOML

extension=zip
```
- 5. Guarda y cierra el archivo php.ini.

- 6. Para que los cambios surtan efecto, debes reiniciar el servidor Apache desde el Panel de control de XAMPP.

**Volver a Intentar la Instalación con Composer**

```sh

php composer.phar require phpoffice/phpspreadsheet
```

###  Configuration

#### Configurar conexión a la base de datos

Edita el archivo `db_connect.php` con los datos de tu servidor:

```php
<?php
$servername = "localhost";
$username = "tu_usuario";
$password = "tu_contraseña";
$database = "nombre_base_datos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
```

###  Usage

Para ejecutar el proyecto:

1. **Inicia tu servidor web** (Apache en XAMPP)
2. **Accede al sistema de consulta**: `http://localhost/tu_proyecto/Sistema_Consulta_Sisben_IV/`
3. **Accede al módulo administrativo**: `http://localhost/tu_proyecto/Modulo_Control_Sisben/sisben_admin.php`

**Funcionalidades principales:**
- Consulta de beneficiarios SISBEN IV
- Administración de datos de beneficiarios
- Importación/exportación de archivos Excel
- Gestión de elecciones y selecciones

---