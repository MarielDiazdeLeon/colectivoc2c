<?php
// Define el título de la página
$page_title = "Checkout | Pago Seguro";

// Incluir configuración, sesión y funciones
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

// NO REQUERIR INICIO DE SESIÓN AQUÍ para permitir el checkout de invitados

$conn = connect_db();

// Variables iniciales
$carrito = $_SESSION['carrito'] ?? [];
$subtotal_total = 0;
$descuento_monto = 0.00;
$costo_envio = 0.00;
$iva_tasa = 0.16; // 🚨 Tasa del 16% 🚨
$iva_monto = 0.00;
$total_final = 0.00;
$productos_en_carrito = []; 
$cupon_aplicado = $_SESSION['cupon_aplicado'] ?? '';
$error_mp = '';
$email_cliente = '';
$nombre_cliente = '';

// Almacenar datos del cupón para el registro (solo se usa si es válido)
$cupon_data_used = null; 


// =========================================================================
// 1. LÓGICA DE CÁLCULO DEL CARRITO Y TOTALES
// =========================================================================

if (!empty($carrito)) {

    $ids = implode(',', array_keys($carrito));
    $sql = "SELECT id, nombre, precio, stock, id_colectivo 
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
                    'title' => $producto_db['nombre'],
                    'quantity' => $cantidad,
                    'unit_price' => $precio_unitario,
                    'stock' => (int)$producto_db['stock'], 
                    'subtotal' => $subtotal_producto,
                    'id_colectivo' => $producto_db['id_colectivo'] ?? 0,
                ];
            }
        }
    }
}

// --- Aplicar descuento (Lógica Simplificada y Estable) ---
if (!empty($cupon_aplicado)) {
    // 🚨 CONSULTA ESTABLE: Solo verifica que el cupón exista y esté activo. 🚨
    $sql_cupon = "SELECT * FROM cupones WHERE codigo = ? AND activo = 1"; 
    $stmt_cupon = $conn->prepare($sql_cupon);
    $stmt_cupon->bind_param("s", $cupon_aplicado);
    $stmt_cupon->execute();
    $cupon_data = $stmt_cupon->get_result()->fetch_assoc();
    
    if ($cupon_data) {
        $valor = (float)$cupon_data['valor'];
        $tipo_descuento = $cupon_data['tipo'];
        
        if ($tipo_descuento == 'porcentaje') {
            $descuento_monto = $subtotal_total * ($valor / 100);
        } else {
            $descuento_monto = $valor;
        }
        $descuento_monto = min($descuento_monto, $subtotal_total);

        $cupon_data_used = true; // El cupón se aplicó con éxito.
    }
    if (isset($stmt_cupon)) $stmt_cupon->close();
}

$subtotal_con_descuento = $subtotal_total - $descuento_monto;

// --- Calcular Envío y IVA ---
$costo_envio_base = 100.00; 
$envio_gratis = ($subtotal_con_descuento >= 300); 

if ($envio_gratis) {
    $costo_envio = 0.00;
} else {
    $costo_envio = $costo_envio_base;
}

// 🚨 CÁLCULO DEL IVA 🚨
$iva_monto = $subtotal_con_descuento * $iva_tasa;

// 🚨 TOTAL FINAL 🚨
$total_final = $subtotal_con_descuento + $costo_envio + $iva_monto;


