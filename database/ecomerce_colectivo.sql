-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-12-2025 a las 06:13:54
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ecomerce_colectivo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Ropa'),
(2, 'Accesorios'),
(3, 'Hogar'),
(4, 'Arte'),
(5, 'Zapatos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colectivos`
--

CREATE TABLE `colectivos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `ultimo_pago_mensual` date DEFAULT NULL,
  `nombre_marca` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `colectivos`
--

INSERT INTO `colectivos` (`id`, `id_usuario`, `ultimo_pago_mensual`, `nombre_marca`, `descripcion`, `logo_url`) VALUES
(1, 2, '2025-12-10', 'Vintage Sinaloa', 'Ropa de segunda mano curada con estilo.', NULL),
(2, 19, '2025-12-10', 'Itzel Shop', NULL, NULL),
(3, 5, NULL, 'Dafne Shop', 'ccc', NULL),
(4, 20, '2025-12-10', 'Itzel Shop', NULL, NULL),
(5, 22, '2025-12-10', 'Rodrigo Shop', NULL, NULL),
(6, 23, '2025-12-10', 'Caro Shop', NULL, NULL),
(7, 24, '2025-12-10', 'Caaaa', NULL, NULL),
(8, 6, '2025-12-10', 'cccccccccccc', NULL, NULL),
(9, 15, '2025-12-10', 'chato', NULL, NULL),
(10, 8, '2025-12-10', 'prueba', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones_vendedor`
--

CREATE TABLE `comisiones_vendedor` (
  `id` int(11) NOT NULL,
  `id_orden_detalle` int(11) NOT NULL COMMENT 'Referencia al producto vendido en la orden',
  `id_vendedor` int(11) NOT NULL,
  `monto_base_venta` decimal(10,2) NOT NULL COMMENT 'Subtotal del producto vendido',
  `porcentaje_comision` decimal(5,2) NOT NULL,
  `monto_comision` decimal(10,2) NOT NULL COMMENT 'Calculado: monto_base_venta * porcentaje_comision / 100',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('PENDIENTE','PAGADA') NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de comisiones ganadas por cada vendedor';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_colectivo` int(11) DEFAULT NULL,
  `tipo` enum('porcentaje','fijo') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `usos_maximos` int(11) DEFAULT 0,
  `usos_actuales` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`id`, `codigo`, `descripcion`, `id_colectivo`, `tipo`, `valor`, `fecha_expiracion`, `usos_maximos`, `usos_actuales`, `activo`) VALUES
(6, 'Navidad25', 'Cupón por el mes de diciembre en todas las compras sin monto mínimo.', 3, 'porcentaje', 15.00, '2025-12-31', 5, 0, 1),
(7, 'Navidad10', 'Cupón por el mes de diciembre en todas las compras sin monto mínimo.', 3, 'porcentaje', 10.00, '2025-12-27', 2, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`) VALUES
(6, 18, 10, 1, 10.00),
(7, 19, 11, 1, 10.00),
(8, 20, 10, 1, 10.00),
(9, 20, 11, 1, 10.00),
(10, 21, 11, 1, 10.00),
(11, 22, 3, 1, 120.00),
(12, 23, 10, 2, 10.00),
(13, 24, 2, 1, 150.00),
(14, 25, 2, 1, 150.00),
(15, 26, 11, 1, 10.00),
(16, 27, 2, 1, 150.00),
(17, 28, 2, 1, 150.00),
(18, 29, 2, 1, 150.00),
(19, 30, 3, 1, 120.00),
(20, 31, 10, 1, 10.00),
(21, 32, 11, 1, 10.00),
(22, 33, 13, 1, 120.00),
(23, 35, 14, 1, 120.00),
(24, 36, 14, 1, 120.00),
(25, 37, 14, 1, 120.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id`, `nombre`) VALUES
(1, 'Débito');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes`
--

CREATE TABLE `ordenes` (
  `id` int(11) NOT NULL COMMENT 'ID de la Orden (clave primaria)',
  `id_cliente` int(11) NOT NULL DEFAULT 1 COMMENT 'ID del cliente que realizó la compra',
  `fecha_orden` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora en que se realizó el pedido',
  `estado` varchar(50) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'Estado actual del pedido (PENDIENTE, PROCESANDO, ENVIADO, ENTREGADO, CANCELADO)',
  `total` decimal(10,2) NOT NULL COMMENT 'Monto total de la compra (incluyendo envío)',
  `direccion_envio` text NOT NULL COMMENT 'Datos completos de envío y contacto (nombre, dirección, teléfono, email)',
  `productos_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Copia de los productos comprados (para referencia, aunque ordenes_detalle es el estándar)' CHECK (json_valid(`productos_snapshot`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para almacenar los pedidos realizados por los clientes';

--
-- Volcado de datos para la tabla `ordenes`
--

INSERT INTO `ordenes` (`id`, `id_cliente`, `fecha_orden`, `estado`, `total`, `direccion_envio`, `productos_snapshot`) VALUES
(1, 21, '2025-12-10 10:57:53', 'Pendiente', 443.70, 'Residencial Fundadores, Guasave, Sinaloa, 81044.', '[{\"id\":\"1\",\"title\":\"Chamarra Mezclilla 90s\",\"quantity\":1,\"unit_price\":450,\"stock\":1,\"subtotal\":450,\"id_colectivo\":\"1\"}]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_detalle`
--

CREATE TABLE `ordenes_detalle` (
  `id` int(11) NOT NULL COMMENT 'ID de Detalle (clave primaria)',
  `id_orden` int(11) NOT NULL COMMENT 'ID de la Orden a la que pertenece este detalle',
  `id_producto` int(11) NOT NULL COMMENT 'ID del producto comprado',
  `cantidad` int(11) NOT NULL COMMENT 'Cantidad comprada de este producto',
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio unitario del producto al momento de la compra',
  `subtotal` decimal(10,2) NOT NULL COMMENT 'Precio total por la cantidad (cantidad * precio_unitario)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para almacenar los detalles (productos y cantidades) de cada pedido';

--
-- Volcado de datos para la tabla `ordenes_detalle`
--

INSERT INTO `ordenes_detalle` (`id`, `id_orden`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 1, 450.00, 450.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `apellido_cliente` varchar(100) NOT NULL,
  `email_cliente` varchar(100) DEFAULT NULL,
  `id_metodo_pago` int(11) DEFAULT NULL,
  `numero_tarjeta` varchar(16) DEFAULT NULL,
  `titular_tarjeta` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` varchar(5) DEFAULT NULL,
  `direccion_envio` text NOT NULL,
  `estado` enum('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `id_usuario`, `fecha`, `total`, `nombre_cliente`, `apellido_cliente`, `email_cliente`, `id_metodo_pago`, `numero_tarjeta`, `titular_tarjeta`, `fecha_vencimiento`, `direccion_envio`, `estado`) VALUES
(18, 21, '2025-12-09 14:02:55', 110.00, 'Pablo', 'Fernandez', 'armentaics@gmail.com.', 1, NULL, NULL, NULL, 'Emiliano Zapata, Guasave, Sinaloa, 81048.', 'pendiente'),
(19, 21, '2025-12-09 15:09:17', 110.00, 'Carolina', 'Armenta', 'armentaics@gmail.com.', 1, NULL, NULL, NULL, 'Emiliano Zapata, Guasave, Sinaloa, 81048. ', 'pendiente'),
(20, 21, '2025-12-09 15:11:23', 120.00, 'Carolina', 'Armenta', 'armentaics@gmail.com', 1, NULL, NULL, NULL, 'Emiliano Zapata, Guasave, Sinaloa, 81048.', 'pendiente'),
(21, 21, '2025-12-09 18:05:05', 111.60, 'Carolina', 'Armenta', 'armentaics@gmail.com', 2, '1234123412341234', 'Itzel C Armenta', '08/32', 'Emiliano Zapata, Guasave, Sinaloa, 81048.', 'pendiente'),
(22, 21, '2025-12-09 20:38:59', 239.20, 'Carolina', 'Armenta', 'armentaics@gmail.com', 2, '1234123412341234', 'Itzel C Armenta', '08/32', 'Emiliano Zapata, Guasave, Sinaloa, 81048.', 'pendiente'),
(23, 21, '2025-12-09 20:50:43', 119.72, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(24, 21, '2025-12-09 21:07:57', 274.00, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(25, 21, '2025-12-09 21:09:21', 274.00, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(26, 21, '2025-12-10 10:32:37', 111.60, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(27, 21, '2025-12-10 10:40:52', 274.00, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(28, 21, '2025-12-10 10:50:03', 274.00, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(29, 21, '2025-12-10 11:01:37', 247.90, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(30, 21, '2025-12-10 11:10:21', 218.32, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(31, 21, '2025-12-10 11:16:28', 111.60, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(32, 21, '2025-12-10 18:25:09', 111.60, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(33, 21, '2025-12-11 16:38:44', 239.20, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(34, 21, '2025-12-11 16:45:23', 100.00, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(35, 21, '2025-12-11 16:58:25', 239.20, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(36, 21, '2025-12-11 17:02:28', 239.20, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'pendiente'),
(37, 21, '2025-12-11 17:06:48', 239.20, 'Mariano', 'Cota', 'maco@gmail.com', 2, '7410963222228520', 'Mariano A Cota', '27/28', 'Residencial Fundadores, Guasave, Sinaloa, 81044.', 'enviado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `id_vendedor` int(11) DEFAULT NULL,
  `id_colectivo` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `comision_porcentaje` decimal(5,2) NOT NULL DEFAULT 10.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen_url` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `vendidos` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `id_vendedor`, `id_colectivo`, `id_categoria`, `nombre`, `descripcion`, `precio`, `comision_porcentaje`, `stock`, `activo`, `imagen_url`, `fecha_creacion`, `vendidos`) VALUES
(1, 2, 1, 1, 'Chamarra Mezclilla 90s', 'Chamarra vintage talla M en buen estado.', 450.00, 10.00, 0, 1, 'chamarra.jpg', '2025-11-25 10:39:56', 0),
(2, 2, 1, 2, 'Lentes de Sol Retro', 'Lentes estilo aviador marco dorado.', 150.00, 10.00, 0, 1, 'lentes.jpg', '2025-11-25 10:39:56', 0),
(3, 5, 3, 2, 'aaaa', 'ww', 120.00, 10.00, 0, 1, 'https://placehold.co/400x400/pink/white?text=Producto', '2025-12-07 22:04:17', 0),
(10, 5, 3, 2, 'Pulsera de trebol', 'Acero Inoxidable', 10.00, 10.00, 0, 1, '/colectivo_c2c/assets/uploads/productos/1765257421_accesorios.jpg', '2025-12-08 16:56:21', 0),
(11, 5, 3, 2, 'Pulsera de trebol', 'prueba', 10.00, 10.00, 0, 1, '/colectivo_c2c/assets/uploads/productos/1765257433_accesorios.jpg', '2025-12-08 16:57:07', 0),
(12, 5, 3, 2, 'Pulsera de flores', 'Pulsera', 120.00, 10.00, 0, 0, '/colectivo_c2c/assets/uploads/productos/1765257441_accesorios.jpg', '2025-12-08 17:01:59', 0),
(13, 5, 3, 2, 'Aretes de gota', 'Aretes acero inoxidable.', 120.00, 10.00, 0, 1, '/colectivo_c2c/assets/uploads/productos/1765257448_Ac-blender.jpg', '2025-12-08 22:00:29', 0),
(14, 5, 3, 2, 'Aretes de perla', 'Perlas', 120.00, 10.00, 7, 1, '/colectivo_c2c/assets/uploads/productos/1765257454_accesorios.jpg', '2025-12-08 22:03:31', 0),
(15, 23, 6, 2, 'Anillo Aurora', 'Anillo con diamante redondo, color oro.', 590.94, 10.00, 2, 1, '/colectivo_c2c/assets/img/placeholder.jpg', '2025-12-11 17:29:56', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reseñas`
--

CREATE TABLE `reseñas` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `calificacion` tinyint(1) NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reseñas`
--

INSERT INTO `reseñas` (`id`, `id_producto`, `nombre_cliente`, `calificacion`, `comentario`, `fecha`) VALUES
(1, 2, 'carolina armenta', 5, 'Muy super cute!!!', '2025-12-01 11:30:08'),
(2, 1, 'Cliente Anónimo', 5, 'prueba', '2025-12-04 10:54:30'),
(3, 2, 'Cliente Anónimo', 5, 'Excelente producto', '2025-12-07 19:23:48'),
(4, 2, 'Cliente Anónimo', 3, 'dd', '2025-12-07 19:27:33'),
(5, 2, 'Cliente Anónimo', 5, 'aa', '2025-12-07 19:52:30'),
(6, 2, 'Cliente Anónimo', 2, '656', '2025-12-07 20:02:29'),
(7, 2, 'Cliente Anónimo', 3, 'asdfg', '2025-12-07 20:28:32'),
(8, 3, 'Cliente Anónimo', 5, 'prueba', '2025-12-08 07:12:11'),
(9, 13, 'chato', 4, 'bien', '2025-12-09 09:42:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('vendedor','admin') DEFAULT 'vendedor',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `rol`, `activo`, `fecha_registro`) VALUES
(1, 'Admin', 'General', 'admin@colectivo.com', '1234', '6871548963', 'admin', 1, '2025-11-25 10:39:56'),
(2, 'Juan ', 'Vendedor', 'juan@marca.com', '$2y$10$YourHashHere', '6679876543', 'vendedor', 1, '2025-11-25 10:39:56'),
(5, 'Dafne', 'Zavala Diaz de Leon', 'dafne@colectivo.com', '$2y$10$8SPd/jNGkUevEvtn6MoGOuxT72sb2bZFBfo8N8tpH1yspEbPVpF0C', '6871295810', 'admin', 1, '2025-11-29 18:47:54'),
(6, 'Carolina', 'Armenta', 'armentaics@gmail.com', '$2y$10$/0iQYVYGtj3BDTwr0hK40ewJN.0AofR3fK0XNqzBvNbM4UE2VCuS.', '6871744321', 'vendedor', 1, '2025-12-03 20:47:34'),
(8, 'prueba', 'prueba2', 'prueba2@gmail.com', '$2y$10$RGPF2OwPAgHY8pWOiZnWlut4/BBdgdAVuudLjWGX31ZlbnzPaoc6m', '6871112222', 'vendedor', 1, '2025-12-03 21:40:36'),
(15, 'chato', 'gaxiola', 'chato@gmail.com', '$2y$10$JSgttkufRknOLgJHTgmQLeTl/nnRdPadmGEskXjsySUqMsqVAzSzG', '6871295810', 'vendedor', 1, '2025-12-07 19:55:57'),
(16, 'Carolina', 'Armenta', 'caro@colectivo.com', '$2y$10$bdW/2aiJaMBjszASDPsSnuRcchi9iCcaibAFe.oRSLwH6b01MZ0kK', '6871295810', 'vendedor', 1, '2025-12-07 22:06:48'),
(17, 'Carolina', 'Armenta', 'caroshop@colectivo.com', '$2y$10$cJufCYHDtWye0Gm4fJIDm.G/67nHN7PM4uEPaiCavJ7Val98Plm/W', '6871295810', 'vendedor', 1, '2025-12-07 22:09:09'),
(18, 'Itzel', 'Garcia', 'itzelshop@colectivo.com', '$2y$10$xICa9tzatrMiglQp8sFSGOjgwbhgHPIthGKeUoTlhwf6OYVb4GE0e', '6874561236', 'vendedor', 1, '2025-12-07 22:12:59'),
(19, 'Itzel', 'Garcia', 'izela@colectivo.com', '$2y$10$WcG7vx4p.HjOUsWsS045D.JqFRxZBaT3CBfJkGzR6bFG81cZX2jLm', '6874561236', 'vendedor', 1, '2025-12-07 22:14:59'),
(20, 'Itzel', 'Garcia', 'itze@colectivo.com', '$2y$10$7Gf5VUcow5JGgxLq.pWgwuBywUYEyLixTMkhq8jbSyn59TmprUCLq', '6874561236', 'vendedor', 1, '2025-12-08 19:34:09'),
(21, 'Cliente', 'Invitado', 'anonimo@tutienda.com', '', NULL, '', 1, '2025-12-09 09:12:17'),
(22, 'Rodrigo', 'Norzagaray', 'rodrigo@colectivo.com', '$2y$10$MfG2vB1TkvO4m7osCjmW7.t2nZAgVwqkdz3TjhrFSTgXufO1RfDuO', '6871458793', 'vendedor', 1, '2025-12-09 10:46:14'),
(23, 'Itzel Carolina', 'Armenta Sanchez', 'carr@gmail.com', '$2y$10$D7V1ZLgiu6TGfWBkoWfXzOpN5jwumSaEYF6ukCCBToTnoTzE6fbLu', '6871221204', 'vendedor', 1, '2025-12-10 22:13:19'),
(24, 'Itzel Carolina', 'Armenta Sanchez', 'carar@gmail.com', '$2y$10$QhpWfXvVzBLNehdCnXPk0ePKXm91R5AVBFiuBzqiA3myHqAoCvzXy', '6871221204', 'vendedor', 1, '2025-12-10 23:12:24');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `colectivos`
--
ALTER TABLE `colectivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_colectivo_usuario` (`id_usuario`);

--
-- Indices de la tabla `comisiones_vendedor`
--
ALTER TABLE `comisiones_vendedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_orden_detalle` (`id_orden_detalle`),
  ADD KEY `id_vendedor` (`id_vendedor`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_cupon_colectivo` (`id_colectivo`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_pedido` (`id_pedido`),
  ADD KEY `fk_detalle_producto` (`id_producto`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_usuario` (`id_cliente`);

--
-- Indices de la tabla `ordenes_detalle`
--
ALTER TABLE `ordenes_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_orden` (`id_orden`),
  ADD KEY `fk_id_producto` (`id_producto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario_pedido` (`id_usuario`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_colectivo` (`id_colectivo`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `fk_id_vendedor` (`id_vendedor`);

--
-- Indices de la tabla `reseñas`
--
ALTER TABLE `reseñas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reseña_producto` (`id_producto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `colectivos`
--
ALTER TABLE `colectivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `comisiones_vendedor`
--
ALTER TABLE `comisiones_vendedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID de la Orden (clave primaria)', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ordenes_detalle`
--
ALTER TABLE `ordenes_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID de Detalle (clave primaria)', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `reseñas`
--
ALTER TABLE `reseñas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `colectivos`
--
ALTER TABLE `colectivos`
  ADD CONSTRAINT `colectivos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_colectivo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `comisiones_vendedor`
--
ALTER TABLE `comisiones_vendedor`
  ADD CONSTRAINT `comisiones_vendedor_ibfk_1` FOREIGN KEY (`id_orden_detalle`) REFERENCES `ordenes_detalle` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comisiones_vendedor_ibfk_2` FOREIGN KEY (`id_vendedor`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION;

--
-- Filtros para la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD CONSTRAINT `fk_cupon_colectivo` FOREIGN KEY (`id_colectivo`) REFERENCES `colectivos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD CONSTRAINT `fk_id_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `ordenes_detalle`
--
ALTER TABLE `ordenes_detalle`
  ADD CONSTRAINT `fk_id_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_usuario_pedido` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_id_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_colectivo`) REFERENCES `colectivos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reseñas`
--
ALTER TABLE `reseñas`
  ADD CONSTRAINT `fk_reseña_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
