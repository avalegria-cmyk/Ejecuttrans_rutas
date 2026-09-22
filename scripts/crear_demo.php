<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../Config/conexion.php';
// Datos ficticios locales. Nunca sobrescribe usuarios ni contraseñas existentes.
$usuarios=[['1710034065','Administrador','Demo','admin'],['0926687856','Secretaría','Demo','secretaria'],['0102030400','Conductor','Demo','conductor']];
foreach($usuarios as [$cedula,$nombres,$apellidos,$rol]) {
 $s=$conexion->prepare('INSERT IGNORE INTO usuario(cedula,nombres,apellidos,rol,password_hash) VALUES (?,?,?,?,?)');
 $s->execute([$cedula,$nombres,$apellidos,$rol,password_hash('Demo.2026*',PASSWORD_DEFAULT)]);
}
$conexion->exec("INSERT IGNORE INTO socio(cedula,nombres,telefono) VALUES ('1710034065','Socio de ejemplo','')");
$conexion->exec("INSERT IGNORE INTO bus(disco,placa,socio_id,conductor_id) SELECT '001','DEMO-001',s.id,u.id FROM socio s CROSS JOIN usuario u WHERE s.cedula='1710034065' AND u.cedula='0102030400'");
echo "Usuarios demo disponibles. Contraseña local: Demo.2026*\n";
