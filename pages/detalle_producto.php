<?php
// ==========================================================
// DETALLE PRODUCTO (pages/detalles_producto.php) - VERSIÓN FINAL Y ROBUSTA
// ==========================================================

// 1. INICIAR SESIÓN Y CARGAR DEPENDENCIAS CLAVE
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$base_url = '/colectivo_c2c';

require_once __DIR__ . '/../config/db.php'; 
$conn = connect_db(); // La conexión se establece al inicio.

// Inicialización de variables clave
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensaje_reseña = '';
$producto = false;
$reseñas = [];
$promedio_calificacion = 0.0;
$total_reseñas = 0;


// 2. LÓGICA PARA AGREGAR RESEÑA (DEBE ESTAR AL INICIO SI REQUIERE REDIRECCIÓN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    
    // Si la conexión falló (ej. DB apagada)
    if (!$conn) {
        $_SESSION['review_error'] = '<p class="text-red-600 font-bold mb-4">Error interno: No se pudo establecer la conexión a la Base de Datos.</p>';
        header("Location: detalle_producto.php?id=" . $producto_id . "#reseñas");
        exit();
    }
    
    // Sanitización básica de datos
    $nombre = $conn->real_escape_string(trim($_POST['nombre'] ?? 'Cliente Anónimo'));
    $calificacion = (int)($_POST['calificacion'] ?? 0);
    $comentario = $conn->real_escape_string(trim($_POST['comentario'] ?? ''));

    if ($calificacion >= 1 && $calificacion <= 5 && !empty($comentario)) {
     
        $sql_insert = "
            INSERT INTO reseñas (id_producto, nombre_cliente, calificacion, comentario, fecha) 
            VALUES (?, ?, ?, ?, NOW())
        ";
        $stmt_insert = $conn->prepare($sql_insert);
        
        // Verifica si la preparación de la consulta falló
        if (!$stmt_insert) {
            $_SESSION['review_error'] = '<p class="text-red-600 font-bold mb-4">Error en la consulta preparada: ' . $conn->error . '</p>';
            header("Location: detalle_producto.php?id=" . $producto_id . "#reseñas");
            exit();
        }

        $stmt_insert->bind_param("isis", $producto_id, $nombre, $calificacion, $comentario); 

        if ($stmt_insert->execute()) {
            header("Location: detalle_producto.php?id=" . $producto_id . "&review_status=success#reseñas");
            exit();
        } else {
            $_SESSION['review_error'] = '<p class="text-red-600 font-bold mb-4">Error al guardar la reseña: ' . $stmt_insert->error . '</p>';
            header("Location: detalle_producto.php?id=" . $producto_id . "#reseñas");
            exit();
        }
        $stmt_insert->close();
    } else {
        $_SESSION['review_error'] = '<p class="text-red-600 font-bold mb-4">Por favor, selecciona una calificación y escribe un comentario.</p>';
        header("Location: detalle_producto.php?id=" . $producto_id . "#reseñas");
        exit();
    }
}


// 3. INICIO DE OUTPUT HTML Y MANEJO DE MENSAJES (Despues de la redirección)
$page_title = "Detalle del Producto | Colectivo CDI";
include '../includes/header.php'; // Incluye la estructura base HTML

// Manejo de mensajes (Usamos la URL o la Sesión)
if (isset($_GET['review_status']) && $_GET['review_status'] === 'success') {
    $mensaje_reseña = '<p class="text-green-600 font-bold mb-4">¡Gracias! Tu reseña ha sido agregada con éxito.</p>';
} elseif (isset($_SESSION['review_error'])) {
    $mensaje_reseña = $_SESSION['review_error'];
    unset($_SESSION['review_error']); // Limpiar el error
}


// Estilos personalizados para las estrellas y el zoom
echo '<style>
/* Estilos para el Zoom */
.zoom-container {
    overflow: hidden;
    cursor: zoom-in;
    position: relative; /* CRÍTICO para el zoom */
}
.zoom-image {
    transition: transform 0.3s ease-out;
    transform-origin: center center;
    pointer-events: none; /* Evita que el ratón interactúe con la imagen durante el movimiento */
}
.zoom-image.zoomed {
    transform: scale(2);
}

