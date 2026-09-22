<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../Config/conexion.php';
// Cuentas iniciales del sistema. Nunca sobrescribe usuarios existentes.
$usuarios=[['1710034065','Administrador','Demo','admin'],['0926687856','Secretaría','Demo','secretaria'],['0102030400','Conductor','Demo','conductor']];
foreach($usuarios as [$cedula,$nombres,$apellidos,$rol]) {
 $s=$conexion->prepare('INSERT IGNORE INTO usuario(cedula,nombres,apellidos,rol,password_hash) VALUES (?,?,?,?,?)');
 $s->execute([$cedula,$nombres,$apellidos,$rol,password_hash($cedula,PASSWORD_DEFAULT)]);
}
$conexion->exec("INSERT IGNORE INTO bus(disco,placa,conductor_id) SELECT '001','DEMO-001',u.id FROM usuario u WHERE u.cedula='0102030400'");
echo "Usuarios creados. La contraseña de cada usuario es su propia cédula.\n";
