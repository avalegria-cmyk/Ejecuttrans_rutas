<?php
require_once __DIR__.'/../Config/inicio.php';
$modulo=$_POST['modulo'] ?? '';
if (!is_string($modulo) || !in_array($modulo,['dashboard','recorridos','buses','rutas','usuarios','perfil'],true)) {
    jsonResponse(['status'=>'error','message'=>'Módulo inválido.'],404);
}
exigirAcceso($modulo==='perfil'?['admin','secretaria','conductor']:($modulo==='usuarios'?['admin']:['admin','secretaria']),true);
exigirPost();
session_write_close();
require_once __DIR__.'/../Config/exportar_excel.php';
try {
    $entrada=$_POST['datos'] ?? '';
    if (!is_string($entrada) || strlen($entrada)>8*1024*1024) throw new DomainException('La exportación es demasiado grande. Reduce los filtros.');
    $datos=json_decode($entrada,true,32,JSON_THROW_ON_ERROR);
    $encabezados=$datos['encabezados'] ?? null; $filas=$datos['filas'] ?? null;
    if (!is_array($encabezados) || !array_is_list($encabezados) || !count($encabezados) || count($encabezados)>30 || !is_array($filas) || !array_is_list($filas) || count($filas)>20000) throw new DomainException('Formato de exportación inválido.');
    foreach ($encabezados as $titulo) if (!is_string($titulo) || mb_strlen($titulo)>150) throw new DomainException('Encabezado inválido.');
    foreach ($filas as $fila) {
        if (!is_array($fila) || !array_is_list($fila) || count($fila)!==count($encabezados)) throw new DomainException('Fila inválida.');
        foreach ($fila as $valor) if ((!is_string($valor) && !is_int($valor) && !is_float($valor) && $valor!==null) || (is_string($valor) && mb_strlen($valor)>32767)) throw new DomainException('Celda inválida.');
    }
    if ($modulo==='recorridos') {
        $columnas=array_keys(array_filter($encabezados, static fn($titulo)=>!preg_match('/^evidencia(?:\s|$)/iu',trim($titulo))));
        if (!$columnas) throw new DomainException('No hay columnas para exportar.');
        $encabezados=array_map(static fn($i)=>$encabezados[$i],$columnas);
        $filas=array_map(static fn($fila)=>array_map(static fn($i)=>$fila[$i],$columnas),$filas);
    }
    descargarExcel($modulo,$encabezados,$filas);
} catch (Throwable $e) { fallo($e); }
