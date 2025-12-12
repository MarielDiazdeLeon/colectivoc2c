<?php
// =========================================================================
// CARRITO.PHP - PROCESAMIENTO DE CUPONES Y CÁLCULO DE TOTALES
// (La lógica POST debe ir ANTES de cualquier salida HTML)
// =========================================================================

// Título de la página
$page_title = "Carrito de Compras | Colectivo C2C";

// Incluir configuración y sesión
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

$conn = connect_db();

// Variables iniciales
$carrito = $_SESSION['carrito'] ?? [];
$subtotal_total = 0; 
$productos_en_carrito = []; 
$descuento_monto = 0.00;
$cupon_aplicado = $_SESSION['cupon_aplicado'] ?? '';
$mensaje_cupon = '';
$tipo_descuento = ''; 

// =========================================================================
// 🚨 1. LÓGICA DE PROCESAMIENTO DE CUPONES (DEBE SER LO PRIMERO) 🚨
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['codigo_cupon'])) {
    $codigo_cupon = trim($_POST['codigo_cupon']);
    
    if (!empty($codigo_cupon)) {
        if (isset($_POST['btnEliminarCupon'])) {
            unset($_SESSION['cupon_aplicado']);
            $cupon_aplicado = '';
            $mensaje_cupon = "Cupón eliminado.";
        }
        if(isset($_POST['btnAplicarCupon'])) {
        // Buscar el cupón activo
        // Se puede añadir aquí la verificación de ID_COLECTIVO si el cupón es por tienda
        $sql_cupon = "SELECT * FROM cupones WHERE codigo = ? AND activo = 1 AND (usos_maximos = 0 OR usos_actuales < usos_maximos)";
        $stmt_cupon = $conn->prepare($sql_cupon);
        $stmt_cupon->bind_param("s", $codigo_cupon);
        $stmt_cupon->execute();
        $res_cupon = $stmt_cupon->get_result()->fetch_assoc();

        if ($res_cupon) {
            $_SESSION['cupon_aplicado'] = $codigo_cupon;
            $mensaje_cupon = "Cupón '{$codigo_cupon}' aplicado con éxito.";
        } else {
            unset($_SESSION['cupon_aplicado']);
            $mensaje_cupon = "Código de cupón inválido, expirado o agotado.";
        }
        $stmt_cupon->close();
        }
    } else {
        // Eliminar cupón si el campo se envía vacío
        unset($_SESSION['cupon_aplicado']);
        $cupon_aplicado = '';
        $mensaje_cupon = "Cupón eliminado.";
    }
    
    // 🚨 REDIRECCIÓN (HEADER) - SE EJECUTA AHORA ANTES DE CUALQUIER SALIDA HTML
    header("Location: carrito.php");
    exit;
}

// =========================================================================
// 2. CÁLCULO: OBTENER PRODUCTOS Y SUBTOTALES
// =========================================================================

if (!empty($carrito)) {

    $ids = implode(',', array_keys($carrito));
    $sql = "SELECT id, nombre, precio, stock, imagen_url, id_colectivo 
            FROM productos 
            WHERE id IN ($ids) AND activo = 1";

    $result = $conn->query($sql);

    if ($result) {
        while ($producto_db = $result->fetch_assoc()) {

            $id = $producto_db['id'];
            $cantidad = (int)($_SESSION['carrito'][$id]['cantidad'] ?? 0);

            if ($cantidad > 0) {
                $precio_unitario = (float)$producto_db['precio'];
                $subtotal_producto = $precio_unitario * $cantidad;
                $subtotal_total += $subtotal_producto;

                $productos_en_carrito[] = [
                    'id' => $id,
                    'nombre' => $producto_db['nombre'],
                    'precio' => $precio_unitario,
                    'stock' => (int)$producto_db['stock'],
                    'cantidad' => $cantidad,
                    'imagen_url' => $producto_db['imagen_url'],
                    'subtotal' => $subtotal_producto,
                    'id_colectivo' => $producto_db['id_colectivo'],
                ];
            }
        }
    }
}

// =========================================================================
// 3. CÁLCULO: APLICAR DESCUENTO Y ENVÍO
// =========================================================================

