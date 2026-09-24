const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const script = fs.readFileSync('Assets/js/common.js', 'utf8');

for (const base of ['/', '/proyecto/']) {
    test(`recursos y peticiones desde ${base}`, async () => {
        let peticion;
        const contexto = {
            document: {documentElement: {dataset: {appBase: base}}},
            navigator: {},
            fetch: async url => {
                peticion = url;
                return {ok: true, json: async () => ({status: 'success'})};
            },
        };
        contexto.window = contexto;
        vm.runInNewContext(script, contexto);
        assert.equal(contexto.appUrl('/Assets/css/tailwind.css'), base + 'Assets/css/tailwind.css');
        await contexto.postForm('/Controllers/PerfilController.php', {});
        assert.equal(peticion, base + 'Controllers/PerfilController.php');
    });
}
