let recorridos=JSON.parse(document.getElementById('datosRecorridos').textContent);
const formato=new Intl.NumberFormat('es-EC');
function render(){
    const filtro=document.getElementById('filtro').value.toLowerCase(),estado=document.getElementById('estado').value;
    const filas=recorridos.filter(r=>`${r.conductor_nombre} ${r.disco} ${r.ruta_nombre}`.toLowerCase().includes(filtro) && (!estado || (estado==='activo'?!r.fin:!!r.fin)));
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
document.getElementById('filtro').oninput=render;document.getElementById('estado').onchange=render;render();
const stream=new EventSource('/Controllers/RecorridosStreamController.php');
stream.addEventListener('recorridos',e=>{recorridos=JSON.parse(e.data);render();document.getElementById('conexion').textContent='Actualización en vivo';});
stream.onerror=()=>{document.getElementById('conexion').textContent='Reconectando… Los datos pueden estar desactualizados.';};
stream.addEventListener('revocado',()=>{stream.close();location.href='/index.php';});
window.addEventListener('pagehide',()=>stream.close());
