<?php
// Este archivo maneja las acciones del carrito de compras: añadir, actualizar, eliminar.

// 1. INCLUIR CONFIGURACIÓN Y SESIÓN
require_once __DIR__ . '/../config/db.php';

// Evitar error de sesión ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aseguramos que la variable de sesión del carrito exista
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 2. VALIDAR LA SOLICITUD
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    header('Location: ../index.php');
    exit;
}

// Obtener datos del formulario
$action     = $_POST['action'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$cantidad   = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

// URL de redirección por defecto
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../index.php';

// Validar ID y Cantidad
if ($product_id <= 0 || $cantidad <= 0) {
    header('Location: ' . $redirect_url);
    exit;
}

// 3. PROCESAR ACCIÓN
switch ($action) {
    case 'add':
        $sql = "SELECT precio, stock, nombre FROM productos WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto_db = $result->fetch_assoc();
            $stmt->close();

            if ($producto_db) {
                $precio           = $producto_db['precio'];
                $stock_disponible = $producto_db['stock'];
                $nombre_producto  = $producto_db['nombre'];

                $current_qty = $_SESSION['carrito'][$product_id]['cantidad'] ?? 0;
                $new_qty     = $current_qty + $cantidad;

                if ($new_qty <= $stock_disponible) {
                    $_SESSION['carrito'][$product_id] = [
                        'nombre'   => $nombre_producto,
                        'precio'   => $precio,
                        'cantidad' => $new_qty
                    ];
                    $redirect_url = '../pages/carrito.php';
                } else {
                    // Opcional: manejar error de stock
                    // $_SESSION['error'] = "Solo hay {$stock_disponible} unidades disponibles.";
                }
            }
        }
        break;

    case 'remove':
        if (isset($_SESSION['carrito'][$product_id])) {
            unset($_SESSION['carrito'][$product_id]);
        }
        $redirect_url = '../pages/carrito.php';
        break;

    case 'update':
        if (isset($_SESSION['carrito'][$product_id])) {
            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$product_id]);
            } else {
                $_SESSION['carrito'][$product_id]['cantidad'] = $cantidad;
            }
        }
        $redirect_url = '../pages/carrito.php';
        break;

    case 'clear':
        $_SESSION['carrito'] = [];
        $redirect_url = '../pages/carrito.php';
        break;
}

// 4. REDIRECCIÓN FINAL
$conn->close();
header('Location: ' . $redirect_url);
exit;
?>
