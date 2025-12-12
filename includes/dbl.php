<?php
// ------------------------------------------
// 1. INICIAR SESIÓN DE PHP
// ------------------------------------------
// Siempre debe estar al inicio de cualquier script que maneje sesiones.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------
// 2. CONFIGURACIÓN DE LA BASE DE DATOS
// ------------------------------------------
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Tu usuario de MySQL
define('DB_PASSWORD', '');     // Tu contraseña de MySQL (vacía si usas XAMPP por defecto)
define('DB_NAME', 'colectivo_c2c'); // El nombre de tu base de datos

// ------------------------------------------
// 3. FUNCIÓN DE CONEXIÓN
// ------------------------------------------
function connect_db() {
    // Intenta conectar a la base de datos
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Verifica la conexión
    if ($conn->connect_error) {
        // En un entorno de producción, esto debería ser un error más amigable.
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }
    
    // Establecer el charset a UTF-8 para evitar problemas de acentos
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// ------------------------------------------
// 4. FUNCIONES DE SESIÓN (Para Vendedores/Admin)
// ------------------------------------------

/**
 * Verifica si el usuario actual ha iniciado sesión.
 * @return bool True si hay sesión activa, False en caso contrario.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige al usuario al login si no está autenticado.
 * @param string $role_required El rol mínimo requerido (e.g., 'admin', 'vendedor').
 */
function require_login($role_required = null) {
    if (!is_logged_in()) {
        header("Location: /colectivo_c2c/pages/login.php");
        exit;
    }

    // Si se requiere un rol específico, lo verificamos
    if ($role_required && isset($_SESSION['user_role'])) {
        // Aquí puedes implementar lógica de permisos más avanzada
        // Para simplificar, solo revisamos que el rol coincida
        if ($_SESSION['user_role'] != $role_required) {
            // Si el rol no coincide, lo enviamos al dashboard principal con un mensaje
            header("Location: /colectivo_c2c/pages/dashboard.php?error=no_permiso");
            exit;
        }
    }
}

/**
 * Registra los datos de la sesión al hacer login.
 * @param int $id ID del usuario.
 * @param string $rol Rol del usuario (vendedor, admin).
 * @param string $nombre Nombre del usuario.
 */
function start_session_for_user($id, $rol, $nombre) {
    $_SESSION['user_id'] = $id;
    $_SESSION['user_role'] = $rol;
    $_SESSION['user_name'] = $nombre;
}

/**
 * Cierra la sesión activa.
 */
function logout_user() {
    // Destruye todas las variables de sesión
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, borre también la cookie de sesión.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruye la sesión
    session_destroy();
}