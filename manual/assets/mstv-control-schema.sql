-- MSTV Control - estructura limpia de base de datos
-- Uso:
--   1. Crear/importar en MySQL o MariaDB: mysql -u TU_USUARIO -p TU_BASE < mstv-control-schema.sql
--   2. Configurar credenciales en control/lib/db.php y app/db.php.
--   3. Levantar la app y crear/actualizar usuarios desde Control.
-- No incluye contrasenas reales ni datos operativos de produccion.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

START TRANSACTION;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `prestamos_pagos`;
DROP TABLE IF EXISTS `prestamos_personal`;
DROP TABLE IF EXISTS `caja_ahorro_movimientos`;
DROP TABLE IF EXISTS `vacaciones_movimientos`;
DROP TABLE IF EXISTS `sanciones_personal`;
DROP TABLE IF EXISTS `descuentos_material`;
DROP TABLE IF EXISTS `bonos_personal`;
DROP TABLE IF EXISTS `faltas_personal`;
DROP TABLE IF EXISTS `adelantos_nomina`;
DROP TABLE IF EXISTS `nomina_capturas`;
DROP TABLE IF EXISTS `nomina_conceptos`;
DROP TABLE IF EXISTS `nomina_resumen`;
DROP TABLE IF EXISTS `nomina_periodos`;
DROP TABLE IF EXISTS `bajas_personal`;
DROP TABLE IF EXISTS `incapacidades_personal`;
DROP TABLE IF EXISTS `dias_festivos`;
DROP TABLE IF EXISTS `ajustes_nomina`;
DROP TABLE IF EXISTS `progreso_capacitacion`;
DROP TABLE IF EXISTS `modulos_capacitacion`;
DROP TABLE IF EXISTS `documentos_personal`;
DROP TABLE IF EXISTS `mensajes_chat`;
DROP TABLE IF EXISTS `bitacora_sistema`;
DROP TABLE IF EXISTS `registros_asistencia`;
DROP TABLE IF EXISTS `incidencias`;
DROP TABLE IF EXISTS `turnos`;
DROP TABLE IF EXISTS `sitios`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `personal`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `configuracion_sistema`;

