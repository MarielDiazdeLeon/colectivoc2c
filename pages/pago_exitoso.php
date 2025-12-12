<?php
// Define el título de la página
$page_title = "¡Pedido Confirmado! | Colectivo C2C";

require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 
include '../includes/header.php'; 

// Cargar los datos confirmados de la sesión
$pedido = $_SESSION['pedido_confirmado'] ?? null;
$referencia_mp = $_GET['ref'] ?? 'N/A';

// Si no hay datos en la sesión, crear un arreglo de contingencia
if (!$pedido) {
    $pedido = [
        'id_pedido_bd' => 'N/A',
        'total' => 0.00,
        'subtotal' => 0.00,
        'envio' => 0.00,
        'descuento' => 0.00,
        'iva' => 0.00,
        'cliente' => 'Cliente Desconocido',
        'email' => 'N/A',
        'direccion' => 'Dirección no registrada.',
        'referencia' => $referencia_mp,
        'items' => [],
        'error' => 'No se encontraron detalles del pedido. Por favor, contacte a soporte.'
    ];
}

$items = is_array($pedido['items']) ? $pedido['items'] : [];
unset($_SESSION['pedido_confirmado']);
?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow-2xl border-t-8 border-pink-500">
        
        <div class="text-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-20 w-20 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h1 class="text-4xl font-extrabold text-gray-800 mt-4">¡Pedido Confirmado!</h1>
            <p class="text-lg text-gray-600 mt-2">Hemos recibido tu orden y se está procesando.</p>
        </div>
        
        <?php if (isset($pedido['error'])): ?>
            <div class="bg-red-100 p-4 rounded-lg text-red-800 border border-red-300">
                <?= htmlspecialchars($pedido['error']) ?>
            </div>
        <?php else: ?>
        
            <div class="space-y-4 border-b pb-6 mb-6">
                <p class="text-sm font-medium text-gray-500">Número de Pedido: <span class="font-bold text-gray-700">#<?= htmlspecialchars($pedido['id_pedido_bd']) ?></span></p>
                <p class="text-sm font-medium text-gray-500">Referencia de Transacción: <span class="text-gray-500 text-xs"><?= htmlspecialchars($pedido['referencia']) ?></span></p>

                <p class="text-sm font-medium text-gray-500">Comprador: <span class="font-bold text-gray-700"><?= htmlspecialchars($pedido['cliente']) ?></span></p>
                <p class="text-sm font-medium text-gray-500">Email: <span class="text-gray-700"><?= htmlspecialchars($pedido['email']) ?></span></p>
                <p class="text-sm font-medium text-gray-500">Dirección de Envío: <span class="text-gray-700"><?= htmlspecialchars($pedido['direccion']) ?></span></p>
            </div>
            
            <div class="text-xl font-semibold mb-4 text-gray-800">Productos Incluidos</div>
            <ul class="space-y-2 mb-6 border-b pb-4">
                <?php foreach ($items as $item): ?>
                    <li class="flex justify-between text-sm text-gray-700">
                        <span><?= htmlspecialchars($item['quantity']) ?> x <?= htmlspecialchars($item['title']) ?></span>
                        <span class="font-medium">$<?= number_format($item['subtotal'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="text-xl font-semibold mb-4 text-gray-800">Resumen de Pago</div>
            <div class="space-y-2 text-sm">
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal de Productos:</span>
                    <span class="font-medium">$<?= number_format($pedido['subtotal'], 2) ?></span>
                </div>

                <?php if ($pedido['descuento'] > 0): ?>
                    <div class="flex justify-between text-green-700">
                        <span>Descuento Aplicado:</span>
                        <span class="font-medium">-$<?= number_format($pedido['descuento'], 2) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($pedido['iva'] > 0): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">IVA (16%):</span>
                        <span class="font-medium">$<?= number_format($pedido['iva'], 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Costo de Envío:</span>
                    <span class="font-medium"><?= $pedido['envio'] == 0 ? 'GRATIS' : '$' . number_format($pedido['envio'], 2) ?></span>
                </div>

                <div class="flex justify-between text-2xl font-extrabold pt-3 border-t border-gray-300">
                    <span>Total Pagado:</span>
                    <span class="text-pink-700">$<?= number_format($pedido['total'], 2) ?></span>
                </div>
            </div>
            
            <div class="mt-8 text-center space-x-4">
                <a href="../index.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-pink-600 hover:bg-pink-700 transition">
                    Volver a la Tienda
                </a>

                <button onclick="descargarTicket()" 
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition">
                    Descargar Ticket PDF
                </button>
            </div>

        <?php endif; ?>
        
    </div>
</div>

<!-- ============ PLANTILLA OCULTA PARA PDF ============ -->
<div id="ticketPDF" style="display:none;">

<style>
.ticket-container {
    font-family: Arial, sans-serif;
    padding: 20px;
    width: 600px;
    margin: auto;
    color: #333;
}
.ticket-header {
    text-align: center;
    border-bottom: 2px solid #e91e63;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.ticket-title {
    font-size: 28px;
    font-weight: bold;
    color: #e91e63;
}
.ticket-section {
    margin-bottom: 15px;
}
.ticket-section h3 {
    margin-bottom: 5px;
    color: #555;
}
.ticket-items li {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dashed #ccc;
    padding: 4px 0;
}
</style>

<div class="ticket-container">
    <div class="ticket-header">
        <div class="ticket-title">Ticket de Compra</div>
        <p>Pedido #<?= htmlspecialchars($pedido['id_pedido_bd']) ?></p>
    </div>

    <div class="ticket-section">
        <h3>Datos del Comprador</h3>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
        <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion']) ?></p>
        <p><strong>Referencia:</strong> <?= htmlspecialchars($pedido['referencia']) ?></p>
    </div>

    <div class="ticket-section">
        <h3>Productos</h3>
        <ul class="ticket-items">
            <?php foreach ($items as $item): ?>
                <li>
                    <span><?= htmlspecialchars($item['quantity']) ?> x <?= htmlspecialchars($item['title']) ?></span>
                    <span>$<?= number_format($item['subtotal'], 2) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="ticket-section">
        <h3>Resumen</h3>
        <p><strong>Subtotal:</strong> $<?= number_format($pedido['subtotal'], 2) ?></p>

        <?php if ($pedido['descuento'] > 0): ?>
            <p><strong>Descuento:</strong> -$<?= number_format($pedido['descuento'], 2) ?></p>
        <?php endif; ?>

        <?php if ($pedido['iva'] > 0): ?>
            <p><strong>IVA 16%:</strong> $<?= number_format($pedido['iva'], 2) ?></p>
        <?php endif; ?>

        <p><strong>Envío:</strong> <?= $pedido['envio'] == 0 ? 'GRATIS' : '$' . number_format($pedido['envio'], 2) ?></p>

        <p style="font-size: 22px; font-weight: bold; margin-top: 10px;">
            Total Pagado: $<?= number_format($pedido['total'], 2) ?>
        </p>
    </div>
</div>
</div>
<!-- FIN TICKET PDF -->

<script>
function descargarTicket() {
    const ticket = document.getElementById("ticketPDF").innerHTML;
    const ventana = window.open("", "_blank", "width=800,height=900");

    ventana.document.write(`
        <html>
            <head>
                <title>Ticket de Compra</title>
            </head>
            <body>
                ${ticket}
            </body>
        </html>
    `);

    ventana.document.close();

    setTimeout(() => {
        ventana.print();
        ventana.close();
    }, 500);
}
</script>

<?php
if ($conn) $conn->close();
include '../includes/footer.php'; 
?>
