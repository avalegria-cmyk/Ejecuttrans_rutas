<?php
require_once __DIR__.'/../Config/rutas.php';

$casos = [
    '/index.php' => '/',
    '/proyecto/index.php' => '/proyecto/',
    '/proyecto/Web/admin/dashboard.php' => '/proyecto/',
    '/proyecto/App/conductor/perfil.php' => '/proyecto/',
    '/proyecto/Controllers/ExportarController.php' => '/proyecto/',
    '/proyecto/auth/logout.php' => '/proyecto/',
];
foreach ($casos as $script => $esperada) {
    $_SERVER['SCRIPT_NAME'] = $script;
    if (baseApp() !== $esperada || urlApp('Assets/css/tailwind.css') !== $esperada.'Assets/css/tailwind.css') {
        throw new RuntimeException('Ruta incorrecta para '.$script);
    }
}
echo "OK: rutas del sitio en raíz y subcarpeta\n";
