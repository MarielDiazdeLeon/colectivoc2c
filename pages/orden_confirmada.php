<?php
$page_title = "Orden Confirmada | Colectivo C2C";

// CORRECCIÓN DE RUTA
include '../includes/header.php';

$base_url = '/colectivo_c2c';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<main class="container mx-auto px-4 py-12 min-h-screen">

    <div class="max-w-xl mx-auto bg-white p-10 rounded-2xl shadow-2xl text-center 
                border-t-8 border-pink-300">

        <!-- Ícono cute -->
        <svg class="mx-auto h-20 w-20 text-pink-500 mb-6" 
             xmlns="http://www.w3.org/2000/svg" fill="none" 
             viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" 
                  stroke-width="2" 
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <h1 class="text-4xl font-extrabold text-pink-600 mb-4">
            ¡Orden Confirmada!
        </h1>

        <?php if ($order_id > 0): ?>
            <p class="text-xl text-gray-700 mb-4">
                Tu compra fue procesada con éxito.
            </p>

            <p class="text-3xl font-bold text-pink-500 mb-6">
                Número de Orden: 
                <span class="text-purple-600">
                    #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </span>
            </p>

        <?php else: ?>

            <p class="text-xl text-gray-700 mb-8">
                Tu pedido fue procesado, pero no se pudo obtener el número de orden.
            </p>

        <?php endif; ?>

        <p class="text-gray-600 mb-10">
            Recibirás un correo de confirmación con los detalles y seguimiento de tu compra.
        </p>

        <!-- Botones Cute -->
        <div class="space-y-4">

            <!-- Botón rosa principal -->
            <a href="<?php echo $base_url; ?>\index.php"
               class="inline-block w-full bg-pink-500 text-white py-3 rounded-xl text-lg font-bold
                      hover:bg-pink-600 active:bg-pink-700 transition shadow-lg">
                Continuar Comprando
            </a>

            <!-- Botón lavanda secundario -------------------------------------------------:::::::::-->
            <a href="<?php echo $base_url; ?>/pages/perfil.php"
               class="inline-block w-full py-3 rounded-xl text-lg font-bold
                      bg-purple-200 text-purple-700 border border-purple-300
                      hover:bg-purple-300 active:bg-purple-400 transition shadow-md">
                Ver Historial de Órdenes (Próximamente)
            </a>
        </div>

        <!-- Mensaje cute -->
        <p class="mt-6 text-sm text-pink-500 font-semibold">
            🌸 ¡Gracias por apoyar a emprendedores y comprar en Colectivo C2C! 🌸
        </p>

    </div>
</main>

<?php include '../includes/footer.php'; ?>
