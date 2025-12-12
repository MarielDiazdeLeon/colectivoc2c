<?php
// ==========================================================
// FORMULARIO PRODUCTO (pages/formulario_producto.php) - CON MODAL DE AGREGAR CATEGORÍA
// ==========================================================
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$base_url = '/colectivo_c2c/';

if (!function_exists('require_vendedor')) { require_login(); } else { require_vendedor(); }

$conn = connect_db();
$user_id = $_SESSION['user_id'];
$colectivo_id = null;
$error = null;
$success_message = null; // Mensaje de éxito general

// Obtener rol y nombre para el sidebar
$user_role = $_SESSION['user_role'] ?? 'invitado'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600'; 

// Obtener colectivo del usuario y su nombre
$colectivo_nombre = '';
if ($conn) {
// Consulta corregida para usar 'nombre_marca' (o el nombre real de tu columna de tienda)
$stmt = $conn->prepare("SELECT id, nombre_marca FROM colectivos WHERE id_usuario = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
$colectivo_id = $res['id'];
$colectivo_nombre = htmlspecialchars($res['nombre_marca']); // Usar nombre_marca
} else {
$colectivo_nombre = "Colectivo (sin nombre)";
}
$stmt->close();
}

$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : null;

$nombre = ''; 
$descripcion = ''; 
$precio = ''; 
$stock = ''; 
$imagen_url = ''; 
$id_categoria = '';
$titulo_pagina = $id_producto ? "Editar Producto" : "Nuevo Producto";


// ===============================================
// PROCESAR POST DE NUEVA CATEGORÍA (Manejado por POST diferente)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'agregar_categoria') {
    $nueva_categoria = trim($conn->real_escape_string($_POST['nueva_categoria']));
    
    if (!empty($nueva_categoria)) {
        try {
            // Verificar si ya existe
            $stmt_check = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt_check->bind_param("s", $nueva_categoria);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                 $error = "La categoría '{$nueva_categoria}' ya existe.";
            } else {
                // Insertar nueva categoría (asumiendo que 'categorias' tiene 'id' y 'nombre')
                $stmt_insert = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $stmt_insert->bind_param("s", $nueva_categoria);
                if ($stmt_insert->execute()) {
                    $success_message = "Categoría '{$nueva_categoria}' agregada con éxito.";
                } else {
                    $error = "Error al guardar la nueva categoría: " . $stmt_insert->error;
                }
                if (isset($stmt_insert)) $stmt_insert->close();
            }
            if (isset($stmt_check)) $stmt_check->close();
            
        } catch (Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
    } else {
        $error = "El nombre de la categoría no puede estar vacío.";
    }
}


// ===============================================
// OBTENER CATEGORÍAS (Despues de cualquier posible inserción)
// ===============================================
$categorias = [];
if ($conn) {
$result_cat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
if ($result_cat) {
while ($row = $result_cat->fetch_assoc()) {
$categorias[] = $row;
}
}
}


// ===============================================
// PROCESAR POST DE PRODUCTO (Lógica de guardado)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $colectivo_id && !isset($_POST['action'])) { // Sólo si NO es la acción de categoría

$nombre = $conn->real_escape_string($_POST['nombre']);
$descripcion = $conn->real_escape_string($_POST['descripcion']);
$precio = (float)$_POST['precio'];
$stock = (int)$_POST['stock'];
$id_categoria = (int)$_POST['id_categoria'];

if ($id_categoria == 0) {
$error = "Debe seleccionar una categoría.";
}

$imagen_actual = $_POST['imagen_actual'] ?? '';
$imagen_final = $imagen_actual;

// SUBIDA DE IMAGEN
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {

$nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
$ruta_destino = __DIR__ . "/../assets/uploads/productos/";

if (!file_exists($ruta_destino)) mkdir($ruta_destino, 0777, true);

if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino . $nombre_archivo)) {
$imagen_final = $base_url . "assets/uploads/productos/" . $nombre_archivo;
} else {
$error = "Error al subir la imagen.";
}
}

if (!$imagen_final) {
$imagen_final = $base_url . "assets/img/placeholder.jpg";
}

// 🚨 LÓGICA OPTIMIZADA: Determinar el estado activo basado SÓLO en el stock
$activo_final = ($stock > 0) ? 1 : 0;

