<?php
require_once __DIR__.'/../../Config/bootstrap.php';
require_once __DIR__.'/../../Dao/RecorridoDao.php';
$u = exigirAcceso(['admin', 'conductor']);
$activo = (new RecorridoDao($conexion))->activo($u['id']);
$s = $conexion->prepare('SELECT disco,placa FROM bus WHERE conductor_id=? AND activo=1');
$s->execute([$u['id']]);
$bus = $s->fetch();
$nombreCorto = explode(' ', trim($u['nombres']))[0];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#f3f4f6">
    <meta name="color-scheme" content="light">
    <title>Menú · Recorridos</title>
    <link rel="icon" href="/Assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/Assets/icons/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={corePlugins:{preflight:false}};</script>
    <link rel="stylesheet" href="/Assets/css/conductor.css?v=3">
</head>
<body>
<svg class="iconos" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><defs>
    <symbol id="icono-perfil" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="icono-ruta" viewBox="0 0 24 24"><circle cx="5" cy="6" r="3"/><circle cx="19" cy="18" r="3"/><path d="M8 6h9a4 4 0 0 1 0 8H7a4 4 0 0 0 0 8"/></symbol>
    <symbol id="icono-fin" viewBox="0 0 24 24"><path d="M5 22V3m0 0c5-4 9 4 15 0v11c-6 4-10-4-15 0"/></symbol>
    <symbol id="icono-buscar" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></symbol>
</defs></svg>
<main class="menu-app">
    <?php if($u['rol']==='admin'): ?><a class="volver-admin" href="/Web/admin/dashboard.php">← Administración</a><?php endif ?>
    <header class="perfil-card">
        <div>
            <h1>Hola, <?= e($nombreCorto) ?></h1>
            <p><?= date('d/m/Y') ?></p>
            <span class="disco"><?= $bus ? 'Disco '.e($bus['disco']) : 'Sin unidad asignada' ?></span>
        </div>
        <a href="perfil.php" class="perfil-link" title="Ver mi perfil" aria-label="Ver mi perfil"><svg><use href="#icono-perfil"/></svg></a>
    </header>
    <section class="modulos" aria-label="Módulos de rutas">
        <button type="button" id="iniciarRuta" class="modulo modulo-inicio" <?= $activo || !$bus ? 'disabled' : '' ?>>
            <span class="modulo-icono"><svg><use href="#icono-ruta"/></svg></span>
            <span class="modulo-titulo">INICIAR RUTA</span>
            <span class="modulo-detalle"><?= $activo ? 'Ya tienes una ruta en curso' : 'Seleccionar ruta y registrar salida' ?></span>
        </button>
        <button type="button" id="finalizarRuta" class="modulo modulo-fin" <?= !$activo ? 'disabled' : '' ?>>
            <span class="modulo-icono"><svg><use href="#icono-fin"/></svg></span>
            <span class="modulo-titulo">FINALIZAR RUTA</span>
            <span class="modulo-detalle"><?= $activo ? 'Registrar llegada y cerrar ruta' : 'Primero debes iniciar una ruta' ?></span>
        </button>
    </section>
    <?php if($activo): ?>
        <div class="estado-ruta" role="status"><span class="punto"></span><div><strong>En ruta · <?= e($activo['ruta_nombre']) ?></strong><p>Disco <?= e($activo['disco']) ?> · <?= number_format($activo['km_inicial'], 0, ',', '.') ?> km iniciales</p></div></div>
    <?php elseif(!$bus): ?>
        <p class="aviso">Solicita a secretaría que te asigne un bus habilitado para iniciar.</p>
    <?php endif ?>
</main>
<dialog id="confirmacionRuta" class="confirmacion" aria-labelledby="tituloConfirmacion" aria-describedby="textoConfirmacion">
    <span class="confirmacion-icono"><svg><use href="<?= $activo ? '#icono-fin' : '#icono-ruta' ?>"/></svg></span>
    <h2 id="tituloConfirmacion"><?= $activo ? '¿Finalizar esta ruta?' : '¿Iniciar una ruta?' ?></h2>
    <p id="textoConfirmacion"><?= $activo ? '¿Estás seguro de que quieres terminar tu ruta? A continuación, registra el kilometraje final y su evidencia.' : '¿Estás seguro de que quieres iniciar una ruta? A continuación, elige la ruta y registra tu kilometraje inicial.' ?></p>
    <div class="acciones"><button type="button" class="secundario" id="cancelarConfirmacion" autofocus>Cancelar</button><button type="button" id="aceptarConfirmacion"><?= $activo ? 'Sí, continuar' : 'Sí, iniciar' ?></button></div>
</dialog>
<dialog id="modalRecorrido" aria-labelledby="tituloFormulario">
<form id="formRecorrido">
    <span class="paso">REGISTRO DE <?= $activo ? 'LLEGADA' : 'SALIDA' ?></span>
    <h2 id="tituloFormulario"><?= $activo ? 'Finalizar ruta' : 'Iniciar ruta' ?></h2>
    <p class="ayuda">Completa los datos para <?= $activo ? 'cerrar tu ruta' : 'comenzar el recorrido' ?>.</p>
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input type="hidden" name="accion" value="<?= $activo ? 'finalizar' : 'iniciar' ?>">
    <input type="hidden" name="recorrido_id" value="<?= $activo ? $activo['id'] : '' ?>">
    <?php if(!$activo): ?>
    <div class="campo selector-ruta">
        <label for="buscarRuta">Ruta</label>
        <div class="buscador"><svg aria-hidden="true"><use href="#icono-buscar"/></svg><input id="buscarRuta" type="text" placeholder="Escribe o selecciona una ruta…" maxlength="120" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="opcionesRutas" aria-describedby="estadoBusqueda" required></div>
        <input type="hidden" name="ruta_id" id="rutaId">
        <div id="resultadosRutas" class="resultados" hidden><ul id="opcionesRutas" role="listbox" aria-label="Rutas disponibles"></ul></div>
        <p id="estadoBusqueda" class="ayuda" role="status">Se muestran hasta 5 coincidencias. Selecciona una ruta del listado.</p>
    </div>
    <?php else: ?>
    <div class="resumen"><strong><?= e($activo['ruta_nombre']) ?></strong><span>Kilometraje inicial: <?= e($activo['km_inicial']) ?> km</span></div>
    <?php endif ?>
    <div class="campo"><label for="kilometraje">Kilometraje <?= $activo ? 'final' : 'inicial' ?></label><input type="text" inputmode="numeric" pattern="[0-9]{1,9}" maxlength="9" name="kilometraje" id="kilometraje" placeholder="Ej. 10000" required></div>
    <div class="campo">
        <label for="evidencia">Foto del kilometraje o PDF</label>
        <input type="file" id="evidencia" name="evidencia" class="archivo-oculto" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        <button type="button" id="subirEvidencia" class="boton-evidencia">Subir evidencia</button>
        <p id="nombreEvidencia" class="nombre-evidencia" role="status"></p>
        <p class="ayuda">JPG, PNG, WEBP o PDF · hasta 10 MB. Las imágenes se comprimen automáticamente.</p>
    </div>
    <div class="mensaje" role="alert" id="mensaje"></div>
    <div class="acciones"><button type="button" class="secundario" id="cancelar">Cancelar</button><button type="submit" id="enviar"><?= $activo ? 'Finalizar ruta' : 'Iniciar ruta' ?></button></div>
</form>
</dialog>
<script src="/Assets/js/common.js"></script>
<script src="/Assets/js/comprimir_comprobante.js"></script>
<script src="/Assets/js/recorrido.js?v=3" defer></script>
</body>
</html>
