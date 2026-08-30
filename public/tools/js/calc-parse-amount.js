/*
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * #P0-audit 2026-08-30 : extrait de calculatrice-taxes.blade.php (v1.238.0, mesuré le même jour :
 * un <input type="number"> REJETTE la virgule française au clavier - frappe réelle « 12,50 » ->
 * valeur DOM "1250", facteur 100). Le commentaire d'origine annonçait déjà cette fonction comme
 * « réutilisable par d'autres outils de la même famille », mais elle restait déclarée DANS le
 * <script> inline de CETTE vue - donc invisible de toute AUTRE page (chaque page a son propre
 * <script>, chargé séparément ; window.CalcParseAmount n'existait que sur la page où il était
 * défini). Ce fichier est désormais le SEUL endroit qui définit ces deux fonctions - toute page
 * qui en a besoin charge <script src="{{ asset('tools/js/calc-parse-amount.js') }}"></script>
 * AVANT son propre script, exactement comme calculatrice-taxes.blade.php le fait ci-dessous.
 * Corps de fonction extrait tel quel (sed sur les lignes 298-338 de la version d'origine) - zéro
 * retype manuel pour ne pas risquer d'altérer les caractères insécables U+00A0/U+202F du regex.
 */

    // window.CalcParseAmount(brut) -> nombre JS ou NaN. Gère, dans cet ordre :
    //  - espaces (normaux ET insécables U+00A0/U+202F) utilisés comme séparateur de milliers,
    //    supprimés uniquement s'ils ne sont PAS suivis d'exactement 2 chiffres (sinon ce serait
    //    un espace décimal, cas non standard - on ne devine pas, cf. "1,234.56" plus bas) ;
    //  - un SEUL séparateur décimal, virgule OU point, désigné explicitement (jamais deviné) :
    //    c'est le DERNIER "," ou "." de la chaîne qui fait foi si un chiffre le suit ; tout
    //    séparateur antérieur est alors traité comme un séparateur de milliers et retiré. Couvre
    //    "12,50", "12.50", "1 234,56", "1 234,56" (espace insécable), "1,234.56" (anglais) ET
    //    "1.234,56" (européen) sans ambiguïté puisque c'est la POSITION (dernier séparateur) qui
    //    tranche, jamais une supposition sur la locale ;
    //  - saisie partielle pendant la frappe ("12," ou "12,0") : NE JAMAIS vider ni rejeter, on
    //    complète mentalement le nombre déjà tapé (parseFloat s'arrête proprement).
    window.CalcParseAmount = function (raw) {
        if (raw === null || raw === undefined) return NaN;
        var s = raw.toString().trim();
        if (s === '') return NaN;
        s = s.replace(/[  ]/g, ' '); // espaces insécables -> espace normal
        s = s.replace(/\s/g, ''); // espaces (milliers) retirés - jamais un séparateur décimal en fr/en
        var lastComma = s.lastIndexOf(',');
        var lastDot = s.lastIndexOf('.');
        var lastSep = Math.max(lastComma, lastDot);
        if (lastSep === -1) {
            var n0 = parseFloat(s);
            return isNaN(n0) ? NaN : n0;
        }
        var intPart = s.slice(0, lastSep).replace(/[.,]/g, '') || '0';
        var decPart = s.slice(lastSep + 1).replace(/[^0-9]/g, '');
        var n = parseFloat(intPart + '.' + (decPart === '' ? '0' : decPart));
        return isNaN(n) ? NaN : n;
    };

    // window.CalcMoney(nombre, {withSymbol}) -> chaîne fr-CA : virgule décimale, espace insécable
    // avant le symbole "$" QUAND il est demandé. withSymbol=false pour les champs qui affichent
    // déjà un "$" séparé (span .currency-symbol du gabarit) - évite le double symbole constaté
    // (capture réelle : "$5.00$") quand le "$" du prefix ET celui du formatteur coexistaient.
    window.CalcMoney = function (n, opts) {
        opts = opts || {};
        if (typeof n !== 'number' || isNaN(n)) n = 0;
        var out = n.toFixed(2).replace('.', ',');
        return opts.withSymbol === false ? out : out + ' $';
    };
