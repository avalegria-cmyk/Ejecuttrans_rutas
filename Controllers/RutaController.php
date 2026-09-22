<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Dao/RutaDao.php';
exigirAcceso(['admin', 'conductor'], true);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['status'=>'error', 'message'=>'Método no permitido.'], 405);
}
$termino = $_GET['q'] ?? '';
if (!is_string($termino) || strlen($termino) > 480) {
    jsonResponse(['status'=>'error', 'message'=>'Escribe un nombre de ruta válido.'], 422);
}
try {
    jsonResponse(['status'=>'success', 'rutas'=>(new RutaDao($conexion))->buscarHabilitadas(trim($termino))]);
} catch (Throwable $e) { fallo($e); }
