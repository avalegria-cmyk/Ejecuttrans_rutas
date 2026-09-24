const dialogo = document.getElementById('modalRecorrido');
const confirmacion = document.getElementById('confirmacionRuta');
const form = document.getElementById('formRecorrido');
const iniciar = document.getElementById('iniciarRuta');
const finalizar = document.getElementById('finalizarRuta');
const buscar = document.getElementById('buscarRuta');
const rutaId = document.getElementById('rutaId');
const opciones = document.getElementById('opcionesRutas');
const resultados = document.getElementById('resultadosRutas');
const estado = document.getElementById('estadoBusqueda');
let solicitud, temporizador, version = 0, seleccion = -1, coincidencias = [];
let guardando = false;
let estadoApp = JSON.parse(document.getElementById('estadoInicialApp').textContent);
let estadoPendiente = null;
let canalApp = null;
const conexionApp = document.getElementById('estadoConexionApp');

function aplicarEstadoApp(nuevo) {
    // El guardado confirma su resultado antes de actualizar los formularios.
    if (guardando) { estadoPendiente = nuevo; return; }
    if (JSON.stringify(nuevo.recorrido) !== JSON.stringify(estadoApp.recorrido)) {
        iniciar.disabled = finalizar.disabled = true;
        location.reload();
        return;
    }
    const cambioBus = JSON.stringify(nuevo.bus) !== JSON.stringify(estadoApp.bus);
    estadoApp = nuevo;
    document.getElementById('discoAsignado').textContent = nuevo.bus ? 'Disco ' + nuevo.bus.disco : 'Sin unidad asignada';
    document.getElementById('avisoAsignacion').hidden = Boolean(nuevo.bus || nuevo.recorrido);
    iniciar.disabled = Boolean(nuevo.recorrido || !nuevo.bus);
    finalizar.disabled = !nuevo.recorrido;
    iniciar.querySelector('.modulo-detalle').textContent = nuevo.recorrido
        ? 'Ya tienes una ruta en curso'
        : nuevo.bus ? 'Seleccionar ruta y registrar salida' : 'Necesitas un bus asignado';
    if (cambioBus && !nuevo.recorrido) {
        // No conserva una salida preparada para una unidad que ya fue cambiada.
        confirmacion.close(); dialogo.close(); form.reset();
        if (buscar) {
            rutaId.value = ''; buscar.setCustomValidity('');
            cerrarOpciones();
            estado.textContent = 'Se muestran hasta 5 coincidencias. Selecciona una ruta del listado.';
        }
        document.getElementById('nombreEvidencia').textContent = '';
        document.getElementById('subirEvidencia').textContent = 'Subir evidencia';
    }
}
function conectarEstadoApp() {
    if (canalApp) return;
    canalApp = new EventSource(appUrl('Controllers/ConductorStreamController.php'));
    canalApp.addEventListener('estado', evento => {
        conexionApp.textContent = '';
        aplicarEstadoApp(JSON.parse(evento.data));
    });
    canalApp.addEventListener('revocado', () => {
        canalApp.close(); canalApp = null;
        location.replace(appUrl('index.php'));
    });
    canalApp.onerror = () => {
        conexionApp.textContent = 'Reconectando… La asignación puede estar desactualizada.';
    };
}

for (const modulo of [iniciar, finalizar]) {
    modulo.addEventListener('click', () => {
        if (!modulo.disabled) confirmacion.showModal();
    });
}
document.getElementById('cancelarConfirmacion').onclick = () => confirmacion.close();
document.getElementById('aceptarConfirmacion').onclick = () => {
    confirmacion.close();
    if (!estadoApp.recorrido && !estadoApp.bus) return;
    document.getElementById('mensaje').textContent = '';
    dialogo.showModal();
};
document.getElementById('cancelar').onclick = () => dialogo.close();
dialogo.addEventListener('cancel', event => { if (guardando) event.preventDefault(); });
dialogo.addEventListener('close', () => {
    clearTimeout(temporizador);
    solicitud?.abort();
    version++;
    if (buscar) cerrarOpciones();
});

