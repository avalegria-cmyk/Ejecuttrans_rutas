const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const script = fs.readFileSync('Assets/js/exportar_excel.js', 'utf8');

async function datosExportados(modulo) {
    const titulos = ['Conductor', 'Disco', 'Ruta', 'Inicio', 'Km inicial', 'Evidencia', 'Fin', 'Km final', 'Evidencia', 'Distancia', 'Estado', 'Acciones'];
    const valores = ['Ana', '009', 'Ruta 1', '2026-09-22 16:40:29', '10.000', 'inicio', '2026-09-22 16:40:37', '13.300', 'final', '3.300 km', 'Finalizado', 'Editar'];
    const celdas = valores.map((valor, indice) => ({
        textContent: valor,
        querySelector: () => indice === 5 || indice === 8 ? {href: `http://localhost/evidencia-${valor}`} : null,
    }));
    const tabla = {
        querySelectorAll: selector => selector === 'thead th'
            ? titulos.map(textContent => ({textContent}))
            : [{cells: celdas, dataset: {}, hidden: false, querySelector: () => null}],
    };
    let alHacerClic;
    let resultado;
    const boton = {
        dataset: {modulo, csrf: 'prueba'}, disabled: false,
        addEventListener: (_evento, callback) => { alHacerClic = callback; },
        setAttribute: () => {}, removeAttribute: () => {},
    };
    const estado = {textContent: ''};
    const document = {
        getElementById: id => id === 'exportarExcel' ? boton : estado,
        querySelector: () => tabla,
    };
    vm.runInNewContext(script, {
        document, FormData,
        fetch: async (_url, opciones) => {
            resultado = JSON.parse(opciones.body.get('datos'));
            throw new Error('Solicitud capturada para la prueba');
        },
    });
    await alHacerClic();
    return resultado;
}

test('Recorridos omite ambas evidencias del Excel y conserva las demás columnas', async () => {
    const {encabezados, filas} = await datosExportados('recorridos');
    assert.deepEqual(encabezados, ['Conductor', 'Disco', 'Ruta', 'Inicio', 'Km inicial', 'Fin', 'Km final', 'Distancia', 'Estado']);
    assert.deepEqual(filas[0], ['Ana', '009', 'Ruta 1', '2026-09-22 16:40:29', 10000, '2026-09-22 16:40:37', 13300, 3300, 'Finalizado']);
});

test('Otros módulos conservan sus columnas excepto Acciones', async () => {
    const {encabezados} = await datosExportados('buses');
    assert.equal(encabezados.filter(titulo => titulo === 'Evidencia').length, 2);
    assert.ok(!encabezados.includes('Acciones'));
});
