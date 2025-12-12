<?php
// ==========================================================
// CONFIGURACIÓN DE LA BASE DE DATOS (config/db.php)
// Contiene la función para establecer la conexión, Y LA ESTABLECE.
// ==========================================================

// 1. DEFINE LAS CONSTANTES DE CONEXIÓN
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ecomerce_colectivo');

// 2. FUNCIÓN DE CONEXIÓN
if (!function_exists('connect_db')) {
    /**
     * Intenta establecer una conexión con la base de datos MySQL.
     * @return mysqli|null Retorna el objeto de conexión mysqli si es exitosa, o null si falla.
     */
    function connect_db() {
        // Establecer la zona horaria 
        date_default_timezone_set('America/Mexico_City');
        
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        // Verificar la conexión
        if ($conn->connect_error) {
            error_log("Fallo de conexión a MySQL: " . $conn->connect_error);
            return null;
        }

        // Establecer el conjunto de caracteres
        if (!$conn->set_charset("utf8")) {
            error_log("Error al cargar el conjunto de caracteres utf8: " . $conn->error);
        }
        
        return $conn;
    }
}

// 3. ¡CORRECCIÓN CLAVE! ESTABLECER LA CONEXIÓN GLOBALMENTE
//    Llamar a la función y almacenar el objeto de conexión en una variable global.
$conn = connect_db(); 

// Opcional: Manejar un fallo total aquí si $conn es null (solo en desarrollo)
if ($conn === null) {
    // Para producción, se recomienda una redirección más sutil.
    // Para desarrollo, un die() ayuda a depurar.
    // die("No se pudo conectar a la base de datos."); 
}

?>