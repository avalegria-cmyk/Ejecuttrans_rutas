<?php
function iconoMenu(string $nombre): string {
    $trazos = [
        'dashboard' => '<path d="M3 3v18h18M7 14l4-4 4 3 6-8"/>',
        'recorridos' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'buses' => '<rect x="5" y="3" width="14" height="16" rx="3"/><path d="M5 11h14M8 19v2m8-2v2M9 15h.01M15 15h.01"/>',
        'rutas' => '<circle cx="6" cy="5" r="2"/><circle cx="18" cy="19" r="2"/><path d="M8 5h8a4 4 0 0 1 0 8H8a3 3 0 0 0 0 6h8"/>',
        'usuarios' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v3"/>',
        'perfil' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'app' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'excel' => '<path d="M14 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9zM14 2v7h7M7 12l5 7m0-7-5 7M16 13h2m-2 4h2"/>',
        'salir' => '<path d="M9 3H3v18h6m6-14 5 5-5 5M8 12h12"/>',
    ];
    return '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($trazos[$nombre] ?? '').'</svg>';
}
function cabecera(string $titulo, array $u, string $modulo='', bool $mostrarSalida=true): void { ?>
<!doctype html><html lang="es" data-app-base="<?= e(baseApp()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titulo) ?> · Ejecuttrans</title><link rel="icon" href="<?= e(urlApp('Assets/icons/icon-192x192.png')) ?>"><link rel="manifest" href="<?= e(urlApp('manifest.json')) ?>"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="<?= e(urlApp('Assets/css/tailwind.css')) ?>"><link rel="stylesheet" href="<?= e(urlApp('Assets/css/app.css')) ?>"><link rel="stylesheet" href="<?= e(urlApp('Assets/css/layout.css')) ?>"></head>
<body><div class="shell <?= $modulo==='perfil'?'profile-page':'' ?> <?= $u['rol']==='conductor' ? 'driver-shell' : 'admin-shell' ?>">
<?php if ($u['rol']!=='conductor'): ?>
<aside class="sidebar" id="sidebar" aria-label="Navegación principal">
    <div class="sidebar-head"><a class="brand" href="<?= e(urlApp('Web/admin/dashboard.php')) ?>"><img src="<?= e(urlApp('Assets/icons/icon-192x192.png')) ?>" alt=""><span>EJECUTTRANS</span></a><button type="button" class="sidebar-close" id="sidebarClose" aria-label="Ocultar menú"><span aria-hidden="true">‹</span></button></div>
    <nav aria-label="Módulos"><p class="nav-label">Administración</p>
    <?php foreach(['dashboard'=>'Dashboard','recorridos'=>'Recorridos','buses'=>'Buses / discos','rutas'=>'Rutas','usuarios'=>'Usuarios'] as $key=>$label): if($key==='usuarios' && $u['rol']!=='admin') continue; ?>
        <a class="<?= $modulo===$key?'selected':'' ?>" <?= $modulo===$key?'aria-current="page"':'' ?> href="<?= e(urlApp('Web/admin/'.$key.'.php')) ?>"><?= iconoMenu($key) ?><span><?= e($label) ?></span></a>
    <?php endforeach ?>
        <a class="<?= $modulo==='perfil'?'selected':'' ?>" <?= $modulo==='perfil'?'aria-current="page"':'' ?> href="<?= e(urlApp('App/conductor/perfil.php')) ?>"><?= iconoMenu('perfil') ?><span>Mi perfil</span></a>
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user"><span class="user-status" aria-hidden="true"></span><div><p>Usuario actual:</p><strong><?= e($u['nombres']) ?></strong><small><?= e($u['rol']) ?></small></div></div>
        <?php if($u['rol']==='admin'): ?><a class="sidebar-app" href="<?= e(urlApp('App/conductor/dashboard.php')) ?>"><?= iconoMenu('app') ?>Ir a la App</a><?php endif ?>
        <?php if($mostrarSalida): ?><a class="sidebar-logout" href="<?= e(urlApp('auth/logout.php')) ?>"><?= iconoMenu('salir') ?>Cerrar Sesión</a><?php endif ?>
    </div>
</aside>
<button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Cerrar menú" hidden></button>
<?php endif ?>
<div class="workspace"><header class="topbar"><div class="topbar-start"><?php if($u['rol']!=='conductor'): ?><button type="button" class="sidebar-toggle" id="sidebarToggle" aria-controls="sidebar" aria-expanded="true" aria-label="Ocultar menú"><span class="hamburger-lines" aria-hidden="true"><i></i><i></i><i></i></span></button><?php endif ?><span><?= $u['rol']==='conductor'?'EJECUTTRANS · CONDUCTOR':'Panel de operación' ?></span></div><div class="user-menu"><a class="user-link" href="<?= e(urlApp('App/conductor/perfil.php')) ?>"><?= e($u['nombres']) ?></a><span class="badge"><?= e($u['rol']) ?></span><?php if($mostrarSalida): ?><a class="logout-link" href="<?= e(urlApp('auth/logout.php')) ?>"><span aria-hidden="true">↪</span> Salir</a><?php endif ?></div></header><main class="content"><div class="page-heading"><div><h1><?= e($titulo) ?></h1></div><div class="page-heading-actions"><span class="date"><?= date('d/m/Y') ?></span><button type="button" class="export-excel" id="exportarExcel" data-modulo="<?= e($modulo) ?>" data-csrf="<?= e(csrf()) ?>"><?= iconoMenu('excel') ?><span>Exportar Excel</span></button><span id="estadoExportacion" class="export-status" role="status" aria-live="polite"></span></div></div>
<?php }
function pie(): void { ?></main><footer>Ejecuttrans · Registro de kilometraje</footer></div></div><script src="<?= e(urlApp('Assets/js/common.js')) ?>"></script><script src="<?= e(urlApp('Assets/js/sidebar.js')) ?>"></script><script src="<?= e(urlApp('Assets/js/exportar_excel.js')) ?>" defer></script></body></html><?php }