if (!$error) {
if ($id_producto) {
$sql = "UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, imagen_url=?, id_categoria=?, activo=? 
WHERE id=? AND id_colectivo=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdisiiii", $nombre, $descripcion, $precio, $stock, $imagen_final, $id_categoria, $activo_final, $id_producto, $colectivo_id);
} else {
$sql = "INSERT INTO productos (id_colectivo, id_vendedor, id_categoria, nombre, descripcion, precio, stock, imagen_url, activo, fecha_creacion) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissdisi", $colectivo_id, $user_id, $id_categoria, $nombre, $descripcion, $precio, $stock, $imagen_final, $activo_final);
}

if ($stmt->execute()) {
$_SESSION['success_message'] = "Producto guardado correctamente.";
header("Location: productos_vendedor.php");
exit;
} else {
$error = "Error al guardar: " . $stmt->error;
}
if (isset($stmt)) $stmt->close();
}
}


// CARGAR DATOS SI ES EDICIÓN
if ($id_producto && !$error) {
$stmt = $conn->prepare("SELECT * FROM productos WHERE id=? AND id_colectivo=?");
$stmt->bind_param("ii", $id_producto, $colectivo_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();

if ($prod) {
$nombre = $prod['nombre'];
$descripcion = $prod['descripcion'];
$precio = $prod['precio'];
$stock = $prod['stock'];
$imagen_url = $prod['imagen_url'];
$id_categoria = $prod['id_categoria'];
}
$stmt->close();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex">

<aside class="w-64 bg-pink-100 text-gray-800 shadow-xl">

<div class="p-6 text-xl font-bold border-b border-pink-300 text-pink-700">
<?php echo $colectivo_nombre ?: 'Tu Colectivo'; ?>
</div>

<div class="p-6 flex-1">

<div class="text-md font-semibold mb-6 pb-2 border-b border-pink-300">
<p class="text-xs text-gray-500 mb-1">Vendedor:</p>
<p class="text-gray-700"><?php echo htmlspecialchars($user_name); ?></p>
</div>

<nav class="space-y-2">
<a href="<?php echo $base_url; ?>pages/productos_vendedor.php"
class="flex items-center p-3 rounded-lg bg-pink-300 font-bold text-white hover:bg-pink-400 transition duration-150 shadow-md">
<i class="fas fa-arrow-left mr-3"></i> Regresar a Productos
</a>
</nav>
</div>
</aside>
<main class="flex-1 p-10">

<div class="bg-white p-8 rounded-xl shadow-xl max-w-5xl mx-auto">

<h1 class="text-3xl font-bold text-gray-800 mb-8 text-center border-b pb-4">
<?= $titulo_pagina ?>
</h1>

<?php if ($error): ?>
<p class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
⚠ <?= htmlspecialchars($error); ?>
</p>
<?php elseif ($success_message): ?>
                <p class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    ✅ <?= htmlspecialchars($success_message); ?>
                </p>
            <?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">

<div class="space-y-6 md:col-span-2">

<input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($imagen_url); ?>">
<div>
<label class="block font-medium text-gray-700">Nombre del Producto</label>
<input type="text" name="nombre" value="<?= htmlspecialchars($nombre); ?>" 
required class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 shadow-sm">
</div>

<div>
<label class="block font-medium text-gray-700">Categoría</label>
                        <div class="flex space-x-2">
    <select name="id_categoria" required class="mt-1 flex-1 p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 shadow-sm">
    <option value="">Seleccione una categoría</option>
    <?php foreach ($categorias as $cat): ?>
    <option value="<?= $cat['id']; ?>" <?= ($id_categoria == $cat['id']) ? 'selected' : ''; ?>>
    <?= htmlspecialchars($cat['nombre']); ?>
    </option>
    <?php endforeach; ?>
    </select>
                             <button type="button" onclick="openCategoriaModal()"
                                class="mt-1 px-4 py-3 bg-pink-500 text-white rounded-lg shadow-md hover:bg-pink-600 transition duration-150 flex items-center justify-center">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                        </div>
</div>

<div>
<label class="block font-medium text-gray-700">Descripción</label>
<textarea name="descripcion" rows="3" required 
class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 shadow-sm"><?= htmlspecialchars($descripcion); ?></textarea>
</div>

<div class="grid grid-cols-2 gap-4">
<div>
<label class="block font-medium text-gray-700">Precio (MXN)</label>
<input type="number" step="0.01" name="precio" 
value="<?= htmlspecialchars($precio); ?>" required 
class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 shadow-sm">
</div>

<div>
<label class="block font-medium text-gray-700">Stock</label>
<input type="number" name="stock" value="<?= htmlspecialchars($stock); ?>" required 
class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 shadow-sm">
</div>
</div>

</div>

<div class="md:col-span-1 flex flex-col items-center">

<div class="bg-gradient-to-b from-gray-50 to-gray-100 p-6 rounded-xl shadow-md border border-gray-200 w-full sticky top-4">

<label class="font-semibold text-gray-700 block mb-4 text-center text-lg">Imagen del Producto</label>

<div class="w-full flex justify-center mb-5">
<?php if ($imagen_url && $imagen_url !== $base_url . "assets/img/placeholder.jpg"): ?>
<img src="<?= htmlspecialchars($imagen_url); ?>" 
class="h-48 w-48 object-cover rounded-xl shadow-md border border-gray-300 transition hover:scale-105 duration-200">
<?php else: ?>
<div class="h-48 w-48 flex flex-col items-center justify-center rounded-xl bg-pink-100 text-pink-600 border border-pink-300 shadow-inner">
<i class="fas fa-image text-3xl mb-2 opacity-80"></i>
<p class="font-medium">Sin imagen</p>
</div>
<?php endif; ?>
</div>

<label class="cursor-pointer w-full block">
<span class="block text-center bg-white border border-gray-300 hover:border-pink-400 text-gray-700 py-2 px-4 rounded-lg shadow-sm hover:shadow transition-all font-medium">
Elegir/Cambiar imagen
</span>
<input type="file" name="imagen" accept="image/*" class="hidden">
</label>

</div>

</div>

<div class="md:col-span-3 mt-4">
<button type="submit" 
class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 rounded-lg text-lg font-semibold shadow-xl transition duration-150">
<i class="fas fa-save mr-2"></i> Guardar Producto
</button>
</div>

</form>

</div>

</main>

</div>

<div id="categoriaModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50 hidden">
    <div class="relative w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 border-t-4 border-pink-600">
        
        <button type="button" onclick="closeCategoriaModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>

        <h3 class="text-xl font-bold text-gray-800 mb-4">Añadir Nueva Categoría</h3>
        
        <form method="POST" action="formulario_producto.php<?= $id_producto ? '?id='.$id_producto : '' ?>">
            <input type="hidden" name="action" value="agregar_categoria">

            <div class="mb-4">
                <label for="nueva_categoria" class="block text-sm font-medium text-gray-700">Nombre de la Categoría</label>
                <input type="text" name="nueva_categoria" id="nueva_categoria" required 
                    class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500" 
                    placeholder="Ej. Joyería, Ropa Vintage">
            </div>

            <button type="submit" class="w-full px-4 py-3 bg-pink-600 text-white font-medium rounded-lg hover:bg-pink-700 transition shadow-md">
                <i class="fas fa-plus mr-2"></i> Crear y Seleccionar
            </button>
        </form>
    </div>
</div>

<script>
    function openCategoriaModal() {
        document.getElementById('categoriaModal').classList.remove('hidden');
        document.getElementById('categoriaModal').classList.add('flex');
    }

    function closeCategoriaModal() {
        document.getElementById('categoriaModal').classList.add('hidden');
        document.getElementById('categoriaModal').classList.remove('flex');
    }
    
    // Si la acción de agregar categoría fue exitosa, el SELECT debería reflejar el cambio.
    // Si hubo un error en la inserción, reabrimos el modal para que el usuario pueda corregir.
    <?php if ($error && isset($_POST['action']) && $_POST['action'] == 'agregar_categoria'): ?>
        document.addEventListener('DOMContentLoaded', function() {
             alert('Error al agregar categoría: <?= htmlspecialchars($error, ENT_QUOTES) ?>');
             openCategoriaModal();
        });
    <?php endif; ?>

    // Opcional: Si la categoría se insertó con éxito, podemos notificar y cerrar el modal.
    <?php if ($success_message && isset($_POST['action']) && $_POST['action'] == 'agregar_categoria'): ?>
        document.addEventListener('DOMContentLoaded', function() {
             // Si quieres auto-seleccionar la categoría, necesitarías el ID de la categoría insertada en el POST/GET.
             // Por ahora, solo recargamos la página para actualizar la lista.
             // El mensaje de éxito ya se mostró al inicio.
        });
    <?php endif; ?>
</script>

<?php 
if ($conn) $conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>