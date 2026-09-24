<?php
require_once __DIR__.'/../Config/inicio.php';
require_once __DIR__.'/../Dao/CatalogoDao.php';
exigirAcceso(['admin','secretaria'],true);
if ($_SERVER['REQUEST_METHOD']!=='GET') jsonResponse(['status'=>'error','message'=>'Método no permitido.'],405);
$texto=$_GET['q'] ?? '';
$busId=filter_var($_GET['bus_id'] ?? '0',FILTER_VALIDATE_INT);
if (!is_string($texto) || mb_strlen($texto)>100 || $busId===false || $busId<0) {
    jsonResponse(['status'=>'error','message'=>'Búsqueda inválida.'],422);
}
try {
    $conductores=(new CatalogoDao($conexion))->buscarConductores(trim($texto),$busId);
    jsonResponse(['status'=>'success','conductores'=>$conductores]);
} catch (Throwable $e) { fallo($e); }
