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
document.getElementById('buscar').oninput=e=>{
    let visibles=0; document.querySelectorAll('#registros tr').forEach(r=>{r.hidden=!r.textContent.toLowerCase().includes(e.target.value.toLowerCase()); if(!r.hidden) visibles++;});
    document.getElementById('sinResultados').hidden=visibles>0;
};
form.onsubmit=async e=>{
    e.preventDefault(); const button=document.getElementById('guardar'); button.disabled=true; mensaje.textContent='';
    try { await postForm('/Controllers/CatalogoController.php',new FormData(form)); location.reload(); }
    catch(error){mensaje.textContent=error.message;} finally{button.disabled=false;}
};
