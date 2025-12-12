<?php
// Define el título de la página
$page_title = "Pedidos Recibidos | Colectivo C2C";

// Incluir configuración, funciones de sesión y header
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

// Requerir rol de vendedor o administrador
if (!function_exists('require_vendedor')) { require_login(); } else { require_vendedor(); }

$conn = connect_db();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'invitado'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario'; 
$colectivo_id = null;
$mensaje = '';
$tipo_mensaje = '';

// Variables de rol para el sidebar
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600'; 

// 1. OBTENER ID DEL COLECTIVO (si es vendedor)
if ($conn && $user_role === 'vendedor') {
    $sql_c = "SELECT id FROM colectivos WHERE id_usuario = ?";
    $stmt_c = $conn->prepare($sql_c);
    if ($stmt_c) {
        $stmt_c->bind_param("i", $user_id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result()->fetch_assoc();
        if ($res_c) $colectivo_id = $res_c['id'];
        $stmt_c->close();
    }
}

// 2. LÓGICA DE ACTUALIZACIÓN DE ESTADO (Vendedor/Admin) - PRG IMPLEMENTADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $pedido_id = (int)($_POST['pedido_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? 'Pendiente';

    if ($pedido_id > 0) {
        $sql_upd = "UPDATE pedidos SET estado = ? WHERE id = ?";
        $params = "si";
        $bind_values = [&$new_status, &$pedido_id];
        
        $stmt_upd = $conn->prepare($sql_upd);
        if ($stmt_upd) {
             call_user_func_array([$stmt_upd, 'bind_param'], array_merge([$params], $bind_values));
            
             if ($stmt_upd->execute() && $stmt_upd->affected_rows > 0) {
                 // Éxito: Guardar mensaje en sesión y REDIRIGIR
                 $_SESSION['update_message'] = "Estado del pedido #{$pedido_id} actualizado a: {$new_status}.";
                 $_SESSION['update_type'] = "bg-green-100 text-green-700 border-green-400";
             } else {
                 $_SESSION['update_message'] = "Error al actualizar el estado o pedido no encontrado.";
                 $_SESSION['update_type'] = "bg-red-100 text-red-700 border-red-400";
             }
             $stmt_upd->close();
        } else {
            $_SESSION['update_message'] = "Error al preparar la consulta de actualización.";
            $_SESSION['update_type'] = "bg-red-100 text-red-700 border-red-400";
        }
    }
    
    // REDIRECCIÓN PRG CRÍTICA para recargar datos frescos
    header("Location: pedidos_vendedor.php");
    exit;
}

// 3. RECUPERAR MENSAJES DE LA SESIÓN (PRG)
if (isset($_SESSION['update_message'])) {
    $mensaje = $_SESSION['update_message'];
    $tipo_mensaje = $_SESSION['update_type'];
    unset($_SESSION['update_message']);
    unset($_SESSION['update_type']);
}


// 4. LISTAR PEDIDOS
$pedidos = [];
$join_sql = "";
$filtro_where = "";
$bind_list_params = "";
$bind_list_values = [];


if ($user_role === 'vendedor' && $colectivo_id) {
    // Triple JOIN para asegurar que el pedido contenga al menos 1 producto del colectivo
    $join_sql = " 
        INNER JOIN detalles_pedido dp ON p.id = dp.id_pedido 
        INNER JOIN productos prod ON dp.id_producto = prod.id 
    ";
    $filtro_where = " WHERE prod.id_colectivo = ? "; 
    $bind_list_params = "i";
    $bind_list_values = [&$colectivo_id];
} elseif ($user_role !== 'admin') {
    $colectivo_id = null; 
}


