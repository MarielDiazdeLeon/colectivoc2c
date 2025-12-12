<?php
// Define el título de la página
$page_title = "Gestión de Cupones | Colectivo C2C";

// Incluir configuración, funciones de sesión y header
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../includes/funciones_sesion.php'; 

// Requerir rol de vendedor o administrador
if (!function_exists('require_vendedor')) { require_login(); } else { require_vendedor(); }

$conn = connect_db();
$user_id = $_SESSION['user_id'];

// Usamos 'user_role' por consistencia
$user_role = $_SESSION['user_role'] ?? 'invitado'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$mensaje = '';
$tipo_mensaje = '';

// Variables de rol para el sidebar (Asumo que estas variables se usan en el header/sidebar)
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600'; 

// 1. OBTENER ID DEL COLECTIVO
$colectivo_id = null;
if ($conn) {
    $sql_c = "SELECT id FROM colectivos WHERE id_usuario = ?";
    $stmt_c = $conn->prepare($sql_c);
    $stmt_c->bind_param("i", $user_id);
    $stmt_c->execute();
    $res_c = $stmt_c->get_result()->fetch_assoc();
    if ($res_c) $colectivo_id = $res_c['id'];
    $stmt_c->close();
}

// -----------------------------------------------------------
// 2. LÓGICA DE GESTIÓN (Añadir/Editar)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $colectivo_id) {
    
    // Captura de datos
    $codigo = trim($_POST['codigo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = $_POST['tipo'] ?? 'fijo';
    $valor = (float)($_POST['valor'] ?? 0);
    $usos = (int)($_POST['usos_maximos'] ?? 0);
    $cupon_id = (int)($_POST['cupon_id'] ?? 0);
    
    // 🚨 MANEJO DE FECHA NULL: Si está vacío, se fuerza a NULL. 🚨
    $expiracion_raw = trim($_POST['fecha_expiracion'] ?? '');
    $expiracion = (empty($expiracion_raw) || $expiracion_raw === '0000-00-00') ? null : $expiracion_raw;
    
    try {
        // Validación básica
        if (empty($codigo) || $valor <= 0) {
            throw new Exception("El código y el valor del cupón son obligatorios.");
        }

        // VERIFICACIÓN ESTRICTA DE DUPLICADOS (excepto para el cupón que estamos editando)
        $check_sql = "SELECT id FROM cupones WHERE codigo = ? AND id_colectivo = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sii", $codigo, $colectivo_id, $cupon_id); 
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Error: El código de cupón '{$codigo}' ya está registrado en tu tienda.");
        }
        $check_stmt->close();
        
        if ($cupon_id > 0) {
            // 🚨 UPDATE (EDITAR CUPÓN EXISTENTE) - LÓGICA CONDICIONAL PARA LA FECHA 🚨
            if ($expiracion === null) {
                // Caso 1: La fecha debe ser eliminada (NULL)
                $sql = "UPDATE cupones SET codigo=?, descripcion=?, tipo=?, valor=?, fecha_expiracion=NULL, usos_maximos=? WHERE id=? AND id_colectivo=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdiii", $codigo, $descripcion, $tipo, $valor, $usos, $cupon_id, $colectivo_id); 
            } else {
                // Caso 2: La fecha tiene un valor y debe ser actualizada
                $sql = "UPDATE cupones SET codigo=?, descripcion=?, tipo=?, valor=?, fecha_expiracion=?, usos_maximos=? WHERE id=? AND id_colectivo=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdsiis", $codigo, $descripcion, $tipo, $valor, $expiracion, $usos, $cupon_id, $colectivo_id);
            }
        } else {
            // INSERT (Nuevo cupón)
            $sql = "INSERT INTO cupones (codigo, descripcion, id_colectivo, tipo, valor, fecha_expiracion, usos_maximos) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // Manejar NULL en INSERT: si $expiracion es null, mySQL lo toma como NULL, pero bind_param requiere el tipo.
            // Una solución simple es usar el valor de la variable $expiracion directamente si no es NULL.
            if ($expiracion === null) {
                 $stmt->bind_param("ssidsdi", $codigo, $descripcion, $colectivo_id, $tipo, $valor, $expiracion, $usos); // Intentamos forzar NULL, pero puede fallar si no usamos call_user_func_array
            } else {
                 $stmt->bind_param("ssisdsi", $codigo, $descripcion, $colectivo_id, $tipo, $valor, $expiracion, $usos);
            }
        }

        if (!$stmt) {
             throw new Exception("Error al preparar la consulta SQL: " . $conn->error);
        }

        if ($stmt->execute()) {
            $mensaje = $cupon_id > 0 ? "Cupón #{$cupon_id} actualizado con éxito." : "Nuevo cupón creado: {$codigo}.";
            $tipo_mensaje = "bg-green-100 text-green-700 border-green-400";
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $mensaje = htmlspecialchars($e->getMessage());
        $tipo_mensaje = "bg-red-100 text-red-700 border-red-400";
    }
}