if (!empty($cupon_aplicado)) {
    $sql_cupon = "SELECT * FROM cupones WHERE codigo = ? AND activo = 1 AND (usos_maximos = 0 OR usos_actuales < usos_maximos)";
    $stmt_cupon = $conn->prepare($sql_cupon);
    $stmt_cupon->bind_param("s", $cupon_aplicado);
    $stmt_cupon->execute();
    $cupon_data = $stmt_cupon->get_result()->fetch_assoc();
    
    if ($cupon_data) {
        $valor = (float)$cupon_data['valor'];
        $tipo_descuento = $cupon_data['tipo'];
        
        if ($tipo_descuento == 'porcentaje') {
            $descuento_monto = $subtotal_total * ($valor / 100);
        } else { // Monto Fijo
            $descuento_monto = $valor;
        }
        $descuento_monto = min($descuento_monto, $subtotal_total);
        
        $mensaje_cupon = "Descuento de " . ($tipo_descuento == 'porcentaje' ? $valor . '%' : '$' . number_format($valor, 2)) . " aplicado.";
        
    } else {
        // Limpiar cupón si ya no es válido
        unset($_SESSION['cupon_aplicado']);
        $cupon_aplicado = '';
        $mensaje_cupon = "El cupón aplicado ya no es válido y ha sido removido.";
    }
    if (isset($stmt_cupon)) $stmt_cupon->close();
}

$subtotal_con_descuento = $subtotal_total - $descuento_monto;

$costo_envio = 100.00; // Costo base de envío (ejemplo)
$envio_gratis = ($subtotal_con_descuento >= 300); // 🚨 REGLA: Envío gratis si el subtotal es >= $300

if ($envio_gratis) {
    $costo_envio = 0.00;
}

$total_estimado = $subtotal_con_descuento + $costo_envio;

// =========================================================================
// 4. INICIO DE LA SALIDA HTML
// =========================================================================

