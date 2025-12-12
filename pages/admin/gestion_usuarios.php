<?php
// ==========================================================
// GESTIÓN DE USUARIOS (pages/admin/gestion_usuarios.php)
// CÓDIGO FINAL ESTABLE: Soluciona el problema de reapertura del modal tras guardado exitoso.
// ==========================================================

// 1. INCLUSIÓN Y AUTENTICACIÓN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$base_url = '/colectivo_c2c/';

// 🚨 CORRECCIÓN DEFINITIVA DE RUTAS 🚨
$project_root = realpath(__DIR__ . '/../..'); 

require_once $project_root . '/includes/funciones_sesion.php'; 
require_once $project_root . '/config/db.php'; 


// CRÍTICO: Requerir que el usuario sea administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . $base_url . "pages/dashboard.php");
    exit;
}

$conn = connect_db(); 
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Admin';

$mensaje = '';
$tipo_mensaje = ''; // success o error

// Variables de rol para el sidebar
$user_role = 'admin';
$role_display = 'Administrador';
$role_color = 'bg-red-500'; 

// -----------------------------------------------------------
// 2. LÓGICA DE PROCESAMIENTO (CRUD, PAGO, y VISIBILIDAD)
// -----------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $usuario_id = (int)($_POST['user_id'] ?? 0);

    try {
        if ($action === 'registrar_pago') {
            // Lógica de registro de pago
            $sql_col_id = "SELECT id FROM colectivos WHERE id_usuario = ?";
            $stmt_col_id = $conn->prepare($sql_col_id);
            $stmt_col_id->bind_param("i", $usuario_id);
            $stmt_col_id->execute();
            $colectivo_data = $stmt_col_id->get_result()->fetch_assoc();
            $stmt_col_id->close();
            
            if (!$colectivo_data) {
                 throw new Exception("El vendedor no tiene un colectivo asociado para registrar el pago.");
            }
            $colectivo_id_pago = $colectivo_data['id'];

            $sql = "UPDATE colectivos SET ultimo_pago_mensual = CURDATE() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $colectivo_id_pago);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar el pago: " . $stmt->error);
            }
            $stmt->close();
            
            $mensaje = "Pago de mensualidad registrado con éxito para el Usuario ID #{$usuario_id}.";
            $tipo_mensaje = 'bg-green-100 border-green-500 text-green-700';

        } elseif ($action === 'toggle_visibilidad') {
            // Alternar el estado 'activo' del usuario 
            $current_status = (int)($_POST['current_status'] ?? 0);
            $new_status = ($current_status == 1) ? 0 : 1;
            
            $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_status, $usuario_id);

            if (!$stmt->execute()) {
                throw new Exception("Error al cambiar el estado de visibilidad: " . $stmt->error);
            }
            $stmt->close();
            
            $action_desc = ($new_status == 1) ? 'Activado' : 'Desactivado';
            $mensaje = "Visibilidad del usuario ID #{$usuario_id} cambiada a **{$action_desc}**.";
            $tipo_mensaje = 'bg-yellow-100 border-yellow-500 text-yellow-700';

        } elseif ($action === 'guardar_usuario') {
            // Lógica existente para CREAR/EDITAR
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol = $_POST['rol'] ?? 'cliente';
            $password = $_POST['password'] ?? '';
            $nombre_colectivo = trim($_POST['nombre_colectivo'] ?? '');
            
            if (empty($nombre) || empty($email) || !in_array($rol, ['admin', 'vendedor', 'cliente'])) {
                throw new Exception("Datos incompletos o rol inválido.");
            }

            // 1. Validación de Email
            $check_sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $usuario_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $check_stmt->close();
                throw new Exception("El email ya está registrado.");
            }
            $check_stmt->close();

            // 2. Actualización de USUARIO (CRUD)
            if ($usuario_id > 0) {
                // UPDATE: Editar usuario existente
                $fields = ['nombre', 'email', 'rol'];
                $types = 'sss';
                $values = [&$nombre, &$email, &$rol];
                
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $fields[] = 'password';
                    $types .= 's';
                    $values[] = &$password_hash;
                }
                
                $sql_update_user = "UPDATE usuarios SET " . implode('=?, ', $fields) . "=? WHERE id = ?";
                $types .= 'i';
                $values[] = &$usuario_id;
                
                $stmt = $conn->prepare($sql_update_user);
                call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $values));
                
                if (!$stmt->execute()) { throw new Exception("Error al actualizar el usuario: " . $stmt->error); }
                $stmt->close();

            } else {
                // INSERT: Crear nuevo usuario
                if (empty($password)) { throw new Exception("La contraseña es requerida para un nuevo usuario."); }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nombre, email, rol, password, activo) VALUES (?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $nombre, $email, $rol, $password_hash);
                if (!$stmt->execute()) { throw new Exception("Error al crear el usuario: " . $stmt->error); }
                $usuario_id = $conn->insert_id; 
                $stmt->close();
            }
            
            // 3. Lógica de ASIGNACIÓN/CREACIÓN DE COLECTIVO (Solo si es vendedor)
            if ($rol === 'vendedor') {
                 if (empty($nombre_colectivo)) {
                    throw new Exception("El nombre del colectivo es requerido para un vendedor.");
                 }

                 // Buscar si ya existe un colectivo con ese nombre
                 $sql_check_col = "SELECT id, id_usuario FROM colectivos WHERE nombre_marca = ?";
                 $stmt_check_col = $conn->prepare($sql_check_col);
                 $stmt_check_col->bind_param("s", $nombre_colectivo);
                 $stmt_check_col->execute();
                 $existing_col = $stmt_check_col->get_result()->fetch_assoc();
                 $stmt_check_col->close();
                 
                 $colectivo_id_asignado = null;
                 $sql_assign = null;

                 if ($existing_col) {
                     // Si existe, verificar si ya está asignado a alguien más
                     if ($existing_col['id_usuario'] !== NULL && $existing_col['id_usuario'] != $usuario_id) {
                         throw new Exception("El colectivo '{$nombre_colectivo}' ya está asignado al Usuario ID {$existing_col['id_usuario']}.");
                     }
                     // Asignar el colectivo existente a este vendedor
                     $colectivo_id_asignado = $existing_col['id'];
                     $sql_assign = "UPDATE colectivos SET id_usuario = ? WHERE id = ?";
                 } else {
                     // Si NO existe, crearlo y asignarlo
                     $sql_create = "INSERT INTO colectivos (nombre_marca, id_usuario) VALUES (?, ?)";
                     $stmt_create = $conn->prepare($sql_create);
                     $stmt_create->bind_param("si", $nombre_colectivo, $usuario_id);
                     if (!$stmt_create->execute()) {
                         throw new Exception("Error al crear el nuevo colectivo.");
                     }
                     $stmt_create->close();
                     $colectivo_id_asignado = $conn->insert_id;
                     $sql_assign = "UPDATE colectivos SET id_usuario = ? WHERE id = ?"; // Usamos esta para reasignación
                 }

                 // Asignar o reasignar el colectivo al usuario
                 if (isset($colectivo_id_asignado)) {
                    $sql_assign_final = "UPDATE colectivos SET id_usuario = ? WHERE id = ?";
                    $stmt_assign = $conn->prepare($sql_assign_final);
                    $stmt_assign->bind_param("ii", $usuario_id, $colectivo_id_asignado);
                    $stmt_assign->execute();
                    $stmt_assign->close();
                 }
            }


            $mensaje = "Usuario y tienda actualizados con éxito.";
            $tipo_mensaje = 'bg-green-100 border-green-500 text-green-700';
            
        } elseif ($action === 'eliminar_usuario') {
            // Lógica existente para ELIMINAR
            if ($usuario_id == $user_id) {
                throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
            }
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            if (!$stmt->execute()) {
                throw new Exception("Error al eliminar el usuario. Revise las dependencias (ej. productos o pedidos).");
            }
            $stmt->close();
            
            $mensaje = "Usuario ID #{$usuario_id} eliminado con éxito.";
            $tipo_mensaje = 'bg-yellow-100 border-yellow-500 text-yellow-700';
        }

    } catch (Exception $e) {
        $mensaje = "Error: " . htmlspecialchars($e->getMessage());
        $tipo_mensaje = 'bg-red-100 border-red-500 text-red-700';
    } 
}