function cerrarOpciones() {
    resultados.hidden = true;
    buscar.setAttribute('aria-expanded', 'false');
    buscar.removeAttribute('aria-activedescendant');
    seleccion = -1;
}
function elegirRuta(indice) {
    const ruta = coincidencias[indice];
    if (!ruta) return;
    buscar.value = ruta.nombre;
    rutaId.value = ruta.id;
    buscar.setCustomValidity('');
    estado.textContent = 'Ruta seleccionada: ' + ruta.nombre;
    cerrarOpciones();
}
function marcarOpcion(indice) {
    seleccion = indice;
    [...opciones.children].forEach((opcion, i) => opcion.setAttribute('aria-selected', String(i === indice)));
    const opcion = opciones.children[indice];
    if (opcion) {
        buscar.setAttribute('aria-activedescendant', opcion.id);
        opcion.scrollIntoView({block: 'nearest'});
    }
}
async function buscarRutas() {
    solicitud?.abort();
    solicitud = new AbortController();
    const actual = ++version;
    cerrarOpciones();
    estado.textContent = 'Buscando rutas…';
    try {
        const response = await fetch(appUrl('Controllers/RutaController.php?q=' + encodeURIComponent(buscar.value.trim())), {signal: solicitud.signal});
        const data = await response.json();
        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No se pudieron consultar las rutas.');
        if (actual !== version || !dialogo.open) return;
        coincidencias = data.rutas.slice(0,5);
        opciones.replaceChildren();
        coincidencias.forEach((ruta, i) => {
            const opcion = document.createElement('li');
            opcion.id = 'ruta-opcion-' + ruta.id;
            opcion.setAttribute('role', 'option');
            opcion.setAttribute('aria-selected', 'false');
            const nombre = document.createElement('strong');
            nombre.textContent = ruta.nombre;
            opcion.append(nombre);
            if (ruta.descripcion) {
                const descripcion = document.createElement('small');
                descripcion.textContent = ruta.descripcion;
                opcion.append(descripcion);
            }
            // Mantiene el foco del combobox al seleccionar con ratón o pantalla táctil.
            opcion.addEventListener('mousedown', event => event.preventDefault());
            opcion.addEventListener('click', () => elegirRuta(i));
            opciones.append(opcion);
        });
        resultados.hidden = coincidencias.length === 0;
        buscar.setAttribute('aria-expanded', String(coincidencias.length > 0));
        estado.textContent = coincidencias.length
            ? 'Selecciona una ruta · hasta 5 coincidencias.'
            : 'No hay rutas habilitadas que coincidan. Prueba con otro nombre.';
    } catch (error) {
        if (error.name === 'AbortError' || actual !== version) return;
        coincidencias = [];
        cerrarOpciones();
        estado.textContent = 'No se pudieron cargar las rutas. Vuelve a escribir para reintentar.';
    }
}
if (buscar) {
    buscar.addEventListener('focus', buscarRutas);
    buscar.addEventListener('input', () => {
        rutaId.value = '';
        buscar.setCustomValidity('Selecciona una ruta de las coincidencias.');
        solicitud?.abort();
        version++;
        clearTimeout(temporizador);
        coincidencias = [];
        cerrarOpciones();
        estado.textContent = 'Buscando rutas…';
        temporizador = setTimeout(buscarRutas, 220);
    });
    buscar.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !resultados.hidden) {
            event.preventDefault(); event.stopPropagation(); cerrarOpciones();
        } else if (['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            if (resultados.hidden) { buscarRutas(); return; }
            const paso = event.key === 'ArrowDown' ? 1 : -1;
            marcarOpcion((seleccion + paso + coincidencias.length) % coincidencias.length);
        } else if (event.key === 'Enter' && !resultados.hidden) {
            event.preventDefault();
            if (seleccion >= 0) elegirRuta(seleccion);
        }
    });
    dialogo.addEventListener('click', event => {
        if (!event.target.closest('.selector-ruta')) cerrarOpciones();
    });
}
form.onsubmit = async event => {
    event.preventDefault();
    if (guardando) return;
    const boton = document.getElementById('enviar');
    const cancelar = document.getElementById('cancelar');
    const mensaje = document.getElementById('mensaje');
    const etiqueta = boton.textContent;
    if (!estadoApp.recorrido && !estadoApp.bus) {
        mensaje.textContent = 'No tienes un bus habilitado asignado. Contacta a secretaría.';
        return;
    }
    if (estadoApp.recorrido && Number(form.elements.kilometraje.value) <= Number(estadoApp.recorrido.km_inicial)) {
        mensaje.textContent = 'El kilometraje final debe ser mayor al inicial de este recorrido.';
        form.elements.kilometraje.focus(); return;
    }
    if (buscar && !rutaId.value) {
        buscar.setCustomValidity('Selecciona una ruta de las coincidencias.');
        buscar.reportValidity();
        return;
    }
    guardando = true;
    boton.disabled = cancelar.disabled = true;
    boton.textContent = 'Guardando…';
    mensaje.textContent = '';
    try {
        const datos = new FormData(form), original = datos.get('evidencia');
        if (original.size > 10 * 1024 * 1024) throw new Error('La evidencia no puede superar 10 MB.');
        datos.set('evidencia', await comprimirComprobante(original));
        await postForm('Controllers/RecorridoController.php', datos);
        // Bloquea ambos módulos hasta cargar el nuevo estado confirmado por el servidor.
        iniciar.disabled = finalizar.disabled = true;
        location.reload();
    } catch (error) {
        mensaje.textContent = error.message;
    } finally {
        guardando = false;
        boton.disabled = cancelar.disabled = false;
        boton.textContent = etiqueta;
        if (estadoPendiente) {
            const nuevo = estadoPendiente; estadoPendiente = null;
            aplicarEstadoApp(nuevo);
        }
    }
};
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });

const evidencia = document.getElementById('evidencia');
const subirEvidencia = document.getElementById('subirEvidencia');
const nombreEvidencia = document.getElementById('nombreEvidencia');
subirEvidencia.addEventListener('click', () => evidencia.click());
evidencia.addEventListener('change', () => {
    const archivo = evidencia.files[0];
    nombreEvidencia.textContent = archivo ? archivo.name : '';
    subirEvidencia.textContent = archivo ? 'Cambiar evidencia' : 'Subir evidencia';
    document.getElementById('mensaje').textContent = '';
});
evidencia.addEventListener('invalid', event => {
    event.preventDefault();
    document.getElementById('mensaje').textContent = 'Sube una foto o PDF como evidencia para continuar.';
    subirEvidencia.focus();
});

window.addEventListener('pagehide', () => {
    canalApp?.close(); canalApp = null;
});
window.addEventListener('online', () => {
    canalApp?.close(); canalApp = null;
    conectarEstadoApp();
});
conectarEstadoApp();
