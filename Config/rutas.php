<?php
function obtenerRutaInicio(string $rol): ?string {
    return match ($rol) {
        'admin', 'secretaria' => 'Web/admin/dashboard.php',
        'conductor' => 'App/conductor/dashboard.php',
        default => null,
    };
}
