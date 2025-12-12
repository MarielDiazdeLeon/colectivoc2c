<?php
// ==========================================================
// PÁGINA DE GESTIÓN DE PRODUCTOS (pages/productos_vendedor.php)
// Muestra todos los productos y permite Activar/Desactivar (Toggle)
// ==========================================================

// 1. CONFIGURACIÓN INICIAL Y SEGURIDAD
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$base_url = '/colectivo_c2c/'; 

require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

// Forzar inicio de sesión y rol de vendedor/admin
if (!function_exists('require_login')) {
    if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'vendedor' && $_SESSION['user_role'] !== 'admin')) {
        header('Location: ' . $base_url . 'pages/login.php');
        exit();
    }
} else {
    // Si la función existe (entorno de producción), usarla
    require_login('vendedor'); 
}

$conn = connect_db();
$user_id = $_SESSION['user_id'] ?? 0;

// Variables de sesión para la interfaz
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_role = $_SESSION['user_role'] ?? 'invitado'; 
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600'; 

// 2. Obtener ID del colectivo (Necesario para filtrar productos)
$colectivo_id = null;
if ($conn && $user_id > 0) {
    $sql_c = "SELECT id FROM colectivos WHERE id_usuario = ?";
    $stmt_c = $conn->prepare($sql_c);
    if ($stmt_c !== false) {
        $stmt_c->bind_param("i", $user_id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result()->fetch_assoc();
        if ($res_c) $colectivo_id = $res_c['id'];
        $stmt_c->close();
    }
}

// ==========================================================
// 3. LÓGICA DE ACCIÓN: CAMBIAR ESTADO ACTIVO/INACTIVO (TOGGLE)
// ==========================================================
if (isset($_GET['action']) && $_GET['action'] == 'toggle_active' && isset($_GET['id']) && $colectivo_id) {
    $prod_id = (int)$_GET['id'];

    // 3.1. Obtener estado actual
    $sql_current = "SELECT activo FROM productos WHERE id = ? AND id_colectivo = ?";
    $stmt_current = $conn->prepare($sql_current);
    $stmt_current->bind_param("ii", $prod_id, $colectivo_id);
    $stmt_current->execute();
    $current_state = $stmt_current->get_result()->fetch_assoc()['activo'] ?? null;
    $stmt_current->close();

    if ($current_state !== null) {
        // 3.2. Invertir y actualizar estado
        $nuevo_estado = ($current_state == 1) ? 0 : 1; 
        
        $sql_update = "UPDATE productos SET activo = ? WHERE id = ? AND id_colectivo = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iii", $nuevo_estado, $prod_id, $colectivo_id);

        if ($stmt_update->execute()) {
            $estado_texto = ($nuevo_estado == 1) ? 'activado' : 'desactivado';
            $_SESSION['success_message'] = "Producto " . $estado_texto . " correctamente.";
        } else {
            $_SESSION['error_message'] = "Error al cambiar el estado: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Error: Producto no encontrado o no te pertenece.";
    }

    header("Location: productos_vendedor.php");
    exit;
}

// 4. Manejo de Mensajes
$mensaje = $_SESSION['success_message'] ?? ($_SESSION['error_message'] ?? '');
$tipo_mensaje = isset($_SESSION['success_message']) ? "bg-green-100 text-green-700 border-green-400" : (isset($_SESSION['error_message']) ? "bg-red-100 text-red-700 border-red-400" : '');

unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// ==========================================================
// 5. LISTAR TODOS LOS PRODUCTOS (Activos e Inactivos)
// ==========================================================
$productos = [];
if ($conn && $colectivo_id) {
    $sql_p = "SELECT id, nombre, precio, stock, imagen_url, vendidos, activo 
              FROM productos 
              WHERE id_colectivo = ? 
              ORDER BY id DESC"; 
    $stmt_p = $conn->prepare($sql_p);

    if ($stmt_p !== false) {
        $stmt_p->bind_param("i", $colectivo_id);
        $stmt_p->execute();
        $result_p = $stmt_p->get_result();
        while ($row = $result_p->fetch_assoc()) {
            $productos[] = $row;
        }
        $stmt_p->close();
    }
}

// 6. Encabezado HTML
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ========================= -->
<!--   ESTILOS DE IMPRESIÓN    -->
<!-- ========================= -->

<style>
@media print {
    aside, nav, .no-print, header, footer, .print-hidden, .bg-pink-600, .bg-pink-300 {
        display: none !important;
    }
    body {
        background: white;
    }
    table {
        font-size: 12px;
    }
    img {
        max-width: 50px;
        max-height: 50px;
    }
}
</style>

<div class="flex min-h-screen bg-gray-50">

<aside class="w-64 bg-pink-100 text-gray-800 shadow-xl no-print">
    <div class="p-6 text-2xl font-bold border-b border-pink-300 text-pink-700">
        Colectivo CDI | <?php echo strtoupper($user_role); ?>
    </div>

    <div class="p-6 flex-1">
        <div class="text-lg font-semibold mb-4 pb-2 border-b border-pink-300">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?>
        </div>

        <nav class="space-y-2">
            <a href="<?php echo $base_url; ?>pages/dashboard.php"
            class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
            <i class="fas fa-home mr-3"></i> Inicio
            </a>

            <a href="<?php echo $base_url; ?>pages/productos_vendedor.php"
            class="flex items-center p-3 rounded-lg bg-pink-300 font-bold text-white transition duration-150">
            <i class="fas fa-shopping-bag mr-3"></i> Mis Productos
            </a>

            <a href="<?php echo $base_url; ?>pages/pedidos_vendedor.php"
            class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
            <i class="fas fa-receipt mr-3"></i> Pedidos
            </a>

            <a href="<?php echo $base_url; ?>pages/cupones_vendedor.php"
            class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
            <i class="fas fa-percent mr-3"></i> Cupones
            </a>

            <?php if ($user_role == 'admin'): ?>
            <a href="<?php echo $base_url; ?>pages/admin/gestion_usuarios.php"
            class="flex items-center p-3 rounded-lg bg-red-400 text-white hover:bg-red-500 transition duration-150">
            <i class="fas fa-users-cog mr-3"></i> Gestión de Usuarios
            </a>
            <?php endif; ?>

            <a href="<?php echo $base_url; ?>acciones/auth_action.php?action=logout"
            class="flex items-center p-3 rounded-lg bg-pink-600 text-white hover:bg-pink-700 mt-4 transition duration-150">
            <i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
            </a>
        </nav>
    </div>
</aside>

<main class="flex-1 px-8 py-10 overflow-y-auto bg-gray-50">

    <div class="flex justify-between items-center mb-8 border-b pb-4 print-hidden">
        <h1 class="text-3xl font-extrabold text-gray-800">
            Mis Productos
        </h1>

        <a href="<?php echo $base_url; ?>pages/formulario_producto.php"
        class="bg-pink-600 text-white px-5 py-2 rounded-lg hover:bg-pink-700 transition shadow-md flex items-center no-print">
        <i class="fas fa-plus mr-2"></i> Nuevo Producto
        </a>
    </div>

    <!-- BOTÓN PARA IMPRIMIR -->
    <div class="flex justify-end mb-4 no-print">
        <button onclick="window.print();" 
            class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-black transition shadow">
            🖨 Imprimir Productos
        </button>
    </div>

    <?php if ($mensaje): ?>
    <div class="border-l-4 p-4 mb-6 rounded border <?php echo $tipo_mensaje; ?> no-print">
        <p><?php echo $mensaje; ?></p>
    </div>
    <?php endif; ?>

    <?php if ($colectivo_id): ?>
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-pink-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/3">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/12">Precio</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/12">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/12">Vendidos</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/6">Estado</th> 
                    <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider w-1/5 no-print">Acciones</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $prod): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="<?php echo htmlspecialchars($prod['imagen_url']); ?>" 
                            onerror="this.src='<?php echo $base_url; ?>assets/img/placeholder.jpg'"
                            class="w-12 h-12 object-cover rounded mr-3">
                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($prod['nombre']); ?></span>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-gray-700">$<?php echo number_format($prod['precio'], 2); ?></td>

                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-3 py-1 rounded-full text-sm 
                        <?php echo $prod['stock'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $prod['stock']; ?> un.
                        </span>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-center font-bold text-gray-700">
                        <?php echo $prod['vendidos']; ?>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <?php if ($prod['activo'] == 1): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                DISPONIBLE
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                NO DISPONIBLE
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-center space-x-2 no-print">

                        <a href="<?php echo $base_url; ?>pages/formulario_producto.php?id=<?php echo $prod['id']; ?>"
                            class="text-blue-600 hover:text-blue-900 transition text-sm"
                            title="Editar">
                            Editar
                        </a>

                        <?php if ($prod['activo'] == 1): ?>
                            <a href="<?php echo $base_url; ?>pages/productos_vendedor.php?action=toggle_active&id=<?php echo $prod['id']; ?>"
                                onclick="return confirm('¿Seguro que deseas DESACTIVAR este producto? Ya no aparecerá en la tienda.');"
                                class="text-red-600 hover:text-red-900 transition text-sm ml-3"
                                title="Desactivar">
                                Desactivar
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $base_url; ?>pages/productos_vendedor.php?action=toggle_active&id=<?php echo $prod['id']; ?>"
                                onclick="return confirm('¿Seguro que deseas ACTIVAR este producto? Volverá a aparecer en la tienda.');"
                                class="text-green-600 hover:text-green-900 transition text-sm ml-3"
                                title="Activar">
                                Activar
                            </a>
                        <?php endif; ?>

                    </td>
                </tr>
                <?php endforeach; ?>

                <?php else: ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500">
                        No tienes productos registrados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <?php else: ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded no-print" role="alert">
        <p class="font-bold">Atención</p>
        <p>Tu cuenta no tiene una tienda configurada.</p>
    </div>
    <?php endif; ?>
</main>

</div>

<?php 
if ($conn) { $conn->close(); }
require_once __DIR__ . '/../includes/footer.php';
?>
