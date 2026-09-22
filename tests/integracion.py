"""Pruebas HTTP con datos propios temporales; requiere Docker Compose activo."""
import concurrent.futures
import http.cookiejar
import json
from pathlib import Path
import re
import subprocess
import urllib.request
import urllib.error
import uuid

BASE='http://localhost:8083'
TAG='TEST-'+uuid.uuid4().hex[:10]

def php(code):
    p=subprocess.run(['docker','compose','exec','-T','web','php'],input='<?php\nrequire "/var/www/html/Config/conexion.php";\n'+code,text=True,capture_output=True,check=True)
    return json.loads(p.stdout)

def cedula(base):
    s=sum((int(x)*(2 if i%2==0 else 1))//10+(int(x)*(2 if i%2==0 else 1))%10 for i,x in enumerate(base))
    return base+str((10-s%10)%10)

class Client:
    def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def get(self,path):
        try:
            r=self.opener.open(BASE+path,timeout=12);return r.status,r.read(),r.headers
        except urllib.error.HTTPError as e:return e.code,e.read(),e.headers
    def post(self,path,fields,file=None):
        boundary='----'+uuid.uuid4().hex;body=b''
        for k,v in fields.items():body+=f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
        if file:
            name,mime,data=file
            body+=f'--{boundary}\r\nContent-Disposition: form-data; name="evidencia"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+data+b'\r\n'
        body+=f'--{boundary}--\r\n'.encode()
        req=urllib.request.Request(BASE+path,data=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary})
        try:r=self.opener.open(req,timeout=15)
        except urllib.error.HTTPError as e:r=e
        return r.code,json.loads(r.read())
    def login(self,c):
        status,data=self.post('/Controllers/AuthController.php',{'cedula':c,'password':'Test.2026*'})
        assert status==200,data
    def token(self,path):
        status,body,_=self.get(path);assert status==200,(path,status)
        return re.search(r'name="csrf" value="([a-f0-9]+)"',body.decode()).group(1)

