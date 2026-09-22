<?php
final class RutaDao {
    public function __construct(private PDO $db) {}

    public function buscarHabilitadas(string $termino): array {
        $consulta = $this->db->prepare(
            'SELECT id, nombre, descripcion FROM ruta
             WHERE activo = 1 AND LOCATE(?, nombre) > 0
             ORDER BY (LOCATE(?, nombre) = 1) DESC, nombre, id LIMIT 5'
        );
        $consulta->execute([$termino, $termino]);
        return $consulta->fetchAll();
    }
}
