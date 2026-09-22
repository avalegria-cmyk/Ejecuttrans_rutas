-- Instalación nueva de Sistema Rutas: estructura y datos iniciales en un solo archivo.
-- Origen: Sistema_minutos/database/sistema_minutos_db.sql (respaldo del 22/09/2026).
-- Ejecutar una sola vez en una base nueva; este script no elimina bases existentes.
CREATE DATABASE IF NOT EXISTS sistema_recorridos
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_recorridos;
SET NAMES utf8mb4;
SET time_zone = '-05:00';

CREATE TABLE usuario (
 id INT AUTO_INCREMENT PRIMARY KEY, cedula VARCHAR(10) NOT NULL UNIQUE,
 nombres VARCHAR(100) NOT NULL, apellidos VARCHAR(100) NOT NULL,
 rol ENUM('admin','secretaria','conductor') NOT NULL,
 password_hash VARCHAR(255) NOT NULL, activo BOOLEAN NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE bus (
 id INT AUTO_INCREMENT PRIMARY KEY, disco VARCHAR(20) NOT NULL UNIQUE, placa VARCHAR(20) NULL UNIQUE,
 conductor_id INT NULL UNIQUE, activo BOOLEAN NOT NULL DEFAULT 1,
 FOREIGN KEY (conductor_id) REFERENCES usuario(id)
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

-- Buses de Minutos: se conservan ID, placa, disco y estado.
-- El respaldo no contiene asignaciones en usuario_bus; conductor_id queda NULL.
START TRANSACTION;
-- Usuarios de Minutos: administradores y secretaría conservan su rol.
-- Operativos, socios y conductores se incorporan como conductores.
-- Se conservan los ID y el estado (activo y habilitado en el origen).
-- Sin clave personalizada en el origen: contraseña inicial igual a la cédula,
-- almacenada con bcrypt para el inicio de sesión de Sistema Rutas.
INSERT INTO usuario (id, cedula, nombres, apellidos, rol, password_hash, activo) VALUES
('1', '1710000017', 'Administrador', 'Sistema', 'admin', '$2y$10$6SMGGFdryRf1yNWzsI.93.N3.wD/FHOaShLQnznL7YoP.Oc3NK5GK', '1'),
('2', '1710000025', 'Operativo', 'Sistema', 'conductor', '$2y$10$QFsiovC3j2/oOC9jDx3kLeFTi/vXGGSW7IdSDoYUycaxC2s/lzR9m', '1'),
('3', '1718739384', 'María Viviana', 'Zambrano Granda', 'secretaria', '$2y$10$fFQ0JYPVb/BInF5XbgWr6uLNmPSMTb4H3/RzIIXcwOy8TB9BLAnmi', '1'),
('4', '1710000041', 'Socio', 'Sistema', 'conductor', '$2y$10$GwhiUjo/QIE1ULKaNcM0Sud2ymlJ/4n8PZgU0ugrA0bTpz5lIj5O6', '1'),
('5', '2351048703', 'Isidro Fernando', 'Toapaxi Burgos', 'conductor', '$2y$10$Phta6dscCyWHlye0H3..Pef3aJxQ8vC2CRxQgllMTyzLHuEgWQk1K', '1'),
('6', '1719422402', 'Marcial Enrique', 'Pazmiño Quijije', 'conductor', '$2y$10$djw1571tOMAceobQtT9zS.AD9CeIZY5jhok6m9JZn2TRBsfO9hyp.', '1'),
('7', '2350637217', 'Jordan Enrique', 'Espinosa Vinueza', 'admin', '$2y$10$OCrNdurmkg..L.QlI/dGU.SW8tQ212IAzVK70W501b9NvQg.DhlKu', '1');

INSERT INTO bus (id, placa, disco, activo) VALUES
('1', 'JAA2412', '37', '1'),
('2', 'JAA3706', '65', '1'),
('3', 'JAA1825', '94', '1'),
('4', 'JAA2843', '93', '1'),
('5', 'JAA1687', '92', '1'),
('6', 'JAA2671', '91', '1'),
('7', 'PUE0145', '90', '1'),
('8', 'JAA3506', '89', '1'),
('9', 'JAA2838', '88', '1'),
('10', 'JAA2844', '87', '1'),
('11', 'JAA3514', '85', '1'),
('12', 'JAA3523', '84', '1'),
('13', 'JAA2659', '82', '1'),
('14', 'JAA2712', '81', '1'),
('15', 'JAA3243', '80', '1'),
('16', 'JAA3498', '79', '1'),
('17', 'PUE0146', '78', '1'),
('18', 'JAA3540', '77', '1'),
('19', 'JAA1915', '76', '1'),
('20', 'JAA3588', '75', '1'),
('21', 'JAA3114', '74', '1'),
('22', 'JAA1649', '73', '1'),
('23', 'JAA2827', '72', '1'),
('24', 'JAA1686', '70', '1'),
('25', 'JAA1614', '69', '1'),
('26', 'JAA2765', '68', '1'),
('27', 'JAA3192', '67', '1'),
('28', 'JAA1564', '66', '1'),
('29', 'JAA3564', '63', '1'),
('30', 'JAA3489', '62', '1'),
('31', 'JAA1965', '61', '1'),
('32', 'JAA0199', '59', '1'),
('33', 'JAA3593', '58', '1'),
('34', 'JAA3408', '57', '1'),
('35', 'JAA2790', '56', '1'),
('36', 'JAA2758', '55', '1'),
('37', 'JAA2825', '53', '1'),
('38', 'JAA2571', '52', '1'),
('39', 'JAA2830', '35', '1'),
('40', 'JAA2683', '47', '1'),
('41', 'JAA1674', '46', '1'),
('42', 'JAA2649', '45', '1'),
('43', 'JAA3244', '44', '1'),
('44', 'JAA3592', '43', '1'),
('45', 'JAA3479', '42', '1'),
('46', 'JAA2612', '41', '1'),
('47', 'JAA2837', '40', '1'),
('48', 'JAA3319', '39', '1'),
('49', 'JAA3161', '36', '1'),
('50', 'JAA3331', '34', '1'),
('51', 'JAA3525', '33', '1'),
('52', 'JAA3516', '32', '1'),
('53', 'JAA3640', '30', '1'),
('54', 'JAA1658', '29', '1'),
('55', 'JAA1509', '28', '1'),
('56', 'JAA3363', '27', '1'),
('57', 'JAA2220', '26', '1'),
('58', 'JAA1866', '25', '1'),
('59', 'JAA3280', '23', '1'),
('60', 'JAA1688', '22', '1'),
('61', 'JAA2718', '21', '1'),
('62', 'JAA3104', '20', '1'),
('63', 'JAA3060', '19', '1'),
('64', 'JAA3527', '18', '1'),
('65', 'JAA3594', '16', '1'),
('66', 'JAA0322', '15', '1'),
('67', 'JAA2636', '14', '1'),
('68', 'JAA1855', '13', '1'),
('69', 'JAA3237', '12', '1'),
('70', 'JAA3122', '11', '1'),
('71', 'JAA2851', '10', '1'),
('72', 'JAA3577', '08', '1'),
('73', 'JAA2586', '07', '1'),
('74', 'JAA3543', '06', '1'),
('75', 'JAA2686', '05', '1'),
('76', 'JAA2673', '04', '1'),
('77', 'JAA2619', '02', '1'),
('78', 'JAA3162', '01', '1'),
('79', 'JAA2735', '83', '1'),
('80', 'JAA3542', '50', '1'),
('81', 'JAA2581', '54', '1'),
('82', 'JAA3632', '49', '1'),
('83', 'JAA2769', '31', '1'),
('84', 'JAA2597', '71', '1'),
('85', 'LBA1213', '51', '1'),
('86', 'STD1232', '64', '1'),
('87', 'STD1234', '17', '1'),
('88', 'LBA1212', '09', '1');
COMMIT;