// -----------------------------------------------------------
// 3. OBTENER LISTA DE USUARIOS (incluyendo nombre de colectivo)
// -----------------------------------------------------------
$usuarios = [];

$sql_users = "
    SELECT 
        u.id, 
        u.nombre, 
        u.email, 
        u.rol, 
        u.fecha_registro,
        u.activo, 
        c.ultimo_pago_mensual,
        c.id AS colectivo_id_asignado,
        c.nombre_marca AS nombre_colectivo
    FROM 
        usuarios u
    LEFT JOIN 
        colectivos c ON u.id = c.id_usuario 
    ORDER BY u.id ASC
";
$result_users = $conn->query($sql_users);

if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $row['activo'] = (int)($row['activo'] ?? 0); 
        $usuarios[] = $row;
    }
}

// Incluir el header y el inicio del layout del dashboard
include $project_root . '/includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="flex h-screen bg-gray-50">
<aside class="w-64 bg-pink-50 text-gray-800 shadow-xl">
<div class="p-6 text-2xl font-bold border-b border-pink-200">
Colectivo CDI | <?php echo strtoupper($user_role); ?>
</div>
<div class="p-6">
<div class="text-lg font-semibold mb-4 border-b border-pink-200 pb-2">
Bienvenido, <?php echo htmlspecialchars($user_name); ?>
<span class="inline-block px-2 py-0.5 text-xs font-medium text-white rounded-full <?php echo $role_color; ?> ml-2">
<?php echo $role_display; ?>
</span>
</div>
<nav class="space-y-2">
<a href="<?php echo $base_url . 'pages/dashboard.php'; ?>" class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
<i class="fas fa-home mr-3"></i> Inicio
</a>
                <a href="<?php echo $base_url . 'pages/productos_vendedor.php'; ?>" class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
