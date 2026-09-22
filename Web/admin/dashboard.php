<?php
require_once __DIR__.'/../../Config/bootstrap.php';
require_once __DIR__.'/../../Config/vista.php';
require_once __DIR__.'/../../Dao/DashboardDao.php';
$u=exigirAcceso(['admin','secretaria']);
$datos=(new DashboardDao($conexion))->obtener();
cabecera('Dashboard',$u,'dashboard');
?>
<div class="overview-intro"><div><p class="eyebrow">RESUMEN GENERAL</p><h2>Así va la operación hoy</h2><p class="muted">Datos de <?= e(date('d/m/Y')) ?> · Hora de Ecuador</p></div><a class="button" href="/Web/admin/recorridos.php">Ver recorridos <span aria-hidden="true">→</span></a></div>
<div class="overview-stats" aria-label="Indicadores de operación">
  <article class="overview-card accent-blue"><span>Recorridos de hoy</span><strong><?= e($datos['recorridos_hoy']) ?></strong><small><?= e($datos['finalizados_hoy']) ?> finalizados</small></article>
  <article class="overview-card accent-amber"><span>En curso ahora</span><strong><?= e($datos['en_curso']) ?></strong><small>Recorridos activos</small></article>
  <article class="overview-card accent-violet"><span>Buses habilitados</span><strong><?= e($datos['buses_activos']) ?></strong><small><?= e($datos['buses_asignados']) ?> con conductor asignado</small></article>
</div>
<div class="overview-grid">
  <section class="panel overview-activity"><div class="section-title"><div><span class="profile-label">ÚLTIMA ACTIVIDAD</span><h2>Recorridos recientes</h2></div><a href="/Web/admin/recorridos.php">Ver todos →</a></div>
  <?php if($datos['recientes']): ?><div class="activity-list"><?php foreach($datos['recientes'] as $r): ?><div class="activity-item"><div class="activity-icon <?= $r['fin']?'done':'running' ?>" aria-hidden="true"><?= $r['fin']?'✓':'↗' ?></div><div><strong><?= e($r['conductor_nombre']) ?></strong><span>Disco <?= e($r['disco']) ?> · <?= e($r['ruta_nombre']) ?></span></div><div class="activity-meta"><span class="badge <?= $r['fin']?'green':'amber' ?>"><?= $r['fin']?'Finalizado':'En curso' ?></span><small><?= e(date('d/m H:i',strtotime($r['inicio']))) ?></small></div></div><?php endforeach ?></div>
  <?php else: ?><p class="empty">Aún no hay recorridos registrados.</p><?php endif ?></section>
  <div class="overview-side"><section class="panel"><div class="section-title"><div><span class="profile-label">CATÁLOGOS</span><h2>Estado del sistema</h2></div></div><div class="system-facts"><div><span>Buses registrados</span><strong><?= e($datos['buses_total']) ?></strong></div><div><span>Sin conductor</span><strong><?= e($datos['buses_sin_conductor']) ?></strong></div><div><span>Rutas habilitadas</span><strong><?= e($datos['rutas_activas']) ?></strong></div><?php if($u['rol']==='admin'): ?><div><span>Usuarios habilitados</span><strong><?= e($datos['usuarios_activos']) ?></strong></div><?php endif ?></div></section></div>
</div>
<?php pie(); ?>
