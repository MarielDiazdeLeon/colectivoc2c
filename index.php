<?php
// Define el título de la página
$page_title = "Catálogo | Colectivo C2C";

// 1. Incluye el header (esto también incluye la conexión a la DB: db.php)
include 'includes/header.php'; 

// 2. Lógica de Catálogo: Consulta a la Base de Datos
$conn = connect_db(); 

// =========================================================
// OBTENER CATEGORÍAS DESDE LA BASE DE DATOS (NUEVO)
// =========================================================
$categorias = [];
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
$result_cat = $conn->query($sql_categorias);
if ($result_cat && $result_cat->num_rows > 0) {
    while ($row = $result_cat->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// --- 1. INICIALIZACIÓN DE VARIABLES DE FILTRADO Y BÚSQUEDA ---
$vendedor_id_filtro = null;
$filtro_sql = "";
$busqueda_sql = "";
$categoria_id_filtro = null;
$categoria_sql = ""; // NUEVO
$termino_busqueda = null;

$titulo_catalogo = "Nuestros Productos del Colectivo";
$subtitulo_catalogo = "Descubre productos únicos y locales, directamente del productor.";


// =========================================================
// FILTRO DE VENDEDOR
// =========================================================
if (isset($_GET['vendedor_id']) && is_numeric($_GET['vendedor_id'])) {
    $vendedor_id_filtro = (int)$_GET['vendedor_id'];

    $sql_vendedor = "SELECT 
                        u.nombre, 
                        u.apellido, 
                        c.nombre_marca
                     FROM 
                        usuarios u
                     INNER JOIN 
                        colectivos c ON u.id = c.id_usuario 
                     WHERE 
                        u.id = " . $vendedor_id_filtro;
    
    $result_vendedor = $conn->query($sql_vendedor);
    
    if ($result_vendedor && $row_vendedor = $result_vendedor->fetch_assoc()) {
        $nombre_marca = htmlspecialchars($row_vendedor['nombre_marca']);
        $nombre_vendedor = htmlspecialchars($row_vendedor['nombre'] . ' ' . $row_vendedor['apellido']);

        $titulo_catalogo = "Productos de: " . $nombre_marca; 
        $subtitulo_catalogo = "Explora la selección especial de nuestro socio " . $nombre_vendedor . ".";
    }

    $filtro_sql = " AND p.id_vendedor = " . $vendedor_id_filtro; 
}


// =========================================================
// FILTRO DE CATEGORÍA (NUEVO)
// =========================================================
if (isset($_GET['categoria']) && is_numeric($_GET['categoria'])) {
    $categoria_id_filtro = (int)$_GET['categoria'];
    $categoria_sql = " AND p.id_categoria = " . $categoria_id_filtro;

    if ($vendedor_id_filtro === null && !isset($_GET['q'])) {
        $titulo_catalogo = "Productos por Categoría";
    }
}


// =========================================================
// BÚSQUEDA POR TEXTO
// =========================================================
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {

    $termino_busqueda = $conn->real_escape_string(trim($_GET['q']));
    
    if ($vendedor_id_filtro === null && $categoria_id_filtro === null) {
        $titulo_catalogo = "Resultados de Búsqueda";
    }

    $subtitulo_catalogo = "Buscando: " . htmlspecialchars($termino_busqueda);

    $busqueda_sql = " 
        AND (
            p.nombre LIKE '%" . $termino_busqueda . "%' 
            OR c.nombre_marca LIKE '%" . $termino_busqueda . "%'
            OR cat.nombre LIKE '%" . $termino_busqueda . "%' 
        )
    ";
}


// =========================================================
// CONSULTA SQL FINAL
// =========================================================

$sql = "
    SELECT 
        p.id, 
        p.nombre, 
        p.precio, 
        p.imagen_url, 
        p.stock,
        c.nombre_marca AS colectivo_nombre,
        cat.nombre AS categoria_nombre
    FROM 
        productos p
    JOIN 
        colectivos c ON p.id_colectivo = c.id
    JOIN 
        categorias cat ON p.id_categoria = cat.id
    WHERE 
        p.activo = 1 
        " . $filtro_sql . "
        " . $categoria_sql . "
        " . $busqueda_sql . "
    ORDER BY 
        p.id DESC
";

// 5. Ejecuta la consulta
$result = $conn->query($sql);
$productos = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}
?>

<!-- Contenido Principal: Catálogo de Productos -->
<main class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-extrabold text-gray-800 mb-6 text-center">
        <?php echo $titulo_catalogo; ?>
    </h1>
    <p class="text-xl text-gray-600 mb-10 text-center">
        <?php echo $subtitulo_catalogo; ?>
    </p>

    <!-- Barra de Búsqueda y Filtros -->
    <form action="index.php" method="GET" class="mb-8 p-4 bg-white rounded-xl shadow-lg flex items-center flex-wrap gap-4">

        <?php if ($vendedor_id_filtro !== null): ?>
            <input type="hidden" name="vendedor_id" value="<?php echo $vendedor_id_filtro; ?>">
        <?php endif; ?>

        <!-- Búsqueda -->
        <input type="text" 
               name="q" 
               placeholder="Buscar productos, colectivos o categorías..." 
               value="<?php echo htmlspecialchars($termino_busqueda ?? ''); ?>"
               class="flex-grow p-3 border border-gray-300 rounded-lg focus:ring-colectivo-accent focus:border-colectivo-accent">

        <!-- SELECT DE CATEGORÍAS (NUEVO) -->
        <select name="categoria" 
                class="p-3 border border-gray-300 rounded-lg focus:ring-colectivo-accent focus:border-colectivo-accent">

            <option value="">Todas las categorías</option>

            <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" 
                    <?php if ($categoria_id_filtro == $cat['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </option>
            <?php endforeach; ?>

        </select>
        
        <button type="submit" class="colectivo-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-150">
            Buscar
        </button>
    </form>


    <!-- 5. Muestra la cuadrícula de productos -->
    <?php if (!empty($productos)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            
            <?php foreach ($productos as $producto): ?>
                <div class="bg-white rounded-xl shadow-xl hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col">
                    
                    <!-- Imagen -->
                    <a href="pages/detalle_producto.php?id=<?php echo $producto['id']; ?>" class="block h-48 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($producto['imagen_url'] ?? 'https://placehold.co/400x300/e5e7eb/4b5563?text=Sin+Imagen'); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x300/e5e7eb/4b5563?text=Sin+Imagen+URL';">
                    </a>

                    <div class="p-5 flex flex-col flex-grow">
                        
                        <span class="text-xs font-semibold text-colectivo-accent uppercase tracking-wider mb-1">
                            <?php echo htmlspecialchars($producto['colectivo_nombre']); ?>
                        </span>

                        <a href="pages/detalle_producto.php?id=<?php echo $producto['id']; ?>" 
                           class="text-lg font-bold text-gray-800 hover:text-colectivo-accent-dark transition duration-150 mb-2 flex-grow">
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                        </a>

                        <p class="text-sm text-gray-500 mb-2">
                            Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
                        </p>

                        <p class="text-2xl font-extrabold text-gray-900 mb-4">
                            $<?php echo number_format($producto['precio'], 2, '.', ','); ?>
                            <span class="text-sm font-normal text-gray-500">MXN</span>
                        </p>
                        
                        <?php if ($producto['stock'] > 0): ?>
                            <form action="acciones/carrito_action.php" method="POST" class="mt-auto">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $producto['id']; ?>">
                                <input type="hidden" name="cantidad" value="1">
                                <button type="submit" class="w-full colectivo-primary text-white py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition duration-150 shadow-md">
                                    Añadir al Carrito
                                </button>
                                <p class="text-xs text-green-600 mt-2 text-center">Disponible: <?php echo $producto['stock']; ?> unidades</p>
                            </form>
                        <?php else: ?>
                            <button disabled class="w-full bg-gray-400 text-white py-2 rounded-lg text-sm font-semibold cursor-not-allowed mt-auto">
                                Agotado
                            </button>
                            <p class="text-xs text-red-600 mt-2 text-center">Sin stock</p>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    <?php else: ?>
        <div class="text-center py-20 bg-gray-50 rounded-xl shadow-inner">
            <p class="text-2xl font-semibold text-gray-500">
                No se encontraron productos con los criterios seleccionados.
            </p>
            <p class="text-gray-400 mt-2">Prueba otra categoría o usa otro término de búsqueda.</p>
        </div>
    <?php endif; ?>
</main>

<?php 
include 'includes/footer.php'; 
?>