/* Estilos para las estrellas de la reseña (usando CSS puro para compatibilidad) */
.star-rating input[type="radio"] {
display: none;
}
.star-rating label {
font-size: 2rem;
color: #cbd5e1; /* Gris claro */
cursor: pointer;
transition: color 0.2s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input[type="radio"]:checked ~ label {
color: #f59e0b; /* Amarillo */
}
/* Revertir el hover para mostrar la calificación seleccionada */
.star-rating label:hover:after {
content: none;
}
/* Para que las estrellas que están a la derecha de la seleccionada no se iluminen al hacer hover */
.star-rating label:hover ~ label {
color: #cbd5e1; 
}

/* Script para la función de hover que permite seleccionar */
.star-rating {
unicode-bidi: bidi-override;
direction: rtl; /* Mueve las estrellas de derecha a izquierda */
display: inline-flex;
}
.star-rating > label {
position: relative;
width: 1.1em; /* Ajuste de ancho */
}
</style>';


// 4. EJECUCIÓN DE CONSULTAS DE LECTURA

if ($producto_id > 0 && $conn) {
    
    // CÓDIGO FINAL CORREGIDO: Asegura la limpieza del SQL y verifica la preparación.

    $sql = "
        SELECT 
            p.id, 
            p.nombre, 
            p.descripcion, 
            p.precio, 
            p.imagen_url, 
            p.stock, 
            c.nombre_marca AS colectivo_nombre, 
            c.id_usuario AS vendedor_id 
        FROM 
            productos p
        JOIN 
            colectivos c ON p.id_colectivo = c.id
        WHERE 
            p.id = ? AND p.activo = 1
    ";

    // Preparar la consulta
    $stmt = $conn->prepare($sql);

    // 🚨 VERIFICACIÓN CRÍTICA: Si la preparación falla, detente y muestra el error.
    if ($stmt === false) {
        die("Error al preparar la consulta de producto: " . $conn->error);
    }

    // Continuar solo si $stmt es un objeto Statement válido
    $stmt->bind_param("i", $producto_id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();

    if ($producto) {
        $page_title = htmlspecialchars($producto['nombre']) . " | Colectivo C2C";

        // --- CONSULTA DE RESEÑAS ---
        $sql_reseñas = "
            SELECT 
                r.calificacion, 
                r.comentario, 
                r.nombre_cliente, 
                r.fecha
            FROM 
                reseñas r
            WHERE 
                r.id_producto = ?
            ORDER BY 
                r.fecha DESC
        ";
        $stmt_r = $conn->prepare($sql_reseñas);
        $stmt_r->bind_param("i", $producto_id);
        $stmt_r->execute();
        $result_r = $stmt_r->get_result();

        while ($row_r = $result_r->fetch_assoc()) {
            $reseñas[] = $row_r;
        }
        $stmt_r->close();

        $total_reseñas = count($reseñas);

        // --- CÁLCULO DEL PROMEDIO ---
        if ($total_reseñas > 0) {
            $suma_calificaciones = array_sum(array_column($reseñas, 'calificacion'));
            $promedio_calificacion = round($suma_calificaciones / $total_reseñas, 1);
        }
    }
} 

// 5. CÓDIGO HTML DE VISUALIZACIÓN

?>

<main class="container mx-auto px-4 py-8 min-h-screen">

<?php if ($producto): ?>

<h1 class="text-4xl font-extrabold text-pink-600 mb-8 hidden sm:block">
<?php echo htmlspecialchars($producto['nombre']); ?>
</h1>

<div class="flex flex-col lg:flex-row gap-10 bg-white p-6 md:p-10 rounded-2xl shadow-2xl border-t-8 border-pink-300 mb-10">

<div class="lg:w-1/2">
    <div id="zoom-container" class="zoom-container w-full h-auto max-h-[600px] rounded-xl shadow-xl">
        <img id="zoom-image"
             src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
             alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
             class="zoom-image w-full h-auto max-h-[600px] object-cover"
             onerror="this.onerror=null;this.src='https://placehold.co/800x600/e5e7eb/4b5563?text=Imagen+No+Disponible';">
    </div>
    </div>

<div class="lg:w-1/2 flex flex-col justify-between">

<div>
<h2 class="text-3xl font-bold text-pink-600 mb-2 sm:hidden">
<?php echo htmlspecialchars($producto['nombre']); ?>
</h2>

<div class="flex items-center mb-4">
<?php if ($total_reseñas > 0): ?>
<div class="text-yellow-500 text-3xl mr-2">
<?php 
$estrellas_llenas = floor($promedio_calificacion);
$estrellas_vacias = 5 - $estrellas_llenas;
echo str_repeat('★', $estrellas_llenas); 
echo str_repeat('☆', $estrellas_vacias); 
?>
</div>
<span class="text-xl font-semibold text-gray-700">
(<?php echo $promedio_calificacion; ?>/5.0) - <?php echo $total_reseñas; ?> reseñas
</span>
<?php else: ?>
<span class="text-gray-500 italic">Sé el primero en calificar este producto.</span>
<?php endif; ?>
</div>


<p class="text-xl font-semibold text-purple-600 uppercase tracking-wider mb-2 flex items-center">
Vendedor: 
<a href="../index.php?vendedor_id=<?php echo $producto['vendedor_id']; ?>" 
    class="ml-2 text-pink-500 hover:text-pink-700 transition duration-150 font-bold">
<?php echo htmlspecialchars($producto['colectivo_nombre']); ?>
</a>
<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2h2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v2M7 7h10" />
</svg>
</p>


<p class="text-5xl font-extrabold text-pink-500 mb-6 border-b pb-4">
$<?php echo number_format($producto['precio'], 2); ?>
<span class="text-xl font-normal text-gray-500">MXN</span>
</p>

<h3 class="text-xl font-semibold text-pink-600 mt-6 mb-3">
Descripción del Producto
</h3>

<p class="text-gray-700 leading-relaxed mb-8">
<?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
</p>
</div>

<div>
<?php if ($producto['stock'] > 0): ?>

<p class="text-sm font-medium text-green-700 mt-2">
En Stock: <?php echo $producto['stock']; ?> unidades
</p>

<form action="<?php echo $base_url; ?>/acciones/carrito_action.php" method="POST" class="mt-5">
<input type="hidden" name="action" value="add">
<input type="hidden" name="product_id" value="<?php echo $producto['id']; ?>">

<div class="flex gap-4 mb-4">
    
    <input type="number" 
           name="cantidad" 
           value="1" 
           min="1" 
           max="<?php echo $producto['stock']; ?>" 
           required 
           class="text-lg font-bold text-gray-700 bg-gray-100 p-3 rounded-xl border border-gray-300 w-24 text-center focus:ring-pink-500 focus:border-pink-500"
    >

    <button type="submit"
    class="flex-grow bg-pink-500 text-white py-3 rounded-xl text-lg font-bold 
    hover:bg-pink-600 active:bg-pink-700 focus:ring-4 focus:ring-pink-200 transition shadow-md">
    Añadir al Carrito
    </button>
</div>
<p class="text-sm text-gray-500 text-center">
*La cantidad se puede modificar en la página del carrito.
</p>
</form>

<?php else: ?>

<button disabled
class="w-full bg-gray-400 text-white py-3 rounded-xl text-lg font-bold mt-4 shadow-md cursor-not-allowed">
¡Agotado!
</button>
<p class="text-md text-red-600 mt-2 text-center">Sin stock disponible actualmente.</p>

<?php endif; ?>

</div>

</div>
</div>

<section id="reseñas" class="bg-white p-6 md:p-10 rounded-2xl shadow-xl">
<h2 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-3">
Opiniones de Clientes (<?php echo $total_reseñas; ?>)
</h2>

<div class="mb-10 p-6 border border-gray-200 rounded-xl bg-gray-50">
<h3 class="text-xl font-bold text-pink-600 mb-4">Deja tu Opinión</h3>
<?php echo $mensaje_reseña; // Muestra mensajes de éxito/error ?>

<form method="POST" action="detalle_producto.php?id=<?php echo $producto_id; ?>">
<input type="hidden" name="action" value="add_review">

<div class="mb-4">
<label for="nombre" class="block text-sm font-medium text-gray-700">Tu Nombre (o alias)</label>
<input type="text" id="nombre" name="nombre" required
    class="mt-1 block w-full p-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400" 
    value="Cliente Anónimo">
</div>

<div class="mb-4">
<label class="block text-sm font-medium text-gray-700 mb-2">Calificación</label>

<div class="star-rating">
<input type="radio" id="star5" name="calificacion" value="5" required /><label for="star5" title="5 estrellas">★</label>
<input type="radio" id="star4" name="calificacion" value="4" /><label for="star4" title="4 estrellas">★</label>
<input type="radio" id="star3" name="calificacion" value="3" /><label for="star3" title="3 estrellas">★</label>
<input type="radio" id="star2" name="calificacion" value="2" /><label for="star2" title="2 estrellas">★</label>
<input type="radio" id="star1" name="calificacion" value="1" /><label for="star1" title="1 estrella">★</label>
</div>
</div>

<div class="mb-6">
<label for="comentario" class="block text-sm font-medium text-gray-700">Comentario</label>
<textarea id="comentario" name="comentario" rows="4" required
class="mt-1 block w-full p-2 border border-gray-300 rounded-lg focus:ring-pink-400 focus:border-pink-400"></textarea>
</div>

<button type="submit" class="bg-pink-500 text-white py-2 px-6 rounded-xl font-bold hover:bg-pink-600 transition">
Enviar Reseña
</button>
</form>
</div>

<?php if ($total_reseñas > 0): ?>
<div class="space-y-6">
<?php foreach ($reseñas as $review): ?>
<div class="border-b pb-4">
<div class="flex items-center mb-1">
<div class="text-yellow-500 text-xl mr-3">
<?php echo str_repeat('★', $review['calificacion']); ?>
<?php echo str_repeat('☆', 5 - $review['calificacion']); ?>
</div>
<span class="font-bold text-gray-800"><?php echo htmlspecialchars($review['nombre_cliente']); ?></span>
</div>
<p class="text-gray-700 mb-2 italic">"<?php echo nl2br(htmlspecialchars($review['comentario'])); ?>"</p>
<p class="text-xs text-gray-400">
Fecha: <?php echo date('d/M/Y', strtotime($review['fecha'])); ?>
</p>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-10 text-gray-500">
Aún no hay opiniones para este producto. ¡Sé el primero en dejar una!
</div>
<?php endif; ?>
</section>

<?php else: ?>

<div class="text-center py-20 bg-white rounded-2xl shadow-xl border-t-8 border-pink-300">
<p class="text-3xl font-bold text-red-600 mb-4">Producto No Encontrado</p>
<p class="text-xl text-gray-600">
El ID proporcionado no existe o el producto está inactivo.
</p>

<a href="<?php echo $base_url; ?>/index.php"
    class="inline-block bg-pink-500 text-white px-8 py-3 rounded-xl mt-6 font-bold
hover:bg-pink-600 active:bg-pink-700 transition shadow-lg">
Volver al Catálogo
</a>
</div>

<?php endif; ?>

</main>

<script>
    // 🚨 LÓGICA JAVASCRIPT PARA EL ZOOM DE LA IMAGEN 🚨
    const zoomContainer = document.getElementById('zoom-container');
    const zoomImage = document.getElementById('zoom-image');
    let isZoomed = false;

    zoomContainer.addEventListener('mousemove', (e) => {
        if (!isZoomed) return;

        // Calcula la posición relativa del ratón dentro del contenedor (0 a 1)
        const rect = zoomContainer.getBoundingClientRect();
        const x = e.clientX - rect.left; 
        const y = e.clientY - rect.top; 
        
        // Calcula el porcentaje de desplazamiento (0 a 100)
        const x_percent = x / rect.width;
        const y_percent = y / rect.height;

        // Mueve la imagen (el doble de lo que se mueve el ratón)
        const maxMoveX = rect.width;
        const maxMoveY = rect.height;

        const moveX = (x_percent - 0.5) * maxMoveX * 1; // *1 para que se centre bien
        const moveY = (y_percent - 0.5) * maxMoveY * 1;

        // Aplica la transformación CSS para desplazar la imagen
        zoomImage.style.transform = `scale(2) translate(${-moveX}px, ${-moveY}px)`;
    });

    zoomContainer.addEventListener('click', () => {
        isZoomed = !isZoomed;
        zoomImage.classList.toggle('zoomed', isZoomed);
        
        if (!isZoomed) {
            // Reinicia la imagen si el zoom se desactiva
            zoomImage.style.transform = 'scale(1)';
        }
    });
</script>

<?php 
// Aseguramos que la conexión se cierre antes de incluir el footer
if ($conn) { $conn->close(); }
include '../includes/footer.php'; 
?>