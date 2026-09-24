<?php
require_once __DIR__.'/../../Config/inicio.php';
require_once __DIR__.'/../../Config/vista.php';
require_once __DIR__.'/../../Dao/CatalogoDao.php';
if (!isset($modulo) || !isset(CatalogoDao::TABLAS[$modulo])) { http_response_code(404); exit; }
$u=exigirAcceso($modulo==='usuarios'?['admin']:['admin','secretaria']);
$dao=new CatalogoDao($conexion); $filas=$dao->listar($modulo);
$paginado=in_array($modulo,['usuarios','buses'],true);
$titulos=['usuarios'=>'Usuarios','buses'=>'Buses / discos','rutas'=>'Rutas'];
$campos=match($modulo) {
 'usuarios'=>['cedula'=>'Cédula','nombres'=>'Nombres','apellidos'=>'Apellidos','rol'=>'Rol','password'=>'Contraseña'],
 'buses'=>['disco'=>'Disco','placa'=>'Placa','conductor_id'=>'Conductor'],
 'rutas'=>['nombre'=>'Nombre de ruta','descripcion'=>'Descripción']
};
$columnas=array_filter($campos,fn($k)=>$k!=='password',ARRAY_FILTER_USE_KEY);
cabecera($titulos[$modulo],$u,$modulo);
?>
<section class="panel"><div class="toolbar filters"><div class="filter-controls"><input id="buscar" type="search" placeholder="Buscar en <?= e(strtolower($titulos[$modulo])) ?>…" aria-label="Buscar">
<?php if($modulo==='usuarios'): ?><select id="filtroRol" aria-label="Filtrar por rol"><option value="">Todos los roles</option><option value="admin">Administrador</option><option value="secretaria">Secretaría</option><option value="conductor">Conductor</option></select><?php endif ?>
<?php if($modulo==='buses'): ?><select id="filtroAsignacion" aria-label="Filtrar por asignación"><option value="">Todos los buses</option><option value="1">Con conductor</option><option value="0">Sin conductor</option></select><?php endif ?>
<select id="filtroEstado" aria-label="Filtrar por estado"><option value="">Todos los estados</option><option value="1">Habilitados</option><option value="0">Deshabilitados</option></select><button type="button" class="secondary" id="limpiarFiltros">Limpiar filtros</button></div><button id="nuevo">+ Nuevo registro</button></div><p class="filter-summary"><span id="conteoFiltrado"><?= count($filas) ?></span> <?= $paginado?'registros encontrados':'registros visibles' ?></p><div class="table-scroll"><table><thead><tr><?php foreach($columnas as $label): ?><th><?= e($label) ?></th><?php endforeach ?><th>Estado</th><th>Acciones</th></tr></thead><tbody id="registros">
<?php foreach($filas as $indiceFila=>$fila): ?><tr <?= $paginado && $indiceFila>=20?'hidden':'' ?> data-activo="<?= (int)$fila['activo'] ?>" data-rol="<?= e($fila['rol'] ?? '') ?>" data-conductor="<?= empty($fila['conductor_id'])?'0':'1' ?>"><?php foreach($columnas as $key=>$label): ?><td><?= e($key==='conductor_id' ? ($fila['conductor_nombre'] ?? 'Sin asignar') : $fila[$key]) ?></td><?php endforeach ?><td><span class="badge <?= $fila['activo']?'green':'' ?>"><?= $fila['activo']?'Habilitado':'Deshabilitado' ?></span></td><td><button type="button" class="<?= $modulo==='buses'?'editar-bus':'secondary' ?> editar" data-id="<?= $fila['id'] ?>"><?php if($modulo==='buses'): ?><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5M4 20l4-1L21 6a2 2 0 0 0-5-5L3 14l-1 7 7-2M13 21h8"/></svg><?php endif ?>Editar</button></td></tr><?php endforeach ?>
</tbody></table><p id="sinResultados" class="empty" <?= $filas?'hidden':'' ?>>No hay registros para mostrar.</p></div>
<?php if($paginado): ?>
<div id="paginacionCatalogo" class="catalog-pagination" data-entidad="<?= e($modulo) ?>">
<p id="resumenPaginaCatalogo" role="status" aria-live="polite"></p>
<div class="catalog-pagination-controls" role="group" aria-label="Paginación de <?= e(strtolower($titulos[$modulo])) ?>">
<button type="button" class="secondary" id="paginaCatalogoAnterior" aria-controls="registros">Anterior</button>
<span id="paginaCatalogoActual"></span>
<button type="button" class="secondary" id="paginaCatalogoSiguiente" aria-controls="registros">Siguiente</button>
</div></div>
<?php endif ?>
</section>
<dialog id="editor"><form id="formCatalogo"><h2 id="tituloEditor">Nuevo registro</h2><p class="hint">Los campos con * son obligatorios.</p><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="modulo" value="<?= e($modulo) ?>"><input type="hidden" name="id"><div class="grid-fields">
<?php foreach($campos as $key=>$label): $required=!in_array($key,['password','placa','conductor_id','descripcion']); ?><div class="field"><label for="campo_<?= $key ?>"><?= e($label) ?><?= $required?' *':'' ?></label>
<?php if($key==='rol'): ?><select name="rol" id="campo_rol"><option value="conductor">Conductor</option><option value="secretaria">Secretaría</option><option value="admin">Administrador</option></select>
<?php elseif($key==='conductor_id'): ?>
<div class="conductor-search">
<input type="hidden" name="conductor_id" id="conductorSeleccionado">
<div class="conductor-search-controls"><input id="campo_conductor_id" type="text" maxlength="100" placeholder="Buscar por nombre o cédula…" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="resultadosConductores" aria-describedby="ayudaConductor estadoConductores"><button type="button" class="secondary" id="limpiarConductor" aria-label="Dejar bus sin conductor">Quitar</button></div>
<ul id="resultadosConductores" class="conductor-results" role="listbox" aria-label="Conductores disponibles" hidden></ul>
<p id="estadoConductores" class="hint" role="status" aria-live="polite"></p>
<p id="ayudaConductor" class="hint">Selecciona un resultado o deja el campo vacío para mantenerlo sin asignar.</p>
</div>
<?php else: ?><input name="<?= $key ?>" id="campo_<?= $key ?>" type="<?= $key==='password'?'password':'text' ?>" <?= $required?'required':'' ?> maxlength="<?= match($key){'cedula'=>10,'password'=>72,'disco','placa'=>20,'descripcion'=>500,default=>100} ?>" <?= in_array($key,['cedula','disco'])?'inputmode="numeric" pattern="[0-9]+"':'' ?>><?php endif ?></div><?php endforeach ?>
<div class="field"><label for="activo">Estado</label><select id="activo" name="activo"><option value="1">Habilitado</option><option value="0">Deshabilitado</option></select></div></div>
<?php if($modulo==='usuarios'): ?><p class="hint">Usuario nuevo: si dejas la contraseña vacía, se usará su cédula. Al editar, dejarla vacía conserva la contraseña actual.</p><?php endif ?>
<div id="mensaje" class="message" role="alert"></div><div class="actions"><button type="button" class="secondary" id="cancelar">Cancelar</button><button type="submit" id="guardar">Guardar cambios</button></div></form></dialog>
<script id="datosCatalogo" type="application/json"><?= json_encode($filas,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><script src="<?= e(urlApp('Assets/js/catalogos.js')) ?>" defer></script>
<?php pie(); ?>
