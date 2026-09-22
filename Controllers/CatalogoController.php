<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Config/validacion_cedula.php';
require_once __DIR__.'/../Dao/CatalogoDao.php';
$modulo=(string)($_POST['modulo'] ?? '');
if (!isset(CatalogoDao::TABLAS[$modulo])) jsonResponse(['status'=>'error','message'=>'Módulo inválido.'],404);
$u=exigirAcceso($modulo==='usuarios' ? ['admin'] : ['admin','secretaria'],true); exigirPost();
try { (new CatalogoDao($conexion))->guardar($modulo,$u['id']); jsonResponse(['status'=>'success','message'=>'Cambios guardados.']); } catch(Throwable $e) { fallo($e); }
