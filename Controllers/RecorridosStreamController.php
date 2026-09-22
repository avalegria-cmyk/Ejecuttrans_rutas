<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Dao/RecorridoDao.php';
exigirAcceso(['admin','secretaria'],true);
$usuarioId=(int)$_SESSION['usuario_id']; session_write_close();
header('Content-Type: text/event-stream'); header('X-Accel-Buffering: no');
set_time_limit(30); $ultimo='';
for ($i=0;$i<10 && !connection_aborted();$i++) {
    $s=$conexion->prepare("SELECT id FROM usuario WHERE id=? AND activo=1 AND rol IN ('admin','secretaria')"); $s->execute([$usuarioId]);
    if (!$s->fetch()) { echo "event: revocado\ndata: {}\n\n"; @ob_flush(); flush(); break; }
    $datos=json_encode((new RecorridoDao($conexion))->listar(),JSON_UNESCAPED_UNICODE);
    $hash=hash('sha256',$datos);
    if ($hash!==$ultimo) { echo "event: recorridos\ndata: $datos\n\n"; $ultimo=$hash; } else echo ": conectado\n\n";
    @ob_flush(); flush(); sleep(2);
}
