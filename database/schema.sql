CREATE TABLE usuario (
 id INT AUTO_INCREMENT PRIMARY KEY, cedula VARCHAR(10) NOT NULL UNIQUE,
 nombres VARCHAR(100) NOT NULL, apellidos VARCHAR(100) NOT NULL,
 rol ENUM('admin','secretaria','conductor') NOT NULL,
 password_hash VARCHAR(255) NOT NULL, activo BOOLEAN NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE socio (
 id INT AUTO_INCREMENT PRIMARY KEY, cedula VARCHAR(10) NOT NULL UNIQUE,
 nombres VARCHAR(150) NOT NULL, telefono VARCHAR(25) NOT NULL DEFAULT '', activo BOOLEAN NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE bus (
 id INT AUTO_INCREMENT PRIMARY KEY, disco VARCHAR(20) NOT NULL UNIQUE, placa VARCHAR(20) NULL UNIQUE,
 socio_id INT NULL, conductor_id INT NULL UNIQUE, activo BOOLEAN NOT NULL DEFAULT 1,
 FOREIGN KEY (socio_id) REFERENCES socio(id), FOREIGN KEY (conductor_id) REFERENCES usuario(id)
) ENGINE=InnoDB;
CREATE TABLE ruta (
 id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL UNIQUE,
 descripcion VARCHAR(500) NOT NULL DEFAULT '', activo BOOLEAN NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE recorrido (
 id INT AUTO_INCREMENT PRIMARY KEY, conductor_id INT NOT NULL, bus_id INT NOT NULL, ruta_id INT NOT NULL,
 conductor_nombre VARCHAR(201) NOT NULL, disco VARCHAR(20) NOT NULL, ruta_nombre VARCHAR(120) NOT NULL,
 inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fin DATETIME NULL,
 km_inicial INT UNSIGNED NOT NULL, km_final INT UNSIGNED NULL,
 evidencia_inicial VARCHAR(80) NOT NULL, evidencia_final VARCHAR(80) NULL,
 conductor_activo INT GENERATED ALWAYS AS (IF(fin IS NULL, conductor_id, NULL)) STORED UNIQUE,
 bus_activo INT GENERATED ALWAYS AS (IF(fin IS NULL, bus_id, NULL)) STORED UNIQUE,
 FOREIGN KEY (conductor_id) REFERENCES usuario(id), FOREIGN KEY (bus_id) REFERENCES bus(id), FOREIGN KEY (ruta_id) REFERENCES ruta(id),
 CONSTRAINT kilometraje_valido CHECK (km_final IS NULL OR km_final >= km_inicial),
 CONSTRAINT cierre_completo CHECK ((fin IS NULL AND km_final IS NULL AND evidencia_final IS NULL) OR (fin IS NOT NULL AND km_final IS NOT NULL AND evidencia_final IS NOT NULL)),
 INDEX (inicio), INDEX (conductor_id, inicio)
) ENGINE=InnoDB;
INSERT INTO ruta(nombre,descripcion) VALUES ('Ruta 1','Ruta de ejemplo; editar según operación'),('Ruta 2','Ruta de ejemplo; editar según operación');
