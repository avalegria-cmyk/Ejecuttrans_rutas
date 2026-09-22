<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Dao/RecorridoDao.php';
require_once __DIR__.'/../Models/Recorrido.php';
require_once __DIR__.'/../Models/Evidencia.php';
$u=exigirAcceso(['admin','conductor'],true); exigirPost(); $archivo=null;
try {
    $km=Recorrido::kilometraje($_POST['kilometraje'] ?? null);
    $accion=$_POST['accion'] ?? '';
    if (!in_array($accion,['iniciar','finalizar'],true)) throw new DomainException('Acción inválida.');
    $archivo=Evidencia::guardar($_FILES['evidencia'] ?? []);
    $dao=new RecorridoDao($conexion);
    if ($accion==='iniciar') $dao->iniciar($u['id'],(int)($_POST['ruta_id'] ?? 0),$km,$archivo);
    else $dao->finalizar($u['id'],(int)($_POST['recorrido_id'] ?? 0),$km,$archivo);
    jsonResponse(['status'=>'success','message'=>$accion==='iniciar' ? 'Recorrido iniciado.' : 'Recorrido finalizado.']);
} catch (Throwable $e) { if ($archivo) @unlink(Evidencia::DIRECTORIO.$archivo); fallo($e); }
