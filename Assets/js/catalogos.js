const registros=JSON.parse(document.getElementById('datosCatalogo').textContent);
const editor=document.getElementById('editor'),form=document.getElementById('formCatalogo'),mensaje=document.getElementById('mensaje');
function abrirEditor(registro={}) {
    form.reset(); mensaje.textContent=''; form.elements.id.value=registro.id || '';
    for (const [k,v] of Object.entries(registro)) if(form.elements[k]) form.elements[k].value=v ?? '';
    reiniciarConductor(registro);
    document.getElementById('tituloEditor').textContent=registro.id?'Editar registro':'Nuevo registro'; editor.showModal();
}
document.getElementById('nuevo').onclick=()=>abrirEditor();
document.getElementById('cancelar').onclick=()=>editor.close();
document.querySelectorAll('.editar').forEach(b=>b.onclick=()=>abrirEditor(registros.find(r=>String(r.id)===b.dataset.id)));
const normalizar=valor=>valor.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
const paginacionCatalogo=document.getElementById('paginacionCatalogo');
const limiteCatalogo=20;
let paginaCatalogo=1;
function aplicarFiltros(reiniciarPagina=true){
    if (reiniciarPagina) paginaCatalogo=1;
    const texto=normalizar(document.getElementById('buscar').value);
    const estado=document.getElementById('filtroEstado').value;
    const rol=document.getElementById('filtroRol')?.value || '';
    const asignacion=document.getElementById('filtroAsignacion')?.value || '';
    const filas=[...document.querySelectorAll('#registros tr')];
    const filtradas=filas.filter(fila=>{
        const coincideTexto=!texto || normalizar(fila.textContent).includes(texto);
        const coincideEstado=!estado || fila.dataset.activo===estado;
        const coincideRol=!rol || fila.dataset.rol===rol;
        const coincideAsignacion=!asignacion || fila.dataset.conductor===asignacion;
        const coincide=coincideTexto && coincideEstado && coincideRol && coincideAsignacion;
        fila.dataset.coincideFiltro=String(coincide);
        return coincide;
    });
    const total=filtradas.length;
    const totalPaginas=Math.max(1,Math.ceil(total/limiteCatalogo));
    paginaCatalogo=Math.max(1,Math.min(paginaCatalogo,totalPaginas));
    const inicio=paginacionCatalogo?(paginaCatalogo-1)*limiteCatalogo:0;
    const fin=paginacionCatalogo?Math.min(inicio+limiteCatalogo,total):total;
    filas.forEach(fila=>{fila.hidden=true;});
    filtradas.slice(inicio,fin).forEach(fila=>{fila.hidden=false;});
    document.getElementById('conteoFiltrado').textContent=total;
    const vacio=document.getElementById('sinResultados');
    vacio.textContent=registros.length?'No hay registros que coincidan con los filtros.':'No hay registros para mostrar.';
    vacio.hidden=total>0;
    if (paginacionCatalogo) {
        document.getElementById('resumenPaginaCatalogo').textContent=`Mostrando ${total?inicio+1:0}–${fin} de ${total} ${paginacionCatalogo.dataset.entidad}`;
        document.getElementById('paginaCatalogoActual').textContent=`Página ${paginaCatalogo} de ${totalPaginas}`;
        document.getElementById('paginaCatalogoAnterior').disabled=paginaCatalogo===1;
        document.getElementById('paginaCatalogoSiguiente').disabled=paginaCatalogo===totalPaginas;
    }
}
if (paginacionCatalogo) {
    document.getElementById('paginaCatalogoAnterior').addEventListener('click',()=>{
        paginaCatalogo--; aplicarFiltros(false);
    });
    document.getElementById('paginaCatalogoSiguiente').addEventListener('click',()=>{
        paginaCatalogo++; aplicarFiltros(false);
    });
}
document.querySelectorAll('#buscar,#filtroEstado,#filtroRol,#filtroAsignacion').forEach(control=>{
    if(control) control.addEventListener(control.tagName==='INPUT'?'input':'change',aplicarFiltros);
});
document.getElementById('limpiarFiltros').onclick=()=>{
    for(const id of ['buscar','filtroEstado','filtroRol','filtroAsignacion']){const control=document.getElementById(id);if(control)control.value='';}
    aplicarFiltros();
};
aplicarFiltros();
form.onsubmit=async e=>{
    e.preventDefault();
    if (campoConductor && campoConductor.value.trim() && !conductorSeleccionado.value) {
        mensaje.textContent='Selecciona un conductor de los resultados o vacía el campo para dejar el bus sin asignar.';
        campoConductor.focus(); return;
    }
    const button=document.getElementById('guardar'); button.disabled=true; mensaje.textContent='';
    try { await postForm('/Controllers/CatalogoController.php',new FormData(form)); location.reload(); }
    catch(error){mensaje.textContent=error.message;} finally{button.disabled=false;}
};

