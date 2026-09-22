let recorridos=JSON.parse(document.getElementById('datosRecorridos').textContent);
const formato=new Intl.NumberFormat('es-EC');
function actualizarOpciones(id,valores,etiqueta){
    const select=document.getElementById(id),seleccion=select.value;
    const opciones=[...new Set(valores.filter(Boolean))].sort((a,b)=>String(a).localeCompare(String(b),'es',{numeric:true}));
    select.replaceChildren(new Option(etiqueta,''),...opciones.map(valor=>new Option(valor,valor)));
    if(opciones.includes(seleccion))select.value=seleccion;
}
function render(){
    actualizarOpciones('ruta',recorridos.map(r=>r.ruta_nombre),'Todas las rutas');
    actualizarOpciones('disco',recorridos.map(r=>r.disco),'Todos los discos');
    const filtro=document.getElementById('filtro').value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,''),estado=document.getElementById('estado').value;
    const ruta=document.getElementById('ruta').value,disco=document.getElementById('disco').value;
    const desde=document.getElementById('desde').value,hasta=document.getElementById('hasta').value;
    const filas=recorridos.filter(r=>{
        const texto=`${r.conductor_nombre} ${r.disco} ${r.ruta_nombre}`.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        const fecha=String(r.inicio).slice(0,10);
        return texto.includes(filtro) && (!estado || (estado==='activo'?!r.fin:!!r.fin)) && (!ruta || r.ruta_nombre===ruta) && (!disco || String(r.disco)===disco) && (!desde || fecha>=desde) && (!hasta || fecha<=hasta);
    });
    document.getElementById('total').textContent=filas.length;
    document.getElementById('activos').textContent=filas.filter(r=>!r.fin).length;
    document.getElementById('kilometros').textContent=formato.format(filas.reduce((s,r)=>s+Number(r.distancia || 0),0));
    const cuerpo=document.getElementById('recorridos'); cuerpo.replaceChildren();
    const evidencia=(id,tipo)=>{ const a=document.createElement('a'); a.href=`/Controllers/EvidenciaController.php?id=${id}&tipo=${tipo}`;a.target='_blank';a.rel='noopener';a.textContent='Ver archivo ↗';return a;};
    for(const r of filas){
        const fila=document.createElement('tr');
        const badge=document.createElement('span');badge.className=`badge ${r.fin?'green':'amber'}`;badge.textContent=r.fin?'Finalizado':'En curso';
        const valores=[r.conductor_nombre,r.disco,r.ruta_nombre,r.inicio,formato.format(r.km_inicial),evidencia(r.id,'inicial'),r.fin || 'En espera',r.km_final===null?'—':formato.format(r.km_final),r.fin?evidencia(r.id,'final'):'—',r.distancia===null?'—':formato.format(r.distancia)+' km',badge];
        for(const valor of valores){const td=document.createElement('td');if(valor instanceof Node)td.append(valor);else td.textContent=valor;fila.append(td);} cuerpo.append(fila);
    }
    document.getElementById('vacio').hidden=filas.length>0;
}
document.querySelectorAll('.filtro-recorrido').forEach(control=>control.addEventListener(control.tagName==='SELECT'?'change':'input',render));
document.getElementById('limpiarFiltros').onclick=()=>{document.querySelectorAll('.filtro-recorrido').forEach(control=>control.value='');render();};
render();
const stream=new EventSource('/Controllers/RecorridosStreamController.php');
stream.addEventListener('recorridos',e=>{recorridos=JSON.parse(e.data);render();document.getElementById('conexion').textContent='Actualización en vivo';});
stream.onerror=()=>{document.getElementById('conexion').textContent='Reconectando… Los datos pueden estar desactualizados.';};
stream.addEventListener('revocado',()=>{stream.close();location.href='/index.php';});
window.addEventListener('pagehide',()=>stream.close());
