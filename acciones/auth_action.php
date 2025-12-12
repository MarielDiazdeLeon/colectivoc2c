<?php
// ==========================================================
// SCRIPT DE PROCESAMIENTO DE REGISTRO/LOGIN (acciones/auth_action.php)
// CORRECCIÓN: Implementación de la creación de Colectivo/Tienda para Vendedores al registrarse.
// ==========================================================

// 1. Iniciar sesión y configuración
if (session_status() == PHP_SESSION_NONE) {
session_start();
}
$base_url = '/colectivo_c2c/';

// Asegura la ruta correcta al archivo de configuración de la DB
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

// 2. Verificar que se recibió una solicitud POST
if ($_SERVER["REQUEST_METHOD"] !== "POST" && (!isset($_GET['action']) || $_GET['action'] !== 'logout')) {
// Si no es POST y no es una solicitud de logout, redirigir
header("Location: " . $base_url . "index.php");
exit;
}

// Determinar si es REGISTRO, LOGIN o LOGOUT
$action = isset($_GET['action']) ? $_GET['action'] : 'register'; 

// ==================================================
// LÓGICA DE REGISTRO (Mantiene Autologin)
// ==================================================
if ($action === 'register') {

// 3. RECUPERAR DATOS DE REGISTRO
    // 3. RECUPERAR DATOS DE REGISTRO
$nombre       = trim($_POST['nombre'] ?? '');
$apellido     = trim($_POST['apellido'] ?? '');
$email        = trim($_POST['email'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
// 🚨 CORRECCIÓN: Agregar $ a la variable password
$password     = $_POST['password'] ?? ''; 
$rol          = $_POST['rol'] ?? 'vendedor'; // Asumimos 'vendedor' por defecto
$terms        = $_POST['terms'] ?? '';
// NUEVO: Recuperar el nombre de la marca
$nombre_marca = trim($_POST['nombre_marca'] ?? ''); 
// Los términos de privacidad se asumen aceptados si 'terms' llega
// $privacy    = $_POST['privacy'] ?? '';

// Guardar datos previos en sesión por si hay un error
$_SESSION['prev_register_data'] = [
'nombre'=> $nombre,
'apellido' => $apellido,
'email'=> $email,
'telefono' => $telefono,
        'nombre_marca' => $nombre_marca // Guardar marca
];

// 4. VALIDACIÓN DE DATOS
$error = [];
    
    // Validar campos obligatorios básicos
if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono) || empty($password) || empty($terms)) {
$error[] = "Todos los campos obligatorios deben ser completados y debes aceptar los términos.";
}
    
    // 🚨 NUEVO: Validar nombre de la marca si es vendedor
    if ($rol === 'vendedor' && empty($nombre_marca)) {
        $error[] = "Debes ingresar el Nombre de tu Tienda/Marca para registrarte como Vendedor.";
    }
    
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$error[] = "El formato del correo electrónico no es válido.";
}
if (!preg_match('/^\d{10}$/', $telefono)) {
$error[] = "El número de teléfono debe contener exactamente 10 dígitos numéricos.";
}
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
$error[] = "La contraseña no cumple con los requisitos de seguridad (mínimo 8 caracteres, mayúscula, número y símbolo).";
}

// 5. Verificar si hay errores y redirigir
if (!empty($error)) {
$_SESSION['error_message'] = implode("<br>", $error);
header("Location: " . $base_url . "pages/registro.php"); 
exit;
}

// 6. VERIFICAR CORREO EXISTENTE
global $conn; 
    // Verificación de conexión
    if (!$conn) {
        $_SESSION['error_message'] = "Error interno del servidor: No se pudo conectar a la base de datos.";
        header("Location: " . $base_url . "pages/registro.php");
        exit;
    }
    
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
$_SESSION['error_message'] = "El correo electrónico ya está registrado. Intenta iniciar sesión.";
$stmt->close();
header("Location: " . $base_url . "pages/registro.php");
exit;
}
$stmt->close();

// 7. REGISTRO FINAL EN LA BASE DE DATOS
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$fecha_registro = date('Y-m-d H:i:s'); 

$sql = "INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol, fecha_registro, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
$stmt = $conn->prepare($sql);
    
    // Verificación de error de preparación de usuario
    if (!$stmt) {
        $_SESSION['error_message'] = "Error interno (SQL-User): " . $conn->error;
        header("Location: " . $base_url . "pages/registro.php");
        exit;
    }
    
$stmt->bind_param("sssssss", $nombre, $apellido, $email, $telefono, $hashed_password, $rol, $fecha_registro);

