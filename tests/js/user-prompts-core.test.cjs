// tests/js/user-prompts-core.test.cjs
// Banc d'essai comportemental (tâche #1416, 2026-08-02) pour le fichier fraîchement extrait
// public/assets/tools/user-prompts/user-prompts-core.js (promptsLibrary(), page /user/prompts
// "Mes prompts"). Contrairement aux tests Pest Round*AdversarialFixesTest, qui vérifient des
// SOUS-CHAÎNES du fichier, ceux-ci EXÉCUTENT réellement le moteur (new Function(src) + mocks
// fetch/DOM), même pattern que tests/js/constructeur-prompts-doublesubmit.test.cjs et
// tests/js/constructeur-prompts-promptprofile.test.cjs pour constructeur-prompts-core.js.
// Exécute : node tests/js/user-prompts-core.test.cjs (ou npm run test:js)
const fs = require('fs');
const path = require('path');

function loadPromptsLibrary(fetchImpl, config) {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/user-prompts/user-prompts-core.js'), 'utf8');
    let factory = null;

    global.document = {
        addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); if (evt === 'DOMContentLoaded') { /* no-op, pas testé ici */ } },
        readyState: 'complete',
        querySelector: (sel) => {
            if (sel === 'meta[name="csrf-token"]') return { getAttribute: () => 'test-csrf-token' };
            return null;
        },
        getElementById: () => null,
    };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        dispatchEvent: () => {},
    };
    global.URLSearchParams = URLSearchParams;
    global.CustomEvent = class { constructor(type, o) { this.type = type; this.detail = o && o.detail; } };
    global.fetch = fetchImpl;

    new Function(src)();
    const component = factory(config || {});
    return component;
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  ✅ ' + label); } else { fail++; console.log('  ❌ ' + label); } }

async function flush() {
    for (let i = 0; i < 12; i++) await Promise.resolve();
}

