<?php
// Inicialización de sesión, conexión y funciones compartidas.
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/rutas.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function usuarioActual(): ?array {
    global $conexion;
    $s = $conexion->prepare('SELECT id, cedula, nombres, apellidos, rol, activo FROM usuario WHERE id=? AND activo=1');
    $s->execute([$_SESSION['usuario_id'] ?? 0]); return $s->fetch() ?: null;
}
function exigirAcceso(array $roles, bool $api = false): array {
    $u = usuarioActual();
    if (!$u || !in_array($u['rol'], $roles, true)) {
        if ($api) jsonResponse(['status'=>'error', 'message'=>'Acceso denegado.'], $u ? 403 : 401);
        if (!$u) { header('Location: '.urlApp('index.php')); exit; }
        http_response_code(403); exit('Acceso denegado.');
    }
    return $u;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function exigirPost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['status'=>'error','message'=>'Método no permitido.'],405);
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) jsonResponse(['status'=>'error','message'=>'Sesión vencida. Recarga la página.'],403);
}
function fallo(Throwable $err): never {
    if ($err instanceof DomainException) jsonResponse(['status'=>'error','message'=>$err->getMessage()],422);
    error_log((string)$err);
    jsonResponse(['status'=>'error','message'=>'No se pudo guardar. Revisa los datos o intenta nuevamente.'],500);
}
