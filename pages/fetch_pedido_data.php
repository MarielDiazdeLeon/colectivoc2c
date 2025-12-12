<?php
// pages/fetch_pedido_data.php

// 🚨 Definir la ruta base absoluta del proyecto 🚨
$root_dir = realpath(__DIR__ . '/../'); 

// 1. Establecer el encabezado JSON
header('Content-Type: application/json');

// 2. Usar rutas absolutas para las inclusiones
require_once $root_dir . '/config/db.php'; 
require_once $root_dir . '/includes/funciones_sesion.php'; 

// Asegurar que solo usuarios logueados accedan a la información sensible
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Debe iniciar sesión para ver detalles.']);
    exit;
}

$conn = connect_db();
$pedido_id = (int)($_GET['pedido_id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($pedido_id > 0 && $conn) {
    
    $data = [];

    // ==========================================================
    // ACCIÓN 1: OBTENER DATOS DEL CLIENTE Y PAGO
    // ==========================================================
    if ($action === 'cliente') {
        $sql = "
            SELECT 
                p.nombre_cliente,
                p.apellido_cliente,
                p.email_cliente,
                p.direccion_envio,
                -- 🚨 USAMOS LOS NOMBRES EXACTOS DE TU BD Y RENOMBRAMOS PARA JS 🚨
                p.numero_tarjeta AS numero_tarjeta_completo,
                p.titular_tarjeta,
                p.fecha_vencimiento AS fecha_vencimiento_sim,
                IFNULL(mp.nombre, 'Pago no registrado') AS metodo_pago_nombre
            FROM pedidos p
            LEFT JOIN metodos_pago mp ON p.id_metodo_pago = mp.id
            WHERE p.id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => 'Error SQL (Cliente): ' . $conn->error]);
             exit;
        }

        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
    } 
    // ==========================================================
    // ACCIÓN 2: OBTENER DETALLE DE ARTÍCULOS (Agrupado)
    // ==========================================================
    elseif ($action === 'articulos') {
        $sql = "
            SELECT 
                prod.nombre AS producto_nombre,
                SUM(dp.cantidad) AS cantidad_total,
                dp.precio_unitario,
                (SUM(dp.cantidad) * dp.precio_unitario) AS subtotal_item
            FROM detalles_pedido dp
            JOIN productos prod ON dp.id_producto = prod.id
            WHERE dp.id_pedido = ?
            GROUP BY prod.nombre, dp.precio_unitario
        ";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => 'Error SQL (Artículos): ' . $conn->error]);
             exit;
        }

        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $articulos = [];
        while($row = $result->fetch_assoc()) {
            $articulos[] = $row;
        }
        $data['articulos'] = $articulos;
        $stmt->close();
    }
    
    $conn->close();
    
    if (!empty($data)) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Datos no encontrados para el pedido.']);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido inválido.']);
}
?>