if ($conn && ($colectivo_id || $user_role === 'admin')) {
    
    // ENSAMBLAJE DE LA CONSULTA SQL
    $sql_pedidos = "
    SELECT 
        p.id, 
        p.total, 
        p.estado, 
        p.fecha, 
        p.nombre_cliente,
        p.apellido_cliente
    FROM 
        pedidos p
    " . $join_sql . "
    " . $filtro_where . "
    GROUP BY p.id 
    ORDER BY p.fecha DESC";
    
    // Ejecución de la consulta
    if ($user_role === 'vendedor' && $colectivo_id) {
        $stmt_list = $conn->prepare($sql_pedidos);
        if ($stmt_list) {
            call_user_func_array([$stmt_list, 'bind_param'], array_merge([$bind_list_params], $bind_list_values));
            $stmt_list->execute(); 
            $result_p = $stmt_list->get_result();
            while ($row = $result_p->fetch_assoc()) {
                $pedidos[] = $row;
            }
            $stmt_list->close();
        }
    } else {
        // Ejecución para ADMIN
        $result_p = $conn->query($sql_pedidos);
        if ($result_p) {
            while ($row = $result_p->fetch_assoc()) {
                $pedidos[] = $row;
            }
        }
    }
}

// Incluir el header después de toda la lógica de procesamiento
require_once __DIR__ . '/../includes/header.php'; 
?>

