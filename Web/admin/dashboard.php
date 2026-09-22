<?php
require_once __DIR__.'/../../Config/bootstrap.php';
require_once __DIR__.'/../../Config/vista.php';
require_once __DIR__.'/../../Dao/RecorridoDao.php';
$u=exigirAcceso(['admin','secretaria']); cabecera('Recorridos',$u,'dashboard');
?>
<div class="stats"><div class="stat"><span>RECORRIDOS VISIBLES</span><strong id="total">0</strong></div><div class="stat"><span>EN CURSO</span><strong id="activos">0</strong></div><div class="stat"><span>KILÓMETROS FINALIZADOS</span><strong id="kilometros">0</strong></div></div>
<section class="panel"><div class="toolbar"><div><h2>Registro de operación</h2><span class="live" id="conexion">Conectando…</span></div><input id="filtro" type="search" placeholder="Buscar conductor, disco o ruta…" aria-label="Buscar recorridos"><select id="estado" aria-label="Estado del recorrido" style="max-width:180px"><option value="">Todos los estados</option><option value="activo">En curso</option><option value="finalizado">Finalizados</option></select></div>
<div class="table-scroll"><table><thead><tr><th>Conductor</th><th>Disco</th><th>Ruta</th><th>Inicio</th><th>Km inicial</th><th>Evidencia</th><th>Fin</th><th>Km final</th><th>Evidencia</th><th>Distancia</th><th>Estado</th></tr></thead><tbody id="recorridos"></tbody></table><div id="vacio" class="empty">Todavía no hay recorridos. Aparecerán aquí cuando un conductor inicie uno.</div></div><p class="hint">Últimos 500 registros · Fechas y horas de Ecuador · Las evidencias se abren en otra pestaña.</p></section>
<script id="datosRecorridos" type="application/json"><?= json_encode((new RecorridoDao($conexion))->listar(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><script src="/Assets/js/recorridos-admin.js" defer></script>
<?php pie(); ?>
