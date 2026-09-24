<?php
require_once __DIR__.'/../../Config/inicio.php';
require_once __DIR__.'/../../Config/vista.php';
$u=exigirAcceso(['admin','secretaria','conductor']);
$iniciales=mb_strtoupper(mb_substr(trim($u['nombres']),0,1).mb_substr(trim($u['apellidos']),0,1));
cabecera('Mi perfil',$u,'perfil',false);
?>
<div class="profile-stack">
<section class="panel profile-summary"><div class="profile-avatar" aria-hidden="true"><?= e($iniciales) ?></div><div class="profile-identity"><span class="profile-label">CUENTA ACTIVA</span><h2><?= e($u['nombres'].' '.$u['apellidos']) ?></h2><p class="muted">Cédula <?= e($u['cedula']) ?></p><span class="badge green"><?= e(ucfirst($u['rol'])) ?></span></div><a class="button secondary profile-back" href="<?= e(urlApp(obtenerRutaInicio($u['rol']))) ?>">← Volver al panel</a></section>
<section class="panel profile-security"><div class="profile-section-heading"><div><span class="profile-label">SEGURIDAD</span><h2>Cambiar contraseña</h2></div><p class="hint">Usa al menos 8 caracteres y evita compartir tu clave.</p></div><form id="perfilForm"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><div class="field"><label for="actual">Contraseña actual</label><input id="actual" type="password" name="actual" required autocomplete="current-password"></div><div class="field"><label for="nueva">Nueva contraseña</label><input id="nueva" type="password" name="nueva" minlength="8" maxlength="72" required autocomplete="new-password"></div><div class="field"><label for="confirmacion">Confirmar nueva contraseña</label><input id="confirmacion" type="password" name="confirmacion" minlength="8" maxlength="72" required autocomplete="new-password"></div><div id="mensaje" class="message" role="status"></div><button>Actualizar contraseña</button></form></section>
<section class="logout-panel"><div><strong>Cerrar sesión</strong><p>Finaliza tu sesión de forma segura en este dispositivo.</p></div><a class="button logout-button" href="<?= e(urlApp('auth/logout.php')) ?>"><span aria-hidden="true">↪</span> Salir de la cuenta</a></section>
</div>
<script src="<?= e(urlApp('Assets/js/perfil.js')) ?>" defer></script>
<?php pie(); ?>
