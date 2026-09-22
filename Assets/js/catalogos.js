const registros=JSON.parse(document.getElementById('datosCatalogo').textContent);
const editor=document.getElementById('editor'),form=document.getElementById('formCatalogo'),mensaje=document.getElementById('mensaje');
function abrirEditor(registro={}) {
    form.reset(); mensaje.textContent=''; form.elements.id.value=registro.id || '';
    for (const [k,v] of Object.entries(registro)) if(form.elements[k]) form.elements[k].value=v ?? '';
    document.getElementById('tituloEditor').textContent=registro.id?'Editar registro':'Nuevo registro'; editor.showModal();
}
document.getElementById('nuevo').onclick=()=>abrirEditor();
document.getElementById('cancelar').onclick=()=>editor.close();
document.querySelectorAll('.editar').forEach(b=>b.onclick=()=>abrirEditor(registros.find(r=>String(r.id)===b.dataset.id)));
const normalizar=valor=>valor.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
function aplicarFiltros(){
    const texto=normalizar(document.getElementById('buscar').value);
    const estado=document.getElementById('filtroEstado').value;
    const rol=document.getElementById('filtroRol')?.value || '';
    const asignacion=document.getElementById('filtroAsignacion')?.value || '';
    let visibles=0;
    document.querySelectorAll('#registros tr').forEach(fila=>{
        const coincideTexto=!texto || normalizar(fila.textContent).includes(texto);
        const coincideEstado=!estado || fila.dataset.activo===estado;
        const coincideRol=!rol || fila.dataset.rol===rol;
        const coincideAsignacion=!asignacion || fila.dataset.conductor===asignacion;
        fila.hidden=!(coincideTexto && coincideEstado && coincideRol && coincideAsignacion);
        if(!fila.hidden) visibles++;
    });
    document.getElementById('conteoFiltrado').textContent=visibles;
    const vacio=document.getElementById('sinResultados');
    vacio.textContent=registros.length?'No hay registros que coincidan con los filtros.':'No hay registros para mostrar.';
    vacio.hidden=visibles>0;
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
    e.preventDefault(); const button=document.getElementById('guardar'); button.disabled=true; mensaje.textContent='';
    try { await postForm('/Controllers/CatalogoController.php',new FormData(form)); location.reload(); }
    catch(error){mensaje.textContent=error.message;} finally{button.disabled=false;}
};