<i class="fas fa-shopping-bag mr-3"></i> Mis Productos
</a>
<a href="<?php echo $base_url . 'pages/pedidos_vendedor.php'; ?>" class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
<i class="fas fa-receipt mr-3"></i> Pedidos
</a>
<a href="<?php echo $base_url . 'pages/cupones_vendedor.php'; ?>" class="flex items-center p-3 rounded-lg text-pink-800 hover:bg-pink-100 transition duration-150">
<i class="fas fa-percent mr-3"></i> Cupones
</a>    
<a href="<?php echo $base_url . 'pages/admin/gestion_usuarios.php'; ?>" class="flex items-center p-3 rounded-lg bg-red-500 text-white transition duration-150">
<i class="fas fa-users-cog mr-3"></i> Gestión de Usuarios
</a>
<a href="<?php echo $base_url; ?>acciones/auth_action.php?action=logout" class="flex items-center p-3 rounded-lg bg-pink-600 text-white hover:bg-700 transition duration-150 mt-4">
<i class="fas fa-sign-out-alt mr-3"></i> Cerrar Sesión
</a>
</nav>
</div>
</aside>

<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
<div class="container mx-auto px-6 py-8">
<header class="flex justify-between items-center mb-8 border-b pb-4">
<h1 class="text-3xl font-extrabold text-gray-800">
<i class="fas fa-users-cog mr-2 text-red-500"></i> Gestión de Usuarios
</h1>
<button onclick="openModal()" 
class="bg-pink-600 text-white px-5 py-2 rounded-lg hover:bg-pink-700 transition shadow-md flex items-center">
<i class="fas fa-user-plus mr-2"></i> Crear Usuario
</button>
</header>

<?php if ($mensaje): ?>
<div class="border-l-4 p-4 mb-6 rounded <?php echo $tipo_mensaje; ?>" role="alert">
<p><?php echo $mensaje; ?></p>
</div>
<?php endif; ?>
             
<div class="bg-white shadow-lg rounded-xl overflow-hidden">
<table class="min-w-full divide-y divide-gray-200">
<thead class="bg-gray-50">
<tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">NOMBRE</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">EMAIL</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ROL</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PAGO MENSUAL</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">REGISTRO</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">VISIBILIDAD</th>
<th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ACCIONES</th>
</tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
<?php foreach ($usuarios as $usuario): ?>
<tr>
<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['id']); ?></td>
<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($usuario['email']); ?></td>
<td class="px-6 py-4 whitespace-nowrap">
<?php
    $role_bg = match($usuario['rol']) {
    'admin' => 'bg-red-200 text-red-800',
    'vendedor' => 'bg-pink-200 text-pink-800',
    default => 'bg-gray-200 text-gray-800',
    };
