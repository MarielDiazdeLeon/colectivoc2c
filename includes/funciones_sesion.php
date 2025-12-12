<?php
// ==========================================================
// FUNCIONES DE SESIÓN Y SEGURIDAD (includes/funciones_sesion.php)
// Se utiliza if (!function_exists) en todas las funciones para prevenir 
// el error "Cannot redeclare function" si este archivo se incluye dos veces.
// ==========================================================

// ------------------------------------------
// 1. INICIAR SESIÓN DE PHP (Asegurar que la sesión inicie si no está activa)
// ------------------------------------------
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función que verifica si el usuario tiene una sesión activa.
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && $_SESSION['loggedin'] === true;
    }
}

/**
 * Función que asegura que el usuario esté logueado y tenga el rol requerido.
 * Si no lo está, lo redirige a la página de inicio de sesión.
 * @param string|null $role_required Rol mínimo requerido ('vendedor', 'admin').
 */
if (!function_exists('require_login')) {
    function require_login($role_required = null) {
        // Asegurarse de que la sesión esté iniciada (aunque ya se verifica arriba, es una buena práctica aquí también)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!is_logged_in()) {
            // Redirigir al login
            $_SESSION['error_message'] = "Debes iniciar sesión para acceder a esta página.";
            header("Location: /colectivo_c2c/pages/login.php");
            exit;
        }
        
        // Revisa si el rol es requerido Y existe en la sesión
        if ($role_required && isset($_SESSION['user_role'])) { 
            // Si el rol requerido es 'vendedor', también permitimos 'admin'.
            $is_authorized = ($_SESSION['user_role'] === 'admin') || ($_SESSION['user_role'] === $role_required);
            
            if (!$is_authorized) {
                // Redirigir al dashboard si no tiene permisos
                $_SESSION['error_message'] = "Acceso denegado. No tienes permisos para acceder a esta sección.";
                header("Location: /colectivo_c2c/pages/dashboard.php?error=no_permiso");
                exit;
            }
        }
        
        // Opcional: Actualizar actividad para prevenir timeouts
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Inicia las variables de sesión para el usuario.
 * @param int $id ID del usuario.
 * @param string $rol Rol del usuario.
 * @param string $nombre Nombre del usuario.
 */
if (!function_exists('start_session_for_user')) {
    function start_session_for_user($id, $rol, $nombre) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpiar y regenerar la sesión por seguridad
        session_unset();
        session_regenerate_id(true);

        $_SESSION['user_id']  = $id;
        $_SESSION['user_role'] = $rol;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['loggedin'] = true;
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Cierra la sesión del usuario.
 */
if (!function_exists('logout_user')) {
    function logout_user() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpia todas las variables de sesión
        $_SESSION = array(); 
        
        // Si se usan cookies de sesión, eliminarlas
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruye la sesión
        session_destroy();
    }
}
?>