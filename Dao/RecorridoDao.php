<?php
final class RecorridoDao {
    public function __construct(private PDO $db) {}
    public function activo(int $usuario): array|false {
        $s=$this->db->prepare('SELECT * FROM recorrido WHERE conductor_activo=?'); $s->execute([$usuario]); return $s->fetch();
    }
    public function listar(): array {
        return $this->db->query('SELECT id, conductor_nombre, disco, ruta_nombre, inicio, fin, km_inicial, km_final, km_final-km_inicial AS distancia FROM recorrido ORDER BY id DESC LIMIT 500')->fetchAll();
    }
    public function iniciar(int $usuario, int $ruta, int $km, string $evidencia): void {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT * FROM bus WHERE conductor_id=? AND activo=1 FOR UPDATE'); $s->execute([$usuario]); $bus=$s->fetch();
            if (!$bus) throw new DomainException('No tienes un bus habilitado asignado. Contacta a secretaría.');
            $s=$this->db->prepare("SELECT * FROM usuario WHERE id=? AND activo=1 AND rol IN ('conductor','admin') FOR UPDATE"); $s->execute([$usuario]); $u=$s->fetch();
            if (!$u) throw new DomainException('El conductor no está habilitado.');
            if ($this->activo($usuario)) throw new DomainException('Finaliza tu recorrido actual antes de iniciar otro.');
            $s=$this->db->prepare('SELECT * FROM ruta WHERE id=? AND activo=1 FOR UPDATE'); $s->execute([$ruta]); $r=$s->fetch();
            if (!$r) throw new DomainException('Selecciona una ruta habilitada.');
            $s=$this->db->prepare('SELECT km_final FROM recorrido WHERE bus_id=? AND fin IS NOT NULL ORDER BY id DESC LIMIT 1'); $s->execute([$bus['id']]); $anterior=$s->fetchColumn();
            if ($anterior!==false && $km<(int)$anterior) throw new DomainException('El kilometraje inicial no puede ser menor al último registro del bus ('.$anterior.').');
            $s=$this->db->prepare('INSERT INTO recorrido(conductor_id,bus_id,ruta_id,conductor_nombre,disco,ruta_nombre,km_inicial,evidencia_inicial) VALUES (?,?,?,?,?,?,?,?)');
            $s->execute([$usuario,$bus['id'],$ruta,$u['nombres'].' '.$u['apellidos'],$bus['disco'],$r['nombre'],$km,$evidencia]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function finalizar(int $usuario, int $id, int $km, string $evidencia): void {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT * FROM recorrido WHERE id=? AND conductor_id=? AND fin IS NULL FOR UPDATE'); $s->execute([$id,$usuario]); $r=$s->fetch();
            if (!$r) throw new DomainException('No hay un recorrido activo para finalizar. Actualiza la pantalla.');
            if ($km<(int)$r['km_inicial']) throw new DomainException('El kilometraje final debe ser igual o mayor al inicial.');
            $s=$this->db->prepare('UPDATE recorrido SET fin=NOW(), km_final=?, evidencia_final=? WHERE id=?'); $s->execute([$km,$evidencia,$id]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
}
