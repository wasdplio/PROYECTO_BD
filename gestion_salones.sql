-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-05-2025 a las 01:33:18
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
-- Base de datos: `gestion_salones`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `computadores`
--

CREATE TABLE `computadores` (
  `id` int(11) NOT NULL,
  `salon_id` int(11) NOT NULL,
  `codigo_patrimonio` varchar(50) DEFAULT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `sistema_operativo` varchar(100) DEFAULT NULL,
  `version_so` varchar(50) DEFAULT NULL,
  `arquitectura` varchar(20) DEFAULT NULL,
  `procesador` varchar(100) DEFAULT NULL,
  `ram_gb` int(11) DEFAULT NULL,
  `almacenamiento_gb` int(11) DEFAULT NULL,
  `tipo_almacenamiento` enum('HDD','SSD','NVMe') DEFAULT NULL,
  `direccion_ip` varchar(15) DEFAULT NULL,
  `direccion_mac` varchar(17) DEFAULT NULL,
  `estado` enum('operativo','mantenimiento','dañado','retirado') DEFAULT 'operativo',
  `fecha_instalacion` date DEFAULT NULL,
  `ultimo_mantenimiento` date DEFAULT NULL,
  `proximo_mantenimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `computadores`
--

INSERT INTO `computadores` (`id`, `salon_id`, `codigo_patrimonio`, `marca`, `modelo`, `sistema_operativo`, `version_so`, `arquitectura`, `procesador`, `ram_gb`, `almacenamiento_gb`, `tipo_almacenamiento`, `direccion_ip`, `direccion_mac`, `estado`, `fecha_instalacion`, `ultimo_mantenimiento`, `proximo_mantenimiento`, `observaciones`) VALUES
(1, 1, 'PAT-001', 'HP', 'EliteDesk 800 G5', 'Windows 10', '21H2', '64-bit', 'Intel Core i5-10500', 8, 256, 'SSD', NULL, NULL, 'operativo', '2022-01-15', '2023-05-10', '2023-11-10', NULL),
(2, 1, 'PAT-002', 'HP', 'EliteDesk 800 G5', 'Windows 10', '21H2', '64-bit', 'Intel Core i5-10500', 8, 256, 'SSD', NULL, NULL, 'operativo', '2022-01-15', '2023-05-12', '2023-11-12', NULL),
(3, 1, 'PAT-003', 'Dell', 'OptiPlex 7080', 'Windows 11', '22H2', '64-bit', 'Intel Core i7-10700', 16, 512, 'SSD', NULL, NULL, 'operativo', '2022-03-10', '2023-06-01', '2023-12-01', NULL),
(4, 5, 'PAT-101', 'Lenovo', 'ThinkCentre M75q', 'Windows 10', '21H2', '64-bit', 'AMD Ryzen 5 PRO 3400GE', 8, 256, 'SSD', NULL, NULL, 'operativo', '2021-11-20', '2023-04-15', '2023-10-15', NULL),
(5, 5, 'PAT-102', 'Lenovo', 'ThinkCentre M75q', 'Windows 10', '21H2', '64-bit', 'AMD Ryzen 5 PRO 3400GE', 8, 256, 'SSD', NULL, NULL, 'mantenimiento', '2021-11-20', '2023-04-15', '2023-10-15', NULL),
(6, 8, 'PAT-201', 'Apple', 'iMac 24\"', 'macOS', 'Ventura 13.4', 'ARM', 'Apple M1', 16, 512, 'SSD', NULL, NULL, 'operativo', '2022-05-10', '2023-03-20', '2023-09-20', NULL),
(7, 8, 'PAT-202', 'Apple', 'iMac 24\"', 'macOS', 'Ventura 13.4', 'ARM', 'Apple M1', 16, 512, 'SSD', NULL, NULL, 'operativo', '2022-05-10', '2023-03-20', '2023-09-20', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL,
  `computador_id` int(11) NOT NULL,
  `usuario_reporte_id` int(11) NOT NULL,
  `usuario_asignado_id` int(11) DEFAULT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('reportada','asignada','en_proceso','resuelta','cerrada') DEFAULT 'reportada',
  `prioridad` enum('baja','media','alta','critica') DEFAULT 'media',
  `fecha_reporte` datetime DEFAULT current_timestamp(),
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `solucion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `computador_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_mantenimiento` enum('preventivo','correctivo','actualizacion') NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `descripcion` text NOT NULL,
  `acciones_realizadas` text NOT NULL,
  `componentes_cambiados` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salones`
--

CREATE TABLE `salones` (
  `id` int(11) NOT NULL,
  `sede_id` int(11) NOT NULL,
  `codigo_salon` varchar(20) NOT NULL,
  `piso` int(11) NOT NULL,
  `capacidad` int(11) DEFAULT NULL,
  `numero_computadores` int(11) DEFAULT 0,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `salones`
--

INSERT INTO `salones` (`id`, `sede_id`, `codigo_salon`, `piso`, `capacidad`, `numero_computadores`, `descripcion`) VALUES
(1, 1, 'A101', 1, 30, 20, 'Salón de informática básica'),
(2, 1, 'A102', 1, 25, 15, 'Salón de programación'),
(3, 1, 'B201', 2, 20, 10, 'Salón de diseño gráfico'),
(4, 1, 'B202', 2, 15, 8, 'Salón de redes'),
(5, 2, 'N101', 1, 20, 15, 'Salón de ofimática'),
(6, 2, 'N102', 1, 25, 18, 'Salón de desarrollo web'),
(7, 2, 'N201', 2, 15, 10, 'Salón de bases de datos'),
(8, 3, 'S101', 1, 30, 25, 'Salón de programación avanzada'),
(9, 3, 'S201', 2, 20, 12, 'Salón de sistemas operativos'),
(10, 4, 'E101', 1, 25, 20, 'Salón de inteligencia artificial'),
(11, 4, 'E102', 1, 15, 10, 'Salón de seguridad informática');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`, `direccion`, `telefono`, `responsable`, `activa`) VALUES
(1, 'Sede Principal', 'Calle Principal 123', '123456789', 'Juan Pérez', 1),
(2, 'Sede Norte', 'Avenida Norte 456', '987654321', 'María Gómez', 1),
(3, 'Sede Sur', 'Calle Sur 789', '456123789', 'Carlos Ruiz', 1),
(4, 'Sede Este', 'Avenida Este 321', '789456123', 'Ana López', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` enum('admin','tecnico','usuario') DEFAULT 'usuario',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `contraseña`, `rol`, `fecha_registro`, `ultimo_login`, `activo`) VALUES
(2, 'kevin', 'kendastar9@gmail.com', 'da4b899484bb4ac4f29fa2553ad4eb83fffc9ffc537106f8ceec1476637c0655', 'usuario', '2025-05-19 18:32:14', '2025-05-19 19:30:19', 1),
(3, 'Administrador', 'admin@example.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '2025-05-19 18:35:45', NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `computadores`
--
ALTER TABLE `computadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_patrimonio` (`codigo_patrimonio`),
  ADD KEY `salon_id` (`salon_id`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `computador_id` (`computador_id`),
  ADD KEY `usuario_reporte_id` (`usuario_reporte_id`),
  ADD KEY `usuario_asignado_id` (`usuario_asignado_id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `computador_id` (`computador_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `salones`
--
ALTER TABLE `salones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sede_id` (`sede_id`,`codigo_salon`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT de la tabla `computadores`
--
ALTER TABLE `computadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salones`
--
ALTER TABLE `salones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `computadores`
--
ALTER TABLE `computadores`
  ADD CONSTRAINT `computadores_ibfk_1` FOREIGN KEY (`salon_id`) REFERENCES `salones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`computador_id`) REFERENCES `computadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`usuario_reporte_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `incidencias_ibfk_3` FOREIGN KEY (`usuario_asignado_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`computador_id`) REFERENCES `computadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mantenimientos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `salones`
--
ALTER TABLE `salones`
  ADD CONSTRAINT `salones_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