?>
<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $role_bg; ?>">
<?php echo ucfirst(htmlspecialchars($usuario['rol'])); ?>
</span>
</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($usuario['rol'] === 'vendedor'): ?>
                                    <?php 
                                        $ultimo_pago = $usuario['ultimo_pago_mensual'];
                                        $colectivo_exists = ($usuario['colectivo_id_asignado'] !== NULL); 

                                        if (!$colectivo_exists) {
                                            $pago_display = '❌ SIN TIENDA'; 
                                            $pago_color = 'bg-red-300 text-red-900';
                                            $ultimo_pago_fecha = 'N/A';
                                            $es_valido = false;
                                        } else {
                                            if ($ultimo_pago === NULL || $ultimo_pago === '0000-00-00') {
                                                $es_valido = false;
                                                $ultimo_pago_fecha = 'Nunca';
                                            } else {
                                                $es_valido = strtotime($ultimo_pago . ' + 30 days') >= time();
                                                $ultimo_pago_fecha = date('d/M/Y', strtotime($ultimo_pago));
                                            }
                                            $pago_display = $es_valido ? 'Al Corriente' : 'PENDIENTE';
                                            $pago_color = $es_valido ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                        }
                                    ?>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $pago_color; ?>">
                                            <?php echo $pago_display; ?>
                                        </span>
                                        <?php if ($colectivo_exists && !$es_valido): ?>
                                            <form id="pago_form_<?php echo $usuario['id']; ?>" method="POST" action="gestion_usuarios.php" class="inline">
                                                <input type="hidden" name="action" value="registrar_pago">
                                                <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="button" 
                                                        class="text-pink-600 hover:text-pink-800 text-xs font-bold"
                                                        title="Registrar pago al día de hoy"
                                                        onclick="confirmarPago(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')">
                                                    <i class="fas fa-check-circle"></i> Pagar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Último: <?php echo $ultimo_pago_fecha; ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-gray-400">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/M/Y', strtotime($usuario['fecha_registro'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php 
                                    $is_active = $usuario['activo'];
                                    $status_text = $is_active ? 'Visible' : 'Oculto';
                                    $status_class = $is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                    $toggle_text = $is_active ? 'Desactivar' : 'Activar';
                                    $toggle_icon = $is_active ? 'fas fa-eye-slash' : 'fas fa-eye';
                                ?>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <?php if ($usuario['id'] != $user_id): ?>
                                        <form method="POST" action="gestion_usuarios.php" class="inline">
                                            <input type="hidden" name="action" value="toggle_visibilidad">
                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $is_active; ?>">
                                            <button type="submit" 
                                                    class="text-blue-600 hover:text-blue-800 text-xs font-bold"
                                                    title="Alternar estado de visibilidad"
                                                    onclick="return confirm('¿Confirmar que desea <?php echo $toggle_text; ?> a <?php echo htmlspecialchars($usuario['nombre']); ?>?');">
                                                <i class="<?php echo $toggle_icon; ?>"></i> <?php echo $toggle_text; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                                <button 
                                    onclick='openModal(<?php echo json_encode($usuario); ?>)'
                                    class="text-blue-600 hover:text-blue-900 transition">
                                    <i class="fas fa-user-edit mr-1"></i> Editar 
                                </button>

                                <?php if ($usuario['id'] != $user_id): // No permitir eliminarse a sí mismo ?>
<form method="POST" action="gestion_usuarios.php" class="inline" onsubmit="return confirm('¿Está seguro de ELIMINAR al usuario <?php echo htmlspecialchars($usuario['nombre']); ?>?');">
<input type="hidden" name="action" value="eliminar_usuario">
<input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
<button type="submit" class="text-red-600 hover:text-red-900 transition">
<i class="fas fa-trash-alt mr-1"></i> Eliminar
</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="8" class="px-6 py-5 text-center text-gray-500">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
</tbody>
</table>
</div>

</div>
</main>
</div>

