<?php
declare(strict_types=1);
date_default_timezone_set('America/Guayaquil');
$conexion = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';dbname=' . (getenv('DB_NAME') ?: 'sistema_recorridos') . ';charset=utf8mb4', getenv('DB_USER') ?: 'recorridos', getenv('DB_PASSWORD') ?: 'recorridos.local.2026', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-05:00'"]);
