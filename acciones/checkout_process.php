<?php
// Este archivo procesa el formulario de checkout, registra el pedido en la DB y vacía el carrito.

// 1. INCLUIR CONFIGURACIÓN Y SESIÓN
require_once __DIR__ . '/../config/db.php';
session_start();

// Validar que la solicitud sea POST y que el carrito no esté vacío
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['carrito'])) {
    header('Location: ../pages/carrito.php');
    exit;
}

$conn->begin_transaction(); // Iniciar una transacción para asegurar la integridad de los datos
$success = false;
$error_message = "Error desconocido al procesar el pedido.";

try {
    // 2. RECUPERAR DATOS DEL FORMULARIO Y CALCULAR TOTALES
    $carrito = $_SESSION['carrito'];
    
    // Simulación de datos del usuario (los que vienen del POST)
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $direccion_completa = $_POST['direccion'] . ', ' . $_POST['ciudad'] . ', ' . $_POST['estado'] . ', CP: ' . $_POST['cp'];
    
    $subtotal = 0;
    foreach ($carrito as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
    }
    $costo_envio = $subtotal > 500 ? 0.00 : 99.00;
    $total = $subtotal + $costo_envio;
    
    // Simulación del ID de usuario (si no hay login, usamos un ID genérico o 0)
    // En un sistema real, $user_id vendría de $_SESSION['user_id']
    $user_id = 1; // Asumimos un ID de cliente por defecto para la simulación
    $fecha_pedido = date('Y-m-d H:i:s');
    $status = 'Pendiente de Pago'; // O 'Pagado' si la pasarela fuera real
    
    // 3. REGISTRAR EL PEDIDO EN LA TABLA 'pedidos'
    $sql_pedido = "INSERT INTO pedidos (id_usuario, fecha_pedido, total, direccion_envio, estado) VALUES (?, ?, ?, ?, ?)";
    if ($stmt_pedido = $conn->prepare($sql_pedido)) {
        $stmt_pedido->bind_param("isdss", $user_id, $fecha_pedido, $total, $direccion_completa, $status);
        if (!$stmt_pedido->execute()) {
            throw new Exception("Error al insertar en pedidos: " . $stmt_pedido->error);
        }
        $pedido_id = $stmt_pedido->insert_id;
        $stmt_pedido->close();
    } else {
        throw new Exception("Error al preparar la consulta de pedidos: " . $conn->error);
    }
    
    // 4. REGISTRAR DETALLES Y ACTUALIZAR STOCK
    $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?";
    
    if ($stmt_detalle = $conn->prepare($sql_detalle) && $stmt_stock = $conn->prepare($sql_stock)) {
        
        foreach ($carrito as $id_producto => $item) {
            $cantidad = $item['cantidad'];
            $precio = $item['precio'];
            
            // a) Insertar detalle de pedido
            $stmt_detalle = $conn->prepare($sql_detalle);
            $stmt_detalle->bind_param("iiid", $pedido_id, $id_producto, $cantidad, $precio);
            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al insertar detalle de pedido para producto ID {$id_producto}: " . $stmt_detalle->error);
            }
            $stmt_detalle->close();

            // b) Actualizar stock
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("iii", $cantidad, $id_producto, $cantidad);
            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar stock para producto ID {$id_producto}: " . $stmt_stock->error);
            }
            // Verificar si la actualización afectó una fila (si el stock era suficiente)
            if ($conn->affected_rows === 0) {
                 throw new Exception("El stock del producto ID {$id_producto} es insuficiente.");
            }
            $stmt_stock->close();
        }
    } else {
        throw new Exception("Error al preparar consultas de detalle o stock.");
    }

    // 5. CONFIRMAR TRANSACCIÓN
    $conn->commit();
    $success = true;

} catch (Exception $e) {
    // 6. MANEJO DE ERRORES y ROLLBACK
    $conn->rollback();
    $error_message = $e->getMessage();
    // En un sistema real, guardaríamos $error_message en un log
    $_SESSION['checkout_error'] = $error_message;
} finally {
    // Cierre de la conexión
    $conn->close();
}

// 7. REDIRECCIÓN FINAL
if ($success) {
    // 8. VACIAMOS EL CARRITO SÓLO si la orden se registró con éxito
    unset($_SESSION['carrito']);
    
    // Redirigir a la página de confirmación con el ID del pedido
    header("Location: ../pages/confirmacion.php?pedido={$pedido_id}");
} else {
    // Si hubo un error, redirigir de vuelta al checkout o al carrito
    header("Location: ../pages/checkout.php?error=1");
}
exit;
?>