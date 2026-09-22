<?php
require_once __DIR__.'/../Config/bootstrap.php';
require_once __DIR__.'/../Models/Evidencia.php';
$u=exigirAcceso(['admin','secretaria','conductor'],true);
$s=$conexion->prepare('SELECT conductor_id,evidencia_inicial,evidencia_final FROM recorrido WHERE id=?'); $s->execute([(int)($_GET['id'] ?? 0)]); $r=$s->fetch();
if (!$r || ($u['rol']==='conductor' && (int)$r['conductor_id']!==$u['id'])) { http_response_code(404); exit; }
$archivo=($_GET['tipo'] ?? '')==='final' ? $r['evidencia_final'] : $r['evidencia_inicial'];
if (!$archivo || !is_file(Evidencia::DIRECTORIO.$archivo)) { http_response_code(404); exit; }
header('Content-Type: '.(str_ends_with($archivo,'.pdf') ? 'application/pdf':'image/jpeg'));
header('Content-Disposition: inline; filename="evidencia-'.(int)$_GET['id'].'.'.pathinfo($archivo,PATHINFO_EXTENSION).'"');
header("Content-Security-Policy: sandbox"); readfile(Evidencia::DIRECTORIO.$archivo);