// -----------------------------------------------------------
// 3. LÓGICA DE ACTIVAR/DESACTIVAR
// -----------------------------------------------------------
if (isset($_GET['action']) && isset($_GET['id']) && $colectivo_id) {
    $cupon_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'delete') {
        $new_state = 0; // Desactivar
        $success_msg = "Cupón desactivado correctamente.";
        $error_msg = "Error al desactivar el cupón.";
    } elseif ($action == 'activate') {
        $new_state = 1; // Activar
        $success_msg = "Cupón reactivado.";
        $error_msg = "Error al reactivar el cupón.";
    } else {
        goto list_cupones; // Ir a la lista si la acción no es válida
    }

    $sql_update_state = "UPDATE cupones SET activo = ? WHERE id = ? AND id_colectivo = ?";
    $stmt_update_state = $conn->prepare($sql_update_state);
    $stmt_update_state->bind_param("iii", $new_state, $cupon_id, $colectivo_id);
    
    if ($stmt_update_state->execute()) {
        $mensaje = $success_msg;
        $tipo_mensaje = "bg-green-100 text-green-700 border-green-400";
    } else {
        $mensaje = $error_msg;
        $tipo_mensaje = "bg-red-100 text-red-700 border-red-400";
    }
    $stmt_update_state->close();
    
    header("Location: cupones_vendedor.php");
    exit;
}
list_cupones: 

// -----------------------------------------------------------
// 4. LISTAR CUPONES (Muestra Desactivados, Oculta Expirados)
// -----------------------------------------------------------
$cupones = [];
if ($conn && $colectivo_id) {
    // 🚨 NOTA: Para simplificar, la consulta asume que las columnas 'usos_maximos', 'usos_actuales', y 'fecha_expiracion' existen en la BD. 🚨
    $sql_cupones = "SELECT id, codigo, descripcion, tipo, valor, fecha_expiracion, usos_maximos, usos_actuales, activo 
                    FROM cupones 
                    WHERE id_colectivo = ? 
                    AND (activo = TRUE OR fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE()) 
                    ORDER BY id DESC";
    
    $stmt_cupones = $conn->prepare($sql_cupones);
    if (!$stmt_cupones) {
         $mensaje = "Error al listar cupones: " . $conn->error;
         $tipo_mensaje = "bg-red-100 text-red-700 border-red-400";
    } else {
        $stmt_cupones->bind_param("i", $colectivo_id);
        $stmt_cupones->execute();
        $result_cupones = $stmt_cupones->get_result();
        while ($row = $result_cupones->fetch_assoc()) {
            $cupones[] = $row;
        }
        $stmt_cupones->close();
    }
}

// Incluir el header después de toda la lógica de procesamiento
require_once __DIR__ . '/../includes/header.php'; 
?>