include '../includes/header.php'; // ⬅️ Se incluye AQUÍ, después del procesamiento POST
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    <div class="text-4xl font-extrabold text-pink-700 mb-8 border-b-4 border-pink-300 pb-4">
        Tu Carrito de Compras
    </div>

    <?php if (empty($productos_en_carrito)): ?>
        
        <div class="text-center py-20 bg-pink-50 rounded-2xl shadow-xl border border-pink-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <div class="text-2xl font-semibold text-gray-700 mb-2">Tu carrito está vacío.</div>
            <p class="text-gray-500 mb-6">Parece que no has añadido productos a tu pedido aún.</p>
            <a href="../index.php" class="bg-[#f28bb2] hover:bg-[#e56a96] text-white font-bold py-3 px-6 rounded-xl transition duration-200 shadow-md">
                Explorar el Catálogo
            </a>
        </div>

    <?php else: ?>
        
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            
            <div class="lg:col-span-2 space-y-4">
                <?php foreach ($productos_en_carrito as $item): ?>
                    <div class="flex items-center bg-white p-5 rounded-2xl shadow-md border border-pink-100 hover:shadow-xl transition duration-200">
                        
                        <img src="<?= htmlspecialchars($item['imagen_url']) ?: 'https://placehold.co/100x100/A3B8CC/fff?text=No+Img' ?>" 
                            alt="<?= htmlspecialchars($item['nombre']) ?>" 
                            class="w-20 h-20 object-cover rounded-md mr-4 shadow-sm">
                        
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg text-pink-700"><?= htmlspecialchars($item['nombre']) ?></h3>
                            <p class="text-sm text-gray-600">Precio unitario: $<?= number_format($item['precio'], 2) ?></p>
                            <p class="text-xs text-red-500">Stock disponible: <?= $item['stock'] ?></p>
                        </div>

                        <div class="flex items-center space-x-4">
                            
                            <form action="../acciones/carrito_action.php" method="POST" class="flex items-center space-x-2">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="update">
                                
                                <input type="number" 
                                        name="cantidad" 
                                        value="<?= $item['cantidad'] ?>" 
                                        min="1" 
                                        max="<?= $item['stock'] ?>" 
                                        class="w-16 p-2 border border-gray-300 rounded-md text-center text-sm focus:ring-pink-400 focus:border-pink-400"
                                        onchange="this.form.submit()">
                            </form>
                            
                            <div class="font-semibold text-lg text-pink-700 w-24 text-right">
                                $<?= number_format($item['subtotal'], 2) ?>
                            </div>
                            
                            <form action="../acciones/carrito_action.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" 
                                        class="text-pink-500 hover:text-pink-700 transition duration-150 p-2 rounded-full hover:bg-pink-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="flex justify-start pt-4">
                    <a href="../index.php" class="text-pink-600 hover:text-pink-800 font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Continuar Comprando
                    </a>
                </div>
            </div>

            <div class="lg:col-span-1 mt-8 lg:mt-0">
                
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-pink-200 mb-6">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Aplicar Cupón de Descuento</h3>
                    
                    <form action="carrito.php" method="POST" class="space-y-3">
                        <input type="text" name="codigo_cupon" placeholder="Código de cupón (ej: NAVIDAD25)" 
                            value="<?= htmlspecialchars($cupon_aplicado) ?>"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 text-sm">
                            
                        <?php if ($cupon_aplicado): ?>
                            <button type="submit" name="btnEliminarCupon" class="w-full bg-red-400 hover:bg-red-500 text-white font-medium py-2 rounded-lg transition">
                                Eliminar Cupón
                            </button>
                        <?php else: ?>
                            <button type="submit" name="btnAplicarCupon" class="w-full bg-pink-500 hover:bg-pink-600 text-white font-medium py-2 rounded-lg transition">
                                Aplicar Cupón
                            </button>
                        <?php endif; ?>
                    </form>

                    <?php if (!empty($mensaje_cupon)): ?>
                        <p class="text-xs mt-3 <?php echo (strpos($mensaje_cupon, 'exitoso') !== false || strpos($mensaje_cupon, 'aplicado') !== false) ? 'text-green-600' : 'text-red-500'; ?> font-semibold">
                            <?= htmlspecialchars($mensaje_cupon) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-xl border border-pink-200 sticky top-4">
                    <h2 class="text-2xl font-bold text-pink-700 mb-6 border-b-4 border-pink-300 pb-3">Resumen del Pedido</h2>
                    
                    <div class="space-y-3 mb-6">
                        
                        <div class="flex justify-between text-gray-700">
                            <span>Subtotal (<?= count($productos_en_carrito) ?> Productos)</span>
                            <span class="font-semibold">$<?= number_format($subtotal_total, 2) ?></span>
                        </div>
                        
                        <?php if ($descuento_monto > 0): ?>
                            <div class="flex justify-between text-green-600 border-b border-dashed border-green-300 pb-2">
                                <span>Descuento (<?= htmlspecialchars($cupon_aplicado) ?>)</span>
                                <span class="font-semibold">-$<?= number_format($descuento_monto, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-700 font-medium">
                                <span>Subtotal con Descuento</span>
                                <span class="font-semibold">$<?= number_format($subtotal_con_descuento, 2) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="flex justify-between text-gray-700">
                            <span>Costo de Envío</span>
                            <span class="font-semibold <?= ($envio_gratis ? 'text-green-600' : 'text-gray-700') ?>">
                                <?= ($envio_gratis ? 'GRATIS' : '$' . number_format($costo_envio, 2)) ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between text-lg font-bold pt-4 border-t border-gray-200">
                            <span>Total Estimado</span>
                            <span class="text-pink-700">$<?= number_format($total_estimado, 2) ?></span>
                        </div>
                    </div>

                    <a href="checkout.php" class="w-full block text-center bg-[#f28bb2] hover:bg-[#e56a96] text-white font-extrabold py-3 rounded-xl transition duration-200 shadow-lg text-lg">
                        Proceder al Pago
                    </a>
                </div>
                
            </div>

        </div>

    <?php endif; ?>

</div>

<?php 
if ($conn) $conn->close();
// Incluimos el footer
include '../includes/footer.php'; 
?>