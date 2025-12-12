<?php
// ==========================================================
// DASHBOARD PRINCIPAL (pages/dashboard.php)
// Usado para roles 'vendedor' y 'admin'.
// ==========================================================

// 1. INICIO DE SESIÓN EXPLICITO
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir base URL
$base_url = '/colectivo_c2c/';

// 2. INCLUSIÓN DE ARCHIVOS
require_once __DIR__ . '/../includes/funciones_sesion.php'; 
require_once __DIR__ . '/../config/db.php'; 

// 3. REQUERIMIENTO DE SESIÓN Y ROL CRÍTICO
require_login('vendedor'); 

// Variables de sesión
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_role = $_SESSION['user_role'] ?? 'invitado'; 
$user_id = $_SESSION['user_id'] ?? 0;

// Determinar rol
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600'; 

// Conexión DB
$conn = connect_db(); 
$colectivo_id = null;
$error_db_message = null;

// Consultas dinámicas
$total_ventas = 0.0;
$productos_activos_count = 0;
$ultimos_pedidos = [];
$RENTA_MENSUAL = 1000.00; // Valor fijo ejemplo

// -----------------------------------------------------------
// LÓGICA DE PAGO MENSUAL PARA VENDEDOR 
// -----------------------------------------------------------
$pago_al_corriente = false;
$ultimo_pago_fecha = 'N/A';
$monto_a_pagar = $RENTA_MENSUAL;

if ($conn && $user_role === 'vendedor') {
    $sql_colectivo = "SELECT id, ultimo_pago_mensual FROM colectivos WHERE id_usuario = ?";
    $stmt_c = $conn->prepare($sql_colectivo);

    if ($stmt_c) {
        $stmt_c->bind_param("i", $user_id);
        $stmt_c->execute();
        $result_c = $stmt_c->get_result()->fetch_assoc();

        if ($result_c) {
            $colectivo_id = $result_c['id'];
            $ultimo_pago = $result_c['ultimo_pago_mensual'];

            if ($ultimo_pago && $ultimo_pago !== '0000-00-00') {
                $pago_al_corriente = strtotime($ultimo_pago . ' + 30 days') >= time();
                $ultimo_pago_fecha = date('d/M/Y', strtotime($ultimo_pago));
            } else {
                $ultimo_pago_fecha = 'Nunca';
            }
        }
        $stmt_c->close();
    }
}

if ($pago_al_corriente) {
    $monto_a_pagar = 0.00;
}

// -----------------------------------------------------------
// VALIDACIONES POR ROL PARA CONSULTAS
// -----------------------------------------------------------

if ($conn && ($colectivo_id !== null || $user_role === 'admin')) {

    $join_filter = "";
    $where_filter = "";
    $bind_params = "";
    $bind_values = [];

    if ($user_role === 'vendedor') {
        $join_filter = "
            JOIN detalles_pedido dp ON p.id = dp.id_pedido 
            JOIN productos prod ON dp.id_producto = prod.id 
        ";
        $where_filter = " WHERE prod.id_colectivo = ? ";
        $bind_params = "i";
        $bind_values = [&$colectivo_id];
    }

    // 1. VENTAS ÚLTIMOS 30 DÍAS
    $sql_ventas = "
        SELECT SUM(p.total) AS total_ventas
        FROM pedidos p
        $join_filter
        $where_filter
        " . (empty($where_filter) ? " WHERE " : " AND ") . "
        p.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";

    $stmt_ventas = $conn->prepare($sql_ventas);
    if ($stmt_ventas) {
        if ($user_role === 'vendedor') {
            call_user_func_array([$stmt_ventas, 'bind_param'], array_merge([$bind_params], $bind_values));
        }
        $stmt_ventas->execute();
        $result_ventas = $stmt_ventas->get_result()->fetch_assoc();
        if ($result_ventas && $result_ventas['total_ventas'] !== null) {
            $total_ventas = (float)$result_ventas['total_ventas'];
        }
        $stmt_ventas->close();
    }

    // 2. PRODUCTOS ACTIVOS
    $sql_productos = "SELECT COUNT(id) AS total FROM productos WHERE activo = 1";
    if ($user_role === 'vendedor') {
        $sql_productos .= " AND id_colectivo = {$colectivo_id}";
    }
    $result_p = $conn->query($sql_productos);
    $productos_activos_count = $result_p->fetch_assoc()['total'] ?? 0;

    // 3. PEDIDOS PENDIENTES
    $estado_pendiente = 'Pendiente';
    $sql_pedidos = "
        SELECT COUNT(DISTINCT p.id) AS total
        FROM pedidos p
        $join_filter
        $where_filter
        " . (empty($where_filter) ? " WHERE " : " AND ") . "
        p.estado = ?
    ";

    $stmt_pedidos = $conn->prepare($sql_pedidos);
    if ($user_role === 'vendedor') {
        $pending_bind_params = "is";
        $pending_bind_values = [&$colectivo_id, &$estado_pendiente];
        call_user_func_array([$stmt_pedidos, 'bind_param'], array_merge([$pending_bind_params], $pending_bind_values));
    } else {
        $stmt_pedidos->bind_param("s", $estado_pendiente);
    }

    $stmt_pedidos->execute();
    $pedidos_pendientes_count = $stmt_pedidos->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_pedidos->close();

    // 4. ÚLTIMOS PEDIDOS
    $sql_ultimos = "
        SELECT DISTINCT p.id, u.nombre AS cliente, p.total, p.estado, p.fecha 
        FROM pedidos p
        JOIN usuarios u ON p.id_usuario = u.id
    ";

    if ($user_role === 'vendedor') {
        $sql_ultimos .= "
            JOIN detalles_pedido dp ON p.id = dp.id_pedido
            JOIN productos prod ON dp.id_producto = prod.id
            WHERE prod.id_colectivo = {$colectivo_id}
        ";
    }

    $sql_ultimos .= " ORDER BY p.fecha DESC LIMIT 5";

    $result_u = $conn->query($sql_ultimos);
    while ($row = $result_u->fetch_assoc()) {
        $ultimos_pedidos[] = $row;
    }
}

