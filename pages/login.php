<?php
// ==========================================================
// PÁGINA DE INICIO DE SESIÓN (pages/login.php)
// ==========================================================

// 1. Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Definir base URL (IMPORTANTE: Debe coincidir con su configuración de XAMPP)
$base_url = '/colectivo_c2c/';

// 3. Incluir configuración y DB
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; // Incluye la función de auto-login si está ahí

// 4. Lógica de Auto-Login por Cookie (Si no está en db.php o functions.php)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me_colectivo'])) {
    // Aquí iría la lógica de verificación de cookie si no está centralizada.
}

// 5. Redirigir si el usuario ya está logueado
if (isset($_SESSION['user_id'])) {
    $rol = $_SESSION['user_rol'];
    if ($rol === 'admin') {
        header("Location: " . $base_url . "pages/admin/dashboard.php");
    } elseif ($rol === 'vendedor') {
        header("Location: " . $base_url . "pages/vendedor/dashboard.php");
    } else {
        header('Location: ' . $base_url . 'index.php');
    }
    exit();
}

$page_title = "Iniciar Sesión | Colectivo CDI";

// 6. Incluir el encabezado
require_once __DIR__ . '/../includes/header.php';

// 7. Manejar mensajes de sesión
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;

// Limpiar mensajes después de mostrarlos
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-c2c-bg-soft">
    <div class="max-w-md w-full space-y-8 p-10 bg-white rounded-xl shadow-2xl">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Iniciar Sesión
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Accede a tu cuenta de vendedor.
            </p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Error de Acceso</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p class="font-bold">¡Cuenta Creada!</p>
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de Login: RUTA CLAVE CORREGIDA -->
        <form class="mt-8 space-y-6" action="<?php echo $base_url; ?>acciones/auth_action.php?action=login" method="POST">
            <input type="hidden" name="remember" value="true">
            
            <div class="rounded-md shadow-sm -space-y-px">
                <!-- Email -->
                <div>
                    <label for="email-address" class="sr-only">Correo Electrónico</label>
                    <input id="email-address" name="email" type="email" autocomplete="email" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 focus:z-10 sm:text-sm"
                           placeholder="Correo Electrónico">
                </div>
                <!-- Contraseña -->
                <div>
                    <label for="password" class="sr-only">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-pink-500 focus:border-pink-500 focus:z-10 sm:text-sm"
                           placeholder="Contraseña">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox"
                           class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Recordarme
                    </label>
                </div>

            </div>

            <!-- Botón de Login -->
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                               text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition shadow-lg">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <!-- Icono de Candado SVG -->
                        <svg class="h-5 w-5 text-pink-500 group-hover:text-pink-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2a4 4 0 008 0V5a4 4 0 00-4-4zM8 7v2h4V7a2 2 0 00-4 0zM3 10a7 7 0 0114 0h-2a5 5 0 00-10 0H3z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Iniciar Sesión
                </button>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                ¿Aún no tienes cuenta? 
                <a href="<?php echo $base_url; ?>pages/registro.php" class="font-medium text-pink-600 hover:text-pink-500">
                    Regístrate aquí
                </a>
            </div>
        </form>
    </div>
</main>

<?php
// Incluir el pie de página
require_once __DIR__ . '/../includes/footer.php';
?>