<div class="flex h-screen bg-gray-50">
    <aside class="w-64 bg-pink-100 text-gray-800 shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-pink-300 text-pink-700">
            Colectivo C2C | <?php echo strtoupper($user_role); ?>
        </div>
        
        <div class="p-6">
            <div class="text-lg font-semibold mb-4 border-b border-pink-300 pb-2">
                Bienvenido, <?php echo htmlspecialchars($user_name); ?>
                <span class="inline-block px-2 py-0.5 text-xs font-medium text-white rounded-full <?php echo $role_color; ?> ml-2">
                    <?php echo $role_display; ?>
                </span>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
                    <i class="fas fa-home mr-3"></i> Inicio
                </a>
                <a href="productos_vendedor.php" class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
                    <i class="fas fa-shopping-bag mr-3"></i> Mis Productos
                </a>
                <a href="pedidos_vendedor.php" class="flex items-center p-3 rounded-lg bg-pink-300 font-bold text-white transition duration-150">
                    <i class="fas fa-receipt mr-3"></i> Pedidos
                </a>
                <a href="cupones_vendedor.php" class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
                    <i class="fas fa-percent mr-3"></i> Cupones
                </a>                
                <?php if ($user_role == 'admin'): ?>
                <a href="admin/gestion_usuarios.php" class="flex items-center p-3 rounded-lg bg-red-400 text-white hover:bg-red-500 transition duration-150">
                    <i class="fas fa-users-cog mr-3"></i> Gestión de Usuarios
                </a>
                <?php endif; ?>
                <a href="../acciones/auth_action.php?action=logout" class="flex items-center p-3 rounded-lg bg-pink-600 text-white hover:bg-pink-700 transition duration-150 mt-4">
                    <i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-50 p-6 min-w-0">
        <div class="max-w-4xl mx-auto">
            
            <header class="text-center mb-6">
                <h1 class="text-4xl font-extrabold text-gray-800 mb-2">
                    Gestión de Pedidos
                </h1>
                <p class="text-lg text-gray-600">
                    <?php echo ($user_role === 'admin') ? 'Vista global de todas las transacciones.' : 'Pedidos que tu tienda debe gestionar y enviar.'; ?>
                </p>
            </header>

            <?php if ($mensaje): ?>
                <div class="border-l-4 p-4 mb-6 rounded <?php echo $tipo_mensaje; ?>">
                    <p><?php echo $mensaje; ?></p>
                </div>
            <?php endif; ?>

            <!-- BOTÓN: Imprimir TODOS los pedidos -->
            <div class="mb-6 text-right">
                <button onclick="imprimirTodosPedidos()" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg shadow hover:bg-pink-700">
                    <i class="fas fa-print mr-2"></i> Imprimir TODOS los pedidos
                </button>
            </div>

            <div class="space-y-6">
                
                <?php if (empty($pedidos)): ?>
                    
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-pink-200 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <h3 class="mt-4 text-xl font-semibold text-gray-800">No hay pedidos para gestionar</h3>
                        <p class="mt-2 text-gray-500">
                            ¡Tu lista está limpia! Momento de descansar o añadir más productos.
                        </p>
                        <div class="mt-6">
                            <a href="formulario_producto.php" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-md text-white bg-pink-500 hover:bg-pink-600 transition">
                                <i class="fas fa-plus mr-2"></i> Añadir Productos
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <?php foreach ($pedidos as $pedido): ?>
                    
                        <?php 
                            $estado_display = htmlspecialchars($pedido['estado']);
                            $status_classes = match ($pedido['estado']) {
                                'Pendiente' => 'bg-yellow-100 text-yellow-800',
                                'Enviado' => 'bg-blue-100 text-blue-800',
                                'Entregado' => 'bg-green-100 text-green-800',
                                'Cancelado' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        ?>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <div class="flex justify-between items-center mb-4 border-b pb-3">
                                <h2 class="text-xl font-bold text-pink-700">
                                    Pedido #<?php echo htmlspecialchars($pedido['id']); ?>
                                </h2>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_classes; ?>">
                                    <?php echo $estado_display; ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                <p class="col-span-2 md:col-span-1">
                                    <strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente'] . ' ' . $pedido['apellido_cliente']); ?>
                                </p>
                                <p><strong>Fecha:</strong> <?php echo date('d M Y', strtotime($pedido['fecha'])); ?></p>
                                <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?> MXN</p>
                                <p>
                                    <strong>Artículos:</strong> 
                                    <button type="button" 
                                        onclick="abrirModal(<?php echo $pedido['id']; ?>, 'articulos')" 
                                        class="text-pink-600 hover:text-pink-800 underline transition">
                                        Ver Detalle
                                    </button>
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center">
                                
                                <div>
                                    <button type="button" 
                                        onclick="abrirModal(<?php echo $pedido['id']; ?>, 'cliente')" 
                                        class="text-sm font-medium text-pink-600 hover:text-pink-800 transition flex items-center underline mr-4">
                                        <i class="fas fa-search mr-1"></i> Ver Detalles del Cliente
                                    </button>
                        </br>
                                    <!-- BOTÓN: Imprimir este pedido -->
                                    <button type="button"
                                            onclick="imprimirPedido(<?php echo $pedido['id']; ?>)"
                                            class="inline-flex items-center px-3 py-2 bg-pink-500 text-white rounded-md hover:bg-pink-600 transition">
                                        <i class="fas fa-print mr-2"></i> Imprimir este pedido
                                    </button>
                                </div>
                                
                                <form method="POST" action="pedidos_vendedor.php" class="flex items-center space-x-3">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    </br>
                                    <label for="status-<?php echo $pedido['id']; ?>" class="text-sm font-medium text-gray-700">Cambiar a:</label>
                                    <select name="new_status" id="status-<?php echo $pedido['id']; ?>" class="p-2 border border-gray-300 rounded-lg text-sm focus:ring-pink-500 focus:border-pink-500">
                                        <option value="Pendiente" <?php echo ($pedido['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="Enviado" <?php echo ($pedido['estado'] == 'Enviado') ? 'selected' : ''; ?>>Enviado</option>
                                        <option value="Entregado" <?php echo ($pedido['estado'] == 'Entregado') ? 'selected' : ''; ?>>Entregado</option>
                                        <option value="Cancelado" <?php echo ($pedido['estado'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    
                                    <button type="submit" class="bg-pink-500 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:bg-pink-600 transition shadow-md">
                                        Guardar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL EXISTENTE -->
<div id="detalleModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 transform transition-all p-6">
        
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-2xl font-bold text-pink-700" id="modalTitle">Detalles del Pedido #</h3>
            <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-800 text-3xl leading-none">&times;</button>
        </div>

        <div id="modalBody" class="space-y-4 max-h-96 overflow-y-auto">
            </div>

        <div class="mt-6 text-right">
            <button onclick="cerrarModal()" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-300 transition">Cerrar</button>
        </div>
    </div>
</div>

<script>
    const detalleModal = document.getElementById('detalleModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const base_url = '../pages/fetch_pedido_data.php'; 

    function cerrarModal() {
        detalleModal.classList.add('hidden');
        detalleModal.classList.remove('flex');
    }

    function mostrarError(mensaje) {
        modalTitle.textContent = "Error";
        modalBody.innerHTML = `<div class="text-red-600 p-4 bg-red-50 rounded">${mensaje}</div>`;
        detalleModal.classList.remove('hidden');
        detalleModal.classList.add('flex');
    }

    function abrirModal(pedidoId, action) {
        modalBody.innerHTML = '<div class="text-center py-4 text-pink-600"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando datos...</div>';
        detalleModal.classList.remove('hidden');
        detalleModal.classList.add('flex');

        fetch(`${base_url}?pedido_id=${pedidoId}&action=${action}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('No se pudo cargar la información.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (action === 'cliente') {
                        renderCliente(data.data, pedidoId);
                    } else if (action === 'articulos') {
                        renderArticulos(data.data, pedidoId);
                    }
                } else {
                    mostrarError(data.error || "Error desconocido al obtener los datos.");
                }
            })
            .catch(error => {
                mostrarError("Error de red o servidor: " + error.message);
            });
    }

    function renderCliente(data, pedidoId) {
    modalTitle.textContent = `Detalles del Cliente - Pedido #${pedidoId}`;
    
    // 1. Obtener los últimos 4 dígitos
    const numCompleto = data.numero_tarjeta_completo || '';
    const ultimosCuatro = numCompleto.slice(-4);
    
    // 2. Determinar el tipo de método para mostrar 'Débito'
    let metodoDisplay = 'Pago no registrado';
    if (data.metodo_pago_nombre) {
        metodoDisplay = 'Tarjeta de Débito'; // Asumimos que es Débito como método único
    }
    
    if (ultimosCuatro && ultimosCuatro.length === 4) {
        metodoDisplay += ` (Terminación: ${ultimosCuatro})`;
    }
    
    // 3. Renderizar el modal
    modalBody.innerHTML = `
        <h4 class="font-bold text-xl text-pink-700 mb-2">Información de Contacto</h4>
        <p><strong>Cliente:</strong> ${data.nombre_cliente} ${data.apellido_cliente}</p>
        <p><strong>Email:</strong> ${data.email_cliente}</p>
        
        <h4 class="font-bold text-xl text-pink-700 mt-4 mb-2 border-t pt-2">Detalles de Pago</h4>
        
        <p><strong>Método:</strong> ${metodoDisplay}</p>
        
        <p><strong>Titular:</strong> ${data.titular_tarjeta || 'N/A'}</p>
        <p><strong>Vencimiento:</strong> ${data.fecha_vencimiento_sim || 'N/A'}</p>

        <h4 class="font-bold text-xl text-pink-700 mt-4 mb-2 border-t pt-2">Dirección de Envío</h4>
        <p class="text-gray-700 whitespace-pre-wrap">${data.direccion_envio}</p>
    `;
}
    function renderArticulos(data, pedidoId) {
    modalTitle.textContent = `Artículos del Pedido #${pedidoId}`;
    
    let totalPedido = 0;
    
    const itemsHtml = data.articulos.map(item => {
        const subtotal = parseFloat(item.subtotal_item).toFixed(2);
        totalPedido += parseFloat(item.subtotal_item);
        
        // 🚨 Usamos cantidad_total (resultado de SUM en SQL) 🚨
        return `
            <div class="flex justify-between border-b py-2">
                <span class="text-gray-700">${item.cantidad_total}x ${item.producto_nombre}</span>
                <span class="font-semibold">$${subtotal}</span>
            </div>
        `;
    }).join('');
        
        modalBody.innerHTML = `
        ${itemsHtml}
        <div class="flex justify-between text-lg font-bold pt-3 border-t border-pink-300">
            <span>Total Estimado de Artículos:</span>
            <span class="text-pink-700">$${totalPedido.toFixed(2)}</span>
        </div>
    `;
}

/* -------------------------
   IMPRESIÓN (ticket rosa)
   ------------------------- */

// Generador del HTML del ticket (misma apariencia rosa que ya usas)
function generarHtmlTicket(datosCliente, datosArticulos, pedidoId, resumen) {
    // datosCliente: objeto con nombre_cliente, apellido_cliente, email_cliente, direccion_envio, numero_tarjeta_completo, metodo_pago_nombre, titular_tarjeta, fecha_vencimiento_sim
    // datosArticulos: array de { producto_nombre, cantidad_total, subtotal_item }
    // resumen: objeto opcional con total, subtotal, envio, iva, descuento, referencia
    const fullName = (datosCliente.nombre_cliente || '') + ' ' + (datosCliente.apellido_cliente || '');
    const ultimos4 = (datosCliente.numero_tarjeta_completo || '').slice(-4);

    const productosHtml = ((datosArticulos?.articulos) || []).map(
it => {
        const qty = it.cantidad_total ?? it.quantity ?? 1;
        const name = it.producto_nombre ?? it.title ?? 'Producto';
        const sub = parseFloat(it.subtotal_item ?? it.subtotal ?? 0).toFixed(2);
        return `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px dashed #e2a0bf;">
                    <span style="font-size:14px;color:#333;">${qty}x ${escapeHtml(name)}</span>
                    <span style="font-weight:600;color:#c2185b;">$${sub}</span>
                </div>`;
    }).join('');

    const subtotal = resumen?.subtotal ? parseFloat(resumen.subtotal).toFixed(2) : '';
    const descuento = resumen?.descuento ? parseFloat(resumen.descuento).toFixed(2) : '';
    const iva = resumen?.iva ? parseFloat(resumen.iva).toFixed(2) : '';
    const envio = resumen?.envio ? (parseFloat(resumen.envio) === 0 ? 'GRATIS' : ('$' + parseFloat(resumen.envio).toFixed(2))) : '';
    const total = resumen?.total ? parseFloat(resumen.total).toFixed(2) : '';

    return `
    <div style="width:680px;max-width:100%;margin:10px auto;padding:20px;background:#fff0f6;border-radius:12px;font-family:Arial,Helvetica,sans-serif;color:#333;">
        <div style="text-align:center;margin-bottom:8px;">
            <h2 style="margin:0;color:#d63384;">Ticket de Compra</h2>
            <div style="font-size:13px;color:#999;margin-top:4px;">Pedido #${pedidoId}</div>
        </div>

        <div style="margin-top:10px;">
            <div style="font-size:14px;"><strong>Cliente:</strong> ${escapeHtml(fullName)}</div>
            <div style="font-size:14px;"><strong>Email:</strong> ${escapeHtml(datosCliente.email_cliente || '')}</div>
            <div style="font-size:14px;"><strong>Dirección:</strong> ${escapeHtml(datosCliente.direccion_envio || '')}</div>
            <div style="font-size:14px;"><strong>Referencia:</strong> ${escapeHtml(resumen?.referencia || '')}</div>
        </div>

        <div style="margin-top:12px;border-top:2px dashed #d63384;padding-top:10px;">
            <div style="font-weight:700;color:#c2185b;margin-bottom:8px;">Productos</div>
            ${productosHtml}
        </div>

        <div style="margin-top:12px;border-top:1px solid #f3d7e6;padding-top:10px;font-size:14px;">
            ${ subtotal ? `<div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Subtotal:</span><span>$${subtotal}</span></div>` : ''}
            ${ descuento ? `<div style="display:flex;justify-content:space-between;padding:4px 0;color:green"><span>Descuento:</span><span>-$${descuento}</span></div>` : ''}
            ${ iva ? `<div style="display:flex;justify-content:space-between;padding:4px 0;"><span>IVA:</span><span>$${iva}</span></div>` : ''}
            ${ envio ? `<div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Envío:</span><span>${envio}</span></div>` : ''}
            ${ total ? `<div style="display:flex;justify-content:space-between;padding:10px 0;font-size:18px;font-weight:800;color:#d63384;"><span>Total:</span><span>$${total}</span></div>` : ''}
        </div>

        <div style="text-align:center;margin-top:14px;color:#d63384;font-weight:700;">¡Gracias por tu compra!</div>

        <div style="margin-top:8px;font-size:12px;color:#999;text-align:center;">Documento generado por Colectivo C2C</div>
    </div>
    <div style="page-break-after: always;"></div>
    `;
}

// helper para escapar texto dentro de template literals (previene XSS si devuelven algo raro)
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Imprimir UN pedido (usa fetch para cliente + articulos y arma ticket)
async function imprimirPedido(pedidoId) {
    try {
        // obtener cliente
        const r1 = await fetch(`${base_url}?pedido_id=${pedidoId}&action=cliente`);
        if (!r1.ok) throw new Error('Error al obtener datos del cliente');
        const j1 = await r1.json();
        if (!j1.success) throw new Error(j1.error || 'Error en cliente');

        // obtener articulos
        const r2 = await fetch(`${base_url}?pedido_id=${pedidoId}&action=articulos`);
        if (!r2.ok) throw new Error('Error al obtener artículos');
        const j2 = await r2.json();
        if (!j2.success) throw new Error(j2.error || 'Error en articulos');

        // Si quisieras traer resumen (subtotal, total, etc.) desde el backend, podrías agregar otra acción.
        // Aquí intentamos inferir total mínimo desde la lista de pedidos cargada en PHP, pero para seguridad
        // también podemos pedir un resumen si tu endpoint lo acepta. Para ahora, usamos el total que está en la lista.
        const resumen = await obtenerResumenLocal(pedidoId);

        const ticketHtml = generarHtmlTicket(j1.data, j2.data, pedidoId, resumen);

        const win = window.open('', '_blank', 'width=900,height=800');
        win.document.write(`
            <html>
            <head>
                <title>Ticket Pedido #${pedidoId}</title>
                <style>
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${ticketHtml}
            </body>
            </html>
        `);
        win.document.close();
        win.focus();
        // dar tiempo ligero para que cargue
        setTimeout(()=>{ win.print(); /*win.close();*/ }, 300);
    } catch (err) {
        alert('No se pudo generar el ticket: ' + err.message);
    }
}

// Imprimir TODOS los pedidos (se itera por la lista PHP 'pedidos_js')
async function imprimirTodosPedidos() {
    try {
        if (!window.pedidos_js || !Array.isArray(window.pedidos_js) || window.pedidos_js.length === 0) {
            alert('No hay pedidos para imprimir.');
            return;
        }

        let allHtml = '';

        for (let p of window.pedidos_js) {
            const pedidoId = p.id;
            // obtener cliente y articulos como en imprimirPedido
            const r1 = await fetch(`${base_url}?pedido_id=${pedidoId}&action=cliente`);
            if (!r1.ok) throw new Error('Error al obtener datos del cliente para pedido ' + pedidoId);
            const j1 = await r1.json();
            if (!j1.success) throw new Error(j1.error || 'Error en cliente ' + pedidoId);

            const r2 = await fetch(`${base_url}?pedido_id=${pedidoId}&action=articulos`);
            if (!r2.ok) throw new Error('Error al obtener artículos para pedido ' + pedidoId);
            const j2 = await r2.json();
            if (!j2.success) throw new Error(j2.error || 'Error en articulos ' + pedidoId);

            const resumen = await obtenerResumenLocal(pedidoId);

            allHtml += generarHtmlTicket(j1.data, j2.data, pedidoId, resumen);
        }

        const win = window.open('', '_blank', 'width=900,height=800');
        win.document.write(`
            <html>
            <head>
                <title>Todos los tickets</title>
                <style>
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                ${allHtml}
            </body>
            </html>
        `);
        win.document.close();
        win.focus();
        setTimeout(()=>{ win.print(); /*win.close();*/ }, 500);

    } catch (err) {
        alert('No se pudo generar los tickets: ' + err.message);
    }
}

// intenta obtener resumen básico (total/subtotal) de la lista cargada en PHP (window.pedidos_js)
function obtenerResumenLocal(pedidoId) {
    // Buscar en pedidos_js el total; si no existe devolvemos objeto vacío
    if (window.pedidos_js && Array.isArray(window.pedidos_js)) {
        const found = window.pedidos_js.find(x => parseInt(x.id) === parseInt(pedidoId));
        if (found) {
            return {
                total: found.total ?? '',
                subtotal: found.subtotal ?? '',
                envio: found.envio ?? '',
                iva: found.iva ?? '',
                descuento: found.descuento ?? '',
                referencia: found.referencia ?? ''
            };
        }
    }
    return {};
}
</script>

<!-- pasar la lista de pedidos a JS para la impresión "todos" -->
<script>
    // Serializamos solo lo necesario; si hay campos undefined, no pasa nada
    window.pedidos_js = <?php echo json_encode(array_map(function($p){ return [
        'id' => $p['id'],
        'total' => $p['total'],
        'estado' => $p['estado'],
        'fecha' => $p['fecha'],
        'nombre_cliente' => $p['nombre_cliente'] ?? '',
        'apellido_cliente' => $p['apellido_cliente'] ?? ''
    ]; }, $pedidos), JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php
if ($conn) $conn->close();
include '../includes/footer.php'; 
?>
