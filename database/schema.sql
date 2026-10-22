-- Sistema Web CDA — esquema en español
-- Ejecutar tras crear la BD o usar «Crear tablas» en el login

CREATE DATABASE IF NOT EXISTS sistema_web_cda
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sistema_web_cda;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) DEFAULT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'administrador',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO usuarios (usuario, clave, nombre, rol) VALUES
('admin', '$2y$12$IAeuaVZ.DxfMzkDongA4ouBkTyb5fVAp0gSsKiqu2EuTJAFBT7TZW', 'Administrador', 'superadmin')
ON DUPLICATE KEY UPDATE clave = VALUES(clave), nombre = VALUES(nombre), rol = VALUES(rol);

CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_formulario VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    celular VARCHAR(30) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    zona VARCHAR(50) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    contactado TINYINT(1) NOT NULL DEFAULT 0,
    estado_bautismo VARCHAR(20) NOT NULL DEFAULT 'ingresado',
    fecha_bautismo DATE DEFAULT NULL,
    estado_bautismo_bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    ip_cliente VARCHAR(45) DEFAULT NULL,
    agente_usuario VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo_formulario (tipo_formulario),
    INDEX idx_creado_en (creado_en),
    INDEX idx_estado_bautismo (estado_bautismo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS presentaciones_ninos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parentesco_representante_1 VARCHAR(30) NOT NULL DEFAULT '',
    nombre_padre VARCHAR(100) NOT NULL,
    telefono_papa VARCHAR(30) NOT NULL,
    parentesco_representante_2 VARCHAR(30) DEFAULT NULL,
    nombre_madre VARCHAR(100) DEFAULT NULL,
    telefono_mama VARCHAR(30) DEFAULT NULL,
    nombre_presentado VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'recibido',
    fecha_presentacion DATE NULL,
    estado_bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    ip_cliente VARCHAR(45) DEFAULT NULL,
    agente_usuario VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS territorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lideres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    genero VARCHAR(20) NOT NULL DEFAULT '',
    pareja VARCHAR(20) NOT NULL DEFAULT 'esposo',
    cedula VARCHAR(30) DEFAULT NULL,
    celular VARCHAR(30) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    fecha_bautismo DATE DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS territorio_asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    territorio_id INT NOT NULL,
    miembro_id INT NOT NULL,
    rol VARCHAR(20) NOT NULL,
    pareja VARCHAR(20) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_territorio_miembro_rol (territorio_id, miembro_id, rol),
    INDEX idx_miembro (miembro_id),
    INDEX idx_territorio (territorio_id),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS miembro_parentescos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    miembro_id INT NOT NULL,
    pariente_id INT NOT NULL,
    parentesco VARCHAR(30) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_miembro_pariente (miembro_id, pariente_id),
    INDEX idx_pariente (pariente_id),
    INDEX idx_parentesco (parentesco)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS miembro_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    miembro_id INT NOT NULL,
    curso VARCHAR(20) NOT NULL,
    fecha_culminacion DATE DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_miembro_curso (miembro_id, curso),
    INDEX idx_miembro (miembro_id),
    INDEX idx_curso (curso)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS casas_vida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    territorio_id INT NOT NULL,
    lider_id INT NOT NULL,
    colaborador_id INT NOT NULL DEFAULT 0,
    anfitrion_id INT NOT NULL DEFAULT 0,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_territorio (territorio_id),
    INDEX idx_lider (lider_id),
    INDEX idx_colaborador (colaborador_id),
    INDEX idx_anfitrion (anfitrion_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ofrendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    casa_id INT DEFAULT NULL,
    casa_vida VARCHAR(100) DEFAULT NULL,
    lider VARCHAR(100) DEFAULT NULL,
    fecha_ofrenda DATE NOT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0,
    registrado_por_id INT DEFAULT NULL,
    registrado_por_nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha_ofrenda (fecha_ofrenda),
    INDEX idx_casa_id (casa_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesiones_api (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_en DATETIME NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_expira (expira_en),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS valores_adicionales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    valor DECIMAL(12,2) NOT NULL DEFAULT 0,
    observacion TEXT DEFAULT NULL,
    evento_id INT DEFAULT NULL,
    numeracion VARCHAR(30) DEFAULT NULL,
    forma_pago VARCHAR(20) DEFAULT NULL,
    tipo_entrada_id INT DEFAULT NULL,
    tipo_entrada VARCHAR(100) DEFAULT NULL,
    estado_pago VARCHAR(20) DEFAULT 'por_cancelar',
    info_adicional TEXT DEFAULT NULL,
    asistio TINYINT(1) NOT NULL DEFAULT 0,
    asistio_en DATETIME DEFAULT NULL,
    asistio_por_id INT DEFAULT NULL,
    asistio_por_nombre VARCHAR(100) DEFAULT NULL,
    registrado_por_id INT DEFAULT NULL,
    registrado_por_nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha),
    INDEX idx_evento_id (evento_id),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    fecha DATE NULL,
    valor DECIMAL(12,2) NOT NULL DEFAULT 0,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    requiere_numeracion TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_fecha (fecha),
    INDEX idx_habilitado (habilitado),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eventos_tipos_entrada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor DECIMAL(12,2) NOT NULL DEFAULT 0,
    orden INT NOT NULL DEFAULT 0,
    visible_publico TINYINT(1) NOT NULL DEFAULT 1,
    es_gratis TINYINT(1) NOT NULL DEFAULT 0,
    prefijo VARCHAR(10) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_id (evento_id),
    INDEX idx_orden (orden)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS eventos_campos_adicionales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    etiqueta VARCHAR(100) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'texto',
    opciones TEXT NULL,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_id (evento_id),
    INDEX idx_orden (orden)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipos_valor_adicional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    etiqueta VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_etiqueta (etiqueta)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rol_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(20) NOT NULL,
    seccion VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_rol_seccion (rol, seccion),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consejerias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(200) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    tipo_consejeria VARCHAR(30) NOT NULL,
    anio_en_cda SMALLINT UNSIGNED NOT NULL,
    primera_consejeria TINYINT(1) NOT NULL DEFAULT 1,
    cita_fecha DATE DEFAULT NULL,
    cita_hora TIME DEFAULT NULL,
    registrado_por_id INT DEFAULT NULL,
    registrado_por_nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo_consejeria (tipo_consejeria),
    INDEX idx_cita_fecha (cita_fecha),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calendario_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    fecha DATE NOT NULL,
    fecha_fin DATE NULL,
    foto VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_fecha_fin (fecha_fin),
    INDEX idx_activo (activo),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transporte_aniversario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(200) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    edad TINYINT UNSIGNED NOT NULL,
    observacion VARCHAR(500) DEFAULT NULL,
    posee_movilizacion TINYINT(1) NOT NULL DEFAULT 0,
    asientos_disponibles SMALLINT UNSIGNED DEFAULT NULL,
    registrado_por_id INT DEFAULT NULL,
    registrado_por_nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posee_movilizacion (posee_movilizacion),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS networking_emprendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    instagram_personal VARCHAR(150) DEFAULT NULL,
    empresa VARCHAR(200) NOT NULL,
    cargo VARCHAR(150) NOT NULL,
    categoria VARCHAR(40) NOT NULL,
    instagram_empresa VARCHAR(150) DEFAULT NULL,
    pagina_web VARCHAR(255) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    ofrece TEXT NOT NULL,
    ofrece_otro VARCHAR(150) DEFAULT NULL,
    descripcion_ofrece VARCHAR(600) NOT NULL,
    busca TEXT NOT NULL,
    busca_otro VARCHAR(150) DEFAULT NULL,
    descripcion_busca VARCHAR(600) NOT NULL,
    autoriza_directorio TINYINT(1) NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    origen VARCHAR(20) NOT NULL DEFAULT 'publico',
    registrado_por_id INT DEFAULT NULL,
    registrado_por_nombre VARCHAR(100) DEFAULT NULL,
    ip_cliente VARCHAR(45) DEFAULT NULL,
    agente_usuario VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_visible (visible),
    INDEX idx_email (email),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS actividad_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(100) NULL,
    usuario_login VARCHAR(50) NULL,
    rol_usuario VARCHAR(20) NULL,
    accion VARCHAR(80) NOT NULL,
    seccion VARCHAR(50) NULL,
    entidad VARCHAR(50) NULL,
    entidad_id INT NULL,
    detalle TEXT NULL,
    datos_extra TEXT NULL,
    ip_cliente VARCHAR(45) NULL,
    agente_usuario VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_seccion (seccion),
    INDEX idx_creado_en (creado_en)
) ENGINE=InnoDB;