if ($stmt->execute()) {

// Obtener ID del usuario recién insertado
$new_user_id = $conn->insert_id;
$full_name = $nombre . ' ' . $apellido;

        // 🚨 CRÍTICO: Insertar el Colectivo/Tienda si el rol es vendedor
        // Líneas 131-139 aproximadamente en acciones/auth_action.php

// 🚨 CRÍTICO: Insertar el Colectivo/Tienda si el rol es vendedor
if ($rol === 'vendedor' && !empty($nombre_marca)) {
    
    // 1. Consulta SQL: 2 Columnas y 2 Placeholders
    $sql_colectivo = "INSERT INTO colectivos (id_usuario, nombre_marca) VALUES (?, ?)";
    $stmt_c = $conn->prepare($sql_colectivo);
    
    if ($stmt_c) {
        // 2. CORRECCIÓN: Usar "is" (Integer, String) para coincidir con 2 variables.
        // Línea 138 (Corregida):
        $stmt_c->bind_param("is", $new_user_id, $nombre_marca);
        
        if (!$stmt_c->execute()) {
            error_log("Error al insertar el colectivo para el usuario ID {$new_user_id}: " . $stmt_c->error);
        }
        $stmt_c->close();
    } else {
         error_log("Error al preparar la inserción del colectivo: " . $conn->error);
    }
}
        
start_session_for_user($new_user_id, $rol, $full_name);

$role_display = ($rol == 'admin') ? 'Administrador' : 'Vendedor';
$_SESSION['success_message'] = "¡Registro exitoso! Bienvenido a tu Dashboard como $role_display.";

header("Location: " . $base_url . "pages/dashboard.php");
exit;


} else {
// Error al insertar el usuario
$_SESSION['error_message'] = "Error interno al registrar el usuario: " . $stmt->error;
header("Location: " . $base_url . "pages/registro.php");
exit;
}
$stmt->close();
}

// ==================================================
// LÓGICA DE LOGIN 
// ==================================================
elseif ($action === 'login') {

// 3. RECUPERAR DATOS DE LOGIN
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember-me']);

// 4. VALIDACIÓN BÁSICA
if (empty($email) || empty($password)) {
$_SESSION['error_message'] = "Debes ingresar tu correo y contraseña.";
header("Location: " . $base_url . "pages/login.php");
exit;
}

// 5. OBTENER USUARIO DE LA DB
global $conn;
    if (!$conn) {
        $_SESSION['error_message'] = "Error interno del servidor: No se pudo conectar a la base de datos.";
        header("Location: " . $base_url . "pages/login.php");
        exit;
    }
    
$stmt = $conn->prepare("SELECT id, nombre, apellido, password, rol, activo FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// 6. VERIFICAR CONTRASEÑA y ESTADO
if ($user && password_verify($password, $user['password'])) {

if ($user['activo'] != 1) {
$_SESSION['error_message'] = "Tu cuenta está inactiva o ha sido suspendida. Contacta a soporte.";
header("Location: " . $base_url . "pages/login.php");
exit;
}

// 7. Éxito: INICIAR SESIÓN (Usa la función que ahora está disponible)
start_session_for_user($user['id'], $user['rol'], $user['nombre'] . ' ' . $user['apellido']);

// 8. MANEJO DE "RECORDARME" (COOKIES)
if ($remember_me) {
$secure_hash_part = hash('sha256', $user['password']);
$cookie_value = $user['id'] . '|' . $secure_hash_part;
$expire = time() + (86400 * 30); 
setcookie('remember_me_colectivo', $cookie_value, $expire, $base_url);
}

// 9. REDIRECCIÓN FINAL: Al dashboard único
header("Location: " . $base_url . "pages/dashboard.php");
exit;

} else {
// Fallo en la autenticación
$_SESSION['error_message'] = "Correo electrónico o contraseña incorrectos.";
header("Location: " . $base_url . "pages/login.php");
exit;
}
}

// ==================================================
// LÓGICA DE LOGOUT 
// ==================================================
elseif ($action === 'logout') {

// 10a. Limpiar la variable de sesión
unset($_SESSION); 

// 10b. Destruir la sesión en el servidor
session_destroy();

// 10c. Limpiar cookies (opcional, si usa "Recordarme")
if (isset($_COOKIE['remember_me_colectivo'])) {
setcookie('remember_me_colectivo', '', time() - 3600, $base_url);
}

// 10d. Redirigir a la página de login
header("Location: " . $base_url . "pages/login.php"); 
exit;
}

// 11. Redirección por defecto si no se reconoce la acción
header("Location: " . $base_url . "index.php");
exit();
?>