function base_url_page($page) {
    global $base_url;
    return $base_url . 'pages/' . $page;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-50">

<!-- ========================================================= -->
<!-- SIDEBAR (sin cambios) -->
<!-- ========================================================= -->
<aside class="w-64 bg-pink-50 text-gray-800 shadow-xl">
    <div class="p-6 text-2xl font-bold border-b border-pink-200">
        Colectivo CDI | <?php echo strtoupper($user_role); ?>
    </div>

    <div class="p-6">
        <div class="text-lg font-semibold mb-4 border-b border-pink-200 pb-2">
            Bienvenido, <?php echo htmlspecialchars($user_name); ?>
            <span class="inline-block px-2 py-0.5 text-xs font-medium text-white rounded-full <?php echo $role_color; ?> ml-2">
                <?php echo $role_display; ?>
            </span>
        </div>

        <nav class="space-y-2">
            <a href="<?php echo base_url_page('dashboard.php'); ?>"
                class="flex items-center p-3 rounded-lg bg-pink-300 text-white hover:bg-pink-400 transition duration-150">
                <i class="fas fa-home mr-3"></i> Inicio
            </a>

            <a href="<?php echo base_url_page('productos_vendedor.php'); ?>"
                class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
                <i class="fas fa-shopping-bag mr-3"></i> Mis Productos
            </a>

            <a href="<?php echo base_url_page('pedidos_vendedor.php'); ?>"
                class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
                <i class="fas fa-receipt mr-3"></i> Pedidos
            </a>

            <a href="<?php echo base_url_page('cupones_vendedor.php'); ?>"
                class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
                <i class="fas fa-percent mr-3"></i> Cupones
            </a>

            <a href="<?php echo base_url_page('reportes.php'); ?>"
                class="flex items-center p-3 rounded-lg text-pink-900 hover:bg-pink-200 transition duration-150">
                <i class="fas fa-file-alt mr-3"></i> Reportes
            </a>

            <?php if ($user_role == 'admin'): ?>
            <a href="<?php echo base_url_page('admin/gestion_usuarios.php'); ?>"
                class="flex items-center p-3 rounded-lg bg-red-400 text-white hover:bg-red-500 transition duration-150">
                <i class="fas fa-users-cog mr-3"></i> Gestión de Usuarios
            </a>
            <?php endif; ?>

            <a href="<?php echo $base_url; ?>acciones/auth_action.php?action=logout"
                class="flex items-center p-3 rounded-lg bg-pink-600 text-white hover:bg-pink-700 transition duration-150 mt-4">
                <i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
            </a>
        </nav>
    </div>
</aside>

<!-- ========================================================= -->
<!-- CONTENIDO PRINCIPAL SIN LA BARRA DE BUSCAR -->
<!-- ========================================================= -->
<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="container mx-auto px-6 py-8">

        <h1 class="text-4xl font-bold text-gray-800 mb-6">
            Dashboard Principal <?php if ($user_role === 'admin') echo '(Vista Global)'; ?>
        </h1>

        <!-- TARJETAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-pink-300">
                <p class="text-sm font-medium text-gray-500">Ventas (Últimos 30 días)</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    $<?php echo number_format($total_ventas, 2); ?> MXN
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-pink-600">
                <p class="text-sm font-medium text-gray-500">Pedidos Pendientes</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    <?php echo $pedidos_pendientes_count; ?>
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-pink-300">
                <p class="text-sm font-medium text-gray-500">Productos Activos</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    <?php echo $productos_activos_count; ?>
                </p>
            </div>

            <?php if ($user_role === 'vendedor'): ?>
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 <?php echo $pago_al_corriente ? 'border-green-400' : 'border-red-300'; ?>">
                <p class="text-sm font-medium text-gray-500">Renta Mensual</p>

                <p class="text-3xl font-bold mt-1 <?php echo $pago_al_corriente ? 'text-green-600' : 'text-red-600'; ?>">
                    $<?php echo number_format($monto_a_pagar, 2); ?> MXN
                </p>

                <p class="text-sm mt-2 <?php echo $pago_al_corriente ? 'text-green-600' : 'text-gray-500'; ?>">
                    <?php echo $pago_al_corriente ? 'PAGADO' : 'Pendiente de pago'; ?>
                </p>
            </div>
            <?php endif; ?>

        </div>

        <!-- TABLA: ÚLTIMOS PEDIDOS -->
        <div class="mt-8 bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                Últimos Pedidos Recibidos
            </h2>

            <div class="overflow-x-auto">

                <?php if (!empty($ultimos_pedidos)): ?>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php foreach ($ultimos_pedidos as $pedido): ?>
                        <tr>
                            <td class="px-6 py-4">#<?php echo $pedido['id']; ?></td>
                            <td class="px-6 py-4"><?php echo $pedido['cliente']; ?></td>
                            <td class="px-6 py-4">$<?php echo number_format($pedido['total'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                    <?php echo ($pedido['estado'] == 'Pendiente') ? 'bg-pink-100 text-pink-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $pedido['estado']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?php echo date('d/M/Y', strtotime($pedido['fecha'])); ?></td>
                        </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

                <?php else: ?>

                <div class="text-center text-gray-500 py-8">
                    No hay pedidos recientes.
                </div>

                <?php endif; ?>

            </div>
        </div>

    </div>
</main>

</div>

<?php 
if ($conn) $conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
