<?php
// pages/reportes.php
// Rediseño estilo "Pastel CDI" - glassmorphism suave
if (session_status() == PHP_SESSION_NONE) session_start();

// Definir base URL
$base_url = '/colectivo_c2c/';

// Definir ruta raíz
$project_root = realpath(__DIR__ . '/..');

require_once $project_root . '/config/db.php';
require_once $project_root . '/includes/funciones_sesion.php';

require_login();

$user_role = $_SESSION['user_role'] ?? 'invitado';
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$role_color = ($user_role == 'admin') ? 'bg-red-500' : 'bg-pink-600';
$role_display = ($user_role == 'admin') ? 'Administrador' : 'Vendedor';

$current_page_name = 'reportes';

$conn = connect_db();

// Funciones auxiliares
function format_money($v) {
    return '$' . number_format((float)$v, 2) . ' MXN';
}

function base_url_page($page) {
    global $base_url;
    return $base_url . 'pages/' . $page;
}

function fetch_report($conn, $sql, $types = '', $params = []) {
    $result = [];
    if ($stmt = $conn->prepare($sql)) {
        if ($types && !empty($params)) {
            $bind_names[] = $types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $result[] = $row;
        $stmt->close();
    }
    return $result;
}

// --------------------------------------------------------------------
// Filtros
// --------------------------------------------------------------------

$colectivos_dropdown = [];
if ($user_role === 'admin') {
    $colectivos_dropdown = fetch_report($conn, "SELECT id, nombre_marca FROM colectivos ORDER BY nombre_marca");
}

$report = $_GET['report'] ?? 'ventas';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$colectivo_filter_name = $_GET['colectivo_name'] ?? '';
$colectivo_filter_id = null;

$from_dt = date('Y-m-d', strtotime($from));
$to_dt = date('Y-m-d', strtotime($to));

// Si es vendedor, obtener el id de su colectivo
if ($user_role === 'vendedor') {
    $stmt = $conn->prepare("SELECT id FROM colectivos WHERE id_usuario = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $colectivo_filter_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
    $stmt->close();
}

// Si es admin y seleccionó un nombre de colectivo
if ($user_role === 'admin' && !empty($colectivo_filter_name)) {
    $stmt = $conn->prepare("SELECT id FROM colectivos WHERE nombre_marca = ? LIMIT 1");
    $stmt->bind_param('s', $colectivo_filter_name);
    $stmt->execute();
    $colectivo_filter_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
    $stmt->close();
}

$data = [];
$meta = ['title' => 'Reportes', 'summary' => []];
$colectivo_filter = $colectivo_filter_id;

// --------------------------------------------------------------------
// Selector de reportes
// --------------------------------------------------------------------

switch ($report) {

    case 'ventas':
    case 'pendientes':
    case 'entregados':
        $estado = $report === 'pendientes' ? 'Pendiente' :
                  ($report === 'entregados' ? 'Entregado' : '');

        $sql = "SELECT DISTINCT p.id, CONCAT(u.nombre, ' ', u.apellido) AS cliente, 
                        p.total, p.estado, p.fecha
                FROM pedidos p
                JOIN usuarios u ON p.id_usuario = u.id
                " . ($colectivo_filter ? "JOIN detalles_pedido dp ON p.id = dp.id_pedido 
                                         JOIN productos prod ON dp.id_producto = prod.id 
                                         WHERE prod.id_colectivo = ? " 
                                        : "WHERE 1=1 ") .
                ($estado ? "AND p.estado = ? " : "") . 
                "AND p.fecha BETWEEN ? AND ? ORDER BY p.fecha DESC";

        $params = [];
        $types = '';

        if ($colectivo_filter) { $types .= 'i'; $params[] = $colectivo_filter; }
        if ($estado) { $types .= 's'; $params[] = $estado; }
        $types .= 'ss';
        $params[] = $from_dt;
        $params[] = $to_dt;

        $data = fetch_report($conn, $sql, $types, $params);

        $meta['title'] = 
            $report === 'ventas' ? 'Reporte de Ventas' :
            ($report === 'pendientes' ? 'Pedidos Pendientes' : 'Pedidos Entregados');

        $total_ventas = array_sum(array_column($data, 'total'));
        $meta['summary'] = ['total_ventas' => $total_ventas, 'ganancia_neta' => $total_ventas];
        break;

    case 'productos':
        $sql = "SELECT id, nombre, precio, stock, id_colectivo 
                FROM productos 
                WHERE activo = 1" 
                . ($colectivo_filter ? " AND id_colectivo = ?" : "") . 
                " ORDER BY nombre";

        $data = fetch_report($conn, $sql, $colectivo_filter ? 'i' : '', $colectivo_filter ? [$colectivo_filter] : []);
        $meta['title'] = "Productos Activos";
        break;

    case 'vendedores':
        if ($user_role === 'vendedor') {
            header('Location: ' . base_url_page('dashboard.php'));
            exit;
        }

        $sql = "SELECT c.id AS colectivo_id, CONCAT(u.nombre, ' ', u.apellido) AS vendedor, 
                        c.nombre_marca,
                        COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total_ventas, 
                        COUNT(DISTINCT p.id) AS pedidos
                FROM colectivos c
                JOIN usuarios u ON c.id_usuario = u.id
                LEFT JOIN productos prod ON prod.id_colectivo = c.id
                LEFT JOIN detalles_pedido dp ON dp.id_producto = prod.id
                LEFT JOIN pedidos p ON p.id = dp.id_pedido AND p.fecha BETWEEN ? AND ?
                GROUP BY c.id, u.nombre, u.apellido, c.nombre_marca
                ORDER BY total_ventas DESC";

        $data = fetch_report($conn, $sql, 'ss', [$from_dt, $to_dt]);
        $meta['title'] = "Reporte por Vendedores";
        break;

    case 'ganancias':
        if ($user_role === 'vendedor') { 
            header('Location: ' . base_url_page('dashboard.php')); exit;
        }

        $sql = "SELECT p.id, CONCAT(u.nombre, ' ', u.apellido) AS cliente, p.total, p.fecha, 
                        SUM(dp.cantidad * (prod.precio * (prod.comision_porcentaje / 100))) AS comision_estimada
                FROM pedidos p 
                JOIN usuarios u ON p.id_usuario = u.id 
                JOIN detalles_pedido dp ON p.id = dp.id_pedido
                JOIN productos prod ON dp.id_producto = prod.id
                WHERE p.fecha BETWEEN ? AND ?
                " . ($colectivo_filter ? "AND prod.id_colectivo = ? " : "") . "
                GROUP BY p.id, u.nombre, u.apellido, p.total, p.fecha
                ORDER BY p.fecha DESC";

        $params = [$from_dt, $to_dt];
        $types = 'ss';
        if ($colectivo_filter) { $types .= 'i'; $params[] = $colectivo_filter; }

        $data = fetch_report($conn, $sql, $types, $params);

        $total_ventas = array_sum(array_column($data, 'total'));
        $total_comisiones = array_sum(array_column($data, 'comision_estimada') ?: []); 

        $meta['title'] = "Ganancias por Comisiones";
        $meta['summary'] = [
            'total_ventas'     => $total_ventas,
            'total_comisiones' => $total_comisiones,
            'ganancia_neta'    => $total_comisiones
        ];
        break;

    case 'mensualidad':
        if ($user_role === 'vendedor') {
            $sql = "SELECT c.id, CONCAT(u.nombre, ' ', u.apellido) AS vendedor, c.nombre_marca, c.ultimo_pago_mensual 
                    FROM colectivos c 
                    JOIN usuarios u ON c.id_usuario = u.id
                    WHERE c.id_usuario = ? 
                    ORDER BY c.ultimo_pago_mensual DESC";
            $data = fetch_report($conn, $sql, 'i', [$user_id]);
        } else {
            $sql = "SELECT c.id, CONCAT(u.nombre, ' ', u.apellido) AS vendedor, c.nombre_marca, c.ultimo_pago_mensual 
                    FROM colectivos c 
                    JOIN usuarios u ON c.id_usuario = u.id 
                    ORDER BY c.ultimo_pago_mensual DESC";
            $data = fetch_report($conn, $sql);
        }
        $meta['title'] = "Pagos de Mensualidad";
        break;

    default:
        $meta['title'] = "Reporte Desconocido";
}

// --------------------------------------------------------------------
// Vista HTML
// --------------------------------------------------------------------

require_once $project_root . '/includes/header.php';
?>

<!-- =========================
     Estilos específicos (pastel / glass)
     ========================= -->
<style>
/* Glass + pastel overrides */
.bg-glass {
    background: rgba(255, 247, 251, 0.7);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    border: 1px solid rgba(249, 207, 221, 0.5);
}
.card-glass {
    background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.70));
    border: 1px solid rgba(243,178,210,0.25);
    box-shadow: 0 8px 24px rgba(243,178,210,0.08);
    border-radius: 14px;
}
.chip {
    background: linear-gradient(90deg, rgba(249,169,212,0.12), rgba(249,169,212,0.08));
    border: 1px solid rgba(249,169,212,0.18);
    color: #b94e82;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.85rem;
}
.table-row-hover:hover {
    background: rgba(255, 236, 246, 0.6);
    transition: background .12s ease;
}
.table-rounded {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 12px;
    overflow: hidden;
}
.table-rounded thead th {
    background: rgba(255,255,255,0.9);
}
.no-results {
    color: #6b7280;
}
@media (max-width: 768px) {
    .grid-filter { grid-template-columns: repeat(1, minmax(0, 1fr)); }
}
</style>