CREATE TABLE `roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `matriz_permisos` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(50) NOT NULL,
  `contrasena_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `rol_id` INT NOT NULL,
  `esta_activo` TINYINT(1) DEFAULT 1,
  `intentos_fallidos` INT DEFAULT 0,
  `bloqueado_hasta` DATETIME DEFAULT NULL,
  `ultimo_acceso` DATETIME DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  KEY `idx_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `numero_empleado` VARCHAR(30) DEFAULT NULL,
  `usuario_id` INT NOT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `url_foto_base` VARCHAR(255) DEFAULT NULL,
  `fecha_contratacion` DATE DEFAULT NULL,
  `estado` ENUM('ACTIVO','INACTIVO','SUSPENDIDO') DEFAULT 'ACTIVO',
  `salario_diario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `salario_hora` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tipo_contrato` VARCHAR(50) DEFAULT NULL,
  `participa_caja_ahorro` TINYINT(1) NOT NULL DEFAULT 1,
  `aportacion_caja_ahorro_quincenal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_caja_ahorro_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dias_vacaciones_disponibles` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `fecha_ultimo_calculo_vacaciones` DATE DEFAULT NULL,
  `tiene_infonavit` TINYINT(1) NOT NULL DEFAULT 0,
  `monto_infonavit_quincenal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tiene_fonacot` TINYINT(1) NOT NULL DEFAULT 0,
  `monto_fonacot_quincenal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `limite_adelanto_nomina` DECIMAL(10,2) DEFAULT NULL,
  `activo_en_nomina` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_baja` DATE DEFAULT NULL,
  `motivo_baja` VARCHAR(255) DEFAULT NULL,
  `finiquito_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estatus_finiquito` ENUM('NINGUNO','PENDIENTE','CERRADO') NOT NULL DEFAULT 'NINGUNO',
  `vacaciones_2024_notas` TEXT DEFAULT NULL,
  `vacaciones_2025_notas` TEXT DEFAULT NULL,
  `vacaciones_2026_notas` TEXT DEFAULT NULL,
  `puesto_operativo` VARCHAR(100) DEFAULT NULL,
  `turno_base` VARCHAR(50) DEFAULT NULL,
  `servicio_asignado` VARCHAR(150) DEFAULT NULL,
  `infospe_estatus` VARCHAR(50) DEFAULT NULL,
  `cecceg_estatus` VARCHAR(50) DEFAULT NULL,
  `sexo` VARCHAR(30) DEFAULT NULL,
  `estado_civil` VARCHAR(50) DEFAULT NULL,
  `domicilio` TEXT DEFAULT NULL,
  `codigo_postal` VARCHAR(10) DEFAULT NULL,
  `nss` VARCHAR(30) DEFAULT NULL,
  `rfc` VARCHAR(20) DEFAULT NULL,
  `curp` VARCHAR(25) DEFAULT NULL,
  `cuenta_bancaria` VARCHAR(50) DEFAULT NULL,
  `banco` VARCHAR(80) DEFAULT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `talla_camisa` VARCHAR(30) DEFAULT NULL,
  `talla_pantalon` VARCHAR(30) DEFAULT NULL,
  `talla_calzado` VARCHAR(30) DEFAULT NULL,
  `contacto_emergencia` VARCHAR(150) DEFAULT NULL,
  `contacto_emergencia_parentesco` VARCHAR(80) DEFAULT NULL,
  `contacto_emergencia_telefono` VARCHAR(30) DEFAULT NULL,
  `tiene_hijos` TINYINT(1) NOT NULL DEFAULT 0,
  `edades_hijos` VARCHAR(255) DEFAULT NULL,
  `folio_base_excel` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_personal_usuario` (`usuario_id`),
  KEY `idx_personal_estado` (`estado`),
  CONSTRAINT `fk_personal_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre_empresa` VARCHAR(100) NOT NULL,
  `nombre_contacto` VARCHAR(100) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `url_logo` VARCHAR(255) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sitios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cliente_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `latitud` DECIMAL(10,8) NOT NULL,
  `longitud` DECIMAL(11,8) NOT NULL,
  `tipo_geocerca` ENUM('CIRCULO','POLIGONO') DEFAULT 'CIRCULO',
  `radio_geocerca` INT DEFAULT 50,
  `poligono_geocerca` LONGTEXT DEFAULT NULL,
  `esta_activo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_sitios_cliente` (`cliente_id`),
  CONSTRAINT `fk_sitios_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turnos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sitio_id` INT NOT NULL,
  `personal_id` INT NOT NULL,
  `supervisor_id` INT DEFAULT NULL,
  `hora_inicio` DATETIME NOT NULL,
  `hora_fin` DATETIME NOT NULL,
  `es_turno_extra` TINYINT(1) DEFAULT 0,
  `estado` ENUM('PROGRAMADO','EN_PROGRESO','COMPLETADO','AUSENTE') DEFAULT 'PROGRAMADO',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `horas_programadas` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
  `hora_entrada_real` DATETIME DEFAULT NULL,
  `hora_salida_real` DATETIME DEFAULT NULL,
  `retardo_minutos` INT NOT NULL DEFAULT 0,
  `retardo_horas_cobradas` INT NOT NULL DEFAULT 0,
  `turno_cancelado_por_retardo` TINYINT(1) NOT NULL DEFAULT 0,
  `tipo_turno_extra` ENUM('NINGUNO','MEDIO','COMPLETO','PERSONALIZADO') NOT NULL DEFAULT 'NINGUNO',
  `monto_turno_extra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `observaciones_nomina` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_turnos_sitio` (`sitio_id`),
  KEY `idx_turnos_personal` (`personal_id`),
  KEY `idx_turnos_inicio` (`hora_inicio`),
  CONSTRAINT `fk_turnos_sitios` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`),
  CONSTRAINT `fk_turnos_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `registros_asistencia` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `turno_id` INT DEFAULT NULL,
  `personal_id` INT NOT NULL,
  `sitio_id` INT NOT NULL,
  `tipo_evento` ENUM('ENTRADA','SALIDA') NOT NULL,
  `fecha_hora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `latitud` DECIMAL(10,8) NOT NULL,
  `longitud` DECIMAL(11,8) NOT NULL,
  `esta_dentro_geocerca` TINYINT(1) NOT NULL,
  `url_selfie` VARCHAR(255) NOT NULL,
  `puntaje_facial` DECIMAL(5,2) DEFAULT NULL,
  `verificado_vida` TINYINT(1) DEFAULT 0,
  `comentarios` TEXT DEFAULT NULL,
  `estado` ENUM('ACEPTADO','RECHAZADO_ROSTRO','RECHAZADO_GPS','PENDIENTE_REVISION') DEFAULT 'ACEPTADO',
  PRIMARY KEY (`id`),
  KEY `idx_asistencia_turno` (`turno_id`),
  KEY `idx_asistencia_personal` (`personal_id`),
  KEY `idx_asistencia_sitio` (`sitio_id`),
  KEY `idx_asistencia_fecha` (`fecha_hora`),
  CONSTRAINT `fk_asistencia_turnos` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  CONSTRAINT `fk_asistencia_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  CONSTRAINT `fk_asistencia_sitios` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sitio_id` INT NOT NULL,
  `reportador_id` INT NOT NULL,
  `turno_id` INT DEFAULT NULL,
  `tipo` ENUM('SEGURIDAD','OPERACION','MANTENIMIENTO','URGENTE') NOT NULL,
  `prioridad` ENUM('BAJA','MEDIA','ALTA','CRITICA') NOT NULL,
  `descripcion` TEXT NOT NULL,
  `url_foto` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('PENDIENTE','EN_PROCESO','CERRADO') DEFAULT 'PENDIENTE',
  `notas_admin` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_incidencias_sitio` (`sitio_id`),
  KEY `idx_incidencias_reportador` (`reportador_id`),
  KEY `idx_incidencias_turno` (`turno_id`),
  CONSTRAINT `fk_incidencias_sitios` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`),
  CONSTRAINT `fk_incidencias_personal` FOREIGN KEY (`reportador_id`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `configuracion_sistema` (
  `clave_configuracion` VARCHAR(80) NOT NULL,
  `valor_configuracion` TEXT NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`clave_configuracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bitacora_sistema` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT DEFAULT NULL,
  `tipo_accion` VARCHAR(50) NOT NULL,
  `tabla_afectada` VARCHAR(50) DEFAULT NULL,
  `registro_id` INT DEFAULT NULL,
  `valor_anterior` LONGTEXT DEFAULT NULL,
  `valor_nuevo` LONGTEXT DEFAULT NULL,
  `direccion_ip` VARCHAR(45) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bitacora_tipo_fecha` (`tipo_accion`, `fecha_creacion`),
  KEY `idx_bitacora_usuario_fecha` (`usuario_id`, `fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mensajes_chat` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `remitente_id` INT NOT NULL,
  `destinatario_id` INT DEFAULT NULL,
  `tipo_canal` ENUM('DIRECTO','CANAL_RH') NOT NULL DEFAULT 'DIRECTO',
  `cuerpo_mensaje` TEXT NOT NULL,
  `contiene_groserias` TINYINT(1) NOT NULL DEFAULT 0,
  `es_leido` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_origen_fecha` (`remitente_id`, `fecha_creacion`),
  KEY `idx_chat_destino_fecha` (`destinatario_id`, `fecha_creacion`),
  CONSTRAINT `fk_chat_remitente` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documentos_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `tipo_documento` VARCHAR(50) NOT NULL,
  `url_archivo` VARCHAR(255) NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `fecha_subida` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documentos_personal` (`personal_id`),
  CONSTRAINT `fk_documentos_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modulos_capacitacion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `url_video` VARCHAR(255) NOT NULL,
  `es_obligatorio` TINYINT(1) DEFAULT 1,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `progreso_capacitacion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `modulo_id` INT NOT NULL,
  `completado` TINYINT(1) DEFAULT 0,
  `fecha_completado` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_progreso_personal` (`personal_id`),
  KEY `idx_progreso_modulo` (`modulo_id`),
  CONSTRAINT `fk_progreso_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  CONSTRAINT `fk_progreso_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos_capacitacion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ajustes_nomina` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `supervisor_id` INT NOT NULL,
  `tipo_ajuste` ENUM('DEDUCCION','BONO','HORA_MENOS') NOT NULL,
  `monto` DECIMAL(10,2) DEFAULT NULL,
  `horas` DECIMAL(5,2) DEFAULT NULL,
  `motivo` TEXT NOT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ajustes_personal` (`personal_id`),
  KEY `idx_ajustes_supervisor` (`supervisor_id`),
  CONSTRAINT `fk_ajustes_personal` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`),
  CONSTRAINT `fk_ajustes_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nomina_periodos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(50) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `anio` INT NOT NULL,
  `numero_quincena` INT NOT NULL,
  `estado` ENUM('ABIERTO','CALCULADO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_periodos_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nomina_resumen` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `periodo_id` INT NOT NULL,
  `personal_id` INT NOT NULL,
  `salario_diario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `salario_hora` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `salario_base` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `retardos_horas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuento_retardos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `horas_extra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pago_horas_extra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `turnos_extra_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `vacaciones_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `prima_vacacional_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `dias_festivos_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `incapacidades_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `bonos_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_faltas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_descansos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_sanciones` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_material` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_infonavit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_fonacot` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_prestamos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuentos_adelantos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `otros_descuentos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `finiquito_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `neto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `turno_cancelado` INT NOT NULL DEFAULT 0,
  `fecha_calculo` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_resumen` (`periodo_id`, `personal_id`),
  KEY `idx_nomina_resumen_personal` (`personal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nomina_conceptos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `resumen_id` INT NOT NULL,
  `periodo_id` INT NOT NULL,
  `personal_id` INT NOT NULL,
  `categoria` ENUM('PERCEPCION','DEDUCCION','INFORMATIVO') NOT NULL DEFAULT 'INFORMATIVO',
  `clave` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `referencia_tabla` VARCHAR(80) DEFAULT NULL,
  `referencia_id` INT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomina_conceptos_periodo` (`periodo_id`, `personal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nomina_capturas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `periodo_id` INT NOT NULL,
  `personal_id` INT NOT NULL,
  `descuento_infonavit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuento_fonacot` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuento_manual_otro` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_capturas` (`periodo_id`, `personal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `adelantos_nomina` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `periodo_id` INT DEFAULT NULL,
  `fecha_solicitud` DATE NOT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `motivo` TEXT DEFAULT NULL,
  `tipo` ENUM('MASIVO','INDIVIDUAL') NOT NULL DEFAULT 'INDIVIDUAL',
  `estado` ENUM('PENDIENTE','APLICADO','CANCELADO') NOT NULL DEFAULT 'APLICADO',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adelantos_personal` (`personal_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faltas_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `periodo_id` INT DEFAULT NULL,
  `categoria` ENUM('FALTA','DESCANSO') NOT NULL DEFAULT 'FALTA',
  `goce_sueldo` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_falta` DATE NOT NULL,
  `tipo` ENUM('JUSTIFICADA','INJUSTIFICADA','AJUSTADA') NOT NULL,
  `monto_descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `motivo` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faltas_personal` (`personal_id`, `fecha_falta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bonos_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `periodo_id` INT DEFAULT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `categoria` ENUM('BONO','INCAPACIDAD','VACACIONES','OTRO') NOT NULL DEFAULT 'BONO',
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `motivo` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bonos_personal` (`personal_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `descuentos_material` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `tipo_material` VARCHAR(100) NOT NULL DEFAULT 'Botas',
  `monto_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quincenas_total` INT NOT NULL DEFAULT 2,
  `quincenas_restantes` INT NOT NULL DEFAULT 2,
  `monto_por_quincena` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('ACTIVO','LIQUIDADO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_descuentos_material` (`personal_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sanciones_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `motivo` TEXT NOT NULL,
  `monto_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quincenas_total` INT NOT NULL DEFAULT 1,
  `quincenas_restantes` INT NOT NULL DEFAULT 1,
  `monto_por_quincena` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('ACTIVA','LIQUIDADA','CANCELADA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sanciones_personal` (`personal_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vacaciones_movimientos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `fecha_solicitud` DATE NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `dias` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `tipo` ENUM('GOZADAS','PAGADAS','AJUSTE','NOTA') NOT NULL DEFAULT 'GOZADAS',
  `prima_porcentual` DECIMAL(5,2) NOT NULL DEFAULT 25.00,
  `monto_prima` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_pago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('CAPTURADO','APLICADO','CANCELADO') NOT NULL DEFAULT 'CAPTURADO',
  `notas` TEXT DEFAULT NULL,
  `origen` VARCHAR(50) DEFAULT NULL,
  `referencia` VARCHAR(80) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vacaciones_personal` (`personal_id`, `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caja_ahorro_movimientos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `periodo_id` INT DEFAULT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `tipo_movimiento` ENUM('APORTACION','RETIRO','INTERES','AJUSTE','LIQUIDACION_ANUAL','PRESTAMO_CARGO','PRESTAMO_ABONO') NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caja_ahorro_personal` (`personal_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prestamos_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `monto_autorizado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_insoluto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tasa_porcentual` DECIMAL(5,2) NOT NULL DEFAULT 4.00,
  `plazo_quincenas` INT NOT NULL DEFAULT 1,
  `descuento_quincenal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_limite` DATE NOT NULL,
  `estado` ENUM('ACTIVO','LIQUIDADO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
  `observaciones` TEXT DEFAULT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamos_personal` (`personal_id`, `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prestamos_pagos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `prestamo_id` INT NOT NULL,
  `personal_id` INT NOT NULL,
  `periodo_id` INT DEFAULT NULL,
  `fecha_aplicacion` DATE NOT NULL,
  `monto_capital` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_interes` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_despues` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fuente` ENUM('NOMINA','CAJA_AHORRO','LIQUIDACION','AJUSTE') NOT NULL DEFAULT 'NOMINA',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamos_pagos` (`prestamo_id`, `fecha_aplicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dias_festivos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `pago_factor` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dias_festivos_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incapacidades_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `dias` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `monto_por_dia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `motivo` TEXT DEFAULT NULL,
  `pagada` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_incapacidades_personal` (`personal_id`, `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bajas_personal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `personal_id` INT NOT NULL,
  `usuario_id` INT DEFAULT NULL,
  `fecha_baja` DATE NOT NULL,
  `motivo` TEXT DEFAULT NULL,
  `finiquito_monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estatus_pago` ENUM('PENDIENTE','CERRADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bajas_personal` (`personal_id`, `fecha_baja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`nombre`, `descripcion`) VALUES
('DUEÑO', 'Acceso total fijo del sistema'),
('ADMIN', 'Administración general'),
('RH', 'Recursos humanos'),
('NOMINA', 'Nómina'),
('SUPERVISOR', 'Supervisión operativa'),
('CLIENTE', 'Cliente con visibilidad limitada')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

INSERT INTO `configuracion_sistema` (`clave_configuracion`, `valor_configuracion`, `descripcion`) VALUES
('turnos_tolerancia_minutos', '15', 'Minutos de tolerancia antes de generar retardo.'),
('turnos_max_retardo_horas', '4', 'Máximo de horas de retardo antes de cancelar el turno.'),
('checadas_minutos_anticipacion', '20', 'Minutos antes del inicio programado en los que se permite registrar entrada.'),
('facial_puntaje_minimo', '35', 'Puntaje facial mínimo para aceptar automáticamente una checada.'),
('facial_selfie_min_width', '180', 'Ancho mínimo de selfie para validar evidencia facial.'),
('facial_selfie_min_height', '180', 'Alto mínimo de selfie para validar evidencia facial.'),
('nomina_valor_hora', '75', 'Valor operativo por hora para retardos y horas extra.'),
('nomina_salario_minimo_diario', '278.80', 'Salario diario por defecto configurable para personal nuevo.'),
('prestamos_tasa_quincenal', '4', 'Porcentaje aplicado sobre saldo insoluto del préstamo por quincena.'),
('prestamos_monto_max_antiguedad_baja', '1000', 'Monto máximo para personal con un año o menos de antigüedad.'),
('prestamos_monto_max_antiguedad_alta', '5000', 'Monto máximo para personal con más de un año de antigüedad.'),
('prestamos_mes_inicio', '03', 'Mes en el que arrancan los préstamos.'),
('prestamos_dia_inicio', '01', 'Día en el que arrancan los préstamos.'),
('prestamos_mes_limite', '12', 'Mes límite para liquidar los préstamos.'),
('prestamos_dia_limite', '15', 'Día límite de la primera quincena de diciembre para liquidar préstamos.'),
('caja_pago_desde_dia', '16', 'Día inicial para entrega anual de caja de ahorro.'),
('caja_pago_hasta_dia', '24', 'Día final para entrega anual de caja de ahorro.'),
('vacaciones_dias_anio_1', '12', 'Días de vacaciones ganados al cumplir el año 1.'),
('vacaciones_dias_anio_2', '14', 'Días de vacaciones ganados al cumplir el año 2.'),
('vacaciones_dias_anio_3', '16', 'Días de vacaciones ganados al cumplir el año 3.'),
('vacaciones_dias_anio_4', '18', 'Días de vacaciones ganados al cumplir el año 4.'),
('vacaciones_dias_anio_5', '20', 'Días de vacaciones ganados al cumplir el año 5.'),
('vacaciones_dias_anios_6_10', '22', 'Días de vacaciones ganados por cada año cumplido del 6 al 10.'),
('vacaciones_dias_anios_11_15', '24', 'Días de vacaciones ganados por cada año cumplido del 11 al 15.'),
('vacaciones_dias_anios_16_20', '26', 'Días de vacaciones ganados por cada año cumplido del 16 al 20.'),
('vacaciones_dias_anios_21_25', '28', 'Días de vacaciones ganados por cada año cumplido del 21 al 25.'),
('vacaciones_dias_anios_26_30', '30', 'Días de vacaciones ganados por cada año cumplido del 26 en adelante.'),
('vacaciones_incremento_anual', '2', 'Parámetro heredado por compatibilidad; el cálculo principal usa la tabla escalonada.'),
('vacaciones_prima_porcentaje', '25', 'Porcentaje de prima vacacional aplicado al pago.'),
('faltas_descuento_justificada', '400', 'Descuento por falta justificada.'),
('faltas_descuento_injustificada', '700', 'Descuento por falta no justificada.'),
('faltas_descuento_ajustada', '0', 'Descuento por falta ajustada.'),
('descansos_descuento_sin_goce', '700', 'Descuento por descanso sin goce de sueldo.'),
('turnos_extra_12h_monto', '400', 'Monto por turno extra de 12 horas.'),
('turnos_extra_24h_monto', '800', 'Monto por turno extra de 24 horas.'),
('adelanto_masivo_monto', '500', 'Monto del adelanto masivo de nómina.'),
('adelanto_masivo_dias_antes_nomina', '5', 'Días antes de la nómina para habilitar el adelanto masivo.'),
('material_quincenas_default', '2', 'Número de quincenas por defecto para descuentos de material.'),
('nomina_factor_dia_festivo', '2', 'Factor de pago para días festivos.')
ON DUPLICATE KEY UPDATE
  `descripcion` = VALUES(`descripcion`),
  `valor_configuracion` = IF(`valor_configuracion` IS NULL OR `valor_configuracion` = '', VALUES(`valor_configuracion`), `valor_configuracion`);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- Usuario inicial:
-- Por seguridad, este archivo no crea una cuenta admin con contraseña fija.
-- Crear el primer usuario desde una herramienta segura o insertar un hash generado con password_hash().
