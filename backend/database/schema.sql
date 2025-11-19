-- SQL schema
-- ============================================
--   BASE DE DATOS: CONTROL_SEGURIDAD
-- ============================================

CREATE DATABASE IF NOT EXISTS control_seguridad DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE control_seguridad;

-- ============================================
--                 USUARIOS
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    apellido VARCHAR(150) NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    usuario VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('guardia','supervisor','admin','rh','nomina','cliente') NOT NULL,
    foto_base VARCHAR(255) NULL,
    api_token VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
--             CLIENTES / FRACCIONAMIENTOS
-- ============================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(150),
    telefono VARCHAR(30),
    email VARCHAR(120),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
--                    SITIOS
-- ============================================
CREATE TABLE sitios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    nombre VARCHAR(150) NOT NULL,
    direccion TEXT,
    lat DECIMAL(10,7),
    lng DECIMAL(10,7),
    radio_metros INT DEFAULT 100,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- ============================================
--         ASIGNACIÓN GUARDIA → SITIO
-- ============================================
CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    sitio_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    desde DATE,
    hasta DATE,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (sitio_id) REFERENCES sitios(id)
);

-- ============================================
--                    TURNOS
-- ============================================
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    sitio_id INT NOT NULL,
    tipo ENUM('normal','extra') DEFAULT 'normal',
    fecha DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    aprobado_supervisor TINYINT(1) DEFAULT 0,
    comentario VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (sitio_id) REFERENCES sitios(id)
);

-- ============================================
--                     CHECADAS
-- ============================================
CREATE TABLE checadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    sitio_id INT NOT NULL,
    tipo ENUM('entrada','salida','llegada','extra_inicio','extra_fin') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    lat DECIMAL(10,7),
    lng DECIMAL(10,7),
    foto VARCHAR(255),
    validado_geo TINYINT(1) DEFAULT 0,
    validado_facial TINYINT(1) DEFAULT 0,
    comentario VARCHAR(255),
    turno_id INT NULL,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (sitio_id) REFERENCES sitios(id),
    FOREIGN KEY (turno_id) REFERENCES turnos(id)
);

CREATE INDEX idx_guardia_fecha ON checadas (guardia_id, fecha_hora);

-- ============================================
--                   INCIDENCIAS
-- ============================================
CREATE TABLE incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    sitio_id INT NOT NULL,
    tipo ENUM('seguridad','operacion','cliente') NOT NULL,
    prioridad ENUM('alta','media','baja') DEFAULT 'baja',
    descripcion TEXT,
    foto VARCHAR(255),
    estado ENUM('pendiente','atendido','cerrado') DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (sitio_id) REFERENCES sitios(id)
);

-- ============================================
--                    BITÁCORA
-- ============================================
CREATE TABLE bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(255) NOT NULL,
    entidad VARCHAR(100),
    entidad_id INT,
    detalles TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================
--                 CAPACITACIÓN
-- ============================================
CREATE TABLE capacitacion_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200),
    url_video VARCHAR(255),
    obligatorio TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE capacitacion_vistos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    video_id INT NOT NULL,
    visto_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (video_id) REFERENCES capacitacion_videos(id)
);

-- ============================================
--                      CHAT
-- ============================================
CREATE TABLE chat_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitio_id INT NULL, 
    emisor_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    moderado TINYINT(1) DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sitio_id) REFERENCES sitios(id),
    FOREIGN KEY (emisor_id) REFERENCES usuarios(id)
);



-- ============================================
--              NÓMINA (EVENTOS)
-- ============================================
CREATE TABLE nomina_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guardia_id INT NOT NULL,
    turno_id INT NULL,
    checada_id INT NULL,
    horas DECIMAL(5,2) DEFAULT 0,
    tipo ENUM('normal','extra','descuento') DEFAULT 'normal',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guardia_id) REFERENCES usuarios(id),
    FOREIGN KEY (turno_id) REFERENCES turnos(id),
    FOREIGN KEY (checada_id) REFERENCES checadas(id)
);

-- ============================================
--              PARÁMETROS GLOBALES
-- ============================================
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    valor TEXT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
