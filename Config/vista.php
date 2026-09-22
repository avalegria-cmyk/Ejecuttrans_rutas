<?php
function cabecera(string $titulo, array $u, string $modulo=''): void { ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titulo) ?> · Ejecuttrans</title><link rel="icon" href="/Assets/icons/icon-192x192.png"><link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#2563eb"><script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={corePlugins:{preflight:false}};</script><link rel="stylesheet" href="/Assets/css/app.css"></head>
<body><div class="shell <?= $u['rol']==='conductor' ? 'driver-shell' : '' ?>">
<?php if ($u['rol']!=='conductor'): ?>
<aside class="sidebar"><a class="brand" href="/Web/admin/dashboard.php"><img src="/Assets/images/logo-ejecuttrans.png" alt="Ejecuttrans"><span>EJECUTTRANS<small>Control de recorridos</small></span></a><span class="nav-label">ADMINISTRACIÓN</span><nav>
<?php foreach(['dashboard'=>'Recorridos','buses'=>'Buses / discos','socios'=>'Socios','rutas'=>'Rutas','usuarios'=>'Usuarios'] as $key=>$label): if($key==='usuarios' && $u['rol']!=='admin') continue; ?><a class="<?= $modulo===$key?'selected':'' ?>" href="/Web/admin/<?= $key ?>.php"><?= e($label) ?></a><?php endforeach ?>
<?php if($u['rol']==='admin'): ?><a href="/App/conductor/dashboard.php">App de recorridos ↗</a><?php endif ?></nav><div class="sidebar-foot">Operación · Ecuador<br><small>America/Guayaquil · UTC−5</small></div></aside>
<?php endif ?>
<div class="workspace"><header class="topbar"><span><?= $u['rol']==='conductor'?'EJECUTTRANS · CONDUCTOR':'Panel de operación' ?></span><div><a href="/App/conductor/perfil.php"><?= e($u['nombres']) ?></a><span class="badge"><?= e($u['rol']) ?></span><a href="/auth/logout.php">Salir</a></div></header><main class="content"><div class="page-heading"><div><p class="eyebrow">CONTROL DE RECORRIDOS</p><h1><?= e($titulo) ?></h1></div><span class="date"><?= date('d/m/Y') ?></span></div>
<?php }
function pie(): void { ?></main><footer>Ejecuttrans · Registro de kilometraje</footer></div></div><script src="/Assets/js/common.js"></script></body></html><?php }
