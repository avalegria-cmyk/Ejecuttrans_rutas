<?php
function baseApp(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    if (preg_match('~^(.*?)/(?:Web/admin|App/conductor|Controllers|auth)/[^/]+\.php$~', $script, $ruta)) {
        return rtrim($ruta[1], '/') . '/';
    }
    return rtrim(dirname($script), '/') . '/';
}
function urlApp(string $ruta): string { return baseApp() . ltrim($ruta, '/'); }
function obtenerRutaInicio(string $rol): ?string {
    return match ($rol) {
        'admin', 'secretaria' => 'Web/admin/dashboard.php',
        'conductor' => 'App/conductor/dashboard.php',
        default => null,
    };
}
