<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Dao/RecorridoDao.php';
$usuario=exigirAcceso(['admin','conductor'],true);
$usuarioId=(int)$usuario['id'];
// Libera la sesión para permitir guardar recorridos mientras el stream está abierto.
session_write_close();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
set_time_limit(30);
echo "retry: 1000\n\n";
$ultimo='';
$dao=new RecorridoDao($conexion);
for ($i=0;$i<10 && !connection_aborted();$i++) {
    $s=$conexion->prepare("SELECT id FROM usuario WHERE id=? AND activo=1 AND rol IN ('admin','conductor')");
    $s->execute([$usuarioId]);
    if (!$s->fetch()) {
        echo "event: revocado\ndata: {}\n\n";
        @ob_flush(); flush(); break;
    }
    $datos=json_encode($dao->estadoConductor($usuarioId),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $hash=hash('sha256',$datos);
    if ($hash!==$ultimo) {
        echo "event: estado\ndata: $datos\n\n";
        $ultimo=$hash;
    } else echo ": conectado\n\n";
    @ob_flush(); flush(); sleep(2);
}
