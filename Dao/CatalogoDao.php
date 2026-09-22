<?php
final class CatalogoDao {
    public const TABLAS=['usuarios'=>'usuario','buses'=>'bus','rutas'=>'ruta'];
    public function __construct(private PDO $db) {}
    public function listar(string $modulo): array {
        if ($modulo==='buses') return $this->db->query("SELECT b.*, CONCAT(u.nombres,' ',u.apellidos) AS conductor_nombre FROM bus b LEFT JOIN usuario u ON u.id=b.conductor_id ORDER BY b.id DESC")->fetchAll();
        $tabla=self::TABLAS[$modulo];
        $columnas=$modulo==='usuarios' ? 'id,cedula,nombres,apellidos,rol,activo' : '*';
        return $this->db->query("SELECT $columnas FROM $tabla ORDER BY id DESC")->fetchAll();
    }
    public function opciones(): array {
        return ['conductores'=>$this->db->query("SELECT id,CONCAT(nombres,' ',apellidos) AS nombre FROM usuario WHERE activo=1 AND rol='conductor' ORDER BY nombres")->fetchAll()];
    }
    public function buscarConductores(string $texto, int $busId): array {
        $patron='%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto).'%';
        $s=$this->db->prepare("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) AS nombre, u.cedula
            FROM usuario u WHERE u.activo=1 AND u.rol='conductor'
            AND (CONCAT(u.nombres,' ',u.apellidos) LIKE ? ESCAPE '!' OR u.cedula LIKE ? ESCAPE '!')
            AND NOT EXISTS (SELECT 1 FROM bus b WHERE b.conductor_id=u.id AND b.id<>?)
            ORDER BY u.nombres,u.apellidos,u.id LIMIT 20");
        $s->execute([$patron,$patron,$busId]);
        return $s->fetchAll();
    }
    private function texto(string $key,int $max,bool $obligatorio=true): string {
        $v=$_POST[$key] ?? '';
        if (!is_string($v)) throw new DomainException('Dato inválido: '.$key);
        $v=trim($v);
        if (($obligatorio && $v==='') || strlen($v)>$max) throw new DomainException('Revisa el campo '.$key.'.');
        return $v;
    }
    public function guardar(string $modulo, int $actor): void {
        $tabla=self::TABLAS[$modulo]; $id=(int)($_POST['id'] ?? 0);
        $this->db->beginTransaction();
        try {
            $actual=null;
            if ($id) {
                $s=$this->db->prepare("SELECT * FROM $tabla WHERE id=? FOR UPDATE"); $s->execute([$id]); $actual=$s->fetch();
                if (!$actual) throw new DomainException('El registro ya no existe.');
            }
            $activo=($_POST['activo'] ?? '')==='1' ? 1:0;
            if ($modulo==='usuarios') {
                if ($id===$actor && (!$activo || ($_POST['rol'] ?? '')!=='admin')) throw new DomainException('No puedes deshabilitar tu propio administrador ni cambiar su rol.');
                $cedula=$this->texto('cedula',10);
                if (!validarCedulaEcuatoriana($cedula)) throw new DomainException('Ingresa una cédula ecuatoriana válida.');
                $rol=$this->texto('rol',20);
                if (!in_array($rol,['admin','secretaria','conductor'],true)) throw new DomainException('Rol inválido.');
                if ($id && (!$activo || $rol!==$actual['rol'])) {
                    $s=$this->db->prepare('SELECT id FROM recorrido WHERE conductor_activo=?'); $s->execute([$id]);
                    if ($s->fetch()) throw new DomainException('El usuario debe finalizar su recorrido antes de cambiar su acceso.');
                    $s=$this->db->prepare('SELECT id FROM bus WHERE conductor_id=?'); $s->execute([$id]);
                    if ($s->fetch()) throw new DomainException('Retira primero la asignación del conductor en Buses.');
                }
                $data=['cedula'=>$cedula,'nombres'=>$this->texto('nombres',100),'apellidos'=>$this->texto('apellidos',100),'rol'=>$rol,'activo'=>$activo];
                $clave=$this->texto('password',72,false);
                if (!$id && $clave==='') $clave=$cedula;
                if ($clave!=='') {
                    if (strlen($clave)<8) throw new DomainException('La contraseña debe tener al menos 8 caracteres.');
                    $data['password_hash']=password_hash($clave,PASSWORD_DEFAULT);
                }
            } elseif ($modulo==='rutas') {
                $data=['nombre'=>$this->texto('nombre',120),'descripcion'=>$this->texto('descripcion',500,false),'activo'=>$activo];
            } else {
                $conductor=(int)($_POST['conductor_id'] ?? 0) ?: null;
                if ($id) {
                    $s=$this->db->prepare('SELECT id FROM recorrido WHERE bus_activo=?'); $s->execute([$id]);
                    if ($s->fetch()) throw new DomainException('No se puede modificar un bus con un recorrido activo.');
                }
                if ($conductor) {
                    $s=$this->db->prepare("SELECT id FROM usuario WHERE id=? AND activo=1 AND rol='conductor' FOR UPDATE"); $s->execute([$conductor]);
                    if (!$s->fetch()) throw new DomainException('Selecciona un conductor habilitado.');
                }
                $disco=$this->texto('disco',20);
                if (!ctype_digit($disco)) throw new DomainException('El disco debe contener solo números.');
                $disco=str_pad(ltrim($disco,'0') ?: '0',3,'0',STR_PAD_LEFT);
                $data=['disco'=>$disco,'placa'=>strtoupper($this->texto('placa',20,false)) ?: null,'conductor_id'=>$conductor,'activo'=>$activo];
            }
            if ($id) {
                $campos=implode(',',array_map(fn($k)=>"$k=?",array_keys($data))); $valores=array_values($data); $valores[]=$id;
                $s=$this->db->prepare("UPDATE $tabla SET $campos WHERE id=?"); $s->execute($valores);
            } else {
                $campos=implode(',',array_keys($data)); $marcas=implode(',',array_fill(0,count($data),'?'));
                $s=$this->db->prepare("INSERT INTO $tabla ($campos) VALUES ($marcas)"); $s->execute(array_values($data));
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            if ($e instanceof PDOException && ($e->errorInfo[1] ?? 0)===1062) throw new DomainException('Ya existe ese registro o el conductor está asignado a otro bus.');
            throw $e;
        }
    }
}
