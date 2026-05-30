-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: ganas001.mysql.guardedhost.com
-- Tiempo de generación: 24-03-2026 a las 06:26:16
-- Versión del servidor: 11.4.9-MariaDB-deb12
-- Versión de PHP: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de datos: `ganas001_asx`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ajustes_nomina`
--

DROP TABLE IF EXISTS `ajustes_nomina`;
CREATE TABLE `ajustes_nomina` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `tipo_ajuste` enum('DEDUCCION','BONO','HORA_MENOS') NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `horas` decimal(5,2) DEFAULT NULL,
  `motivo` text NOT NULL,
  `fecha_aplicacion` date NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora_sistema`
--

DROP TABLE IF EXISTS `bitacora_sistema`;
CREATE TABLE `bitacora_sistema` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_accion` varchar(50) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `valor_anterior` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_anterior`)),
  `valor_nuevo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valor_nuevo`)),
  `direccion_ip` varchar(45) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(100) NOT NULL,
  `nombre_contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `url_logo` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

DROP TABLE IF EXISTS `configuracion_sistema`;
CREATE TABLE `configuracion_sistema` (
  `clave_configuracion` varchar(50) NOT NULL,
  `valor_configuracion` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_personal`
--

DROP TABLE IF EXISTS `documentos_personal`;
CREATE TABLE `documentos_personal` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `tipo_documento` varchar(50) NOT NULL,
  `url_archivo` varchar(255) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

DROP TABLE IF EXISTS `incidencias`;
CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL,
  `sitio_id` int(11) NOT NULL,
  `reportador_id` int(11) NOT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `tipo` enum('SEGURIDAD','OPERACION','MANTENIMIENTO','URGENTE') NOT NULL,
  `prioridad` enum('BAJA','MEDIA','ALTA','CRITICA') NOT NULL,
  `descripcion` text NOT NULL,
  `url_foto` varchar(255) DEFAULT NULL,
  `estado` enum('PENDIENTE','EN_PROCESO','CERRADO') DEFAULT 'PENDIENTE',
  `notas_admin` text DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_chat`
--

DROP TABLE IF EXISTS `mensajes_chat`;
CREATE TABLE `mensajes_chat` (
  `id` int(11) NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `tipo_canal` enum('DIRECTO','CANAL_RH') DEFAULT 'DIRECTO',
  `cuerpo_mensaje` text NOT NULL,
  `contiene_groserias` tinyint(1) DEFAULT 0,
  `es_leido` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_capacitacion`
--

DROP TABLE IF EXISTS `modulos_capacitacion`;
CREATE TABLE `modulos_capacitacion` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `url_video` varchar(255) NOT NULL,
  `es_obligatorio` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

DROP TABLE IF EXISTS `personal`;
CREATE TABLE `personal` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `url_foto_base` varchar(255) DEFAULT NULL,
  `fecha_contratacion` date DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO','SUSPENDIDO') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_capacitacion`
--

DROP TABLE IF EXISTS `progreso_capacitacion`;
CREATE TABLE `progreso_capacitacion` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `completado` tinyint(1) DEFAULT 0,
  `fecha_completado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros_asistencia`
--

DROP TABLE IF EXISTS `registros_asistencia`;
CREATE TABLE `registros_asistencia` (
  `id` int(11) NOT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `personal_id` int(11) NOT NULL,
  `sitio_id` int(11) NOT NULL,
  `tipo_evento` enum('ENTRADA','SALIDA') NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `esta_dentro_geocerca` tinyint(1) NOT NULL,
  `url_selfie` varchar(255) NOT NULL,
  `puntaje_facial` decimal(5,2) DEFAULT NULL,
  `verificado_vida` tinyint(1) DEFAULT 0,
  `comentarios` text DEFAULT NULL,
  `estado` enum('ACEPTADO','RECHAZADO_ROSTRO','RECHAZADO_GPS','PENDIENTE_REVISION') DEFAULT 'ACEPTADO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `matriz_permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matriz_permisos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sitios`
--

DROP TABLE IF EXISTS `sitios`;
CREATE TABLE `sitios` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `tipo_geocerca` enum('CIRCULO','POLIGONO') DEFAULT 'CIRCULO',
  `radio_geocerca` int(11) DEFAULT 50,
  `poligono_geocerca` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`poligono_geocerca`)),
  `esta_activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `sitio_id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `hora_inicio` datetime NOT NULL,
  `hora_fin` datetime NOT NULL,
  `es_turno_extra` tinyint(1) DEFAULT 0,
  `estado` enum('PROGRAMADO','EN_PROGRESO','COMPLETADO','AUSENTE') DEFAULT 'PROGRAMADO',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `esta_activo` tinyint(1) DEFAULT 1,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajustes_nomina`
--
ALTER TABLE `ajustes_nomina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indices de la tabla `bitacora_sistema`
--
ALTER TABLE `bitacora_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`clave_configuracion`);

--
-- Indices de la tabla `documentos_personal`
--
ALTER TABLE `documentos_personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sitio_id` (`sitio_id`),
  ADD KEY `reportador_id` (`reportador_id`);

--
-- Indices de la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remitente_id` (`remitente_id`);

--
-- Indices de la tabla `modulos_capacitacion`
--
ALTER TABLE `modulos_capacitacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `progreso_capacitacion`
--
ALTER TABLE `progreso_capacitacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_id` (`personal_id`),
  ADD KEY `modulo_id` (`modulo_id`);

--
-- Indices de la tabla `registros_asistencia`
--
ALTER TABLE `registros_asistencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turno_id` (`turno_id`),
  ADD KEY `personal_id` (`personal_id`),
  ADD KEY `sitio_id` (`sitio_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `sitios`
--
ALTER TABLE `sitios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sitio_id` (`sitio_id`),
  ADD KEY `personal_id` (`personal_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajustes_nomina`
--
ALTER TABLE `ajustes_nomina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bitacora_sistema`
--
ALTER TABLE `bitacora_sistema`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_personal`
--
ALTER TABLE `documentos_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos_capacitacion`
--
ALTER TABLE `modulos_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `progreso_capacitacion`
--
ALTER TABLE `progreso_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registros_asistencia`
--
ALTER TABLE `registros_asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sitios`
--
ALTER TABLE `sitios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ajustes_nomina`
--
ALTER TABLE `ajustes_nomina`
  ADD CONSTRAINT `ajustes_nomina_ibfk_1` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  ADD CONSTRAINT `ajustes_nomina_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `documentos_personal`
--
ALTER TABLE `documentos_personal`
  ADD CONSTRAINT `documentos_personal_ibfk_1` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`);

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`),
  ADD CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`reportador_id`) REFERENCES `personal` (`id`);

--
-- Filtros para la tabla `mensajes_chat`
--
ALTER TABLE `mensajes_chat`
  ADD CONSTRAINT `mensajes_chat_ibfk_1` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `progreso_capacitacion`
--
ALTER TABLE `progreso_capacitacion`
  ADD CONSTRAINT `progreso_capacitacion_ibfk_1` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  ADD CONSTRAINT `progreso_capacitacion_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `modulos_capacitacion` (`id`);

--
-- Filtros para la tabla `registros_asistencia`
--
ALTER TABLE `registros_asistencia`
  ADD CONSTRAINT `registros_asistencia_ibfk_1` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  ADD CONSTRAINT `registros_asistencia_ibfk_2` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  ADD CONSTRAINT `registros_asistencia_ibfk_3` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`);

--
-- Filtros para la tabla `sitios`
--
ALTER TABLE `sitios`
  ADD CONSTRAINT `sitios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`),
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;