<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50 hidden">
    <div class="relative w-full max-w-md shadow-2xl rounded-2xl bg-white transform transition-all overflow-hidden border-t-4 border-pink-500">
        
        <button type="button" 
                onclick="closeModal()"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>

        <div class="p-6 md:p-8">
            <h3 class="text-2xl font-extrabold text-gray-800 border-b pb-3 mb-6" id="modalTitle">Crear Nuevo Usuario</h3>
            
            <form method="POST" action="gestion_usuarios.php" id="userForm" class="space-y-4">
                <input type="hidden" name="action" value="guardar_usuario">
                <input type="hidden" name="user_id" id="modalUserId" value="0">

                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" name="nombre" id="modalNombre" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="email" id="modalEmail" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700">Rol</label>
                    <select name="rol" id="modalRol" required
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        <option value="cliente">Cliente</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div id="colectivoField" class="hidden">
                    <label for="nombre_colectivo" class="block text-sm font-medium text-gray-700">
                        Nombre de Tienda/Colectivo 
                        <span class="text-xs text-gray-400">(Se creará si no existe y se asignará)</span>
                    </label>
                    <input type="text" name="nombre_colectivo" id="modalNombreColectivo"
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña <span id="passwordLabel" class="text-gray-400 text-xs">(Dejar vacío para no cambiar)</span></label>
                    <input type="password" name="password" id="modalPassword" 
                        class="mt-1 block w-full p-3 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div class="pt-4 flex space-x-4">
                    <button type="submit" id="submitButton" class="w-full px-4 py-3 bg-pink-500 text-white font-medium rounded-lg hover:bg-pink-700 transition shadow-lg flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 🚨 FUNCIÓN DE AISLAMIENTO DE PAGO 🚨
    function confirmarPago(userId, userName) {
        if (confirm('¿Confirmar que el vendedor ' + userName + ' realizó el pago hoy?')) {
            // Ejecuta el formulario de pago específico
            document.getElementById('pago_form_' + userId).submit();
        }
    }

    // Variables y lógica del Modal
    const userModal = document.getElementById('userModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalPassword = document.getElementById('modalPassword');
    const passwordLabel = document.getElementById('passwordLabel');
    const modalNombre = document.getElementById('modalNombre');
    const modalEmail = document.getElementById('modalEmail');
    const modalRol = document.getElementById('modalRol');
    const modalUserId = document.getElementById('modalUserId');
    
    // Variables de Colectivo
    const colectivoField = document.getElementById('colectivoField');
    const modalNombreColectivo = document.getElementById('modalNombreColectivo');

    // 🚨 Función para mostrar/ocultar el campo de colectivo 🚨
    // Usamos 'change' para actualizar al cambiar el rol
    modalRol.addEventListener('change', function() {
        if (this.value === 'vendedor') {
            colectivoField.classList.remove('hidden');
            modalNombreColectivo.setAttribute('required', 'required');
        } else {
            colectivoField.classList.add('hidden');
            modalNombreColectivo.removeAttribute('required');
            modalNombreColectivo.value = ''; // Limpiar el nombre si no es vendedor
        }
    });

    function openModal(userData = null) {
        // Lógica para rellenar los datos en el modal
        if (userData && userData.id) {
            modalTitle.textContent = 'Editar Usuario #' + userData.id;
            modalUserId.value = userData.id;
            // Usamos || '' para manejar valores nulos (evitar 'undefined')
            modalNombre.value = userData.nombre || ''; 
            modalEmail.value = userData.email || '';
            modalRol.value = userData.rol || '';
            
            // Lógica de Colectivo
            if (userData.rol === 'vendedor') {
                colectivoField.classList.remove('hidden');
                // Cargamos el nombre del colectivo asociado si existe, si no, se queda vacío.
                modalNombreColectivo.value = userData.nombre_colectivo || '';
                modalNombreColectivo.setAttribute('required', 'required');
            } else {
                colectivoField.classList.add('hidden');
                modalNombreColectivo.value = '';
                modalNombreColectivo.removeAttribute('required');
            }

            modalPassword.removeAttribute('required');
            modalPassword.value = ''; 
            passwordLabel.textContent = '(Dejar vacío para no cambiar)';
        } else {
            // Modo Creación
            modalTitle.textContent = 'Crear Nuevo Usuario';
            modalUserId.value = 0;
            document.getElementById('userForm').reset();
            
            colectivoField.classList.add('hidden');
            modalNombreColectivo.value = '';
            modalNombreColectivo.removeAttribute('required');

            modalPassword.setAttribute('required', 'required');
            passwordLabel.textContent = '(Requerida para nuevo)';
        }
        userModal.classList.remove('hidden');
        userModal.classList.add('flex');
    }

    function closeModal() {
        userModal.classList.add('hidden');
        userModal.classList.remove('flex');
    }
    
    // 🚨 Lógica para reabrir el modal tras un error o edición 🚨
    <?php 
    $action_after_post = $_POST['action'] ?? '';
    $is_error = ($tipo_mensaje === 'bg-red-100 border-red-500 text-red-700');
    $is_guardar = ($action_after_post === 'guardar_usuario');
    $user_id_post = (int)($_POST['user_id'] ?? 0);

    // El modal SOLO debe abrirse si:
    // 1. Hubo un error crítico (is_error)
    // 2. O si se intentó GUARDAR y falló (para mostrar el formulario con datos)
    
    if ($is_error && $is_guardar): 
    ?>
        document.addEventListener('DOMContentLoaded', function() {
             // Reabrir el modal con los datos del POST
             openModal(<?php echo json_encode([
                 'id' => $user_id_post, 
                 'nombre' => $_POST['nombre'] ?? null, 
                 'email' => $_POST['email'] ?? null, 
                 'rol' => $_POST['rol'] ?? 'cliente',
                 'nombre_colectivo' => $_POST['nombre_colectivo'] ?? null
             ]); ?>);
        });
    <?php endif; ?>
</script>

<?php 
if ($conn) $conn->close();
include $project_root . '/includes/footer.php'; 
?>