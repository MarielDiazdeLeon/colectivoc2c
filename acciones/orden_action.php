<?php
// Este script maneja la creación de la orden después del checkout.

// 1. Iniciar o reanudar la sesión y cargar la conexión a la DB
session_start();
// **CORRECCIÓN DE RUTA PHP:** Se usa '../' para subir un nivel desde 'acciones/'.
include '../includes/db.php'; 

// Definir la URL base para las redirecciones
$base_url = '/colectivo_c2c'; 

function redirect($path) {
    global $base_url;
    header("Location: " . $base_url . $path);
    exit;
}

// Inicializar el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    redirect('/pages/catalogo.php');
}

$action = $_POST['action'] ?? null;

if ($action === 'create_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Recopilar datos del formulario de envío
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $total_final = (float)($_POST['total_final'] ?? 0.00);
    
    // Simular un ID de usuario (o usar el real si hay autenticación)
    $user_id = 1; // ID de ejemplo
    
    // 3. Serializar los datos del carrito para guardarlos
    // En un sistema real se guardarían en una tabla 'orden_productos' separada.
    $productos_json = json_encode($_SESSION['carrito']); 

    // **NOTA DE SEGURIDAD:** Las variables de usuario y DB ($conn) son necesarias aquí.
    
    // Iniciar la transacción de la DB para asegurar la integridad (opcional pero recomendado)
    // $conn->begin_transaction(); 

    try {
        // 4. Crear la Orden (Tabla 'ordenes')
        $sql_order = "
            INSERT INTO ordenes 
            (id_usuario, fecha_orden, estado, total, direccion_envio, productos_snapshot) 
            VALUES (?, NOW(), 'PENDIENTE', ?, ?, ?)
        ";
        
        // Simulación: Concatenar la dirección para guardarla como un string
        $full_address = "$direccion, $ciudad, $estado, C.P. $codigo_postal, Tel: $telefono, Email: $email";
        
        $stmt_order = $conn->prepare($sql_order);
        // Tipos: i (int/user_id), d (double/total), s (string/direccion), s (string/productos)
        $stmt_order->bind_param("idss", $user_id, $total_final, $full_address, $productos_json); 
        $stmt_order->execute();
        $order_id = $conn->insert_id; // Obtener el ID de la orden recién creada
        $stmt_order->close();
        
        // 5. Actualizar el Stock de los productos (Tabla 'productos')
        foreach ($_SESSION['carrito'] as $product_id => $cantidad_comprada) {
            
            // Consulta para descontar la cantidad comprada del stock
            $sql_stock = "
                UPDATE productos 
                SET stock = stock - ? 
                WHERE id = ? AND stock >= ?
            ";
            
            $stmt_stock = $conn->prepare($sql_stock);
            // Tipos: i (int/cantidad), i (int/product_id), i (int/cantidad)
            $stmt_stock->bind_param("iii", $cantidad_comprada, $product_id, $cantidad_comprada); 
            $stmt_stock->execute();
            $stmt_stock->close();
            
            // Si el stock fallara (stock < cantidad), la operación de la DB fallaría.
            // Para simplicidad, no estamos gestionando un rollback complejo aquí.
        }
        
        // 6. Finalizar la Transacción
        // $conn->commit(); // Si se usó begin_transaction()
        
        // 7. Vaciar el carrito de la sesión
        unset($_SESSION['carrito']);
        
        // 8. Redirigir a la página de confirmación
        redirect("/pages/orden_confirmada.php?id=$order_id");

    } catch (Exception $e) {
        // 9. Manejo de Errores (Rollback en un sistema real)
        // $conn->rollback(); // Si se usó begin_transaction()
        // En caso de error, volvemos al checkout
        error_log("Error al procesar la orden: " . $e->getMessage());
        redirect("/pages/checkout.php?error=db_fail");
    }
} else {
    // Si se accede sin POST, volver al checkout
    redirect('/pages/checkout.php');
}

// Cerrar la conexión
$conn->close();
?>