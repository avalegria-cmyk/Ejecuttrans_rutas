<?php
require_once __DIR__.'/../Config/inicio.php';
require_once __DIR__.'/../Config/validacion_cedula.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['status'=>'error','message'=>'Método no permitido.'],405);
$cedula = (string)($_POST['cedula'] ?? '');
$clave = (string)($_POST['password'] ?? '');
if (!validarCedulaEcuatoriana($cedula)) jsonResponse(['status'=>'error','message'=>'Cédula inválida.'],422);
$s=$conexion->prepare('SELECT * FROM usuario WHERE cedula=? AND activo=1'); $s->execute([$cedula]); $u=$s->fetch();
if (!$u || !password_verify($clave,$u['password_hash'])) jsonResponse(['status'=>'error','message'=>'Cédula o contraseña incorrectos.'],401);
session_regenerate_id(true); $_SESSION=['usuario_id'=>$u['id'],'rol'=>$u['rol']];
jsonResponse(['status'=>'success','redirect'=>obtenerRutaInicio($u['rol'])]);
