<?php
require_once __DIR__.'/../../Config/bootstrap.php';
require_once __DIR__.'/../../Config/vista.php';
require_once __DIR__.'/../../Dao/CatalogoDao.php';
if (!isset($modulo) || !isset(CatalogoDao::TABLAS[$modulo])) { http_response_code(404); exit; }
$u=exigirAcceso($modulo==='usuarios'?['admin']:['admin','secretaria']);
$dao=new CatalogoDao($conexion); $filas=$dao->listar($modulo); $opciones=$dao->opciones();
$titulos=['usuarios'=>'Usuarios','socios'=>'Socios','buses'=>'Buses / discos','rutas'=>'Rutas'];
$campos=match($modulo) {
 'usuarios'=>['cedula'=>'Cédula','nombres'=>'Nombres','apellidos'=>'Apellidos','rol'=>'Rol','password'=>'Contraseña'],
 'socios'=>['cedula'=>'Cédula','nombres'=>'Nombres y apellidos','telefono'=>'Teléfono'],
 'buses'=>['disco'=>'Disco','placa'=>'Placa','socio_id'=>'Socio','conductor_id'=>'Conductor'],
 'rutas'=>['nombre'=>'Nombre de ruta','descripcion'=>'Descripción']
};
$columnas=array_filter($campos,fn($k)=>$k!=='password',ARRAY_FILTER_USE_KEY);
$mapas=['socio_id'=>array_column($opciones['socios'],'nombre','id'),'conductor_id'=>array_column($opciones['conductores'],'nombre','id')];
// Mantener visibles también los propietarios deshabilitados ya vinculados.
foreach($conexion->query('SELECT id,nombres FROM socio')->fetchAll() as $s) $mapas['socio_id'][$s['id']]=$s['nombres'];
cabecera($titulos[$modulo],$u,$modulo);
?>
<p class="muted" style="margin:-12px 0 24px"><?= match($modulo){'usuarios'=>'Acceso por rol: administrador, secretaría y conductor.','socios'=>'Directorio de propietarios y socios. Sus buses se vinculan desde Buses / discos.','buses'=>'Asigna un socio y un conductor fijo a cada unidad.','rutas'=>'Administra las rutas disponibles para iniciar un recorrido.'} ?></p>
<section class="panel"><div class="toolbar"><input id="buscar" type="search" placeholder="Buscar en <?= e(strtolower($titulos[$modulo])) ?>…" aria-label="Buscar"><button id="nuevo">+ Nuevo registro</button></div><div class="table-scroll"><table><thead><tr><?php foreach($columnas as $label): ?><th><?= e($label) ?></th><?php endforeach ?><th>Estado</th><th>Acciones</th></tr></thead><tbody id="registros">
<?php foreach($filas as $fila): ?><tr><?php foreach($columnas as $key=>$label): ?><td><?= e(isset($mapas[$key]) ? ($mapas[$key][$fila[$key]] ?? 'Sin asignar') : $fila[$key]) ?></td><?php endforeach ?><td><span class="badge <?= $fila['activo']?'green':'' ?>"><?= $fila['activo']?'Habilitado':'Deshabilitado' ?></span></td><td><button class="secondary editar" data-id="<?= $fila['id'] ?>">Editar</button></td></tr><?php endforeach ?>
</tbody></table><p id="sinResultados" class="empty" <?= $filas?'hidden':'' ?>>No hay registros para mostrar.</p></div></section>
<dialog id="editor"><form id="formCatalogo"><h2 id="tituloEditor">Nuevo registro</h2><p class="hint">Los campos con * son obligatorios.</p><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="modulo" value="<?= e($modulo) ?>"><input type="hidden" name="id"><div class="grid-fields">
<?php foreach($campos as $key=>$label): $required=!in_array($key,['password','telefono','placa','socio_id','conductor_id','descripcion']); ?><div class="field"><label for="campo_<?= $key ?>"><?= e($label) ?><?= $required?' *':'' ?></label>
<?php if($key==='rol'): ?><select name="rol" id="campo_rol"><option value="conductor">Conductor</option><option value="secretaria">Secretaría</option><option value="admin">Administrador</option></select>
<?php elseif(isset($mapas[$key])): ?><select name="<?= $key ?>" id="campo_<?= $key ?>"><option value="">Sin asignar</option><?php foreach($mapas[$key] as $id=>$nombre): ?><option value="<?= $id ?>"><?= e($nombre) ?></option><?php endforeach ?></select>
<?php else: ?><input name="<?= $key ?>" id="campo_<?= $key ?>" type="<?= $key==='password'?'password':'text' ?>" <?= $required?'required':'' ?> maxlength="<?= match($key){'cedula'=>10,'password'=>72,'disco','placa'=>20,'telefono'=>25,'descripcion'=>500,default=>100} ?>" <?= in_array($key,['cedula','disco'])?'inputmode="numeric" pattern="[0-9]+"':'' ?>><?php endif ?></div><?php endforeach ?>
<div class="field"><label for="activo">Estado</label><select id="activo" name="activo"><option value="1">Habilitado</option><option value="0">Deshabilitado</option></select></div></div>
<?php if($modulo==='usuarios'): ?><p class="hint">Usuario nuevo: si dejas la contraseña vacía, se usará su cédula. Al editar, dejarla vacía conserva la contraseña actual.</p><?php endif ?>
<div id="mensaje" class="message" role="alert"></div><div class="actions"><button type="button" class="secondary" id="cancelar">Cancelar</button><button type="submit" id="guardar">Guardar cambios</button></div></form></dialog>
<script id="datosCatalogo" type="application/json"><?= json_encode($filas,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><script src="/Assets/js/catalogos.js" defer></script>
<?php pie(); ?>
