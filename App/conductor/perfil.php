<?php
require_once __DIR__.'/../../Config/bootstrap.php';
require_once __DIR__.'/../../Config/vista.php';
$u=exigirAcceso(['admin','secretaria','conductor']); cabecera('Mi perfil',$u);
?>
<section class="panel"><h2><?= e($u['nombres'].' '.$u['apellidos']) ?></h2><p class="muted">Cédula: <?= e($u['cedula']) ?> · <?= e($u['rol']) ?></p><a class="button secondary" href="/<?= e(obtenerRutaInicio($u['rol'])) ?>">← Volver</a></section>
<section class="panel" style="max-width:560px"><h2>Cambiar contraseña</h2><form id="perfilForm"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><div class="field"><label for="actual">Contraseña actual</label><input id="actual" type="password" name="actual" required autocomplete="current-password"></div><div class="field"><label for="nueva">Nueva contraseña</label><input id="nueva" type="password" name="nueva" minlength="8" maxlength="72" required autocomplete="new-password"></div><div class="field"><label for="confirmacion">Confirmar nueva contraseña</label><input id="confirmacion" type="password" name="confirmacion" minlength="8" maxlength="72" required autocomplete="new-password"></div><div id="mensaje" class="message" role="status"></div><button>Actualizar contraseña</button></form></section>
<script src="/Assets/js/perfil.js" defer></script>
<?php pie(); ?>
