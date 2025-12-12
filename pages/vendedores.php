<?php
// Título de la página
$page_title = "Nuestros Vendedores y Socios | Colectivo C2C";
include '../includes/header.php';

// --- CORRECCIÓN CRÍTICA: Inicializar la conexión a la base de datos ---
// Esto resuelve el "Undefined variable $conn"
$conn = connect_db();

// --- Lógica de Obtención de Vendedores ---
// Verifica que la conexión fue exitosa antes de hacer la consulta
$vendedores = [];
if ($conn) {
    $sql = "SELECT id, nombre, apellido, email, telefono, fecha_registro 
            FROM usuarios 
            WHERE rol = 'vendedor' AND activo = 1 
            ORDER BY nombre ASC";
    $result = $conn->query($sql);

    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vendedores[] = $row;
        }
    }
    
    // Cerrar la conexión para liberar recursos
    $conn->close();
}
?>

<!-- CONTENEDOR PRINCIPAL -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- TÍTULO PRINCIPAL -->
    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold text-gray-900 border-b-4 border-pink-400 inline-block pb-2">
            Conoce a Nuestros Socios Vendedores
        </h1>
        <p class="mt-4 text-xl text-gray-600">
            Personas y negocios detrás de los productos que amas.
        </p>
    </div>

    <?php if (empty($vendedores)): ?>
        
        <!-- ESTADO: VACÍO -->
        <div class="text-center py-20 bg-white rounded-xl shadow-lg border border-pink-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 mx-auto text-pink-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a1 1 0 00-1-1H3a1 1 0 00-1 1v1h5m8 0h-2m-3 0H5m4 0H4a2 2 0 01-2-2V4a2 2 0 012-2h16a2 2 0 012 2v14a2 2 0 01-2 2h-1l-3 3-3-3H7a2 2 0 01-2-2v-1z" />
            </svg>
            <div class="text-2xl font-semibold text-gray-700 mb-2">Aún no hay vendedores registrados.</div>
            <p class="text-gray-500">Estamos trabajando para incorporar a más socios talentosos.</p>
        </div>

    <?php else: ?>

        <!-- GRID DE VENDEDORES -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

            <?php foreach ($vendedores as $vendedor): ?>
                <div class="bg-white p-6 rounded-xl shadow-xl border border-pink-200 hover:shadow-pink-300/50 transition duration-300 flex flex-col items-center text-center">

                    <!-- ICONO / IMAGEN -->
                    <div class="bg-pink-100 text-pink-500 rounded-full p-4 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>

                    <!-- NOMBRE -->
                    <h3 class="font-bold text-xl text-gray-900 mb-2">
                        <?= htmlspecialchars($vendedor['nombre'] . ' ' . $vendedor['apellido']) ?>
                    </h3>

                    <!-- INFORMACIÓN ADICIONAL (Opcional) -->
                    <p class="text-xs text-gray-400 mb-3">
                        Miembro desde: <?= date('d/M/Y', strtotime($vendedor['fecha_registro'])) ?>
                    </p>

                    <!-- CONTACTO -->
                    <div class="space-y-1 text-sm text-gray-600 w-full mt-2">

                        <p class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <?= htmlspecialchars($vendedor['email']) ?>
                        </p>

                        <p class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <?= htmlspecialchars($vendedor['telefono'] ?: 'No disponible') ?>
                        </p>

                    </div>

                    <!-- BOTÓN -->
                    <a href="../index.php?vendedor_id=<?= $vendedor['id'] ?>" 
                       class="mt-4 text-pink-500 hover:text-pink-600 font-semibold text-sm border border-pink-500 hover:border-pink-600 rounded-full px-4 py-1 transition duration-150">
                        Ver sus productos
                    </a>

                </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php 
// Esta es la línea que probablemente tenía un error de sintaxis previo.
// La separamos en su propio bloque limpio para asegurar la correcta interpretación.
include '../includes/footer.php'; 
?>