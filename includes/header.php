<?php
// Este archivo inicia la sesión, la conexión a la base de datos y la estructura HTML superior.

// 1. Iniciar sesión (necesario para el carrito y autenticación)
if (session_status() == PHP_SESSION_NONE) {
session_start();
}

// 2. Lógica de Autenticación (¡CRUCIAL!)
$placeholder_id = 21; // ID del usuario temporal/invitado usado en checkout

// CRÍTICO: Solo está logueado si user_id existe Y NO es el ID del placeholder.
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_id'] != $placeholder_id;

// Definición de Roles
$user_session_role = $_SESSION['user_role'] ?? 'cliente';
$is_admin = ($user_session_role === 'admin'); // Admin sí ve el Dashboard
$is_vendedor_only = ($user_session_role === 'vendedor'); // Vendedor estándar

$user_name = $is_logged_in ? ($_SESSION['user_nombre'] ?? 'Usuario') : null;


// 3. Inicializar contador del carrito
$cart_count = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
// Usando la versión de contar productos únicos
$cart_count = count($_SESSION['carrito']);}

// 4. Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// 5. Lógica de rutas absolutas
$base_url = '/colectivo_c2c/';

// 6. Título por defecto
if (!isset($page_title)) {
$page_title = "Colectivo CDI - Tienda";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
// Configuración de Tailwind para usar la paleta de colores personalizada
tailwind.config = {
theme: {
extend: {
colors: {
'c2c-primary': '#f7c9df',   // Rosa pastel
'c2c-primary-dark': '#f3b2d2',  // Rosa más oscuro
'c2c-accent': '#b94e82',// Rosa vino suave (texto principal)
'c2c-bg-soft': '#fff7fb',   // Rosa ultra claro
},
}
}
}
</script>

<style>
/* Paleta pastel */
.c2c-primary { background-color: #f7c9df; } 
.c2c-primary-dark { background-color: #f3b2d2; }
.c2c-accent { background-color: #ffe9b8; } 
.c2c-accent-dark { background-color: #ffd88a; }
.c2c-border { border-color: #f4d9e8; }
.c2c-text-title { color: #b94e82; } 
.c2c-text-dark { color: #5a4a52; } 
.c2c-bg-soft { background-color: #fff7fb; } 

/* Sombras cute */
.c2c-shadow {
box-shadow: 0 4px 10px rgba(241, 182, 224, 0.4);
}

/* Hover suave */
.c2c-hover:hover {
background-color: #f3b2d2;
transition: 0.2s ease-in-out;
}

/* Estilos de compatibilidad */
.colectivo-primary { background-color: #f9a8d4; }
.colectivo-accent { color: #be185d; } 
</style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">

<header class="backdrop-blur-xl bg-pink-100/80 shadow-lg sticky top-0 z-50 border-b border-pink-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- LOGO -->
            <div class="flex items-center">
                <a href="<?php echo $base_url; ?>index.php"
                    class="text-2xl font-extrabold text-pink-700 flex items-center gap-2 hover:scale-105 transition">
                    
                    <span class="bg-pink-400 text-white px-2 py-1 rounded-lg shadow-lg">
                        CDI
                    </span>

                    <span>Colectivo</span>
                    <span class="text-pink-600">e-Shop</span>
                </a>

                <!-- NAV -->
                <nav class="hidden md:flex space-x-8 ml-10">
                    <a href="<?php echo $base_url; ?>index.php"
                        class="text-pink-700 hover:text-pink-900 font-medium transition">
                        Catálogo
                    </a>

                    <a href="<?php echo $base_url; ?>pages/vendedores.php"
                        class="text-pink-700 hover:text-pink-900 font-medium transition">
                        Vendedores
                    </a>

                    <?php if (!$is_logged_in): ?>
                        <a href="<?php echo $base_url; ?>pages/registro.php"
                            class="text-pink-700 hover:text-pink-900 font-bold transition">
                            Vende con Nosotros
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- ICONOS DERECHA -->
            <div class="flex items-center space-x-4">

                <!-- CARRITO -->
                <a href="<?php echo $base_url; ?>pages/carrito.php"
                    class="relative text-pink-700 hover:text-pink-900 transition p-2">
                    <i class="fas fa-shopping-cart text-xl"></i>

                    <?php if ($cart_count > 0): ?>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center
                        px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full shadow-lg">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- USUARIO LOGUEADO -->
                <?php if ($is_logged_in): ?>
                    <div class="relative group h-full flex items-center">

                        <button
                            class="flex items-center gap-2 text-pink-700 hover:text-pink-900 transition px-3 py-2 rounded-full bg-white/50 backdrop-blur-lg border border-pink-200/50 shadow">
                            <i class="fas fa-user-circle text-2xl"></i>
                            <span class="hidden sm:inline font-semibold text-sm">
                                <?php echo htmlspecialchars($user_name); ?>
                            </span>
                        </button>

                        <!-- MENÚ DESPLEGABLE -->
                        <div class="absolute right-0 top-full w-56 bg-white/80 backdrop-blur-xl 
                            border border-pink-200/40 rounded-2xl shadow-2xl opacity-0 invisible 
                            group-hover:opacity-100 group-hover:visible transition-all duration-300 
                            transform scale-95 group-hover:scale-100 z-50">

                            <div class="py-2">

                                <!-- ADMIN -->
                                <?php if ($is_admin): ?>
                                    <a href="<?php echo $base_url; ?>pages/dashboard.php"
                                        class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-pink-700 
                                        hover:bg-pink-100/60 hover:text-pink-900 transition rounded-xl">
                                        <i class="fas fa-chart-bar"></i> Dashboard
                                    </a>

                                    <a href="<?php echo $base_url; ?>pages/admin/gestion_usuarios.php"
                                        class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-red-700 
                                        hover:bg-red-100 hover:text-red-800 transition rounded-xl">
                                        <i class="fas fa-users-cog"></i> Panel Admin
                                    </a>
                                <?php endif; ?>

                                <!-- VENDEDOR -->
                                <?php if ($is_vendedor_only): ?>
                                    <a href="<?php echo $base_url; ?>pages/dashboard.php"
                                        class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-pink-700 
                                        hover:bg-pink-100/60 hover:text-pink-900 transition rounded-xl">
                                        <i class="fas fa-store"></i> Panel de Vendedor
                                    </a>
                                <?php endif; ?>

                                <!-- CERRAR SESIÓN -->
                                <a href="<?php echo $base_url; ?>acciones/auth_action.php?action=logout"
                                    class="flex items-center gap-3 px-4 py-2 mt-1 text-sm font-semibold 
                                    text-red-600 bg-red-50/60 hover:bg-red-100 hover:text-red-700 
                                    transition rounded-xl">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>

                            </div>
                        </div>
                    </div>

                <!-- SI NO ESTA LOGUEADO -->
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>pages/login.php"
                        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-semibold 
                        hover:bg-pink-600 transition shadow-lg hover:shadow-xl">
                        Iniciar Sesión
                    </a>
                <?php endif; ?>

            </div>

        </div>
    </div>
</header>