// =========================================================================
// 2. Lógica para generar la preferencia de pago (SIMULACIÓN Y REGISTRO BD)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proceder_pago'])) {
    
    // A. Recoger y validar datos del invitado
    $nombre_cliente = trim($_POST['nombre_completo'] ?? '');
    $email_cliente = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    
    // Datos de la tarjeta SIMULADA
    $card_number = str_replace(' ', '', trim($_POST['card_number'] ?? ''));
    $card_holder = trim($_POST['card_holder'] ?? '');
    $card_exp_date = trim($_POST['card_exp_date'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');
    
    
    // Validación básica de campos de envío
    if (empty($nombre_cliente) || empty($email_cliente) || empty($direccion) || empty($ciudad) || empty($cp) || $total_final <= 0) {
        $error_mp = "Por favor, complete todos los campos de envío.";
    } 
    
    // Validación básica de tarjeta simulada
    if (empty($card_number) || empty($card_holder) || empty($card_cvv) || strlen($card_number) < 13) {
         $error_mp = "Por favor, ingrese todos los datos de la tarjeta simulada.";
    }


    if (empty($error_mp)) {
        
        $external_reference = "ORD-" . time(); 
        $order_success = false;
        
        // 🚨 LÓGICA DE EXTRACCIÓN Y REGISTRO DE PAGO 🚨
        $card_type_id = (substr($card_number, 0, 1) === '4') ? 1 : 2; // 1=Visa, 2=Mastercard
        
        // Lógica para nombre y placeholder
        $name_parts = explode(' ', $nombre_cliente, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? 'N/A'; 
        $id_usuario_existente = 21; 

        // 🚨 INICIAR TRANSACCIÓN 🚨
        $conn->begin_transaction();
        
        try {
            // =======================================================
            // PASO 1: REGISTRAR LA CABECERA DEL PEDIDO (Tabla: pedidos)
            // =======================================================
            
            $sql_pedido = "INSERT INTO pedidos (
                                id_usuario, nombre_cliente, apellido_cliente, email_cliente, 
                                id_metodo_pago, numero_tarjeta, titular_tarjeta, fecha_vencimiento, 
                                fecha, total, direccion_envio, estado
                           ) VALUES (
                                ?, ?, ?, ?, 
                                ?, ?, ?, ?,
                                NOW(), ?, ?, 'Pendiente'
                           )"; 
            $stmt_pedido = $conn->prepare($sql_pedido);
            
            $direccion_completa_registro = "{$direccion}, {$ciudad}, {$cp}.";

            // Parámetros (Total 10): id(i), nombre(s), apellido(s), email(s), id_pago(i), num_tarjeta(s), titular(s), fecha_exp(s), total(d), direccion(s) -> "isssisssds"
            $stmt_pedido->bind_param("isssisssds", 
                $id_usuario_existente,
                $first_name,
                $last_name,
                $email_cliente, 
                $card_type_id,              // ID del método de pago (FK)
                $card_number,               // Número de tarjeta
                $card_holder,               // Titular de la tarjeta
                $card_exp_date,             // Fecha de vencimiento
                $total_final, 
                $direccion_completa_registro
            );
            $stmt_pedido->execute();
            $pedido_id = $conn->insert_id; 
            $stmt_pedido->close();
            
            if (!$pedido_id) {
                throw new Exception("Error al crear la cabecera del pedido.");
            }
            
            // =======================================================
            // PASO 2: REGISTRAR DETALLES Y DESCONTAR STOCK
            // =======================================================
            foreach ($productos_en_carrito as $item) {
                $item_id = $item['id'];
                $item_qty = $item['quantity'];
                $item_price = (float)$item['unit_price'];

                $sql_detalle = "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad, precio_unitario) 
                                VALUES (?, ?, ?, ?)";
                $stmt_detalle = $conn->prepare($sql_detalle);
                $stmt_detalle->bind_param("iiid", $pedido_id, $item_id, $item_qty, $item_price);
                $stmt_detalle->execute();
                $stmt_detalle->close();

                $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $stmt_stock = $conn->prepare($sql_stock);
                $stmt_stock->bind_param("iii", $item_qty, $item_id, $item_qty);
                $stmt_stock->execute();
                
                if ($conn->affected_rows === 0) {
                    throw new Exception("Stock insuficiente para el producto ID: {$item_id}.");
                }
                $stmt_stock->close();
            }

            // 🚨 PASO 2.5: SE OMITIÓ LA LÓGICA DE ACTUALIZACIÓN DE CUPONES (USOS/FECHAS) para mayor estabilidad.

            $conn->commit();
            $order_success = true;

        } catch (Exception $e) {
            $conn->rollback();
            $error_mp = "Error de transacción: " . htmlspecialchars($e->getMessage());
            $order_success = false;
        }

        // =======================================================
        // PASO 3: REDIRECCIÓN (Solo si la transacción fue exitosa)
        // =======================================================
       if ($order_success) {
            // Sincronización de variables de sesión para pago_exitoso.php
            $_SESSION['pedido_confirmado'] = [
                'id_pedido_bd' => $pedido_id,
                'total' => $total_final,
                'subtotal' => $subtotal_total, // Subtotal ANTES del descuento
                'envio' => $costo_envio,
                'descuento' => $descuento_monto,
                'iva' => $iva_monto, 
                'cliente' => $nombre_cliente, 
                'email' => $email_cliente,
                'items' => $productos_en_carrito, // Arreglo de artículos
                'direccion' => $direccion_completa_registro, 
                'referencia' => $external_reference, 
            ];
            
            unset($_SESSION['carrito']);
            unset($_SESSION['cupon_aplicado']);
            
            $simulacion_url = "pago_exitoso.php?ref={$external_reference}";
            header("Location: {$simulacion_url}");
            exit;
        }
    }
}


// Incluimos el header después de la lógica PHP
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <h1 class="text-4xl font-extrabold text-pink-700 mb-10 border-b-4 border-pink-300 pb-4">
        Finalizar Compra
    </h1>

    <?php if (isset($error_mp) && !empty($error_mp)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Error de Proceso</p>
            <p><?= htmlspecialchars($error_mp) ?></p>
        </div>
    <?php endif; ?>

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white p-6 rounded-xl shadow-xl border border-pink-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">1. Datos de Contacto y Envío</h2>
                
                <form id="checkout-form" method="POST" action="checkout.php" class="space-y-4">
                    <input type="hidden" name="proceder_pago" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                            <input type="text" name="nombre_completo" value="<?= htmlspecialchars($nombre_cliente) ?>" required
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Correo Electrónico (Para seguimiento)</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($email_cliente) ?>" placeholder="ejemplo@correo.com" required
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Calle y Número (o referencias)</label>
                            <input type="text" name="direccion" placeholder="Ej: Av. Juárez #123 y Calle 5" required
                            class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                            <input type="text" name="ciudad" required
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Postal</label>
                            <input type="text" name="cp" required
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>

                    <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                            <img src="https://img.icons8.com/color/48/000000/mercado-pago.png" alt="Mercado Pago" class="w-6 h-6 mr-2"> 
                            2. Datos de la Tarjeta (Simulación)
                        </h3>

                        <div>
                            <label for="card_number" class="block text-sm font-medium text-gray-700">Número de Tarjeta</label>
                            <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required maxlength="16"
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <div class="mt-4">
                            <label for="card_holder" class="block text-sm font-medium text-gray-700">Nombre del Titular</label>
                            <input type="text" id="card_holder" name="card_holder" placeholder="Como aparece en la tarjeta" required
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div class="col-span-2">
                                <label for="card_exp_date" class="block text-sm font-medium text-gray-700">Fecha de Vencimiento (MM/YY)</label>
                                <input type="text" id="card_exp_date" name="card_exp_date" placeholder="MM/YY" required maxlength="5"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div class="col-span-1">
                                <label for="card_cvv" class="block text-sm font-medium text-gray-700">CVV</label>
                                <input type="password" id="card_cvv" name="card_cvv" placeholder="CVV" required maxlength="3"
                                    class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>
        
        <div class="lg:col-span-1 mt-8 lg:mt-0">
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-pink-200 sticky top-4">
                <h2 class="text-2xl font-bold text-pink-700 mb-6 border-b-4 border-pink-300 pb-3">Resumen Final</h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-gray-700">
                        <span>Subtotal Neto</span>
                        <span class="font-semibold">$<?= number_format($subtotal_con_descuento, 2) ?></span>
                    </div>
                    
                    <?php if ($descuento_monto > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Descuento Aplicado (<?= htmlspecialchars($cupon_aplicado ?: 'N/A') ?>)</span>
                            <span class="font-semibold">-$<?= number_format($descuento_monto, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between text-gray-700 border-t border-dashed pt-1">
                        <span>IVA (<?= $iva_tasa * 100 ?>%)</span>
                        <span class="font-semibold">$<?= number_format($iva_monto, 2) ?></span>
                    </div>
                    
                    <div class="flex justify-between text-gray-700">
                        <span>Costo de Envío</span>
                        <span class="font-semibold <?= $costo_envio == 0 ? 'text-green-600' : 'text-gray-700' ?>">
                            <?= $costo_envio == 0 ? 'GRATIS' : '$' . number_format($costo_envio, 2) ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between text-xl font-bold pt-4 border-t border-gray-200">
                        <span>Total a Pagar</span>
                        <span class="text-pink-700">$<?= number_format($total_final, 2) ?></span>
                    </div>
                </div>

                <button type="submit" form="checkout-form" class="w-full block text-center bg-[#f28bb2] hover:bg-[#e56a96] text-white font-extrabold py-3 rounded-xl transition duration-200 shadow-lg text-lg">
                    Confirmar Compra
                </button>
            </div>
        </div>

    </div>
</div>

<?php 
if ($conn) $conn->close();
include '../includes/footer.php'; 
?>