<div class="bg-gradient-to-b from-pink-50 to-white min-h-screen pb-20">
    <main class="container mx-auto px-6 py-10">

        <!-- Breadcrumb / volver -->
        <div class="flex items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-4">
                <a href="<?php echo base_url_page('dashboard.php'); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-pink-600 text-white font-medium hover:bg-pink-700 transition-shadow shadow">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>

                <div>
                    <h1 class="text-3xl font-extrabold text-pink-700"><?php echo htmlspecialchars($meta['title']); ?></h1>
                    <p class="text-sm text-gray-500 mt-1">Período: <strong class="text-gray-700"><?php echo htmlspecialchars($from_dt); ?></strong> — <strong class="text-gray-700"><?php echo htmlspecialchars($to_dt); ?></strong></p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="chip">Usuario: <?php echo htmlspecialchars($user_name); ?></span>
                <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-white border border-pink-100 shadow hover:shadow-md transition">
                    <i class="fas fa-print text-pink-600 mr-2"></i>Imprimir
                </button>
            </div>
        </div>

        <!-- Contenedor principal glass -->
        <div class="card-glass p-6">

            <!-- FILTROS -->
            <form method="get" id="report-form" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-6 grid-filter no-print">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Reporte</label>
                    <select name="report" onchange="document.getElementById('report-form').submit();" class="w-full p-2 rounded-lg border border-pink-100 focus:ring-pink-300 focus:border-pink-300">
                        <option value="ventas"     <?= $report=='ventas' ? 'selected' : '' ?>>Ventas</option>
                        <option value="pendientes" <?= $report=='pendientes' ? 'selected' : '' ?>>Pedidos Pendientes</option>
                        <option value="entregados" <?= $report=='entregados' ? 'selected' : '' ?>>Pedidos Entregados</option>
                        <option value="productos"  <?= $report=='productos' ? 'selected' : '' ?>>Productos Activos</option>
                        <?php if ($user_role === 'admin'): ?>
                            <option value="vendedores" <?= $report=='vendedores' ? 'selected' : '' ?>>Reporte por Vendedores</option>
                            <option value="ganancias"  <?= $report=='ganancias' ? 'selected' : '' ?>>Ganancias</option>
                            <option value="mensualidad"<?= $report=='mensualidad' ? 'selected' : '' ?>>Pagos Mensualidad</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Desde</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($from_dt) ?>" class="w-full p-2 rounded-lg border border-pink-100 focus:ring-pink-300 focus:border-pink-300" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Hasta</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($to_dt) ?>" class="w-full p-2 rounded-lg border border-pink-100 focus:ring-pink-300 focus:border-pink-300" />
                </div>

                <?php if ($user_role === 'admin'): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Colectivo</label>
                    <select name="colectivo_name" class="w-full p-2 rounded-lg border border-pink-100 focus:ring-pink-300 focus:border-pink-300">
                        <option value="">-- Todos --</option>
                        <?php foreach ($colectivos_dropdown as $col): ?>
                            <option value="<?= htmlspecialchars($col['nombre_marca']) ?>" <?= $colectivo_filter_name === $col['nombre_marca'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($col['nombre_marca']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-pink-600 text-white font-semibold shadow hover:bg-pink-700 transition">Aplicar</button>
                </div>
            </form>

            <!-- RESUMEN (si aplica) -->
            <?php if (!empty($meta['summary'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white/80 p-4 rounded-lg border border-pink-50 shadow-sm">
                    <div class="text-sm text-gray-500">Total Ventas</div>
                    <div class="text-2xl font-bold text-pink-700 mt-1"><?= format_money($meta['summary']['total_ventas']) ?></div>
                    <div class="text-xs text-gray-400 mt-1">Periodo seleccionado</div>
                </div>

                <?php if (isset($meta['summary']['total_comisiones'])): ?>
                <div class="bg-white/80 p-4 rounded-lg border border-pink-50 shadow-sm">
                    <div class="text-sm text-gray-500">Comisiones Estimadas</div>
                    <div class="text-2xl font-bold text-pink-700 mt-1"><?= format_money($meta['summary']['total_comisiones']) ?></div>
                    <div class="text-xs text-gray-400 mt-1">Comisión plataforma</div>
                </div>
                <?php endif; ?>

                <?php if (isset($meta['summary']['ganancia_neta'])): ?>
                <div class="bg-white/80 p-4 rounded-lg border border-pink-50 shadow-sm">
                    <div class="text-sm text-gray-500">Ganancia Neta</div>
                    <div class="text-2xl font-bold text-pink-700 mt-1"><?= format_money($meta['summary']['ganancia_neta']) ?></div>
                    <div class="text-xs text-gray-400 mt-1">Estimación neta</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Tabla de resultados -->
            <div class="overflow-x-auto">
                <?php if (empty($data)): ?>
                    <div class="py-12 text-center no-results">
                        No se encontraron registros para los filtros seleccionados.
                    </div>
                <?php else: ?>
                    <table class="min-w-full table-rounded">
                        <thead>
                            <tr>
                                <?php
                                $th = 'px-6 py-3 text-left text-xs font-semibold text-pink-700 uppercase tracking-wide';
                                switch($report) {
                                    case 'ventas': case 'pendientes': case 'entregados':
                                        echo "<th class='$th'>ID</th><th class='$th'>Cliente</th><th class='$th'>Total</th><th class='$th'>Estado</th><th class='$th'>Fecha</th>";
                                        break;
                                    case 'productos':
                                        echo "<th class='$th'>ID</th><th class='$th'>Nombre</th><th class='$th'>Precio</th><th class='$th'>Stock</th><th class='$th'>Colectivo</th>";
                                        break;
                                    case 'vendedores':
                                        echo "<th class='$th'>ID Colectivo</th><th class='$th'>Vendedor</th><th class='$th'>Marca</th><th class='$th'>Total Ventas</th><th class='$th'>Pedidos</th>";
                                        break;
                                    case 'ganancias':
                                        echo "<th class='$th'>ID Pedido</th><th class='$th'>Cliente</th><th class='$th'>Total Venta</th><th class='$th'>Comisión</th><th class='$th'>Fecha</th>";
                                        break;
                                    case 'mensualidad':
                                        echo "<th class='$th'>ID Colectivo</th><th class='$th'>Vendedor</th><th class='$th'>Marca</th><th class='$th'>Último Pago</th>";
                                        break;
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $td = 'px-6 py-4 text-sm text-gray-800 whitespace-nowrap table-row-hover';
                        foreach ($data as $r): ?>
                            <tr class="<?= ($r['estado'] ?? '') === 'Pendiente' ? '' : '' ?>">
                                <?php
                                switch($report) {

                                    case 'ventas':
                                    case 'pendientes':
                                    case 'entregados':
                                        echo "<td class='$td'>#" . htmlspecialchars($r['id']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['cliente']) . "</td>";
                                        echo "<td class='$td'>" . format_money($r['total']) . "</td>";
                                        $estado_label = htmlspecialchars($r['estado']);
                                        $badge_class = ($r['estado'] == 'Pendiente') ? 'bg-pink-100 text-pink-700' : 'bg-green-100 text-green-700';
                                        echo "<td class='$td'><span class='px-3 py-1 rounded-full text-xs font-semibold " . $badge_class . "'>$estado_label</span></td>";
                                        echo "<td class='$td'>" . date('d/M/Y', strtotime($r['fecha'])) . "</td>";
                                        break;

                                    case 'productos':
                                        echo "<td class='$td'>" . htmlspecialchars($r['id']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['nombre']) . "</td>";
                                        echo "<td class='$td'>" . format_money($r['precio']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['stock']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['id_colectivo']) . "</td>";
                                        break;

                                    case 'vendedores':
                                        echo "<td class='$td'>" . htmlspecialchars($r['colectivo_id']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['vendedor']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['nombre_marca']) . "</td>";
                                        echo "<td class='$td'>" . format_money($r['total_ventas']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['pedidos']) . "</td>";
                                        break;

                                    case 'ganancias':
                                        echo "<td class='$td'>#" . htmlspecialchars($r['id']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['cliente']) . "</td>";
                                        echo "<td class='$td'>" . format_money($r['total']) . "</td>";
                                        echo "<td class='$td'>" . format_money($r['comision_estimada']) . "</td>";
                                        echo "<td class='$td'>" . date('d/M/Y', strtotime($r['fecha'])) . "</td>";
                                        break;

                                    case 'mensualidad':
                                        $pago = ($r['ultimo_pago_mensual'] === '0000-00-00' || empty($r['ultimo_pago_mensual'])) ? 'Nunca' : htmlspecialchars($r['ultimo_pago_mensual']);
                                        echo "<td class='$td'>" . htmlspecialchars($r['id']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['vendedor']) . "</td>";
                                        echo "<td class='$td'>" . htmlspecialchars($r['nombre_marca']) . "</td>";
                                        echo "<td class='$td'>" . $pago . "</td>";
                                        break;
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>

    </main>
</div>

<?php
require_once $project_root . '/includes/footer.php';
if ($conn) $conn->close();
?>
