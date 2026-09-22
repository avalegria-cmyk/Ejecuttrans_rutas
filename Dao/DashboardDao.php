<?php
final class DashboardDao {
    public function __construct(private PDO $db) {}

    private function numero(string $sql): int {
        return (int)$this->db->query($sql)->fetchColumn();
    }

    public function obtener(): array {
        return [
            'recorridos_hoy'=>$this->numero('SELECT COUNT(*) FROM recorrido WHERE inicio >= CURDATE() AND inicio < CURDATE() + INTERVAL 1 DAY'),
            'finalizados_hoy'=>$this->numero('SELECT COUNT(*) FROM recorrido WHERE fin >= CURDATE() AND fin < CURDATE() + INTERVAL 1 DAY'),
            'en_curso'=>$this->numero('SELECT COUNT(*) FROM recorrido WHERE fin IS NULL'),
            'buses_total'=>$this->numero('SELECT COUNT(*) FROM bus'),
            'buses_activos'=>$this->numero('SELECT COUNT(*) FROM bus WHERE activo=1'),
            'buses_asignados'=>$this->numero('SELECT COUNT(*) FROM bus WHERE activo=1 AND conductor_id IS NOT NULL'),
            'buses_sin_conductor'=>$this->numero('SELECT COUNT(*) FROM bus WHERE activo=1 AND conductor_id IS NULL'),
            'rutas_activas'=>$this->numero('SELECT COUNT(*) FROM ruta WHERE activo=1'),
            'usuarios_activos'=>$this->numero('SELECT COUNT(*) FROM usuario WHERE activo=1'),
            'recientes'=>$this->db->query('SELECT conductor_nombre,disco,ruta_nombre,inicio,fin FROM recorrido ORDER BY id DESC LIMIT 6')->fetchAll(),
        ];
    }
}
