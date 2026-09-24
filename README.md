# Ejecuttrans · Control de recorridos

Aplicación PHP 8.3, MySQL 8.4, JavaScript y Tailwind, organizada en MVC con DAO. Sustituye los módulos de minutos, pagos, turnos y QR por control de rutas y kilometraje.

## Iniciar

```sh
docker compose up -d --build
```

- Aplicación: http://localhost:8083
- phpMyAdmin: http://localhost:8084 (servidor `db`, usuario `recorridos`; la contraseña se configura con `DB_PASSWORD`).
- MySQL y evidencias persisten en los volúmenes de este proyecto: `sistema_recorridos_recorridos_db` y `sistema_recorridos_evidencias`.
- Los contenedores y volúmenes del sistema anterior no se modifican.
- Esta instalación se construyó aprovechando la imagen PHP 8.3.33 local con `docker compose build --build-arg PHP_BASE=sistema_minutos-web:latest web`. La construcción predeterminada usa la imagen oficial `php:8.3.33-apache`.
- `database/schema.sql` reúne la creación de `sistema_recorridos`, sus tablas, las rutas de ejemplo y la carga inicial de 7 usuarios y 88 buses del respaldo de Minutos del 22/09/2026. Se ejecuta automáticamente al inicializar MySQL con un volumen vacío, o puede importarse completo manualmente para una instalación nueva. No elimina bases existentes ni actualiza una instalación que ya tenga las tablas.
- Los 2 administradores y la cuenta de secretaría conservan sus roles; los otros 4 usuarios quedan como conductores. Se mantienen los identificadores y estados. La clave inicial es la cédula, almacenada como hash bcrypt. Los buses quedan sin conductor fijo porque el respaldo no contiene asignaciones.

Esta copia local de `schema.sql` contiene cédulas, nombres y hashes de contraseña procedentes del respaldo de Minutos. `scripts/crear_demo.php` queda disponible solo para pruebas opcionales y puede agregar cuentas y una unidad de demostración. Las variables `DB_PASSWORD` y `MYSQL_ROOT_PASSWORD` se pueden definir en `.env` antes de inicializar la base.

## Uso y permisos

- Administrador: dashboard, recorridos, buses, rutas, usuarios y acceso a la vista de la app.
- Secretaría: dashboard, recorridos, buses y rutas. Sin acceso a usuarios, incluso mediante llamadas directas al controlador.
- Conductor: perfil, contraseña e inicio/finalización de su recorrido. Sin acceso a administración.
- Cada bus puede tener un conductor fijo y cada conductor un solo bus asignado.
- En Buses se asigna o cambia el conductor. Un bus con recorrido activo no puede editarse. Para trasladar un conductor, retíralo primero del bus anterior.
- Los registros se deshabilitan, conservando las relaciones y el historial.

La app conserva el menú de dos tarjetas del sistema de minutos, sin reloj. Para iniciar: confirmar la intención, buscar o seleccionar una ruta habilitada (AJAX, hasta 5 coincidencias), introducir kilometraje entero y adjuntar evidencia. Escribir filtra el catálogo; se debe seleccionar una coincidencia y no se crean rutas desde la app. Para finalizar: confirmar la intención e introducir kilometraje final y evidencia. Ambas tarjetas permanecen visibles: Iniciar ruta se deshabilita durante el recorrido y Finalizar ruta se habilita únicamente mientras existe un recorrido activo. Los botones dependen del estado persistido. La base también impide recorridos simultáneos para un mismo conductor o bus. El kilometraje final no puede ser menor al inicial; el nuevo inicial no puede ser menor al último final del bus.

Las horas se registran en el servidor en UTC−5. Los nombres de conductor, ruta y disco se guardan en el recorrido para preservar el historial. Los módulos administrativos incluyen búsqueda y filtros combinables. Recorridos muestra los últimos 500 registros, permite filtrar por estado, ruta, disco y fechas, y recibe cambios con Server-Sent Events; reconecta automáticamente.

El Dashboard resume recorridos de hoy, viajes en curso, kilómetros finalizados hoy, buses, rutas y actividad reciente. El menú lateral se puede ocultar con el botón de hamburguesa; en móviles se abre como un panel superpuesto. La elección de escritorio se conserva en este navegador.

Las evidencias aceptan JPG, PNG, WEBP o PDF de hasta 10 MB. Las imágenes se reducen a 1600 píxeles y se guardan como JPEG; los PDF se conservan. Los archivos se sirven exclusivamente desde un controlador con autorización. Las imágenes de más de 24 megapíxeles se rechazan en el servidor para limitar memoria.

La app tiene manifest y service worker, pero necesita Internet para registrar recorridos. No almacena páginas autenticadas en caché. La instalación como PWA fuera de localhost requiere HTTPS. Los estilos de Tailwind se compilan en `Assets/css/tailwind.css`, que se entrega junto con el código; el servidor no necesita Node ni depender del CDN. Las vistas conservan sus estilos locales para componentes propios. Para regenerar Tailwind durante el desarrollo: `npm ci` y `npm run build:css`.

La aplicación puede servirse desde la raíz del dominio o una subcarpeta: los enlaces internos, los recursos estáticos y las llamadas AJAX utilizan la ruta base detectada del sitio. En un hosting compartido que no use Docker, configura PHP 8.3+, MySQL 8.4+ y una base de datos vacía antes de importar `database/schema.sql`. La configuración de Apache incluida en Docker bloquea el acceso web a `Config/`, `Dao/`, `Models/`, `database/`, `scripts/`, `tests/` y `storage/`; aplica restricciones equivalentes en el host antes de publicar.

## Estructura

- `Controllers/`: autenticación, permisos de cada acción, catálogos, recorridos, evidencias y SSE.
- `Models/`: validación de kilometraje y procesamiento de evidencias.
- `Dao/`: consultas y transacciones de recorridos y catálogos.
- `Web/admin/`: vistas administrativas.
- `App/conductor/`: vistas de recorrido y perfil.
- `Config/`: conexión, sesión, rutas y presentación compartida.
- `Assets/`: JavaScript, estilos e identidad visual.
- `database/`: esquema nuevo sin tablas de pagos ni turnos.

Este proyecto está separado en `Sistema_rutas`. El proyecto original `../Sistema_minutos/` fue restaurado junto con sus comprobantes y datos locales. El respaldo adicional se conserva en `../Sistema_minutos_archivos_anteriores_20260922/`. Minutos mantiene sus puertos 8082 (web) y 8081 (phpMyAdmin); Rutas usa 8083 y 8084. Ambos conservan sus propios volúmenes de base de datos.

## Verificar

```sh
docker compose exec -T web sh -c 'find Config Controllers Dao Models Web App scripts -name "*.php" -exec php -l {} \;'
python3 tests/integracion.py
```

Las pruebas HTTP crean usuarios, catálogos y recorridos temporales, y limpian únicamente sus propios datos. Verifican permisos, CSRF, concurrencia, imágenes/PDF, SSE y cierre de recorridos.
