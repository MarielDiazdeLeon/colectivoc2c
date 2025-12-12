<?php
// ==========================================================
// PÁGINA DE REGISTRO DE USUARIOS (pages/registro.php) - CON VENTANAS EMERGENTES (window.open)
// ==========================================================

// 1. Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir configuración y DB
$base_url = '/colectivo_c2c/';
require_once __DIR__ . '/../config/db.php'; 

// 3. Redirigir si el usuario ya está logueado
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'index.php'); // Redirige al inicio
    exit();
}

$page_title = "Registro de Vendedor | Colectivo CDI";

// 4. Incluir el encabezado (esto comienza la salida HTML)
require_once __DIR__ . '/../includes/header.php';

// 5. Manejar mensajes de sesión y datos previos
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;

// Limpiar mensajes después de mostrarlos
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Variables para mantener los datos del formulario si la validación falla
$prev_data = $_SESSION['prev_register_data'] ?? [];
unset($_SESSION['prev_register_data']); 
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-xl w-full space-y-10 p-10 bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-bold text-gray-800">
                Crear una Cuenta de Vendedor
            </h2>
            <p class="mt-2 text-md text-gray-500">
                Regístrate para iniciar tu colectivo.
            </p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                <p class="font-bold">Error de Registro</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg" role="alert">
                <p class="font-bold">¡Éxito!</p>
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <form id="registroForm" class="mt-8 space-y-7" action="<?php echo $base_url; ?>acciones/auth_action.php" method="POST">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input id="nombre" name="nombre" type="text" autocomplete="given-name" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($prev_data['nombre'] ?? ''); ?>">
                </div>

                <div>
                    <label for="apellido" class="block text-sm font-medium text-gray-700">Apellido</label>
                    <input id="apellido" name="apellido" type="text" autocomplete="family-name" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($prev_data['apellido'] ?? ''); ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($prev_data['email'] ?? ''); ?>">
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono (10 dígitos)</label>
                    <input id="telefono" name="telefono" type="tel" autocomplete="tel" required maxlength="10"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($prev_data['telefono'] ?? ''); ?>">
                </div>

                <div>
                    <label for="nombre_marca" class="block text-sm font-medium text-gray-700">Nombre de tu Tienda/Marca</label>
                    <input id="nombre_marca" name="nombre_marca" type="text" autocomplete="organization" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                        value="<?php echo htmlspecialchars($prev_data['nombre_marca'] ?? ''); ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    <div id="password-feedback" class="mt-2 text-xs space-y-1 p-3 rounded-lg border border-pink-200">
                        </div>
                </div>

                <input type="hidden" name="rol" value="vendedor">
            </div>

            <div class="flex flex-col space-y-4 pt-2">

                <div class="flex flex-col space-y-3 pt-2">

                    <div class="flex items-start">
                        <input id="terms" name="terms" type="checkbox" required
                            class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded mt-1">
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            Acepto los 
                            <a href="<?php echo $base_url; ?>pages/terminos_condiciones.php"
                               id="open-terms"
                               class="font-medium text-pink-600 hover:text-pink-700 underline transition">
                                Términos y condiciones
                            </a>
                        </label>
                    </div>

                    <div class="flex items-start">
                        <input id="privacy" name="privacy" type="checkbox" required
                            class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded mt-1">
                        <label for="privacy" class="ml-2 block text-sm text-gray-700">
                            He leído el 
                            <a href="<?php echo $base_url; ?>pages/aviso_seguridad.php"
                               id="open-privacy"
                               class="font-medium text-pink-600 hover:text-pink-700 underline transition">
                                Aviso de seguridad y privacidad
                            </a>
                        </label>
                    </div>

                    <div class="flex items-start">
                        <input id="refunds" name="refunds" type="checkbox" required
                            class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded mt-1">
                        <label for="refunds" class="ml-2 block text-sm text-gray-700">
                            Acepto las 
                            <a href="<?php echo $base_url; ?>pages/politicas_devoluciones.php"
                               id="open-refunds"
                               class="font-medium text-pink-600 hover:text-pink-700 underline transition">
                                Políticas de devoluciones y cambios
                            </a>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent 
                        text-md font-semibold rounded-lg text-white bg-pink-600 hover:bg-pink-700 
                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 
                        transition duration-200 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    Registrarse
                </button>
            </div>

            <div class="text-center text-sm text-gray-600">
                ¿Ya tienes cuenta? 
                <a href="<?php echo $base_url; ?>pages/login.php" class="font-medium text-pink-600 hover:text-pink-500 transition">
                    Inicia sesión aquí
                </a>
            </div>
        </form>
    </div>
</main>