const campoConductor=document.getElementById('campo_conductor_id');
const conductorSeleccionado=document.getElementById('conductorSeleccionado');
const resultadosConductores=document.getElementById('resultadosConductores');
const estadoConductores=document.getElementById('estadoConductores');
let consultaConductor=null, esperaConductor=null, opcionesConductores=[], indiceConductor=-1;
function cerrarConductores() {
    if (!campoConductor) return;
    clearTimeout(esperaConductor);
    consultaConductor?.abort(); consultaConductor=null;
    resultadosConductores.hidden=true;
    campoConductor.setAttribute('aria-expanded','false');
    campoConductor.removeAttribute('aria-activedescendant');
    estadoConductores.textContent='';
    indiceConductor=-1;
}
function reiniciarConductor(registro={}) {
    if (!campoConductor) return;
    cerrarConductores();
    conductorSeleccionado.value=registro.conductor_id ?? '';
    campoConductor.value=registro.conductor_nombre ?? '';
    resultadosConductores.replaceChildren(); opcionesConductores=[];
}
function seleccionarConductor(indice) {
    const conductor=opcionesConductores[indice];
    if (!conductor) return;
    conductorSeleccionado.value=conductor.id;
    campoConductor.value=conductor.nombre;
    cerrarConductores(); campoConductor.focus();
}
async function buscarConductores() {
    consultaConductor?.abort();
    const solicitud=new AbortController(); consultaConductor=solicitud;
    estadoConductores.textContent='Buscando conductores…';
    try {
        const parametros=new URLSearchParams({q:campoConductor.value.trim(),bus_id:form.elements.id.value || '0'});
        const respuesta=await fetch('/Controllers/ConductoresController.php?'+parametros,{signal:solicitud.signal,headers:{Accept:'application/json'}});
        const datos=await respuesta.json();
        if (!respuesta.ok || datos.status!=='success') throw new Error(datos.message || 'No se pudo buscar conductores.');
        if (solicitud.signal.aborted || consultaConductor!==solicitud || !editor.open) return;
        opcionesConductores=datos.conductores.slice(0,5); indiceConductor=-1;
        resultadosConductores.replaceChildren();
        campoConductor.removeAttribute('aria-activedescendant');
        opcionesConductores.forEach((conductor,indice)=>{
            const opcion=document.createElement('li');
            opcion.id='conductor-opcion-'+indice;
            opcion.setAttribute('role','option'); opcion.setAttribute('aria-selected','false');
            opcion.textContent=conductor.nombre+' · '+conductor.cedula;
            opcion.addEventListener('mousedown',evento=>evento.preventDefault());
            opcion.addEventListener('click',()=>seleccionarConductor(indice));
            resultadosConductores.append(opcion);
        });
        resultadosConductores.hidden=!opcionesConductores.length;
        campoConductor.setAttribute('aria-expanded',String(opcionesConductores.length>0));
        estadoConductores.textContent=opcionesConductores.length
            ? 'Selecciona un conductor. Se muestran hasta 5 coincidencias.'
            : 'No hay conductores disponibles que coincidan.';
    } catch(error) {
        if (solicitud.signal.aborted || consultaConductor!==solicitud) return;
        resultadosConductores.hidden=true;
        campoConductor.setAttribute('aria-expanded','false');
        estadoConductores.textContent=error.message || 'No se pudo buscar conductores. Intenta de nuevo.';
    }
}
if (campoConductor) {
    campoConductor.addEventListener('input',()=>{
        conductorSeleccionado.value=''; cerrarConductores();
        esperaConductor=setTimeout(buscarConductores,250);
    });
    campoConductor.addEventListener('focus',()=>{
        if (!conductorSeleccionado.value) buscarConductores();
    });
    campoConductor.addEventListener('blur',cerrarConductores);
    campoConductor.addEventListener('keydown',evento=>{
        if (evento.key==='Escape' && !resultadosConductores.hidden) {
            evento.preventDefault(); evento.stopPropagation(); cerrarConductores(); return;
        }
        if (resultadosConductores.hidden) return;
        if (evento.key==='ArrowDown' || evento.key==='ArrowUp') {
            evento.preventDefault();
            indiceConductor=(indiceConductor+(evento.key==='ArrowDown'?1:-1)+opcionesConductores.length)%opcionesConductores.length;
            [...resultadosConductores.children].forEach((opcion,indice)=>opcion.setAttribute('aria-selected',String(indice===indiceConductor)));
            const activa=resultadosConductores.children[indiceConductor];
            campoConductor.setAttribute('aria-activedescendant',activa.id);
            activa.scrollIntoView({block:'nearest'});
        } else if (evento.key==='Enter') {
            evento.preventDefault();
            if (indiceConductor>=0) seleccionarConductor(indiceConductor);
        }
    });
    document.getElementById('limpiarConductor').addEventListener('click',()=>{
        reiniciarConductor(); campoConductor.focus();
    });
    editor.addEventListener('close',cerrarConductores);
}
