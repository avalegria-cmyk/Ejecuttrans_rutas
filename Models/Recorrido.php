<?php
final class Recorrido {
    public static function kilometraje(mixed $valor): int {
        if (!is_string($valor) || !preg_match('/^[0-9]{1,9}$/D', $valor)) throw new DomainException('El kilometraje debe contener solo números enteros (hasta 9 dígitos).');
        return (int)$valor;
    }
}