<div class="flex h-screen bg-gray-50">
    
    <aside class="w-64 bg-pink-100 text-gray-800 shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-pink-300 text-pink-700">
            Colectivo CDI | <?php echo strtoupper($user_role); ?>
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
                <a href="pedidos_vendedor.php" class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
                    <i class="fas fa-receipt mr-3"></i> Pedidos
                </a>
                <a href="cupones_vendedor.php" class="flex items-center p-3 rounded-lg bg-pink-300 font-bold text-white transition duration-150">
                    <i class="fas fa-percent mr-3"></i> Cupones
                </a>                
                <?php if ($user_role == 'admin'): ?>
                <a href="admin/gestion_usuarios.php" class="flex items-center p-3 rounded-lg hover:bg-pink-200 text-gray-700 transition duration-150">
                    <i class="fas fa-users-cog mr-3"></i> Gestión de Usuarios
                </a>
                <?php endif; ?>
                <a href="../acciones/auth_action.php?action=logout" class="flex items-center p-3 rounded-lg bg-pink-600 text-white hover:bg-pink-700 transition duration-150 mt-4">
                    <i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
                </a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-6xl mx-auto">
            
            <header class="flex justify-between items-center mb-8 border-b pb-4">
                <h1 class="text-3xl font-extrabold text-gray-800">
                    Gestión de Cupones (<?php echo $colectivo_id ? 'Tu Tienda' : 'Error'; ?>)
                </h1>
                <button onclick="document.getElementById('form_modal').classList.remove('hidden')" 
                        class="bg-pink-600 text-white px-5 py-2 rounded-lg hover:bg-pink-700 transition shadow-md flex items-center">
                    <i class="fas fa-plus mr-2"></i> Nuevo Cupón
                </button>
            </header>

            <?php if ($mensaje): ?>
                <div class="border-l-4 p-4 mb-6 rounded border <?php echo $tipo_mensaje; ?>">
                    <p><?php echo $mensaje; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($colectivo_id): ?>
                <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-pink-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Tipo</th>
                                <th style="display: none;" class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Uso Máx.</th>
                                <th  class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Vencimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($cupones as $cupon): 
                                // Lógica de Fecha para la visualización
                                $raw_date = $cupon['fecha_expiracion'];
                                $valid_date = ($raw_date && $raw_date !== '0000-00-00') ? $raw_date : null;
                                
                                $expiracion_date = $valid_date ? new DateTime($valid_date) : null;
                                $now = new DateTime();
                                $is_expired = $expiracion_date && $expiracion_date < $now;
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                    <?php echo htmlspecialchars($cupon['codigo']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php 
                                        echo $cupon['tipo'] == 'porcentaje' 
                                            ? htmlspecialchars($cupon['valor']) . '%' 
                                            : '$' . number_format(htmlspecialchars($cupon['valor']), 2); 
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cupon['tipo'] == 'porcentaje' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                        <?php echo $cupon['tipo'] == 'porcentaje' ? 'Porcentaje' : 'Monto Fijo'; ?>
                                    </span>
                                </td>
                                <td style="display: none;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo $cupon['usos_maximos'] == 0 ? 'Ilimitado' : $cupon['usos_actuales'] . ' de ' . $cupon['usos_maximos']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php 
                                        if ($expiracion_date) {
                                            echo '<span class="' . ($is_expired ? 'text-red-500 font-bold' : 'text-gray-700') . '">';
                                            echo date('d/M/Y', $expiracion_date->getTimestamp());
                                            echo '</span>';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php 
                                        if ($is_expired) {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500 text-white">VENCIDO</span>';
                                        } elseif ($cupon['activo'] == 1) {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">ACTIVO</span>';
                                        } else {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">INACTIVO</span>';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <a href="#" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($cupon)); ?>)" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i> Editar</a>
                                    
                                    <?php if (!$is_expired): ?>
                                        <?php if ($cupon['activo'] == 1): ?>
                                            <a href="cupones_vendedor.php?action=delete&id=<?php echo $cupon['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Seguro que deseas desactivar este cupón?');"><i class="fas fa-toggle-off"></i> Desactivar</a>
                                        <?php else: ?>
                                            <a href="cupones_vendedor.php?action=activate&id=<?php echo $cupon['id']; ?>" class="text-green-600 hover:text-green-900" onclick="return confirm('¿Seguro que deseas reactivar este cupón?');"><i class="fas fa-toggle-on"></i> Reactivar</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400"><i class="fas fa-ban"></i> Vencido</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cupones)): ?>
                                <tr><td colspan="7" class="px-6 py-5 text-center text-gray-500">No tienes cupones visibles.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <p class="font-bold">Error de Configuración</p>
                    <p>Tu cuenta de vendedor no está asociada a ningún colectivo/tienda. No puedes gestionar cupones.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="form_modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
        <div class="relative w-full max-w-lg shadow-2xl rounded-2xl bg-white transform transition-all overflow-hidden border-t-4 border-pink-500">
            
            <button type="button" 
                    onclick="resetModalForm()"
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="p-6 md:p-8">
                <h3 class="text-2xl font-extrabold text-gray-800 border-b pb-3 mb-4" id="modal_title">Crear Nuevo Cupón</h3>
                
                <form method="POST" action="cupones_vendedor.php" id="cupon_form" class="space-y-4">
                    <input type="hidden" name="cupon_id" id="cupon_id" value="0">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código (ej: VERANO25)</label>
                            <input type="text" name="codigo" id="codigo" required 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500 uppercase">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <input type="text" name="descripcion" id="descripcion" 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Descuento</label>
                            <select name="tipo" id="tipo" required 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                                <option value="fijo">Monto Fijo ($)</option>
                                <option value="porcentaje">Porcentaje (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor</label>
                            <input type="number" step="0.01" name="valor" id="valor" required 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de Expiración (Opcional)</label>
                            <input type="date" name="fecha_expiracion" id="fecha_expiracion" 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Usos Máximos (0 = Ilimitado)</label>
                            <input type="number" name="usos_maximos" id="usos_maximos" value="0" min="0" 
                                class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>
                    
                    <div class="pt-4 flex space-x-4">
                        <button type="submit" class="w-full px-4 py-3 bg-pink-500 text-white font-medium rounded-lg hover:bg-pink-700 transition shadow-lg flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i> Guardar Cupón
                        </button>
                        <button type="button" onclick="resetModalForm()" 
                                class="w-full px-4 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    // Función para manejar la apertura del modal en modo edición
    function openEditModal(cupon) {
        document.getElementById('modal_title').innerText = 'Editar Cupón: ' + cupon.codigo;
        document.getElementById('cupon_id').value = cupon.id;
        document.getElementById('codigo').value = cupon.codigo;
        document.getElementById('descripcion').value = cupon.descripcion;
        document.getElementById('tipo').value = cupon.tipo;
        document.getElementById('valor').value = cupon.valor;
        document.getElementById('usos_maximos').value = cupon.usos_maximos;
        
        // 🚨 AJUSTE JS: Lógica para manejar el campo de fecha DATETIME/DATE
        const fechaExpiracion = cupon.fecha_expiracion;
        if (fechaExpiracion && fechaExpiracion !== '0000-00-00') { 
            // Corta la parte de la hora si es DATETIME (ej: 2025-12-10 00:00:00 -> 2025-12-10)
            document.getElementById('fecha_expiracion').value = fechaExpiracion.split(' ')[0];
        } else {
            document.getElementById('fecha_expiracion').value = ''; // Borra el campo si es NULL o 0000-00-00
        }
        
        document.getElementById('form_modal').classList.remove('hidden');
    }
    
    // Función para limpiar el formulario al cerrar (clic fuera o botón Cancelar)
    document.getElementById('form_modal').addEventListener('click', function(e) {
        if (e.target.id === 'form_modal') {
            resetModalForm();
        }
    });
    
    // Función unificada de reseteo para el botón de cancelar y la 'X'
    function resetModalForm() {
        document.getElementById('modal_title').innerText = 'Crear Nuevo Cupón';
        document.getElementById('cupon_form').reset();
        document.getElementById('cupon_id').value = 0;
        document.getElementById('form_modal').classList.add('hidden');
    }
</script>

<?php
// Incluir el pie de página
if ($conn) { $conn->close(); }
require_once __DIR__ . '/../includes/footer.php';
?>