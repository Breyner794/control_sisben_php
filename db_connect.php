<?php
// db_connect.php
// -----------------------------------------------------------------------------
// Conexión a la base de datos - Compatible con código existente + nuevas funcionalidades
// -----------------------------------------------------------------------------

// --- Configuración de la base de datos ---
$servidor = 'localhost';
$usuario_db = 'root';
$clave = '';
$base_de_datos = 'sisben_db';

// --- CONEXIÓN MYSQLI (Para tu código existente) ---
$conexion = mysqli_connect($servidor, $usuario_db, $clave, $base_de_datos);

// --- Verificar si la conexión fue exitosa ---
if (!$conexion) {
    die('Error al conectar con la base de datos: ' . mysqli_connect_error());
}

// --- Configuración adicional ---
mysqli_set_charset($conexion, 'utf8');

// --- CLASE PDO (Para login y nuevas funcionalidades) ---
class DatabasePDO {
    private $host = 'localhost';
    private $db_name = 'sisben_db';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function conectar() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password,
                    array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    )
                );
            } catch(PDOException $e) {
                die("Error de conexión PDO: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}

// --- FUNCIÓN HELPER PARA PDO ---
function getPDO() {
    static $pdo_instance = null;
    if ($pdo_instance === null) {
        $database = new DatabasePDO();
        $pdo_instance = $database->conectar();
    }
    return $pdo_instance;
}

// --- FUNCIONES HELPER PARA CONSULTAS SEGURAS ---
function ejecutarConsulta($sql, $parametros = []) {
    $pdo = getPDO();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}

function obtenerRegistro($sql, $parametros = []) {
    $stmt = ejecutarConsulta($sql, $parametros);
    return $stmt->fetch();
}

function obtenerRegistros($sql, $parametros = []) {
    $stmt = ejecutarConsulta($sql, $parametros);
    return $stmt->fetchAll();
}

function ejecutarComando($sql, $parametros = []) {
    $stmt = ejecutarConsulta($sql, $parametros);
    return $stmt->rowCount();
}

// NOTA: 
// - $conexion (MySQLi) sigue disponible para tu código SISBEN existente
// - getPDO() y funciones helper están disponibles para login y código nuevo
?>