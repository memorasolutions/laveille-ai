const fs = require('fs');
const path = require('path');

function loadPromptBuilder() {
    const src = fs.readFileSync(path.join(__dirname, '../../public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'), 'utf8');
    let factory = null;
    global.document = { addEventListener: (evt, cb) => { if (evt === 'alpine:init') cb(); } };
    global.Alpine = { data: (name, f) => { factory = f; } };
    global.window = {
        location: { search: '' },
        promptBuilderConfig: { personas: [], verbs: [], audiences: [], taskCards: [], isAuthenticated: false, i18n: {} },
        dispatchEvent: () => {},
        open: () => {},
        toast: () => {},
    };
    global.navigator.clipboard = { writeText: (text) => Promise.resolve() };
    global.window.copyToClipboard = function (text) { return navigator.clipboard.writeText(text); };
    global.CustomEvent = class { constructor(type, opts) { this.type = type; this.detail = opts && opts.detail; } };
    new Function(src)();
    const component = factory();
    return { component };
}

let pass = 0, fail = 0;
function assert(cond, label) { if (cond) { pass++; console.log('  OK ' + label); } else { fail++; console.log('  FAIL ' + label); } }
function withoutAccents(text) { return text.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }

// Une seule tache par defaut : comportement historique inchange.
{
    const { component } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Resume';
    component.taskObject = 'cet article';
    assert(withoutAccents(component.prompt).includes('Ta tache : Resume cet article.'), 'une seule tache quand la deuxieme tache est desactivee');
}

// Deux taches preset : les etapes sont numerotees et conservent l'ordre des verbes.
{
    const { component } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Resume';
    component.taskObject = 'cet article';
    component.secondTaskEnabled = true;
    component.verbType2 = 'preset';
    component.verb2 = 'Critique';
    const prompt = component.prompt;
    const normalized = withoutAccents(prompt).toLowerCase();
    assert(prompt.includes('1)') && prompt.includes('2)'), 'les deux etapes sont numerotees');
    assert(prompt.indexOf('Resume') < prompt.indexOf('Critique'), 'les deux verbes sont presents dans le bon ordre');
    assert(normalized.includes("a partir du resultat de l'etape precedente"), 'la deuxieme etape part du resultat de la premiere');
}

// Deuxieme tache activee sans verbe : repli strict sur le prompt a une tache.
{
    const { component } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Resume';
    component.taskObject = 'cet article';
    const promptWithoutSecondTask = component.prompt;
    component.secondTaskEnabled = true;
    assert(component.prompt === promptWithoutSecondTask && withoutAccents(component.prompt).includes('Ta tache : Resume cet article.'), 'aucun verbe 2 conserve exactement le comportement historique');
}

// Le verbe personnalise de la deuxieme tache est utilise dans le prompt final.
{
    const { component } = loadPromptBuilder();
    component.verbType = 'preset';
    component.verb = 'Resume';
    component.taskObject = 'cet article';
    component.secondTaskEnabled = true;
    component.verbType2 = 'custom';
    component.verbCustom2 = 'Traduis en anglais';
    assert(component.prompt.includes('Traduis en anglais'), 'le verbe personnalise de la deuxieme tache apparait');
}

console.log('\n' + pass + '/' + (pass + fail) + ' OK');
process.exit(fail > 0 ? 1 : 0);