ids=[];ruta_id=None;bus_id=None;socio_id=None
try:
    cs=[cedula('09'+str(int(uuid.uuid4().hex[:8],16)%10000000).zfill(7)) for _ in range(4)]
    # Tercer dígito debe ser menor a 6.
    cs=[cedula(c[:2]+'1'+c[3:9]) for c in cs]
    payload=json.dumps([[c,rol] for c,rol in zip(cs,['admin','secretaria','conductor','conductor'])])
    ids=php('$datos=json_decode('+json.dumps(payload)+',true);$ids=[];foreach($datos as [$c,$r]){$s=$conexion->prepare("INSERT INTO usuario(cedula,nombres,apellidos,rol,password_hash) VALUES (?,?,?,?,?)");$s->execute([$c,"'+TAG+'","Temporal",$r,password_hash("Test.2026*",PASSWORD_DEFAULT)]);$ids[]=(int)$conexion->lastInsertId();}echo json_encode($ids);')
    admin,secretaria,driver,other=[Client() for _ in range(4)]
    for client,c in zip([admin,secretaria,driver,other],cs):client.login(c)
    token=admin.token('/Web/admin/usuarios.php')
    stoken=secretaria.token('/Web/admin/rutas.php')
    dtoken=driver.token('/App/conductor/dashboard.php')
    for path in ['/Web/admin/usuarios.php','/Web/admin/buses.php','/Web/admin/socios.php','/Web/admin/rutas.php','/Web/admin/dashboard.php']:
        assert driver.get(path)[0]==403,path
        assert admin.get(path)[0]==200,path
    assert secretaria.get('/Web/admin/usuarios.php')[0]==403
    assert secretaria.get('/App/conductor/dashboard.php')[0]==403
    assert secretaria.post('/Controllers/CatalogoController.php',{'csrf':stoken,'modulo':'usuarios'})[0]==403
    for path in ['/storage/evidencias/fake.jpg','/database/schema.sql','/Config/conexion.php','/.git/config']:
        assert Client().get(path)[0]==403,path
    print('OK: permisos y protección de archivos')
    def save(client,csrf,modulo,**fields):return client.post('/Controllers/CatalogoController.php',dict(csrf=csrf,modulo=modulo,activo='1',**fields))
    status,data=save(secretaria,stoken,'rutas',nombre=TAG,descripcion='Temporal');assert status==200,data
    ruta_id=php('$s=$conexion->prepare("SELECT id FROM ruta WHERE nombre=?");$s->execute(["'+TAG+'"]);echo json_encode((int)$s->fetchColumn());')
    # Búsqueda AJAX: límite, filtrado, rutas deshabilitadas y permisos.
    php('$s=$conexion->prepare("INSERT INTO ruta(nombre,descripcion,activo) VALUES (?,?,?)");for($i=0;$i<7;$i++)$s->execute(["'+TAG+' buscar ".$i,"'+TAG+'",$i===6?0:1]);echo "true";')
    status,body,_=driver.get('/Controllers/RutaController.php?q='+TAG)
    assert status==200 and len(json.loads(body)['rutas'])==5
    status,body,_=driver.get('/Controllers/RutaController.php?q='+TAG+'%20buscar%206')
    assert status==200 and json.loads(body)['rutas']==[]
    status,body,_=driver.get('/Controllers/RutaController.php?q='+TAG+'%20buscar%202')
    assert status==200 and len(json.loads(body)['rutas'])==1
    assert Client().get('/Controllers/RutaController.php')[0]==401
    assert secretaria.get('/Controllers/RutaController.php')[0]==403
    assert driver.get('/Controllers/RutaController.php?q[]=x')[0]==422
    print('OK: búsqueda AJAX limitada a 5 rutas habilitadas')
    status,data=save(admin,token,'socios',cedula=cs[0],nombres=TAG,telefono='');assert status==200,data
    socio_id=php('$s=$conexion->prepare("SELECT id FROM socio WHERE nombres=?");$s->execute(["'+TAG+'"]);echo json_encode((int)$s->fetchColumn());')
    disco=str(int(uuid.uuid4().hex[:8],16))
    status,data=save(secretaria,stoken,'buses',disco=disco,placa=TAG,socio_id=socio_id,conductor_id=ids[2]);assert status==200,data
    bus_id=php('$s=$conexion->prepare("SELECT id FROM bus WHERE placa=?");$s->execute(["'+TAG+'"]);echo json_encode((int)$s->fetchColumn());')
    image=('foto.png','image/png',Path('Assets/icons/icon-512x512.png').read_bytes())
    fields={'csrf':dtoken,'accion':'iniciar','ruta_id':ruta_id,'kilometraje':'10000'}
    endpoint='/Controllers/RecorridoController.php'
    result=driver.post(endpoint,dict(fields,csrf='bad'),image); assert result[0]==403,result
    assert driver.post(endpoint,dict(fields,kilometraje='10e3'),image)[0]==422
    assert driver.post(endpoint,fields)[0]==422
    assert driver.post(endpoint,fields,('fake.jpg','image/jpeg',b'<?php echo 1;'))[0]==422
    # Dos sesiones del mismo conductor compiten por iniciar.
    second=Client();second.login(cs[2]);token2=second.token('/App/conductor/dashboard.php')
    with concurrent.futures.ThreadPoolExecutor() as pool:
        results=list(pool.map(lambda pair:pair[0].post(endpoint,dict(fields,csrf=pair[1]),image),[(driver,dtoken),(second,token2)]))
    assert sorted(r[0] for r in results)==[200,422],results
    row=php('$s=$conexion->prepare("SELECT * FROM recorrido WHERE conductor_id=?");$s->execute(['+str(ids[2])+']);echo json_encode($s->fetch());')
    rid=row['id'];assert row['km_inicial']==10000 and row['fin'] is None
    pagina=driver.get('/App/conductor/dashboard.php')[1].decode()
    assert re.search(r'id="iniciarRuta"[^>]*disabled',pagina)
    assert not re.search(r'id="finalizarRuta"[^>]*disabled',pagina)
    assert '¿Finalizar esta ruta?' in pagina
    assert driver.get('/Controllers/EvidenciaController.php?id='+str(rid))[0]==200
    assert other.get('/Controllers/EvidenciaController.php?id='+str(rid))[0]==404
    assert admin.get('/Controllers/EvidenciaController.php?id='+str(rid))[2]['Content-Type']=='image/jpeg'
    status,data=save(secretaria,stoken,'buses',id=bus_id,disco=disco,placa=TAG,socio_id=socio_id,conductor_id=ids[3]);assert status==422,data
    print('OK: catálogos, evidencias, CSRF y exclusión de recorridos simultáneos')
    # El evento SSE debe contener el inicio, sin esperar al cierre de conexión.
    with admin.opener.open(BASE+'/Controllers/RecorridosStreamController.php',timeout=8) as stream:
        lines=[stream.readline(),stream.readline()]
        assert b'event: recorridos' in lines[0] and TAG.encode() in lines[1],lines
    final={'csrf':dtoken,'accion':'finalizar','recorrido_id':rid,'kilometraje':'9999'}
    assert driver.post(endpoint,final,image)[0]==422
    otoken=other.token('/App/conductor/dashboard.php')
    assert other.post(endpoint,dict(final,csrf=otoken,kilometraje='12000'),image)[0]==422
    # PDF mínimo válido para verificar la segunda vía de carga.
    pdf=('evidencia.pdf','application/pdf',b'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF')
    status,data=driver.post(endpoint,dict(final,kilometraje='12000'),pdf);assert status==200,data
    assert driver.post(endpoint,dict(final,kilometraje='12000'),pdf)[0]==422
    row=php('$s=$conexion->prepare("SELECT * FROM recorrido WHERE id=?");$s->execute(['+str(rid)+']);echo json_encode($s->fetch());')
    assert row['km_final']-row['km_inicial']==2000 and row['fin']
    pagina=driver.get('/App/conductor/dashboard.php')[1].decode()
    assert not re.search(r'id="iniciarRuta"[^>]*disabled',pagina)
    assert re.search(r'id="finalizarRuta"[^>]*disabled',pagina)
    assert admin.get('/Controllers/EvidenciaController.php?id='+str(rid)+'&tipo=final')[2]['Content-Type']=='application/pdf'
    assert driver.post(endpoint,fields,image)[0]==422
    status,data=save(secretaria,stoken,'rutas',id=ruta_id,nombre=TAG+' editada',descripcion='');assert status==200,data
    snapshot=php('$s=$conexion->prepare("SELECT ruta_nombre FROM recorrido WHERE id=?");$s->execute(['+str(rid)+']);echo json_encode($s->fetchColumn());')
    assert snapshot==TAG
    status,data=save(secretaria,stoken,'buses',id=bus_id,disco=disco,placa=TAG,socio_id=socio_id,conductor_id=ids[3]);assert status==200,data
    print('OK: SSE, cierre, kilometraje, PDF, historial y reasignación')
    print('TODAS LAS PRUEBAS PASARON')
finally:
    if ids:
        # Eliminar exclusivamente filas y evidencias de esta ejecución.
        php('$s=$conexion->prepare("DELETE FROM ruta WHERE descripcion=?");$s->execute(["'+TAG+'"]);echo "true";')
        sqlids=','.join(map(str,ids))
        php('$ids="'+sqlids+'";$files=$conexion->query("SELECT evidencia_inicial,evidencia_final FROM recorrido WHERE conductor_id IN ($ids)")->fetchAll();$conexion->exec("DELETE FROM recorrido WHERE conductor_id IN ($ids)");$conexion->exec("DELETE FROM bus WHERE conductor_id IN ($ids) OR placa=\''+TAG+'\'");$conexion->exec("DELETE FROM usuario WHERE id IN ($ids)");$conexion->exec("DELETE FROM socio WHERE nombres=\''+TAG+'\'");$conexion->exec("DELETE FROM ruta WHERE nombre IN (\''+TAG+'\',\''+TAG+' editada\')");foreach($files as $f)foreach([$f["evidencia_inicial"],$f["evidencia_final"]] as $file)if($file)@unlink("/var/www/html/storage/evidencias/".$file);echo "true";')
