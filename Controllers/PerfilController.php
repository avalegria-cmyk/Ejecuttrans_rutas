<?php
require_once __DIR__.'/../Config/inicio.php';
$u=exigirAcceso(['admin','secretaria','conductor'],true); exigirPost();
$actual=$_POST['actual'] ?? ''; $nueva=$_POST['nueva'] ?? ''; $confirmacion=$_POST['confirmacion'] ?? '';
if (!is_string($actual) || !is_string($nueva) || $nueva!==$confirmacion || strlen($nueva)<8 || strlen($nueva)>72) jsonResponse(['status'=>'error','message'=>'La nueva contraseña debe coincidir y tener de 8 a 72 caracteres.'],422);
$s=$conexion->prepare('SELECT password_hash FROM usuario WHERE id=?'); $s->execute([$u['id']]);
if (!password_verify($actual,$s->fetchColumn())) jsonResponse(['status'=>'error','message'=>'La contraseña actual es incorrecta.'],422);
$s=$conexion->prepare('UPDATE usuario SET password_hash=? WHERE id=?'); $s->execute([password_hash($nueva,PASSWORD_DEFAULT),$u['id']]); session_regenerate_id(true);
jsonResponse(['status'=>'success','message'=>'Contraseña actualizada.']);
