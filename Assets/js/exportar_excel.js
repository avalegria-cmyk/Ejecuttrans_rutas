(() => {
    const boton=document.getElementById('exportarExcel');
    if (!boton) return;
    const estado=document.getElementById('estadoExportacion');
    const texto=elemento=>elemento?.textContent.trim() || '';
    function exportarTabla(modulo) {
        const tabla=document.querySelector('main table');
        if (!tabla) throw new Error('No hay una tabla disponible para exportar.');
        const columnas=[...tabla.querySelectorAll('thead th')]
            .map((celda,indice)=>({indice,titulo:texto(celda)}))
            .filter(columna=>columna.titulo.toLocaleLowerCase('es')!=='acciones'
                && !(modulo==='recorridos' && /^evidencia(?:\s|$)/i.test(columna.titulo)));
        const filas=[...tabla.querySelectorAll('tbody tr')].filter(fila=>
            !fila.querySelector('td[colspan]') && (fila.dataset.coincideFiltro!==undefined
                ? fila.dataset.coincideFiltro==='true' : !fila.hidden)
        ).map(fila=>columnas.map(({indice,titulo})=>{
            const celda=fila.cells[indice];
            const enlace=celda?.querySelector('a');
            if (enlace) return enlace.href;
            const valor=texto(celda);
            if (/^(km inicial|km final|distancia)$/i.test(titulo) && /\d/.test(valor)) {
                const numero=Number(valor.replace(/\./g,'').replace(',','.').replace(/\s*km\s*$/i,''));
                if (Number.isFinite(numero)) return numero;
            }
            return valor;
        }));
        return {encabezados:columnas.map(columna=>columna.titulo),filas};
    }
    function exportarDashboard() {
        const filas=[];
        document.querySelectorAll('.overview-card').forEach(tarjeta=>{
            filas.push(['Resumen',texto(tarjeta.querySelector(':scope > span')),Number(texto(tarjeta.querySelector('strong'))),texto(tarjeta.querySelector('small'))]);
        });
        document.querySelectorAll('.system-facts > div').forEach(dato=>{
            filas.push(['Estado del sistema',texto(dato.querySelector('span')),Number(texto(dato.querySelector('strong'))),'']);
        });
        document.querySelectorAll('.activity-item').forEach(actividad=>{
            const identidad=actividad.children[1];
            filas.push(['Recorridos recientes',texto(identidad?.querySelector('strong')),texto(identidad?.querySelector('span')),
                [texto(actividad.querySelector('.badge')),texto(actividad.querySelector('.activity-meta small'))].filter(Boolean).join(' · ')]);
        });
        return {encabezados:['Sección','Indicador / conductor','Valor / recorrido','Detalle'],filas};
    }
    function exportarPerfil() {
        const identidad=document.querySelector('.profile-identity');
        return {
            encabezados:['Nombre completo','Cédula','Rol'],
            filas:[[texto(identidad?.querySelector('h2')),texto(identidad?.querySelector('.muted')).replace(/^Cédula\s*/i,''),texto(identidad?.querySelector('.badge'))]]
        };
    }
    boton.addEventListener('click',async()=>{
        if (boton.disabled) return;
        boton.disabled=true; boton.setAttribute('aria-busy','true');
        estado.textContent='Preparando Excel…';
        try {
            const modulo=boton.dataset.modulo;
            const datos=modulo==='dashboard'?exportarDashboard():modulo==='perfil'?exportarPerfil():exportarTabla(modulo);
            const formulario=new FormData();
            formulario.set('csrf',boton.dataset.csrf);
            formulario.set('modulo',modulo);
            formulario.set('datos',JSON.stringify(datos));
            const respuesta=await fetch('/Controllers/ExportarController.php',{method:'POST',body:formulario});
            if (!respuesta.ok || !respuesta.headers.get('Content-Type')?.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                const error=await respuesta.json().catch(()=>null);
                throw new Error(error?.message || 'No se pudo generar el Excel. Intenta de nuevo.');
            }
            const archivo=await respuesta.blob();
            const url=URL.createObjectURL(archivo);
            const enlace=document.createElement('a');
            enlace.href=url;
            enlace.download=respuesta.headers.get('Content-Disposition')?.match(/filename="([^"]+)"/)?.[1] || `${modulo}.xlsx`;
            enlace.hidden=true; document.body.append(enlace); enlace.click(); enlace.remove();
            setTimeout(()=>URL.revokeObjectURL(url),60000);
            estado.textContent='Excel descargado.';
        } catch(error) {
            estado.textContent=error.message || 'No se pudo descargar el Excel.';
        } finally {
            boton.disabled=false; boton.removeAttribute('aria-busy');
        }
    });
})();