<script>
// ==========================================================
// JS para la Validación de Contraseña y Ventanas Emergentes
// ==========================================================
document.addEventListener('DOMContentLoaded', function() {
    // 🚨 1. DECLARACIÓN DE VARIABLES (Actualizadas con los nuevos IDs)
    const passwordInput = document.getElementById('password');
    const feedbackDiv = document.getElementById('password-feedback');
    const termsCheckbox = document.getElementById('terms');
    const privacyCheckbox = document.getElementById('privacy');
    const refundsCheckbox = document.getElementById('refunds'); // Nuevo checkbox
    const submitButton = document.querySelector('#registroForm button[type="submit"]');
    
    // Enlaces para Ventanas Emergentes
    const openTermsLink = document.getElementById('open-terms');
    const openPrivacyLink = document.getElementById('open-privacy');
    const openRefundsLink = document.getElementById('open-refunds'); // Nuevo enlace
    
    // Verificación de elementos críticos
    if (!passwordInput || !feedbackDiv || !termsCheckbox || !privacyCheckbox || !refundsCheckbox || !submitButton || !openTermsLink || !openPrivacyLink || !openRefundsLink) {
        if (submitButton) submitButton.disabled = true;
        console.error("Faltan elementos críticos del formulario o enlaces.");
        return;
    }

    // ------------------------------------------------------------------
    // Lógica para Abrir Ventana Emergente
    // ------------------------------------------------------------------
    function openDocumentPopup(url, name) {
        // Define las especificaciones de la ventana emergente (800x600px)
        const specs = 'width=800,height=600,scrollbars=yes,resizable=yes';
        window.open(url, name, specs);
    }
    
    // Asignación de eventos para las ventanas emergentes
    openTermsLink.addEventListener('click', function(e) {
        e.preventDefault(); 
        openDocumentPopup(this.href, 'TerminosCondiciones');
    });

    openPrivacyLink.addEventListener('click', function(e) {
        e.preventDefault(); 
        openDocumentPopup(this.href, 'AvisoSeguridad');
    });
    
    openRefundsLink.addEventListener('click', function(e) { // Asignación del nuevo enlace
        e.preventDefault(); 
        openDocumentPopup(this.href, 'PoliticasDevoluciones');
    });
    // ------------------------------------------------------------------


    // 🚨 2. FUNCIÓN DE VALIDACIÓN EN TIEMPO REAL
    function updateValidation() {
        const password = passwordInput.value;
        let feedback = [];
        let isValid = true;
        
        // --- Validación de Contraseña ---
        
        // R1: Mínimo 8 caracteres
        if (password.length >= 8) {
            feedback.push('<p class="text-green-600">✓ 8 caracteres mínimo</p>');
        } else {
            feedback.push('<p class="text-red-500">✗ 8 caracteres mínimo</p>');
            isValid = false;
        }

        // R2: Al menos una mayúscula
        if (/[A-Z]/.test(password)) {
            feedback.push('<p class="text-green-600">✓ Una letra mayúscula</p>');
        } else {
            feedback.push('<p class="text-red-500">✗ Una letra mayúscula</p>');
            isValid = false;
        }

        // R3: Al menos un número
        if (/\d/.test(password)) {
            feedback.push('<p class="text-green-600">✓ Un número</p>');
        } else {
            feedback.push('<p class="text-red-500">✗ Un número</p>');
            isValid = false;
        }

        // R4: Al menos un símbolo (no alfanumérico)
        if (/[^A-Za-z0-9]/.test(password)) {
            feedback.push('<p class="text-green-600">✓ Un carácter especial</p>');
        } else {
            feedback.push('<p class="text-red-500">✗ Un carácter especial</p>');
            isValid = false;
        }

        feedbackDiv.innerHTML = feedback.join('');

        // 3. Habilitar/Deshabilitar el botón de envío
        // Depende de la validación de contraseña Y la aceptación de las tres políticas.
        const termsChecked = termsCheckbox.checked;
        const privacyChecked = privacyCheckbox.checked;
        const refundsChecked = refundsCheckbox.checked;
        
        const formValid = isValid && termsChecked && privacyChecked && refundsChecked;

        submitButton.disabled = !formValid;
    }

    // 4. ASIGNACIÓN DE EVENTOS
    passwordInput.addEventListener('input', updateValidation);
    termsCheckbox.addEventListener('change', updateValidation);
    privacyCheckbox.addEventListener('change', updateValidation);
    refundsCheckbox.addEventListener('change', updateValidation); // Asignar evento al nuevo checkbox

    // 5. INICIALIZACIÓN: Disparar validación al cargar la página
    updateValidation(); 
});
</script>

<?php
// Incluir el pie de página.
require_once __DIR__ . '/../includes/footer.php';
?>