(async function run() {
    // --- Test 1 : la config injectée par le Blade (@js($userPromptsLibraryConfig)) alimente bien
    // l'état du composant - profil, compteur, et libellés i18n du compteur. ---
    {
        const component = loadPromptsLibrary(function () {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        }, {
            profileRole: 'Enseignant',
            profileStyle: 'Direct',
            profileConstraints: 'Toujours vouvoyer',
            promptCount: 3,
            i18n: { countMany: ':count prompts sauvegardés.', countOne: '1 prompt sauvegardé.', countZero: 'Aucun prompt sauvegardé.' },
        });

        assert(component.profileRole === 'Enseignant', 'config.profileRole alimente profileRole');
        assert(component.profileStyle === 'Direct', 'config.profileStyle alimente profileStyle');
        assert(component.profileConstraints === 'Toujours vouvoyer', 'config.profileConstraints alimente profileConstraints');
        assert(component.promptCount === 3, 'config.promptCount alimente promptCount');
        assert(component.promptCountLabel() === '3 prompts sauvegardés.', 'promptCountLabel() interpole :count via i18n.countMany injecté');
    }

    // --- Test 2 : sans config (objet vide), tous les replis par défaut s'appliquent - le
    // garde-fou ne casse jamais si l'injection venait à manquer. ---
    {
        const component = loadPromptsLibrary(function () {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        }, {});

        assert(component.profileRole === '', 'sans config, profileRole reste vide (repli)');
        assert(component.promptCount === 0, 'sans config, promptCount reste à 0 (repli)');
        assert(component.promptCountLabel() === 'Aucun prompt sauvegardé.', 'sans config, promptCountLabel() utilise le repli français en dur');
    }

    // --- Test 3 : promptCountLabel() couvre les 3 branches (0, 1, N). ---
    {
        const component = loadPromptsLibrary(function () {
            return Promise.resolve({ ok: true });
        }, { promptCount: 1, i18n: { countOne: '1 prompt sauvegardé.' } });

        assert(component.promptCountLabel() === '1 prompt sauvegardé.', 'promptCountLabel() branche singulier (promptCount === 1)');
    }

    // --- Test 4 : duplicatePrompt() - garde anti double-invocation (round 56), exécutée réellement
    // (pas seulement une sous-chaîne). ---
    {
        let postCount = 0;
        const component = loadPromptsLibrary(function (url, opts) {
            if (opts && opts.method === 'POST') {
                postCount++;
                return Promise.resolve({ status: 201 });
            }
            return Promise.resolve({ ok: false, status: 500 });
        }, {});
        component.$el = {};

        component.duplicatePrompt('p1');
        component.duplicatePrompt('p1'); // double-clic avant résolution de la 1re requête
        await flush();

        assert(postCount === 1, 'round 56 : double-invocation de duplicatePrompt() ne poste qu\'une seule fois');
    }

    // --- Test 5 : deletePrompt() - garde anti double-invocation (round 58), exécutée réellement. ---
    {
        let deleteCount = 0;
        const component = loadPromptsLibrary(function (url, opts) {
            if (opts && opts.method === 'DELETE') {
                deleteCount++;
                return Promise.resolve({ status: 204 });
            }
            return Promise.resolve({ ok: false, status: 500 });
        }, {});

        component.deletePrompt('p1');
        component.deletePrompt('p1'); // double-clic avant résolution de la 1re requête
        await flush();

        assert(deleteCount === 1, 'round 58 : double-invocation de deletePrompt() n\'envoie qu\'un seul DELETE');
        assert(component.promptCount === 0, 'deletePrompt() décrémente promptCount (borné à 0, round 51)');
    }

    // --- Test 6 : toggleFavorite() lit l'état courant depuis le DOM (aria-pressed), pas depuis un
    // paramètre figé au rendu serveur (round 50). ---
    {
        const component = loadPromptsLibrary(function (url, opts) {
            if (opts && opts.method === 'PUT') {
                return Promise.resolve({ ok: true });
            }
            return Promise.resolve({ ok: false });
        }, {});

        let currentPressed = 'false';
        const buttonEl = {
            getAttribute: (name) => (name === 'aria-pressed' ? currentPressed : null),
            setAttribute: (name, value) => { if (name === 'aria-pressed') currentPressed = value; },
            querySelector: () => null,
            style: {},
        };

        await component.toggleFavorite('p1', buttonEl);
        assert(currentPressed === 'true', 'round 50 : 1er clic passe aria-pressed à true (lu depuis le DOM, pas un paramètre figé)');

        await component.toggleFavorite('p1', buttonEl);
        assert(currentPressed === 'false', 'round 50 : 2e clic repasse à false - preuve que l\'état est bien relu depuis le DOM à chaque appel (pas figé après le 1er clic)');
    }

    // --- Test 7 : saveTags() bloque côté client un tag > 30 caractères, SANS appel réseau (round 120). ---
    {
        let fetchCalled = false;
        const component = loadPromptsLibrary(function () {
            fetchCalled = true;
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        }, { i18n: { tagsTooLong: 'Ces étiquettes dépassent 30 caractères : :tags. Raccourcissez-les avant d\'enregistrer.' } });

        const ok = await component.saveTags('p1', 'un-tag-beaucoup-trop-long-pour-la-regle-de-30-caracteres');

        assert(ok === false, 'round 120 : saveTags() renvoie false quand un tag dépasse 30 caractères');
        assert(fetchCalled === false, 'round 120 : la garde bloque AVANT tout appel réseau évitable');
    }

    // --- Test 8 : saveTags() dédoublonne et plafonne à 5 tags (non-régression round 120). ---
    {
        let sentTags = null;
        const component = loadPromptsLibrary(function (url, opts) {
            sentTags = JSON.parse(opts.body).tags;
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        }, {});

        await component.saveTags('p1', 'a, A, b, c, d, e, f');

        assert(JSON.stringify(sentTags) === JSON.stringify(['a', 'b', 'c', 'd', 'e']), 'round 120 non-régression : dédoublonnage insensible à la casse + plafond à 5 tags respecté (obtenu : ' + JSON.stringify(sentTags) + ')');
    }

    console.log('\n' + pass + '/' + (pass + fail) + ' OK');
    process.exit(fail > 0 ? 1 : 0);
})();
