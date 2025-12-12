<?php
$page_title = "Confirmación de Pedido | Colectivo C2C";
require_once __DIR__ . '/../includes/header.php';

// 1. Obtener el ID del pedido de la URL
$pedido_id = isset($_GET['pedido']) ? intval($_GET['pedido']) : 0;
$pedido = null;
$detalles = [];

if ($pedido_id > 0) {
    // 2. Consultar la información del pedido
    $sql_pedido = "SELECT * FROM pedidos WHERE id = ?";
    if ($stmt_pedido = $conn->prepare($sql_pedido)) {
        $stmt_pedido->bind_param("i", $pedido_id);
        $stmt_pedido->execute();
        $result_pedido = $stmt_pedido->get_result();
        $pedido = $result_pedido->fetch_assoc();
        $stmt_pedido->close();
    }

    // 3. Consultar los detalles (productos) del pedido
    $sql_detalles = "SELECT 
                        dp.cantidad, 
                        dp.precio_unitario, 
                        p.nombre 
                     FROM detalle_pedido dp
                     JOIN productos p ON dp.id_producto = p.id
                     WHERE dp.id_pedido = ?";
    if (in_array("query", ["select", "insert", "update", "delete", "drop", "create"])) {
        if ($stmt_detalles = $conn->prepare($sql_detalles)) {
            $stmt_detalles->bind_param("i", $pedido_id);
            $stmt_detalles->execute();
            $result_detalles = $stmt_detalles->get_result();
            while($row = $result_detalles->fetch_assoc()) {
                $detalles[] = $row;
            }
            $stmt_detalles->close();
        }
    } else {
        $result = $conn->query($sql_detalles);
        while ($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
    }
}

// Si no se encontró el pedido, mostrar error
if (!$pedido) {
    echo '
        <div class="max-w-4xl mx-auto py-20 text-center">
            <h2 class="text-4xl font-bold text-red-600 mb-4">Error: Pedido No Encontrado</h2>
            <p class="text-lg text-gray-600">No pudimos encontrar la información de su pedido. Por favor, revise su correo electrónico.</p>

            <!-- BOTÓN ROSA CORREGIDO -->
            <a href="../index.php" class="mt-6 inline-block bg-pink-500 text-white px-6 py-3 rounded-xl
               font-semibold hover:bg-pink-600 active:bg-pink-700 transition duration-150 shadow-md">
                Volver al Catálogo
            </a>
        </div>
    ';
    include '../includes/footer.php';
    exit();
}

// Calcular totales
$subtotal = $pedido['total'] - ($pedido['total'] == 99.00 || $pedido['total'] > 500 ? 0.00 : 99.00);
$costo_envio = $pedido['total'] - $subtotal;
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="bg-white rounded-2xl shadow-2xl p-8 sm:p-12 text-center border-t-8 border-pink-300">
        
        <!-- Ícono -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto text-pink-500 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <h1 class="text-4xl font-extrabold text-pink-600 mb-3">
            ¡Tu Pedido ha sido Confirmado!
        </h1>

        <p class="text-xl text-gray-600 mb-6">
            Gracias por tu compra en <strong>Colectivo C2C</strong>. Tu pedido ha sido registrado con éxito.
        </p>

        <!-- Número de Pedido -->
        <div class="inline-block bg-pink-50 rounded-xl p-4 mb-8 border border-pink-200">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest">Número de Pedido</p>
            <p class="text-3xl font-bold text-pink-500 mt-1">#<?php echo htmlspecialchars($pedido['id']); ?></p>
            <p class="text-md font-semibold text-pink-600 mt-2">Estado: <?php echo htmlspecialchars($pedido['estado']); ?></p>
        </div>

        <!-- Dirección + Pago -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left mb-10">
            
            <div class="p-5 bg-pink-50 rounded-xl border border-pink-200">
                <h2 class="text-xl font-bold text-pink-600 mb-2 border-b pb-1">Dirección de Envío</h2>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></p>
                <p class="text-sm text-gray-500 mt-2">Fecha del Pedido: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
            </div>

            <div class="p-5 bg-pink-50 rounded-xl border border-pink-200">
                <h2 class="text-xl font-bold text-pink-600 mb-2 border-b pb-1">Resumen del Pago</h2>
                
                <div class="flex justify-between text-gray-700">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>

                <div class="flex justify-between text-gray-700">
                    <span>Envío:</span>
                    <span>$<?php echo number_format($costo_envio, 2); ?></span>
                </div>

                <div class="flex justify-between text-2xl font-bold text-gray-900 mt-2 pt-2 border-t border-pink-300">
                    <span>Total Final:</span>
                    <span>$<?php echo number_format($pedido['total'], 2); ?> MXN</span>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <h2 class="text-2xl font-bold text-pink-600 mb-4 text-left border-b pb-2">Productos Comprados</h2>

        <ul class="space-y-3 mb-10 text-left">
            <?php foreach ($detalles as $item): ?>
                <li class="flex justify-between items-center text-gray-700 border-b pb-2 border-pink-200">
                    <span class="font-semibold text-pink-700"><?php echo htmlspecialchars($item['nombre']); ?></span>
                    <span class="text-sm text-gray-500">x<?php echo $item['cantidad']; ?></span>
                    <span class="font-bold text-pink-600">
                        $<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Botones -->
        <div class="mt-8 pt-6 border-t border-pink-200 flex justify-center space-x-4">

            <!-- Botón Rosa -->
            <a href="../index.php" 
               class="bg-pink-500 text-white px-6 py-3 rounded-xl font-semibold 
                      hover:bg-pink-600 active:bg-pink-700 focus:bg-pink-700 
                      transition duration-150 shadow-md">
                Seguir Comprando
            </a>

            <!-- Botón Lavanda -->
            <a href="#" 
               class="bg-purple-200 text-purple-700 px-6 py-3 rounded-xl font-semibold 
                      hover:bg-purple-300 active:bg-purple-400 focus:bg-purple-400 
                      transition duration-150 shadow-md">
                Ver Historial de Pedidos (Próximamente)
            </a>
        </div>

        <p class="mt-6 text-sm text-pink-500 font-semibold text-center">
            🌸 ¡Gracias por apoyar a emprendedores y comprar en Colectivo CDI! 🌸
        </p>

    </div>
</div>

<?php include '../includes/footer.php'; ?>