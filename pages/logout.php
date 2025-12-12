<?php
// ==========================================================
// SCRIPT DE CIERRE DE SESIÓN (pages/logout.php)
// Cierra sesión de PHP y elimina la cookie de "Recordarme"
// ==========================================================

// 1. Iniciar la sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Lógica de rutas absolutas
$base_url = '/colectivo_c2c/';

// 2. Limpiar la cookie "Recordarme" si existe
// Esto es esencial para desloguear completamente al usuario.
if (isset($_COOKIE['remember_me_colectivo'])) {
    // Establecer la cookie en el pasado para que el navegador la elimine
    // El path debe ser el mismo que se usó al crearla, que es $base_url
    setcookie('remember_me_colectivo', '', time() - 3600, $base_url);
}
    
// 3. Destruir la sesión actual
$_SESSION = array();

// Destruir la cookie de ID de sesión de PHP
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// 4. Redirigir al usuario a la página principal
// Usamos index.php, que es la página de catálogo
header('Location: ' . $base_url . 'index.php');
exit();
?>