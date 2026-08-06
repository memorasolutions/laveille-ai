// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// constructeur-prompts-core.js — logique Alpine.js du wizard « Constructeur de prompts » (4 étapes).
// Extrait de resources/views/public/tools/constructeur-prompts.blade.php (Phase 0 audit 2026-07-26)
// pour permettre au navigateur de mettre ce JS en cache (auparavant inline, jamais caché).
// Les données dynamiques (personas/verbes/audiences configurables + i18n) sont injectées par
// le Blade via window.promptBuilderConfig (même pattern que window.taxConfig sur calculatrice-taxes).
// Fichier servi tel quel (pas de build Vite pour Modules/Tools — même convention que
// minuteur-visuel-core.js, anonymizer-core.js, etc.) : cache-busting via ?v={semver} en query string.
document.addEventListener('alpine:init', function() {
    Alpine.data('promptBuilder', function() {
        return {
            step: 1,
            resetArmed: false,
            // Phase 1 (audit 2026-07-26) : entrée par l'intention. selectedTask = id de la carte
            // choisie à l'étape 1 ; taskCards = taxonomie de tâches → mapping persona/verbe,
            // injectée par le Blade via window.promptBuilderConfig (même contrat que personas/verbes).
            selectedTask: '',
            taskCards: (window.promptBuilderConfig && window.promptBuilderConfig.taskCards) || [],
            // Round 64 (2026-07-27) : vrai dès le démarrage si ?edit=ID est présent (avant même que
            // init() ne lance le fetch) - bloque selectTask() tant que le prompt existant n'a pas
            // fini de charger. Sans ce flag, un clic sur une carte d'objectif pendant que le GET
            // /api/prompts/{id} est en vol était écrasé silencieusement par la réponse tardive
            // (personaPreset/verb/taskObject/... réécrits avec les valeurs de l'ANCIEN prompt),
            // sauf selectedTask lui-même - désynchronisant le badge affiché des champs réels.
            // 2026-08-05 (Phase 1 permalien public + remix) : le flag doit aussi bloquer
            // selectTask() pendant le chargement de ?remix=ID (voir init() plus bas), même
            // garde-fou anti-race-condition que ?edit=ID ci-dessus (round 64).
            editLoading: !!(new URLSearchParams(window.location.search).get('edit') || new URLSearchParams(window.location.search).get('remix')),
            // Round 152 (2026-08-01, écran 3) : les 5 accordéons imbriqués « + Réglages avancés »
            // (openSections.role/verb/format/technique/contraintes) sont RETIRÉS - le proprétaire a
            // été explicite (« les ouvrir et fermer à chaque fois... ark ! »). Les mêmes réglages
            // vivent maintenant dans 5 blocs TOUJOURS VISIBLES (voir x-tools::prompt-block dans le
            // Blade). L'ancien flag affinerOpen (un seul clic pour les révéler) a été retiré le
            // 2026-08-04 : jamais lu nulle part (ni Blade ni JS), les blocs étant déjà toujours
            // visibles depuis ce round 152 - le flag n'avait plus aucun effet.
            // Round 152 : profils de règles conditionnels (section 7 du plan). Aucune IA dans l'outil :
            // ce n'est PAS de la « compréhension », seulement une correspondance par mots-clés qui
            // pré-sélectionne un profil TOUJOURS visible et corrigeable d'un clic (voir
            // _autoDetectProfile ci-dessous, appelé une fois à la transition écran 1 → écran 2, jamais
            // ensuite tant que la personne n'a pas choisi elle-même un profil).
            profile: 'texte',
            profileTouched: false,
            profiles: (window.promptBuilderConfig && window.promptBuilderConfig.profiles) || [
                { value: 'texte', label: 'Texte', hint: 'Écriture humaine, typographie française, ton.' },
                { value: 'programmation', label: 'Programmation', hint: 'Aucune règle de style français ; ajoute la mise en forme du code.' },
                { value: 'traduction', label: 'Traduction', hint: 'Aucune règle de français du Québec appliquée au résultat.' },
            ],
            // Round 152 : réglages sans mode preset/custom avant cette refonte (contrairement à
            // personaType/verbType/audienceType) - petit état d'UI LOCAL (jamais persisté, jamais
            // injecté dans le prompt) qui révèle un champ libre quand la carte « Autre » est cliquée.
            customOpen: { tone: false, format: false, length: false },
            personaType: 'preset',
            personaPreset: '',
            personaCustom: '',
            personas: (window.promptBuilderConfig && window.promptBuilderConfig.personas) || [],
            verbType: 'preset',
            verb: '',
            verbs: (window.promptBuilderConfig && window.promptBuilderConfig.verbs) || [],
            verbCustom: '',
            taskObject: '',
            // Deuxième tâche optionnelle (2026-08-04, club des sages 5/5 unanime) : bornée à 2
            // tâches maximum, jamais de sélection multiple libre - la version cartes/multi-select
            // a déjà été essayée et rejetée 2 fois cette année sur cet outil. Le prompt généré
            // exprime une séquence explicite ("D'abord X, ensuite Y"), jamais une juxtaposition.
            secondTaskEnabled: false,
            verbType2: 'preset',
            verb2: '',
            verbCustom2: '',
            audienceType: 'preset',
            // Round 151 (2026-08-01, refonte écrans 1-2) : `audiencePreset` (singulier) retiré de
            // l'état - plus aucune action utilisateur ne l'écrit depuis l'introduction du
            // multi-sélection (audiencePresets, pluriel). La MIGRATION DE LECTURE d'anciens prompts
            // sauvegardés avec ce champ singulier reste intacte plus bas (init(), lecture directe de
            // `p.audiencePreset` depuis le payload chargé - n'a jamais eu besoin de cette propriété
            // d'état pour fonctionner).
            audiencePresets: [],
            audienceCustom: '',
            audiences: (window.promptBuilderConfig && window.promptBuilderConfig.audiences) || [],
            // Round 152 (2026-08-01, écran 3) : formats/longueurs/tons/techniques/langues - même
            // contrat {value,label} que personas/audiences ci-dessus, injectés par le Blade
            // (window.promptBuilderConfig) pour éviter de dupliquer leur contenu entre ce fichier et
            // le Blade (DRY). Repli français en dur si le Blade n'a pas encore injecté sa config
            // (même garde que partout ailleurs dans ce fichier).
            formats: (window.promptBuilderConfig && window.promptBuilderConfig.formats) || [],
            lengths: (window.promptBuilderConfig && window.promptBuilderConfig.lengths) || [],
            tones: (window.promptBuilderConfig && window.promptBuilderConfig.tones) || [],
            techniques: (window.promptBuilderConfig && window.promptBuilderConfig.techniques) || [],
            languages: (window.promptBuilderConfig && window.promptBuilderConfig.languages) || [],
            // Défauts intelligents (2026-08-04, club des sages 5/5 + veille) : vides auparavant,
            // ce qui déclenchait systématiquement l'avertissement "Diagnostic rapide" (voir plus
            // bas) pour quiconque ne touchait jamais à l'écran 4 - l'outil créait lui-même le
            // problème qu'il signalait ensuite. Valeurs parmi les options existantes (formats/
            // lengths/tones ci-dessus), toujours modifiables, rien n'est retiré.
            // LOT 1 (2026-08-06, verdict Codex) : le format de sortie est désormais une
            // multi-sélection de cartes (max 3, JSON/Mermaid exclusifs entre eux et avec le
            // reste - voir isFormatDisabled()/handleFormatChange() plus bas) au lieu d'un
            // <select> à valeur unique. `format` (scalaire) est retiré de l'état ; formatsSelected
            // (tableau de valeurs de window.promptBuilderConfig.formats) et formatCustom (texte
            // libre, séparé - avant ce lot les deux partageaient la même variable `format`)
            // le remplacent. Défaut identique au comportement précédent (1 format présélectionné,
            // « défauts intelligents » round 140).
            formatsSelected: ['Paragraphes détaillés'],
            formatCustom: '',
            // Classement des 12 valeurs de $defaultFormats (Blade) utilisé par formatBulletText()
            // pour choisir la formulation du prompt généré (règle unique / structure multiple /
            // livrables multiples / mélange) - copie volontaire des `value` (jamais les `label`,
            // traduits) pour rester la source de vérité du TEXTE injecté dans le prompt, comme
            // partout ailleurs dans ce fichier (personas/verbes/audiences non traduits).
            _formatStructureValues: ['Liste à puces', 'Paragraphes détaillés', 'Tableau structuré', 'Plan hiérarchisé', 'Étapes numérotées'],
            _formatDeliverableValues: ['Questionnaire / QCM avec corrigé', 'Grille d\'évaluation (rubrique)', 'Fiche pratique (1 page)', 'Modèle réutilisable (gabarit)', 'FAQ structurée'],
            _formatExclusiveValues: ['Format JSON', 'Diagramme Mermaid'],
            length: 'Modéré (300-500 mots)',
            tone: 'Professionnel',
            language: 'fr',
            constraintAntiAI: true,
            constraintTypo: false,
            constraintCanvas: false,
            canvasAI: 'chatgpt',
            canvasFormat: '',
            canvasCustomFormat: '',
            // 2026-05-05 #104 : formats officiels mai 2026 (sources Anthropic Oct 2025, OpenAI 2026, Google AI 2025-2026, Mistral)
            canvasFormatMap: {
                chatgpt: ['Markdown', 'PDF', 'DOCX (Word)', 'Code (Python/JS/SQL)', 'Tableau interactif', 'Python exécutable'],
                claude: ['Markdown', 'HTML', 'SVG', 'React (.jsx)', 'Mermaid', 'Code', 'DOCX (Word)', 'PDF', 'XLSX (tableur)', 'PPTX (slides)'],
                gemini: ['Google Docs', 'Google Slides', 'PDF', 'Code (Colab)', 'App embarquée', 'Quiz/Infographie', 'Markdown'],
                mistral: ['Markdown', 'HTML', 'Code', 'Diagramme'],
            },
            formatMode: 'preset', // 2026-05-05 #104 : 'preset' (liste prédéfinie selon IA) ou 'custom' (champ texte libre universel)
            get canvasFormats() { return this.canvasFormatMap[this.canvasAI] || []; },
            // Phase 2 (audit 2026-07-26) : « Destination » (OÙ la réponse doit apparaître) séparée
            // visuellement de « Format attendu » (QUOI, la structure du contenu) - décision d'architecture
            // d'info validée par 3 IA. Getter/setter calculé qui pilote constraintCanvas + canvasAI SANS
            // renommer ces clés internes (compatibilité de reload d'un prompt déjà sauvegardé, ?edit=ID).
            get destination() { return this.constraintCanvas ? this.canvasAI : ''; },
            set destination(value) {
                if (!value) { this.constraintCanvas = false; return; }
                this.constraintCanvas = true;
                // Round 55 (2026-07-27) : canvasFormat est une liste PRÉDÉFINIE propre à chaque IA
                // (canvasFormatMap). Changer d'IA sans réinitialiser laissait une valeur périmée
                // injectée silencieusement dans le prompt final (getter prompt, section canvasLine)
                // alors que le <select> canvasFormat s'affichait vide (aucune option ne matchait
                // plus dans la nouvelle liste) - incohérence invisible pour l'utilisateur.
                if (value !== this.canvasAI) { this.canvasFormat = ''; }
                this.canvasAI = value;
            },
            constraintChainOfThought: false,
            constraintAskIfUnclear: false,
            constraintCustom: '',
            technique: 'zero-shot',
            examples: '',
            useDelimiters: false,
            // Round 151 (2026-08-01, écran 2 « Votre prompt est prêt ») : interrupteur visible qui
            // coupe les règles AUTOMATIQUEMENT injectées (écriture anti-IA, typographie française,
            // critères de qualité) sans toucher aux choix explicites de la personne (technique de
            // réflexion, destination/canvas, réflexion étape par étape, poser des questions,
            // contraintes personnalisées). Activé par défaut = comportement identique à avant cette
            // refonte (constraintAntiAI reste coché par défaut, constraintTypo décoché).
            cadreStrict: true,
            // Round 151 : IA ciblée par le duo Copier/Ouvrir dans, mis en avant sur l'écran 2 (au
            // lieu d'être seulement 5 boutons isolés). N'affecte JAMAIS le texte du prompt lui-même
            // (openIn() ne fait que choisir l'URL) - seul le libellé et la note d'usage en dessous
            // changent selon le choix, pour qu'il n'y ait jamais de surprise au clic sur Copier.
            openTarget: 'chatgpt',
            showHelp: {},
            // Round 77 (2026-07-27, passe adversariale) : repli français en dur, mais valeur réelle
            // toujours prise dans window.promptBuilderConfig.helps (injecté par le Blade via __(),
            // même pattern que i18n.* juste au-dessus) - donc traduit en EN/ES quand la locale change.
            helps: (window.promptBuilderConfig && window.promptBuilderConfig.helps) || {
                persona: 'Donner un rôle à l\'IA aide à orienter ses réponses selon une expertise ou un style spécifique. Ex: « Tu es un expert marketing » donnera des réponses plus stratégiques.',
                verb: 'Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.',
                taskObject: 'Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de contexte et de détails, meilleur sera le résultat.',
                audience: 'Spécifier le public aide l\'IA à adapter son langage. Un texte pour des débutants sera différent d\'un texte pour des experts.',
                format: 'Le format guide la structure de la réponse. Une liste à puces est facile à lire, un tableau est bon pour comparer, un plan est idéal pour organiser.',
                length: 'Indiquer une longueur permet de contrôler si la réponse est concise (pour un résumé) ou détaillée (pour un article complet).',
                tone: 'Le ton change le style : professionnel pour un rapport, chaleureux pour un courriel client, académique pour un mémoire.',
                technique: 'Réponse directe : l\'IA répond tout de suite sans exemple. Avec des exemples : vous lui donnez 2-3 modèles à suivre. Réflexion étape par étape : l\'IA détaille son raisonnement avant de conclure (meilleur pour la logique et les calculs). Par étapes : l\'IA valide chaque étape avec vous avant de continuer.',
                delimiters: 'Les délimiteurs (###) séparent vos instructions de vos données. Utile quand vous analysez un texte spécifique : l\'IA sait où commence le texte à analyser.',
                constraintAntiAI: 'L\'IA a tendance à produire des textes génériques reconnaissables. Cette option force un style plus naturel, varié et authentiquement humain.',
                constraintCanvas: 'Canvas (ChatGPT) et artefact (Claude) sont des espaces de travail dédiés où l\'IA crée du contenu que vous pouvez modifier directement.',
                constraintChainOfThought: 'La chaîne de pensée force l\'IA à montrer son raisonnement, pas juste le résultat. Très utile pour les problèmes complexes, les mathématiques ou la logique.',
                constraintAskIfUnclear: 'Au lieu de deviner, l\'IA vous posera des questions de clarification. Résultat : des réponses beaucoup plus pertinentes dès le premier essai.'
            },
            // Phase 2 (audit 2026-07-26) : micro-explication d'une ligne par option de la section
            // « Comment l'IA doit réfléchir » (labels renommés par usage, pas par jargon). Défini en
            // JS (pas en x-text inline dans le Blade) pour éviter tout conflit d'apostrophes françaises
            // avec la syntaxe des chaînes JS - un objet littéral inline avait cassé Alpine (bug trouvé
            // en vérification visuelle : "L'IA" contient une apostrophe qui ferme la chaîne JS '...').
            // Round 77 : même pattern que `helps` ci-dessus - repli français, valeur réelle via config.
            // LOT 3 (2026-08-06) : nom de la méthode entre parenthèses en tête de chaque texte -
            // seul emplacement possible pour l'afficher en petit gris (voir commentaire à
            // window.promptBuilderConfig.techniqueHints côté Blade, même liste de 8 clés).
            techniqueHints: (window.promptBuilderConfig && window.promptBuilderConfig.techniqueHints) || {
                'zero-shot': "(Méthode : zero-shot) L'IA répond directement, sans exemple ni étape intermédiaire.",
                'zero-shot-cot': "(Méthode : chaîne de pensée) L'IA réfléchit en interne avant de répondre, sans montrer ce raisonnement.",
                'few-shot': "(Méthode : few-shot) Vous donnez 2-3 exemples du résultat attendu pour guider l'IA.",
                'few-shot-cot': "(Méthode : few-shot + chaîne de pensée) Exemples fournis, puis raisonnement détaillé appliqué au même modèle.",
                'iterative': "(Méthode : décomposition guidée) L'IA avance étape par étape et attend votre accord avant de continuer.",
                'reformulation': "(Méthode : reformulation) L'IA reformule d'abord ta demande dans ses mots, puis répond.",
                'auto-verification': "(Méthode : auto-vérification) L'IA relit sa réponse, corrige ses erreurs et ses oublis avant de te la livrer.",
                'variantes-comparees': "(Méthode : variantes comparées) L'IA propose 2 ou 3 versions différentes et recommande la meilleure."
            },
            copied: false,
            // "Améliorer avec mon IA" (Option 3 hybride, 2026-07-26 — validation croisée
            // Codex/Gemini/claude.ai) : AUCUN appel réseau backend, zéro coût serveur (posture
            // BYOA stricte). Le panneau ne fait qu'afficher les mêmes boutons "Ouvrir dans"/
            // "Copier" déjà existants, reparamétrés avec un méta-prompt généré côté client.
            metaPromptShown: false,
            // Correctif #3 (2026-08-05, allègement charge cognitive, club des sages) : l'aperçu et
            // le bloc « Vérifications » sont désormais des disclosures repliées par défaut - l'état
            // vit ici (persistant pendant la session Alpine, jamais en localStorage : pur confort
            // d'affichage, aucun impact sur le prompt généré).
            previewOpen: false,
            checksOpen: false,
            // Correctif #4 (2026-08-05) : étape 4 (Options avancées) est toujours optionnelle, donc
            // "complète" seulement une fois visitée au moins une fois (voir stepComplete() et
            // nextStep()/goToStep() plus bas qui arment ce drapeau).
            step4Visited: false,
            showValidation: false,
            saveName: '',
            saving: false,
            saveError: '',
            isAuthenticated: !!(window.promptBuilderConfig && window.promptBuilderConfig.isAuthenticated),
            hasLocalData: false,
            _editingId: null,
            history: [],
            // Round 63 (2026-07-27) : même garde que customCardsLoaded (round 41) - self.history
            // est écrasé sans merci par le GET initial (voir init()) dès qu'il résout. Sans ce flag,
            // addToHistory()/deletePrompt()/importLocalStorage() pouvaient résoudre AVANT ce GET
            // (ex. ?edit=ID saute directement à l'étape 2, bouton Enregistrer immédiatement
            // cliquable) - l'écho tardif du GET (snapshot antérieur à la mutation) effaçait alors
            // silencieusement le prompt pourtant confirmé sauvegardé de l'écran.
            historyLoaded: false,
            // Round 36 (2026-07-27) : gardes anti double-soumission (double-clic/double-tap) -
            // trouvé par la passe adversariale : sans elles, importLocalStorage() poste chaque
            // item en double (duplication réelle en base) et deletePrompt() renvoie un 404
            // trompeur sur le 2e clic alors que la suppression a déjà réussi.
            importing: false,
            _deletingIds: [],

            // Cartes de démarrage personnalisées (Option D, 2026-07-26) : jusqu'à 10 cartes en
            // plus des 9 cartes système (taskCards, jamais modifiées ici). Connecté = persistées
            // via tool_preferences->custom_cards (même contrôleur que minuteur-visuel) ; invité =
            // localStorage versionné (cp_custom_cards). Import geste explicite au login, jamais
            // de fusion silencieuse (voir importLocalCustomCards).
            customCards: [],
            // Round 41 (2026-07-27) : tant que le GET initial de _loadCustomCards() n'a pas résolu
            // (ou échoué), customCards vaut encore [] - un addCustomCard() déclenché avant cette
            // résolution poussait dans ce tableau vide, puis persistCustomCards() envoyait ce
            // tableau en REMPLACEMENT COMPLET côté serveur (pas une fusion, nécessaire pour que la
            // suppression de carte fonctionne), écrasant silencieusement les cartes déjà
            // sauvegardées. Guette la fin du chargement avant d'autoriser addCustomCard().
            customCardsLoaded: false,
            taskNotice: '',
            _taskNoticeTimer: null,
            // Round 118 (2026-07-27, passe adversariale) : vrai quand le chargement des cartes
            // serveur a échoué - pilote l'avertissement persistant + le bouton « Réessayer ».
            customCardsLoadFailed: false,
            // Round 37 (2026-07-27) : file d'attente sérialisant les appels à persistCustomCards()
            // - sans elle, 2 mutations rapprochées (ex. ajout puis réordonnancement) déclenchent 2
            // POST concurrents ; le serveur fait un lecture-modification-écriture non atomique
            // (ToolPreferenceController::update()) et la réponse arrivée en dernier écrase
            // silencieusement l'autre mutation (perte de donnée, pas juste un doublon visible).
            _cardsPersistQueue: Promise.resolve(),
            editingCardId: null,
            // Round 45 (2026-07-27) : x-model="c.title" est un lien direct (live two-way binding)
            // sur l'objet partagé du tableau customCards - sans ce snapshot, Escape ne pouvait que
            // fermer l'input (editingCardId = null) sans jamais restaurer la valeur pré-édition, et
            // le titre partiel/corrompu tapé restait en mémoire jusqu'à la prochaine mutation
            // quelconque (réordonner, icône, ajout/suppression d'une AUTRE carte), qui l'aurait
            // alors silencieusement persisté via persistCustomCards() (remplacement intégral du tableau).
            editingCardTitleSnapshot: '',
            editingCardPanelId: null,
            // Round 46 (2026-07-27) : même risque que editingCardTitleSnapshot ci-dessus, pour le
            // textarea query_template du panneau d'édition (x-model direct sur l'objet partagé).
            editingCardPanelSnapshot: '',
            iconPickerOpenId: null,
            customCardsImportAvailable: false,
            // Round 97 (2026-07-27, passe adversariale) : garde de ré-entrance sur
            // importLocalCustomCards() - même pattern que `importing` (round 36) - un double-clic
            // sans elle créait de vrais doublons persistés en base (id différent, contenu identique).
            importingCards: false,
            _localCardsToImport: [],
            // Sélecteur d'icônes des cartes de démarrage (enrichi 2026-07-31) : catalogue classé en
            // 12 catégories nommées en français, chaque entrée { c: emoji, m: [mots-clés] } cherchable.
            // Les 30 emojis d'origine (Option D, 2026-07-26) sont TOUS conservés dans ce catalogue -
            // aucune carte déjà enregistrée ne peut perdre son icône. Chaque emoji tient sous 8 octets
            // UTF-8 : ToolPreferenceController::update() tronque l'icône via Str::limit(icon, 8, '') -
            // un emoji composé (jointeur de largeur nulle, modificateur de teinte, drapeau) dépassant
            // cette taille serait corrompu au stockage. Vérifié par un script jetable (Buffer.byteLength)
            // avant livraison ; aucun emoji du catalogue n'a été retiré (les 199 tenaient déjà sous 8 octets).
            iconCatalog: [
                { category: 'Écriture', icons: [
                    { c: '✍️', m: ['ecrire', 'ecriture', 'redaction', 'redige', 'main'] },
                    { c: '📝', m: ['note', 'memo', 'notes', 'rediger'] },
                    { c: '📄', m: ['document', 'page', 'feuille', 'texte'] },
                    { c: '📃', m: ['document', 'feuille', 'page', 'recto'] },
                    { c: '📜', m: ['parchemin', 'texte', 'manuscrit', 'rouleau'] },
                    { c: '✒️', m: ['plume', 'encre', 'ecrire', 'signature'] },
                    { c: '🖊️', m: ['stylo', 'ecrire', 'bille'] },
                    { c: '🖋️', m: ['stylo', 'plume', 'ecrire', 'signature'] },
                    { c: '✏️', m: ['crayon', 'ecrire', 'brouillon', 'esquisse'] },
                    { c: '📓', m: ['carnet', 'cahier', 'notes'] },
                    { c: '📔', m: ['carnet', 'journal', 'cahier'] },
                    { c: '📒', m: ['registre', 'cahier', 'comptes'] },
                    { c: '🗒️', m: ['bloc-notes', 'notes', 'memo'] },
                    { c: '🗞️', m: ['journal', 'actualites', 'presse'] },
                    { c: '📰', m: ['journal', 'presse', 'nouvelles', 'actualites'] },
                    { c: '🔤', m: ['alphabet', 'lettres', 'texte', 'langue'] }
                ] },
                { category: 'Analyse et données', icons: [
                    { c: '🔍', m: ['recherche', 'loupe', 'analyser', 'chercher'] },
                    { c: '🔎', m: ['recherche', 'loupe', 'examiner'] },
                    { c: '📊', m: ['graphique', 'statistiques', 'donnees', 'barres'] },
                    { c: '📈', m: ['tendance', 'hausse', 'croissance', 'graphique'] },
                    { c: '📉', m: ['tendance', 'baisse', 'declin', 'graphique'] },
                    { c: '💡', m: ['idee', 'ampoule', 'inspiration', 'eureka'] },
                    { c: '🧪', m: ['experience', 'test', 'laboratoire', 'science'] },
                    { c: '🔬', m: ['microscope', 'science', 'laboratoire', 'recherche'] },
                    { c: '🧮', m: ['calcul', 'calculatrice', 'comptage', 'abaque'] },
                    { c: '🗃️', m: ['archives', 'classement', 'fiches', 'boite'] },
                    { c: '🗄️', m: ['classeur', 'archives', 'dossiers', 'rangement'] },
                    { c: '📇', m: ['fiches', 'contacts', 'rolodex'] },
                    { c: '🧭', m: ['boussole', 'orientation', 'direction', 'exploration'] },
                    { c: '🔢', m: ['chiffres', 'nombres', 'numeros'] },
                    { c: '➗', m: ['division', 'calcul', 'mathematiques'] },
                    { c: '➕', m: ['addition', 'plus', 'calcul', 'ajouter'] },
                    { c: '➖', m: ['soustraction', 'moins', 'calcul', 'retirer'] },
                    { c: '✖️', m: ['multiplication', 'fois', 'calcul'] }
                ] },
                { category: 'Apprentissage', icons: [
                    { c: '🎓', m: ['diplome', 'graduation', 'etude', 'universite'] },
                    { c: '📚', m: ['livres', 'bibliotheque', 'etude', 'lecture'] },
                    { c: '📖', m: ['livre', 'lecture', 'etude', 'ouvert'] },
                    { c: '📗', m: ['livre', 'manuel', 'vert'] },
                    { c: '📘', m: ['livre', 'manuel', 'bleu'] },
                    { c: '📙', m: ['livre', 'manuel', 'orange'] },
                    { c: '📕', m: ['livre', 'manuel', 'rouge'] },
                    { c: '🏫', m: ['ecole', 'etablissement', 'classe'] },
                    { c: '🧠', m: ['cerveau', 'intelligence', 'reflexion', 'memoire'] },
                    { c: '🎒', m: ['sac a dos', 'rentree', 'ecolier'] },
                    { c: '🔖', m: ['signet', 'reference', 'marque-page'] },
                    { c: '📑', m: ['onglets', 'reference', 'classement'] },
                    { c: '🏛️', m: ['universite', 'institution', 'savoir', 'academie'] },
                    { c: '🌱', m: ['croissance', 'apprentissage', 'developpement', 'debutant'] }
                ] },
                { category: 'Communication', icons: [
                    { c: '📣', m: ['annonce', 'megaphone', 'promouvoir', 'communiquer'] },
                    { c: '💬', m: ['discussion', 'message', 'bulle', 'chat'] },
                    { c: '🗨️', m: ['discussion', 'bulle', 'conversation'] },
                    { c: '🗯️', m: ['reaction', 'colere', 'exclamation'] },
                    { c: '🗣️', m: ['parler', 'discours', 'prise de parole'] },
                    { c: '📢', m: ['annonce', 'alerte', 'haut-parleur'] },
                    { c: '📧', m: ['courriel', 'email', 'message'] },
                    { c: '✉️', m: ['lettre', 'courrier', 'enveloppe'] },
                    { c: '📨', m: ['message recu', 'courriel', 'enveloppe'] },
                    { c: '📩', m: ['message envoye', 'courriel', 'enveloppe'] },
                    { c: '📤', m: ['envoi', 'sortant', 'partager'] },
                    { c: '📥', m: ['reception', 'entrant', 'recevoir'] },
                    { c: '☎️', m: ['telephone', 'appel', 'contact'] },
                    { c: '📞', m: ['appel', 'telephone', 'contact'] },
                    { c: '📱', m: ['cellulaire', 'telephone', 'mobile'] },
                    { c: '🔔', m: ['notification', 'alerte', 'cloche', 'rappel'] },
                    { c: '🔕', m: ['silence', 'desactive', 'muet'] },
                    { c: '🧵', m: ['fil', 'discussion', 'sujet', 'thread'] },
                    { c: '📡', m: ['diffusion', 'signal', 'reseau', 'antenne'] }
                ] },
                { category: 'Travail et organisation', icons: [
                    { c: '🗂️', m: ['classeur', 'dossiers', 'organisation', 'classement'] },
                    { c: '🎯', m: ['objectif', 'cible', 'but', 'viser'] },
                    { c: '📌', m: ['epingle', 'important', 'marquer', 'punaise'] },
                    { c: '📋', m: ['liste', 'taches', 'presse-papiers'] },
                    { c: '🧰', m: ['boite a outils', 'materiel', 'equipement'] },
                    { c: '🗑️', m: ['corbeille', 'supprimer', 'effacer'] },
                    { c: '📎', m: ['trombone', 'attache', 'joindre'] },
                    { c: '🖇️', m: ['lien', 'attache', 'trombones'] },
                    { c: '✂️', m: ['couper', 'editer', 'ciseaux'] },
                    { c: '🗝️', m: ['cle', 'acces', 'ancienne'] },
                    { c: '🔑', m: ['cle', 'acces', 'mot de passe'] },
                    { c: '🔒', m: ['verrouille', 'securite', 'prive'] },
                    { c: '🔓', m: ['deverrouille', 'ouvert', 'accessible'] },
                    { c: '📁', m: ['dossier', 'classement', 'fichiers'] },
                    { c: '📂', m: ['dossier ouvert', 'fichiers', 'classement'] },
                    { c: '☑️', m: ['case cochee', 'tache faite', 'complete'] }
                ] },
                { category: 'Technique et code', icons: [
                    { c: '💻', m: ['ordinateur', 'portable', 'developpement', 'code'] },
                    { c: '🖥️', m: ['ordinateur', 'poste de travail', 'ecran'] },
                    { c: '⌨️', m: ['clavier', 'saisie', 'frappe'] },
                    { c: '🖱️', m: ['souris', 'clic', 'pointeur'] },
                    { c: '🧩', m: ['puzzle', 'module', 'integration', 'piece'] },
                    { c: '🤖', m: ['robot', 'intelligence artificielle', 'automatisation', 'ia'] },
                    { c: '🌐', m: ['web', 'internet', 'reseau', 'site', 'mondial'] },
                    { c: '🛠️', m: ['outils', 'developpement', 'reparation', 'technique'] },
                    { c: '🔧', m: ['cle', 'outil', 'reparer', 'configurer'] },
                    { c: '⚙️', m: ['parametres', 'engrenage', 'configuration', 'reglages'] },
                    { c: '🔩', m: ['assemblage', 'technique', 'boulon'] },
                    { c: '🖨️', m: ['imprimante', 'impression'] },
                    { c: '💾', m: ['sauvegarde', 'disquette', 'enregistrer'] },
                    { c: '💿', m: ['disque', 'cd', 'stockage'] },
                    { c: '📀', m: ['disque', 'dvd', 'stockage'] },
                    { c: '🔌', m: ['branchement', 'connexion', 'prise'] },
                    { c: '🔋', m: ['batterie', 'energie', 'charge'] },
                    { c: '🛰️', m: ['satellite', 'technologie', 'orbite'] },
                    { c: '🕹️', m: ['manette', 'jeu', 'controle'] },
                    { c: '📶', m: ['signal', 'reseau', 'antenne', 'connexion'] },
                    { c: '🛡️', m: ['securite', 'protection', 'bouclier'] },
                    { c: '🔐', m: ['securite', 'chiffrement', 'verrouille'] }
                ] },
                { category: 'Création et design', icons: [
                    { c: '🎨', m: ['palette', 'art', 'design', 'creativite'] },
                    { c: '📐', m: ['geometrie', 'plan', 'equerre', 'precision'] },
                    { c: '📏', m: ['regle', 'mesure', 'precision'] },
                    { c: '🖌️', m: ['pinceau', 'peindre', 'art'] },
                    { c: '🖍️', m: ['crayon de cire', 'couleur', 'dessin'] },
                    { c: '🎭', m: ['theatre', 'performance', 'art', 'masques'] },
                    { c: '🎬', m: ['cinema', 'video', 'production', 'tournage'] },
                    { c: '📷', m: ['photo', 'image', 'appareil'] },
                    { c: '📸', m: ['photo', 'flash', 'appareil'] },
                    { c: '🎥', m: ['video', 'tournage', 'cinema'] },
                    { c: '🖼️', m: ['cadre', 'image', 'tableau', 'illustration'] },
                    { c: '🎼', m: ['musique', 'partition', 'composition'] },
                    { c: '🎵', m: ['musique', 'note', 'melodie'] },
                    { c: '🎶', m: ['musique', 'notes', 'melodie'] },
                    { c: '🎹', m: ['piano', 'musique', 'clavier'] },
                    { c: '🎸', m: ['guitare', 'musique'] },
                    { c: '🌈', m: ['couleurs', 'creativite', 'arc-en-ciel'] },
                    { c: '✨', m: ['etincelles', 'magie', 'nouveaute', 'effet'] },
                    { c: '💎', m: ['precieux', 'qualite', 'valeur', 'diamant'] }
                ] },
                { category: 'Santé', icons: [
                    { c: '🩺', m: ['medecine', 'sante', 'diagnostic', 'stethoscope'] },
                    { c: '💊', m: ['medicament', 'pilule', 'traitement'] },
                    { c: '🏥', m: ['hopital', 'clinique', 'soins'] },
                    { c: '🧬', m: ['genetique', 'biologie', 'adn'] },
                    { c: '🦷', m: ['dentaire', 'dent', 'soins'] },
                    { c: '🩹', m: ['premiers soins', 'pansement', 'blessure'] },
                    { c: '🌡️', m: ['temperature', 'fievre', 'thermometre'] },
                    { c: '🧴', m: ['soins', 'hygiene', 'lotion'] },
                    { c: '🧘', m: ['meditation', 'bien-etre', 'relaxation', 'calme'] },
                    { c: '💪', m: ['force', 'forme', 'sport', 'muscle'] },
                    { c: '🏃', m: ['course', 'sport', 'activite', 'courir'] },
                    { c: '🥗', m: ['alimentation', 'nutrition', 'sante', 'salade'] },
                    { c: '💤', m: ['sommeil', 'repos', 'fatigue'] },
                    { c: '❤️', m: ['coeur', 'sante', 'bien-etre', 'amour'] }
                ] },
                { category: 'Commerce', icons: [
                    { c: '🧾', m: ['recu', 'facture', 'ticket', 'depense'] },
                    { c: '💰', m: ['argent', 'budget', 'sac'] },
                    { c: '💵', m: ['argent', 'billet', 'prix'] },
                    { c: '💳', m: ['carte', 'paiement', 'achat'] },
                    { c: '🛒', m: ['achats', 'panier', 'magasinage'] },
                    { c: '🛍️', m: ['sacs', 'achats', 'boutique'] },
                    { c: '🏪', m: ['commerce', 'boutique', 'depanneur'] },
                    { c: '🏬', m: ['magasin', 'commerce', 'grand magasin'] },
                    { c: '📦', m: ['colis', 'livraison', 'produit', 'boite'] },
                    { c: '🚚', m: ['livraison', 'transport', 'camion'] },
                    { c: '💹', m: ['marche', 'bourse', 'croissance'] },
                    { c: '🤝', m: ['partenariat', 'accord', 'entente', 'poignee de main'] },
                    { c: '💼', m: ['affaires', 'travail', 'entreprise', 'mallette'] },
                    { c: '🏷️', m: ['etiquette', 'prix', 'promotion', 'rabais'] }
                ] },
                { category: 'Lieux et voyage', icons: [
                    { c: '🌍', m: ['monde', 'planete', 'international', 'globe'] },
                    { c: '🗺️', m: ['carte', 'geographie', 'itineraire'] },
                    { c: '📍', m: ['localisation', 'endroit', 'position', 'marqueur'] },
                    { c: '🏠', m: ['maison', 'domicile', 'residence'] },
                    { c: '🏢', m: ['bureau', 'entreprise', 'immeuble'] },
                    { c: '✈️', m: ['avion', 'voyage', 'vol'] },
                    { c: '🚗', m: ['voiture', 'deplacement', 'conduite'] },
                    { c: '🚆', m: ['train', 'transport', 'rail'] },
                    { c: '🚢', m: ['bateau', 'transport', 'navire'] },
                    { c: '🏔️', m: ['montagne', 'nature', 'sommet'] },
                    { c: '🏙️', m: ['ville', 'urbain', 'gratte-ciel'] },
                    { c: '🌆', m: ['ville', 'urbain', 'crepuscule'] },
                    { c: '🏞️', m: ['paysage', 'nature', 'parc'] },
                    { c: '🧳', m: ['valise', 'voyage', 'bagage'] }
                ] },
                { category: 'Temps et planification', icons: [
                    { c: '📅', m: ['calendrier', 'date', 'agenda', 'planification'] },
                    { c: '🗓️', m: ['agenda', 'planification', 'calendrier'] },
                    { c: '⏰', m: ['reveil', 'alarme', 'heure'] },
                    { c: '⏱️', m: ['chronometre', 'minuterie', 'temps'] },
                    { c: '⏲️', m: ['minuteur', 'compte a rebours'] },
                    { c: '⌛', m: ['sablier', 'attente', 'delai'] },
                    { c: '⏳', m: ['temps qui passe', 'en cours', 'patience'] },
                    { c: '🕐', m: ['heure', 'horloge', 'temps'] },
                    { c: '📆', m: ['date', 'calendrier', 'jour'] },
                    { c: '⏸️', m: ['pause', 'en attente', 'arret temporaire'] },
                    { c: '▶️', m: ['demarrer', 'lancer', 'jouer'] },
                    { c: '⏹️', m: ['arreter', 'fin', 'stop'] },
                    { c: '🔁', m: ['repetition', 'cycle', 'recurrence'] },
                    { c: '🔄', m: ['actualiser', 'rafraichir', 'mise a jour'] }
                ] },
                { category: 'Symboles et statuts', icons: [
                    { c: '✅', m: ['fait', 'coche', 'valide', 'termine'] },
                    { c: '✔️', m: ['valide', 'correct', 'coche'] },
                    { c: '❌', m: ['erreur', 'refuse', 'incorrect'] },
                    { c: '⚠️', m: ['attention', 'avertissement', 'prudence'] },
                    { c: '❗', m: ['important', 'urgent', 'exclamation'] },
                    { c: '❓', m: ['question', 'inconnu', 'interrogation'] },
                    { c: 'ℹ️', m: ['information', 'renseignement', 'aide'] },
                    { c: '⭐', m: ['favori', 'important', 'qualite', 'etoile'] },
                    { c: '🌟', m: ['excellence', 'remarquable', 'brillant'] },
                    { c: '🔥', m: ['tendance', 'populaire', 'urgent', 'feu'] },
                    { c: '💯', m: ['parfait', 'excellent', 'complet', 'cent'] },
                    { c: '🏆', m: ['victoire', 'reussite', 'recompense', 'trophee'] },
                    { c: '🥇', m: ['premier', 'gagnant', 'medaille'] },
                    { c: '🎉', m: ['celebration', 'succes', 'felicitations'] },
                    { c: '🚀', m: ['lancement', 'croissance', 'rapide', 'decollage'] },
                    { c: '🆕', m: ['nouveau', 'recent'] },
                    { c: '🔴', m: ['urgent', 'actif', 'alerte', 'rouge'] },
                    { c: '🟢', m: ['actif', 'succes', 'disponible', 'vert'] },
                    { c: '🟡', m: ['attention', 'en attente', 'jaune'] }
                ] }
            ],
            iconSearchQuery: '',

            // Normalisation factorisée (recherche insensible aux accents ET à la casse) - NFD +
            // retrait des diacritiques, appliquée des DEUX côtés de la comparaison (mot-clé ET saisie).
            _normalizeIconText: function(str) {
                return (str || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            },

            // Regroupé par catégorie quand aucune recherche n'est active ; à plat (une seule
            // « catégorie » sans titre) dès qu'une recherche filtre les résultats.
            get iconSearchGroups() {
                var q = this._normalizeIconText(this.iconSearchQuery);
                if (!q) return this.iconCatalog;
                var flat = [];
                for (var i = 0; i < this.iconCatalog.length; i++) {
                    var icons = this.iconCatalog[i].icons;
                    for (var j = 0; j < icons.length; j++) {
                        var icon = icons[j];
                        for (var k = 0; k < icon.m.length; k++) {
                            if (this._normalizeIconText(icon.m[k]).indexOf(q) !== -1) { flat.push(icon); break; }
                        }
                    }
                }
                return [{ category: null, icons: flat }];
            },

            get iconSearchResultsCount() {
                var groups = this.iconSearchGroups;
                var n = 0;
                for (var i = 0; i < groups.length; i++) n += groups[i].icons.length;
                return n;
            },

            // Zone aria-live polie : ne parle QUE pendant une recherche active (silencieuse à
            // l'ouverture du sélecteur, pour ne pas annoncer inutilement ~200 icônes groupées).
            get iconResultsAnnouncement() {
                if (!this.iconSearchQuery) return '';
                var i18nIcon = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var n = this.iconSearchResultsCount;
                if (n === 0) return i18nIcon.iconSearchEmpty || 'Aucune icône ne correspond à cette recherche.';
                var template = n === 1
                    ? (i18nIcon.iconSearchResultOne || '1 icône trouvée')
                    : (i18nIcon.iconSearchResultMany || '{count} icônes trouvées');
                return template.replace('{count}', n);
            },

            iconAriaLabel: function(icon) {
                var i18nIcon = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var label = icon.m && icon.m[0] ? icon.m[0] : icon.c;
                label = label.charAt(0).toUpperCase() + label.slice(1);
                return (i18nIcon.iconLabelPrefix || 'Icône : ') + label;
            },

            // Navigation clavier réelle dans la grille d'icônes (WCAG 2.2 AAA) : les flèches
            // déplacent le focus (Haut/Bas sautent d'une colonne entière selon la largeur d'écran
            // réelle - 5 colonnes en bureau, 4 en mobile, cohérent avec .ct-emoji-grid), Début/Fin
            // vont au premier/dernier bouton visible. Entrée/Espace restent gérés nativement par le
            // <button> HTML ; Échap reste géré par @keydown.escape.window="closeIconPicker()" déjà
            // branché sur le conteneur racine.
            handleIconGridKeydown: function(event, cardId) {
                var key = event.key;
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(key) === -1) return;
                var grid = document.getElementById('cpEmojiGrid-' + cardId);
                if (!grid) return;
                var buttons = Array.prototype.slice.call(grid.querySelectorAll('button[data-icon-idx]'));
                if (buttons.length === 0) return;
                event.preventDefault();
                var current = buttons.indexOf(document.activeElement);
                if (current === -1) current = 0;
                var cols = window.matchMedia('(max-width: 575.98px)').matches ? 4 : 5;
                var next = current;
                if (key === 'ArrowRight') next = Math.min(current + 1, buttons.length - 1);
                else if (key === 'ArrowLeft') next = Math.max(current - 1, 0);
                else if (key === 'ArrowDown') next = Math.min(current + cols, buttons.length - 1);
                else if (key === 'ArrowUp') next = Math.max(current - cols, 0);
                else if (key === 'Home') next = 0;
                else if (key === 'End') next = buttons.length - 1;
                buttons[next].focus();
            },

            // === Format de sortie multi-sélection (LOT 1, 2026-08-06) ===
            // formatExclusiveActive : la valeur EXCLUSIVE (Format JSON / Diagramme Mermaid)
            // actuellement sélectionnée, ou null. Le garde-fou d'exclusivité ne peut jamais
            // sélectionner les DEUX exclusifs à la fois (handleFormatChange les réduit à 1 seul).
            get formatExclusiveActive() {
                for (var i = 0; i < this.formatsSelected.length; i++) {
                    if (this._formatExclusiveValues.indexOf(this.formatsSelected[i]) !== -1) return this.formatsSelected[i];
                }
                return null;
            },
            // isFormatDisabled : une carte déjà cochée reste TOUJOURS cliquable (pour la
            // décocher) - seules les cartes NON cochées peuvent devenir indisponibles, soit
            // parce qu'un format exclusif est actif, soit parce que le maximum de 3 est atteint.
            isFormatDisabled: function (value) {
                if (this.formatsSelected.indexOf(value) !== -1) return false;
                if (this.formatExclusiveActive) return true;
                if (this.formatsSelected.length >= 3) return true;
                return false;
            },
            formatDisabledReason: function (value) {
                if (!this.isFormatDisabled(value)) return '';
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                if (this.formatExclusiveActive) return i18n.formatExclusiveReason || 'Ce format technique doit être utilisé seul.';
                return i18n.formatMaxReason || 'Maximum 3 formats.';
            },
            // handleFormatChange : appelé APRÈS qu'Alpine ait déjà ajouté/retiré `value` de
            // formatsSelected (x-model natif sur un tableau de cases à cocher). Si la carte qui
            // vient d'être cochée est un format exclusif (JSON/Mermaid), on réduit la sélection à
            // elle seule - les autres cartes, déjà cochées avant ce clic, sont retirées.
            handleFormatChange: function (value) {
                if (this._formatExclusiveValues.indexOf(value) !== -1 && this.formatsSelected.indexOf(value) !== -1) {
                    this.formatsSelected = [value];
                }
            },
            // formatSelectionAll / formatText : représentation texte plate (formats prédéfinis +
            // format personnalisé) utilisée par feedbackResultat et promptSummary - jamais par le
            // générateur de prompt final (get prompt()), qui a sa propre logique de formulation
            // (voir formatBulletText plus bas).
            get formatSelectionAll() {
                var arr = this.formatsSelected.slice();
                if (this.formatCustom) arr.push(this.formatCustom);
                return arr;
            },
            get formatText() {
                return this.formatSelectionAll.join(', ');
            },
            // formatBulletText : la ligne « Structure : ... » du prompt final. Un seul format
            // (prédéfini ou personnalisé seul) → comportement inchangé depuis l'origine de cette
            // fonctionnalité. Plusieurs formats de STRUCTURE (liste à puces, paragraphes, tableau,
            // plan, étapes numérotées) → une structure principale + des compléments. Plusieurs
            // LIVRABLES (questionnaire, grille, fiche, gabarit, FAQ) → une séquence numérotée.
            // Mélange structure + livrable(s) → le ou les livrables deviennent le format
            // principal, la structure devient une précision « à l'intérieur ». formatCustom
            // s'ajoute toujours en dernier, quelle que soit la composition ci-dessus.
            get formatBulletText() {
                var structs = [], delivs = [], exclusive = null;
                for (var i = 0; i < this.formatsSelected.length; i++) {
                    var v = this.formatsSelected[i];
                    if (this._formatExclusiveValues.indexOf(v) !== -1) exclusive = v;
                    else if (this._formatStructureValues.indexOf(v) !== -1) structs.push(v);
                    else delivs.push(v);
                }
                var predefinedCount = structs.length + delivs.length + (exclusive ? 1 : 0);
                var numbered = function (list) { return list.map(function (item, idx) { return (idx + 1) + ') ' + item; }).join(', '); };
                var main = '';
                var usePrefix = false;
                if (predefinedCount === 0) {
                    usePrefix = true;
                } else if (predefinedCount === 1) {
                    main = exclusive || structs[0] || delivs[0];
                    usePrefix = true;
                } else if (delivs.length === 0) {
                    main = 'Structure principale : ' + structs[0] + '. En complément, intègre : ' + structs.slice(1).join(', ') + '.';
                } else if (structs.length === 0) {
                    main = 'Produis successivement : ' + numbered(delivs) + '.';
                } else {
                    var livrablePart = delivs.length === 1 ? delivs[0] : ('Produis successivement : ' + numbered(delivs));
                    main = livrablePart + '. à l\'intérieur, utilise ' + structs.join(', ') + '.';
                }
                if (this.formatCustom) {
                    main = main ? (main + (/[.!?]$/.test(main) ? '' : '.') + ' ' + this.formatCustom) : this.formatCustom;
                }
                if (!main) return '';
                return usePrefix ? ('Structure : ' + main) : main;
            },

            get isValid() {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;
            },

            get personaText() {
                if (this.personaType === 'custom' && this.personaCustom) return this.personaCustom;
                if (this.personaType === 'preset' && this.personaPreset) {
                    for (var i = 0; i < this.personas.length; i++) {
                        if (this.personas[i].value === this.personaPreset) return this.personas[i].label;
                    }
                }
                return '';
            },

            get audienceText() {
                // Round 151 (2026-08-01) : la branche `audienceType === 'none'` a été retirée - le
                // formulaire n'expose que 'preset'/'custom' (radio, voir cpAudienceBlock), aucun
                // contrôle ne peut plus jamais produire 'none'. Code mort confirmé, zéro effet retiré.
                if (this.audienceType === 'custom' && this.audienceCustom) return this.audienceCustom;
                if (this.audienceType === 'preset' && this.audiencePresets.length > 0) {
                    var selectedLabels = [];
                    for (var i = 0; i < this.audiences.length; i++) {
                        if (this.audiencePresets.includes(this.audiences[i].value)) selectedLabels.push(this.audiences[i].label);
                    }
                    if (selectedLabels.length === 1) return selectedLabels[0];
                    if (selectedLabels.length === 2) return selectedLabels.join(' et ');
                    if (selectedLabels.length >= 3) { var last = selectedLabels.pop(); return selectedLabels.join(', ') + ' et ' + last; }
                }
                return '';
            },

            // Round 152 (2026-08-01) : petit doublon volontaire de la ligne déjà répétée dans
            // promptSummary/promptSegments (this.verbType === 'custom' ? this.verbCustom : this.verb)
            // - un getter partagé aurait fallu toucher promptSegments (source unique du texte
            // réellement copié, protégée par des dizaines de rounds adversariaux) pour un gain
            // cosmétique. Utilisé seulement par les 4 lignes « Ajouté : » ci-dessous.
            get verbText() {
                return this.verbType === 'custom' ? this.verbCustom : this.verb;
            },

            // === Lignes « Ajouté : ... » (écran 3, round 152) ===
            // La version utile de l'aperçu : au lieu de réafficher tout le prompt en boucle, chaque
            // bloc explique en une phrase ce que le DERNIER choix vient de produire. Lecture seule -
            // ne touchent jamais promptSegments (source unique déjà établie au round 151).
            get feedbackAudience() {
                if (!this.audienceText) return '';
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var txt = this.audienceText.charAt(0).toLowerCase() + this.audienceText.slice(1);
                return (i18n.addedAudience || 'Ajouté : niveau de langage adapté à ') + txt + '.';
            },
            get feedbackResultat() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                if (this.verbText) parts.push((i18n.fragVerb || 'verbe ') + '« ' + this.verbText + ' »');
                if (this.formatText) parts.push((i18n.fragFormat || 'format ') + this.formatText.toLowerCase());
                if (this.length) parts.push((i18n.fragLength || 'longueur ') + this.length.toLowerCase());
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Ajouté : ') + parts.join(', ') + '.';
            },
            get feedbackTon() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                if (this.personaText) parts.push((i18n.fragRole || 'rôle ') + '« ' + this.personaText + ' »');
                if (this.tone) parts.push((i18n.fragTone || 'ton ') + this.tone.toLowerCase());
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Ajouté : ') + parts.join(', ') + '.';
            },
            // Round 152 (2026-08-01) : même condition EXACTE que `stylistRulesApply` dans
            // get promptSegments() (variable locale à cette autre fonction, donc dupliquée ici en
            // toute petite ligne plutôt que de complexifier une signature déjà chargée). Sans ce
            // garde-fou, la ligne « Ajouté : écriture naturelle anti-IA » mentait quand Cadre strict
            // était désactivé ou que le profil Programmation/Traduction supprimait réellement la
            // règle du prompt final - trouvé en vérification visuelle (case cochée par défaut,
            // ligne affichée, mais absente de l'aperçu colorisé une fois le profil changé).
            get _stylistRulesApply() {
                return this.cadreStrict && this.profile !== 'programmation' && this.profile !== 'traduction';
            },
            get feedbackLimites() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                if (this.constraintTypo && this._stylistRulesApply) parts.push(i18n.fragTypo || 'typographie française stricte');
                if (this.constraintAntiAI && this._stylistRulesApply) parts.push(i18n.fragAntiAI || 'écriture naturelle anti-IA');
                if (this.constraintChainOfThought) parts.push(i18n.fragCot || 'raisonnement affiché');
                if (this.constraintAskIfUnclear) parts.push(i18n.fragAsk || 'questions de clarification si besoin');
                if (this.constraintCanvas) parts.push((i18n.fragCanvas || 'document modifiable') + ' (' + this.canvasAI + ')');
                if (this.language === 'en') parts.push(i18n.fragLangEn || 'réponse en anglais');
                if (this.language === 'es') parts.push(i18n.fragLangEs || 'réponse en espagnol');
                if (this.constraintCustom) parts.push(i18n.fragCustom || 'vos contraintes personnalisées');
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Ajouté : ') + parts.join(', ') + '.';
            },
            get feedbackModele() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                var techniqueFrags = {
                    'zero-shot-cot': i18n.fragZeroShotCot || 'réflexion visible',
                    'few-shot': i18n.fragFewShot || 'des exemples',
                    'few-shot-cot': i18n.fragFewShotCot || 'des exemples et une réflexion visible',
                    'iterative': i18n.fragIterative || 'une validation à chaque étape',
                    // LOT 3 (2026-08-06) : 3 nouvelles méthodes.
                    'reformulation': i18n.fragReformulation || 'une reformulation de la demande',
                    'auto-verification': i18n.fragAutoVerification || 'une vérification finale',
                    'variantes-comparees': i18n.fragVariantesComparees || 'plusieurs versions comparées'
                };
                if (techniqueFrags[this.technique]) parts.push(techniqueFrags[this.technique]);
                if (this.useDelimiters) parts.push(i18n.fragDelimiters || 'délimiteurs ###');
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Ajouté : ') + parts.join(', ') + '.';
            },
            get feedbackProfile() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                if (this.profile === 'programmation') {
                    return i18n.profileFeedbackProgrammation || "Vous avez choisi Programmation : j'ajoute les règles de mise en forme du code, je retire les règles de français du Québec.";
                }
                if (this.profile === 'traduction') {
                    return i18n.profileFeedbackTraduction || 'Vous avez choisi Traduction : je retire les règles de français du Québec du résultat.';
                }
                return i18n.profileFeedbackTexte || "Vous avez choisi Texte : les règles d'écriture humaine et de typographie s'appliquent selon vos cases cochées.";
            },

            get selectedTaskLabel() {
                for (var i = 0; i < this.taskCards.length; i++) {
                    if (this.taskCards[i].id === this.selectedTask) return this.taskCards[i].label;
                }
                for (var j = 0; j < this.customCards.length; j++) {
                    if (this.customCards[j].id === this.selectedTask) return this.customCards[j].title;
                }
                // Round 127 (2026-07-30, passe adversariale) : référence pendante. Supprimer une
                // carte de démarrage personnalisée ne touche PAS les prompts déjà enregistrés qui
                // la référencent (deleteCustomCard ne nettoie que la session en cours), et la
                // restauration via ?edit=ID recopie l'identifiant stocké sans le valider. Le badge
                // « Objectif choisi : » restait donc affiché avec un contenu vide.
                //
                // La garde sur customCardsLoaded est le point CRITIQUE : les cartes arrivent par un
                // appel réseau, donc sans elle un identifiant parfaitement valide serait déclaré
                // supprimé pendant le chargement, puis se corrigerait tout seul - un clignotement
                // qui accuserait à tort. Ce drapeau reste aussi à faux si le chargement a ÉCHOUÉ
                // (round 118), cas où un avertissement est déjà affiché : mieux vaut se taire que
                // d'annoncer une suppression qui n'a pas eu lieu.
                if (!this.customCardsLoaded) return '';

                var i18nLabel = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};

                return i18nLabel.taskCardDeleted || 'Objectif supprimé';
            },

            // Round 153 (2026-08-01, trouvé en preuve navigateur) : le générateur préfixait la
            // tâche par le verbe d'action sans regarder si la personne avait elle-même commencé
            // sa demande par ce verbe. Résultat visible dans le prompt : « Ta tâche : Rédige
            // rédige un courriel ». On compare MOT À MOT et non par index de caractères : la
            // normalisation NFD change le nombre d'unités de code, donc toute découpe
            // positionnelle serait fausse sur un texte saisi en forme décomposée.
            _taskWithoutLeadingVerb: function (verb, task) {
                // Round 156 (2026-08-03, simulation E2E) : tous les appelants concatènent un
                // '.' littéral juste après ce retour (ex. "Ta tâche : Rédige " + retour + ".").
                // Si la demande de l'utilisateur se terminait déjà par un point, le résultat
                // affichait « ...au Québec.. » (point double). On retire donc UN SEUL point
                // final ici, au point de sortie commun aux deux branches, plutôt que de dupliquer
                // ce nettoyage dans chaque appelant.
                var stripOneTrailingPeriod = function (s) {
                    return String(s).replace(/\.$/, '');
                };

                if (!verb || !task) return stripOneTrailingPeriod(task);

                var normalize = function (s) {
                    return String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
                };
                var stripTrailingPunct = function (word) {
                    return String(word).replace(/[,:.!?;]+$/, '');
                };

                var verbNorm = normalize(stripTrailingPunct(String(verb).trim()));
                var trimmed = String(task).replace(/^\s+/, '');
                var words = trimmed.split(/\s+/);
                var firstWordNorm = normalize(stripTrailingPunct(words[0]));

                // words.length > 1 : si la demande se réduit AU verbe seul, on la laisse
                // intacte, sinon la tâche perdrait son objet et le prompt serait vide de sens.
                if (verbNorm !== '' && firstWordNorm === verbNorm && words.length > 1) {
                    // On découpe la chaîne D'ORIGINE (jamais la version normalisée) après le
                    // premier mot, et on ne retire ensuite que les espaces et tabulations. Un
                    // simple words.slice(1).join(' ') écraserait les retours à la ligne d'une
                    // demande multiligne et détruirait sa mise en forme.
                    // Le séparateur immédiat après le verbe est absorbé, y compris s'il
                    // s'agit d'UN seul retour à la ligne ; les sauts de ligne suivants, eux,
                    // font partie de la mise en forme voulue et sont conservés tels quels.
                    return stripOneTrailingPeriod(trimmed.slice(words[0].length).replace(/^[ \t]*\n?[ \t]*/, ''));
                }

                return stripOneTrailingPeriod(task);
            },

            // Aperçu en langage courant (Phase 2) : composé à partir des MÊMES données que le
            // générateur de prompt ci-dessous, sans dupliquer ni modifier sa logique d'assemblage.
            get promptSummary() {
                var i18nSummary = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                var actionVerb = this.verbType === 'custom' ? this.verbCustom : this.verb;
                if (this.personaText) {
                    var defaultArticle = /^\s*(un |une |des |le |la |l'|d'|du |de )/i.test(this.personaText) ? '' : (i18nSummary.summaryRoleArticle !== undefined ? i18nSummary.summaryRoleArticle : 'un(e) ');
                    parts.push((i18nSummary.summaryRole || 'L\'IA va se comporter comme ') + defaultArticle + this.personaText.charAt(0).toLowerCase() + this.personaText.slice(1) + '.');
                }
                if (actionVerb && this.taskObject) {
                    // Round 156 (2026-08-03, simulation E2E) : "Elle va " + verbe déjà à
                    // l'impératif (ex. "Rédige") donnait "Elle va rédige..." - faute d'accord.
                    // Les verbes proposés sont à l'impératif partout ailleurs dans l'outil (ex.
                    // "Ta tâche : Rédige ..." dans le prompt reel) - on garde donc cette forme au
                    // lieu de tenter une conjugaison a l'infinitif (peu fiable sur un verbe
                    // personnalise saisi librement par l'utilisateur, actionVerbIsUser).
                    parts.push((i18nSummary.summaryAction || 'Tâche demandée : ') + actionVerb + ' ' + this._taskWithoutLeadingVerb(actionVerb, this.taskObject) + '.');
                } else if (this.taskObject) {
                    parts.push((i18nSummary.summarySubject || 'Sujet : ') + this.taskObject + '.');
                }
                if (this.audienceText) parts.push((i18nSummary.summaryAudience || 'Le résultat sera adapté pour : ') + this.audienceText + '.');
                if (this.tone) parts.push((i18nSummary.summaryTone || 'Ton : ') + this.tone + '.');
                if (this.formatText) parts.push((i18nSummary.summaryFormat || 'Présenté sous forme de : ') + this.formatText.toLowerCase() + '.');
                if (this.length) parts.push((i18nSummary.summaryLength || 'Longueur visée : ') + this.length.toLowerCase() + '.');
                if (!parts.length) return '';
                return parts.join(' ');
            },

            // Round 151 (2026-08-01, écran 2 « Votre prompt est prêt ») : SOURCE UNIQUE de
            // l'assemblage du prompt. `get prompt()` (texte brut, utilisé partout ailleurs : copy(),
            // openIn(), export, sauvegarde) est dérivé de `get promptSegments()` par simple
            // concaténation - jamais l'inverse - pour garantir un texte final BYTE POUR BYTE
            // identique à l'ancienne implémentation (mêmes conditions, même ordre, mêmes phrases).
            // `promptSegments()` tague en plus chaque fragment 'user' (ce que la personne a tapé
            // elle-même : taskObject, personaCustom, audienceCustom, verbCustom, examples,
            // constraintCustom, canvasCustomFormat) ou 'tool' (gabarit assemblé par l'outil), pour
            // la colorisation de l'aperçu à l'écran 2 - c'est le coeur de l'effet recherché : rendre
            // visible un travail normalement invisible.
            get promptSegments() {
                var segs = [];
                var firstSection = true;
                function tool(s) { if (s) segs.push({ text: s, kind: 'tool' }); }
                function user(s) { if (s) segs.push({ text: s, kind: 'user' }); }
                function startSection() {
                    if (!firstSection) tool('\n\n');
                    firstSection = false;
                }

                var actionVerb = this.verbType === 'custom' ? this.verbCustom : this.verb;
                var actionVerbIsUser = this.verbType === 'custom';
                // Deuxième tâche optionnelle (2026-08-04) : mêmes deux variables miroir que
                // actionVerb/actionVerbIsUser ci-dessus, jamais lues si secondTaskEnabled est faux.
                var secondActionVerb = this.verbType2 === 'custom' ? this.verbCustom2 : this.verb2;
                var secondActionVerbIsUser = this.verbType2 === 'custom';
                var personaIsUser = this.personaType === 'custom';

                // === RÔLE (enrichi) ===
                if (this.personaText) {
                    startSection();
                    var roleArticle = /^\s*(un |une |des |le |la |l'|d'|du |de )/i.test(this.personaText) ? '' : 'un(e) ';
                    tool('Tu es ' + roleArticle);
                    // Même normalisation que promptSummary (ligne ~761) : "Tu es un(e) Rédacteur..."
                    // était grammaticalement incorrect avec la majuscule des libellés prédéfinis.
                    var personaLower = this.personaText.charAt(0).toLowerCase() + this.personaText.slice(1);
                    if (personaIsUser) { user(personaLower); } else { tool(personaLower); }
                    tool(' avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience.');
                }

                // === TÂCHE ===
                // Deuxième tâche optionnelle (2026-08-04) : séquence explicite en 2 étapes
                // numérotées, jamais une simple juxtaposition - décision du club des sages (5 IA,
                // unanimité) après que le multi-select libre ait été jugé source d'ambiguïté.
                // Le comportement à une seule tâche (branches suivantes) reste inchangé tant que
                // secondTaskEnabled est faux ou qu'aucun verbe 2 valide n'est renseigné.
                if (this.secondTaskEnabled && secondActionVerb && actionVerb && this.taskObject) {
                    startSection();
                    tool('Ta tâche comporte deux étapes, à réaliser dans l\'ordre :\n1) ');
                    if (actionVerbIsUser) { user(actionVerb); } else { tool(actionVerb); }
                    tool(' : ');
                    user(this._taskWithoutLeadingVerb(actionVerb, this.taskObject));
                    tool('.\n2) ');
                    if (secondActionVerbIsUser) { user(secondActionVerb); } else { tool(secondActionVerb); }
                    tool(', à partir du résultat de l\'étape précédente.');
                } else if (actionVerb && this.taskObject) {
                    startSection();
                    tool('Ta tâche : ');
                    if (actionVerbIsUser) { user(actionVerb); } else { tool(actionVerb); }
                    tool(' ');
                    // Sans ce retrait, une demande commençant déjà par le verbe donnait
                    // « Ta tâche : Rédige rédige un courriel » dans le prompt envoyé à l'IA.
                    user(this._taskWithoutLeadingVerb(actionVerb, this.taskObject));
                    tool('.');
                } else if (this.taskObject) {
                    startSection();
                    tool('Ta tâche : ');
                    user(this.taskObject);
                    tool('.');
                }

                // === AUDIENCE ===
                if (this.audienceText) {
                    startSection();
                    tool('Audience cible : ');
                    if (this.audienceType === 'custom') { user(this.audienceText); } else { tool(this.audienceText); }
                    tool('. Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public.');
                }

                // === FORMAT DE SORTIE ===
                // LOT 1 (2026-08-06) : formatBulletText() encapsule les règles de composition
                // multi-format (1 format inchangé / plusieurs structures / plusieurs livrables /
                // mélange) - voir sa définition plus haut, juste avant get isValid().
                var outputRuleSegs = [];
                var formatBullet = this.formatBulletText;
                if (formatBullet) outputRuleSegs.push([{ t: 'tool', s: formatBullet }]);
                if (this.length) outputRuleSegs.push([{ t: 'tool', s: 'Longueur visée : ' }, { t: 'tool', s: this.length }]);
                if (this.tone) outputRuleSegs.push([{ t: 'tool', s: 'Ton et style : ' }, { t: 'tool', s: this.tone }]);
                if (this.language === 'en') outputRuleSegs.push([{ t: 'tool', s: 'Langue de rédaction : anglais' }]);
                if (this.language === 'es') outputRuleSegs.push([{ t: 'tool', s: 'Langue de rédaction : espagnol' }]);
                if (outputRuleSegs.length > 0) {
                    startSection();
                    tool('Format de la réponse :\n- ');
                    outputRuleSegs.forEach(function(rule, i) {
                        if (i > 0) tool('\n- ');
                        rule.forEach(function(part) { if (part.t === 'user') { user(part.s); } else { tool(part.s); } });
                    });
                }

                // === CONTRAINTES ===
                var constraintSegs = [];
                // Round 151 : les 2 règles de style automatiques passent sous le contrôle de
                // « Cadre strict » (cadreStrict) - désactivé, elles disparaissent du prompt quelle
                // que soit la position des cases à cocher, sans jamais y toucher elles-mêmes.
                // Round 152 (2026-08-01) : PROFIL - un texte destiné à du code ou à une traduction ne
                // doit pas hériter des règles de style français (Gemini + claude.ai ont convergé
                // indépendamment : dégrade le résultat ~1 fois sur 5, voir SPEC section 7). Gate
                // ADDITIONNELLE au-dessus de Cadre strict, jamais à la place : Cadre strict coupe TOUT
                // (quel que soit le profil), le profil ne coupe que ces 2 règles de style.
                var stylistRulesApply = this.profile !== 'programmation' && this.profile !== 'traduction';
                if (this.cadreStrict && this.constraintAntiAI && stylistRulesApply) constraintSegs.push([{ t: 'tool', s: 'Écriture naturelle et humaine : varie la longueur des phrases, utilise des expressions authentiques et des transitions fluides. Évite les formulations génériques (« dans un monde en constante évolution »), les listes à puces systématiques et les répétitions de structure.' }]);
                if (this.cadreStrict && this.constraintTypo && stylistRulesApply) constraintSegs.push([{ t: 'tool', s: 'Typographie française stricte : majuscules en début de phrase et noms propres uniquement, pas de tiret cadratin (utilise le tiret court), ponctuation correcte, accents toujours présents.' }]);
                // Round 152 : règle AUTOMATIQUE propre au profil Programmation (section 7 du plan),
                // coupée par Cadre strict comme les 2 règles ci-dessus (même interrupteur, même logique).
                if (this.cadreStrict && this.profile === 'programmation') constraintSegs.push([{ t: 'tool', s: 'Respecte les conventions de mise en forme du code : indentation cohérente, noms de variables explicites, commentaires seulement quand ils aident à comprendre, blocs de code entourés de triples accents graves avec le langage précisé.' }]);
                if (this.constraintCanvas) {
                    // Phase 2 (audit 2026-07-26) : Destination (OÙ) nommée explicitement + Format
                    // attendu (QUOI) rattaché - les deux doivent apparaître clairement dans le prompt
                    // final assemblé (exigence de la refonte, validée par 3 IA en juillet 2026).
                    var canvasNames = { chatgpt: 'Canvas de ChatGPT', claude: 'artefact de Claude', gemini: 'Canvas de Gemini', mistral: 'espace de travail de Mistral' };
                    var canvasName = canvasNames[this.canvasAI] || 'espace de travail dédié';
                    var canvasParts = [{ t: 'tool', s: 'Destination : crée un nouveau ' + canvasName + ' pour ta réponse (pas dans le fil de conversation).' }];
                    // 2026-05-05 #104 : format custom universel (formatMode) - dispo pour les 4 IA
                    var fmt = this.formatMode === 'custom' ? this.canvasCustomFormat : this.canvasFormat;
                    if (fmt) {
                        canvasParts.push({ t: 'tool', s: ' Format attendu dans cet espace : ' });
                        canvasParts.push({ t: this.formatMode === 'custom' ? 'user' : 'tool', s: fmt });
                        canvasParts.push({ t: 'tool', s: '.' });
                    }
                    constraintSegs.push(canvasParts);
                }
                if (this.constraintChainOfThought) constraintSegs.push([{ t: 'tool', s: 'Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.' }]);
                if (this.constraintAskIfUnclear) constraintSegs.push([{ t: 'tool', s: 'Si un élément de ma demande est ambigu ou manque de contexte, pose-moi des questions de clarification avant de commencer. Ne devine pas, demande.' }]);
                if (this.constraintCustom) constraintSegs.push([{ t: 'user', s: this.constraintCustom }]);
                if (constraintSegs.length > 0) {
                    startSection();
                    tool('Contraintes à respecter :\n- ');
                    constraintSegs.forEach(function(c, i) {
                        if (i > 0) tool('\n- ');
                        c.forEach(function(part) { if (part.t === 'user') { user(part.s); } else { tool(part.s); } });
                    });
                }

                // === CRITÈRES DE QUALITÉ === (Round 151 : scaffolding 100% automatique, coupé par Cadre strict)
                if (this.cadreStrict) {
                    var quality = [];
                    if (this.tone) quality.push('le ton demandé est respecté du début à la fin');
                    if (this.audienceText) quality.push('le contenu est adapté à l\'audience cible');
                    if (this.length) quality.push('la longueur correspond à ce qui est demandé');
                    if (this.constraintAntiAI && stylistRulesApply) quality.push('le texte ne ressemble pas à du contenu généré par IA');
                    if (quality.length > 0) {
                        startSection();
                        tool('Avant de finaliser, vérifie que :\n- ' + quality.join('\n- '));
                    }
                }

                // === DÉLIMITEURS ===
                if (this.useDelimiters) {
                    startSection();
                    tool('Utilise des délimiteurs ### pour séparer clairement chaque section de ta réponse.');
                }

                // === TECHNIQUE ===
                if (this.technique === 'zero-shot-cot') {
                    startSection();
                    tool('Avant de répondre, réfléchis étape par étape à ta stratégie (ne montre pas ce raisonnement dans ta réponse finale).');
                }
                if ((this.technique === 'few-shot' || this.technique === 'few-shot-cot') && this.examples) {
                    startSection();
                    tool('Voici des exemples pour guider ta réponse :\n\n');
                    user(this.examples);
                    if (this.technique === 'few-shot-cot') {
                        startSection();
                        tool('Applique le même type de raisonnement détaillé que dans les exemples ci-dessus.');
                    }
                }
                if (this.technique === 'iterative') {
                    startSection();
                    tool('Procède étape par étape. Après chaque étape majeure, présente ton travail et demande ma validation avant de continuer.');
                }
                // LOT 3 (2026-08-06, Perplexity pédagogie) : 3 nouvelles méthodes.
                if (this.technique === 'reformulation') {
                    startSection();
                    tool('Commence par reformuler en 2 ou 3 phrases ce que tu as compris de la demande, puis produis ta réponse.');
                }
                if (this.technique === 'auto-verification') {
                    startSection();
                    tool('Avant de livrer ta réponse finale, relis-la, repère les erreurs ou les oublis par rapport à la demande, et corrige-les.');
                }
                if (this.technique === 'variantes-comparees') {
                    startSection();
                    tool('Produis 2 ou 3 propositions distinctes, compare-les brièvement selon la demande, et recommande la meilleure.');
                }

                // Phrase de clôture actionnable (audit UX 2026-08-05) : sans elle, le prompt
                // s'arrêtait net après la checklist qualité, ambigu pour certains modèles.
                if (segs.length > 0) {
                    startSection();
                    tool('Réponds maintenant à cette demande.');
                }

                return segs;
            },

            get prompt() {
                return this.promptSegments.map(function(s) { return s.text; }).join('');
            },

            // Diagnostic rapide (Option 3 hybride, Partie A) : détection par règles simples,
            // ZÉRO IA et zéro appel réseau, des manques les plus fréquents d'un prompt. Chaque
            // manque pointe vers le bloc de l'écran 3 correspondant (toujours visible depuis le
            // round 152) via openDiagnosticSection() pour que l'utilisateur complète en un clic.
            get diagnostic() {
                // Round 77 (2026-07-27, passe adversariale) : messages traduits via
                // window.promptBuilderConfig.i18n.diagnostic* (repli français en dur si absent).
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var issues = [];
                // Round 14 (2026-07-27) : pas de check "verbe manquant" ici - le panneau entier n'est
                // affiché que si isValid (qui exige déjà un verbe), rendant ce diagnostic inatteignable.
                // Le cas "verbe manquant" est couvert par l'alerte x-show="!isValid" juste en dessous.
                //
                // Correctif étape prématurée (2026-08-06) : chaque suggestion pointe vers le bloc de
                // l'étape qui la contient (même mapping que openDiagnosticSection() plus bas : audience
                // = étape 3, format/contraintes = étape 4). On ne signale un manque QUE si l'étape
                // courante a déjà atteint celle du champ concerné - sinon un utilisateur encore à
                // l'étape 2 (Tâche) se fait reprocher des champs d'étapes qu'il n'a pas encore vues.
                if (this.step >= 4 && this.formatSelectionAll.length === 0 && !this.length) {
                    issues.push({ key: 'format', message: i18n.diagnosticFormat || "Tu n'as pas indiqué la forme de la réponse attendue (texte court, liste, tableau...) ni sa longueur." });
                }
                if (this.step >= 3 && !this.audienceText) {
                    issues.push({ key: 'audience', message: i18n.diagnosticAudience || "Tu n'as pas indiqué à qui s'adresse la réponse (par exemple : tes élèves, des parents, des collègues). L'IA adaptera mieux son ton si elle le sait." });
                }
                var hasConstraint = this.constraintAntiAI || this.constraintTypo || this.constraintCanvas
                    || this.constraintChainOfThought || this.constraintAskIfUnclear || !!this.constraintCustom;
                if (this.step >= 4 && !hasConstraint) {
                    issues.push({ key: 'contraintes', message: i18n.diagnosticContraintes || "Tu n'as coché aucune règle à faire respecter (par exemple : éviter le style trop « IA », poser une question si la demande est floue)." });
                }
                return { ok: issues.length === 0, issues: issues };
            },

            // Méta-prompt BYOA (Option 3 hybride, Partie B) : généré 100% côté client, aucune
            // donnée envoyée à un serveur MEMORA. Pousse l'utilisateur vers SA propre IA (déjà
            // connectée dans un autre onglet) plutôt que de payer un appel IA côté serveur.
            get metaPrompt() {
                if (!this.prompt) return '';
                return "Tu es un expert en ingénierie de prompt. Améliore le prompt suivant pour le rendre plus clair, "
                    + "plus précis et plus efficace pour un LLM, SANS changer l'intention de l'utilisateur. Retourne "
                    + "UNIQUEMENT le prompt amélioré, sans commentaire ni explication.\n\n"
                    + "Prompt à améliorer :\n« " + this.prompt + " »";
            },

            get wizardParams() {
                // Round 42 (2026-07-27) : constraintTypo/constraintChainOfThought/constraintAskIfUnclear/
                // constraintCustom/useDelimiters/examples influencent bel et bien le texte final (get
                // prompt()) mais n'étaient jamais inclus ici - le round-trip sauvegarde→"Réutiliser"
                // (?edit=ID) les réinitialisait silencieusement à leurs valeurs par défaut, et un
                // "Enregistrer" ultérieur écrasait la version en base avec ces champs perdus.
                // Round 101 (2026-07-27, passe adversariale) : selectedTask (id de la carte
                // d'objectif choisie à l'étape 1) n'était jamais inclus ici - un prompt rouvert en
                // édition (?edit=ID) retombait donc TOUJOURS sur le badge "Autre chose" (repli 'autre'
                // ligne ~484), quelle que soit la carte réellement utilisée à la création.
                // Round 151 (2026-08-01) : `audiencePreset` (singulier) retiré de la sérialisation -
                // champ mort à l'écriture (voir déclaration d'état plus haut). `cadreStrict` ajouté
                // pour que le réglage survive à une réédition (?edit=ID) comme tous les autres.
                // Round 152 (2026-08-01) : `profile` ajouté - sans lui, rouvrir un prompt sauvegardé
                // en Programmation/Traduction (?edit=ID) retomberait silencieusement sur le profil
                // Texte par défaut, réinjectant des règles de style français que la personne avait
                // délibérément coupées.
                // LOT 1 (2026-08-06) : `format` (scalaire) remplacé par `formats` (tableau,
                // formatsSelected) + `formatCustom` (séparé). Migration de LECTURE des anciens
                // prompts sauvegardés avec le scalaire `format` : voir init() (?edit=ID et
                // ?remix=ID) - piège round 42 (tout champ oublié ici se perd à la réouverture).
                return { selectedTask: this.selectedTask, personaType: this.personaType, personaPreset: this.personaPreset, personaCustom: this.personaCustom, verbType: this.verbType, verb: this.verb, verbCustom: this.verbCustom, taskObject: this.taskObject, audienceType: this.audienceType, audiencePresets: this.audiencePresets, audienceCustom: this.audienceCustom, formats: this.formatsSelected, formatCustom: this.formatCustom, length: this.length, tone: this.tone, language: this.language, technique: this.technique, constraintAntiAI: this.constraintAntiAI, constraintTypo: this.constraintTypo, constraintCanvas: this.constraintCanvas, canvasAI: this.canvasAI, canvasFormat: this.canvasFormat, formatMode: this.formatMode, canvasCustomFormat: this.canvasCustomFormat, constraintChainOfThought: this.constraintChainOfThought, constraintAskIfUnclear: this.constraintAskIfUnclear, constraintCustom: this.constraintCustom, useDelimiters: this.useDelimiters, examples: this.examples, cadreStrict: this.cadreStrict, profile: this.profile };
            },
            _headers: function() {
                return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            },
            // Round 128 : notice NEUTRE (pas une erreur - saveError s'affiche en rouge). Sert à
            // dire à l'utilisateur que son texte a été conservé, pour qu'il ne croie pas la carte
            // cassée quand le gabarit ne s'applique pas.
            _showTaskNotice: function(msg) {
                var self = this;
                clearTimeout(this._taskNoticeTimer);
                this.taskNotice = msg || (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.taskTextKept) || 'Votre texte a été conservé.';
                this._taskNoticeTimer = setTimeout(function() { self.taskNotice = ''; }, 4000);
            },
            _showSaveError: function(msg) {
                var self = this;
                clearTimeout(this._saveErrorTimer);
                this.saveError = msg || (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.saveError) || 'Erreur de sauvegarde. Réessayez.';
                this._saveErrorTimer = setTimeout(function() { self.saveError = ''; }, 4000);
            },
            init: function() {
                var self = this;
                if (this.isAuthenticated) {
                    fetch('/api/prompts', { headers: this._headers() })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            self.history = (data.data || []).map(function(item) {
                                return { id: item.public_id || item.id, prompt: item.prompt_text, name: item.name, date: new Date(item.created_at).toLocaleString('fr-CA'), params: item.params };
                            });
                            // Round 65 (2026-07-27) : localStorage.getItem() peut lever (mode privé
                            // Safari, storage désactivé par politique) - sans ce try/catch, l'exception
                            // rejetait ce .then() et tombait dans le .catch() suivant, qui ÉCRASAIT
                            // self.history (déjà rempli avec les vraies données serveur ci-dessus) par
                            // le contenu (périmé ou vide) de localStorage.
                            // Round 90 (2026-07-27, passe adversariale) : testait uniquement la
                            // présence de la clé, jamais son contenu réel. deletePrompt() (invité)
                            // écrit littéralement la chaîne '[]' quand le dernier item local est
                            // supprimé - non-vide donc truthy en JS - ce qui laissait hasLocalData
                            // bloqué à true (bannière/bouton "Importer" affichés en permanence,
                            // sans jamais rien à importer, même à la connexion suivante).
                            try {
                                var _lh = JSON.parse(localStorage.getItem('pb_history') || '[]');
                                if (Array.isArray(_lh) && _lh.length > 0) self.hasLocalData = true;
                            } catch (e) {}
                            self.historyLoaded = true;
                        })
                        .catch(function() {
                            try { self.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { self.history = []; }
                            self.historyLoaded = true;
                        });
                    // Charger un prompt existant pour edition (?edit=ID)
                    var editId = new URLSearchParams(window.location.search).get('edit');
                    if (editId) {
                        fetch('/api/prompts/' + encodeURIComponent(editId), { headers: self._headers() })
                            .then(function(r) { if (!r.ok) throw new Error('http_' + r.status); return r.json(); })
                            .then(function(found) {
                                if (found && found.params) {
                                    var p = found.params;
                                    if (p.personaType) self.personaType = p.personaType;
                                    if (p.personaPreset) self.personaPreset = p.personaPreset;
                                    if (p.personaCustom) {
                                        self.personaCustom = p.personaCustom;
                                        self.personaType = 'custom';
                                    }
                                    if (p.verbType) self.verbType = p.verbType;
                                    if (p.verb) self.verb = p.verb;
                                    if (p.verbCustom) {
                                        self.verbCustom = p.verbCustom;
                                        self.verbType = 'custom';
                                    }
                                    if (p.taskObject) self.taskObject = p.taskObject;
                                    if (p.audienceType) self.audienceType = p.audienceType;
                                    // Round 151 (2026-08-01) : `self.audiencePreset = p.audiencePreset`
                                    // retiré - rien ne lit plus jamais cette propriété d'état (voir
                                    // déclaration plus haut). La migration de LECTURE elle-même (ligne
                                    // suivante, singulier → tableau pluriel) reste intacte : elle lit
                                    // `p.audiencePreset` depuis le PAYLOAD chargé, pas depuis l'état.
                                    if (Array.isArray(p.audiencePresets)) { self.audiencePresets = p.audiencePresets; } else if (p.audiencePreset) { self.audiencePresets = [p.audiencePreset]; }
                                    if (p.audienceCustom) {
                                        self.audienceCustom = p.audienceCustom;
                                        self.audienceType = 'custom';
                                    }
                                    // LOT 1 (2026-08-06) : migration de l'ancien scalaire `format`
                                    // (prompts sauvegardés avant ce lot) vers le nouveau tableau
                                    // formatsSelected - Array.isArray(p.formats) couvre les
                                    // prompts DÉJÀ migrés (ré-enregistrés depuis ce lot).
                                    if (Array.isArray(p.formats)) { self.formatsSelected = p.formats; } else if (p.format) { self.formatsSelected = [p.format]; }
                                    if (p.formatCustom) self.formatCustom = p.formatCustom;
                                    if (p.length) self.length = p.length;
                                    if (p.tone) self.tone = p.tone;
                                    if (p.language) self.language = p.language;
                                    if (p.technique) self.technique = p.technique;
                                    if (p.constraintAntiAI !== undefined) self.constraintAntiAI = p.constraintAntiAI;
                                    // Round 151 (2026-08-01) : Cadre strict doit survivre à une réédition
                                    // comme les autres réglages, sinon rouvrir un prompt sauvegardé avec
                                    // le cadre désactivé le réactiverait silencieusement (repli à `true`).
                                    if (p.cadreStrict !== undefined) self.cadreStrict = p.cadreStrict;
                                    // Round 152 (2026-08-01) : restaure le profil sauvegardé ET marque
                                    // profileTouched - un prompt déjà sauvegardé porte un choix DÉJÀ FAIT
                                    // par la personne, la détection par mots-clés ne doit plus jamais
                                    // l'écraser (même règle que les autres champs "custom" restaurés ici).
                                    if (p.profile) { self.profile = p.profile; self.profileTouched = true; }
                                    // Round 42 (2026-07-27) : ces 6 champs manquaient à la restauration
                                    // ?edit=ID - le prompt rouvrait avec ces options réinitialisées,
                                    // et un "Enregistrer" ultérieur écrasait silencieusement la version
                                    // en base (perte de donnée, ex. constraintCustom peut contenir des
                                    // instructions longues, examples rend "few-shot" non fonctionnel une
                                    // fois vidé).
                                    if (p.constraintTypo !== undefined) self.constraintTypo = p.constraintTypo;
                                    if (p.constraintChainOfThought !== undefined) self.constraintChainOfThought = p.constraintChainOfThought;
                                    if (p.constraintAskIfUnclear !== undefined) self.constraintAskIfUnclear = p.constraintAskIfUnclear;
                                    if (p.constraintCustom) self.constraintCustom = p.constraintCustom;
                                    if (p.useDelimiters !== undefined) self.useDelimiters = p.useDelimiters;
                                    if (p.examples) self.examples = p.examples;
                                    if (p.constraintCanvas) self.constraintCanvas = p.constraintCanvas;
                                    if (p.canvasAI) {
                                        // 2026-05-05 #104 : migration anciens prompts canvasAI='custom' → canvasAI='chatgpt' + formatMode='custom'
                                        if (p.canvasAI === 'custom') { self.canvasAI = 'chatgpt'; self.formatMode = 'custom'; }
                                        else self.canvasAI = p.canvasAI;
                                    }
                                    if (p.canvasFormat) self.canvasFormat = p.canvasFormat;
                                    if (p.canvasCustomFormat) self.canvasCustomFormat = p.canvasCustomFormat;
                                    if (p.formatMode) self.formatMode = p.formatMode;
                                    self.saveName = found.name;
                                    // Prompt existant chargé pour édition : on saute l'étape « objectif »
                                    // (déjà répondue par un précédent passage) et on ouvre directement
                                    // toutes les divulgations locales (Phase 2 : ex-showAdvanced unique),
                                    // car un prompt sauvegardé utilise typiquement des valeurs
                                    // personnalisées qui vivent dans ces sections repliées par défaut.
                                    // Round 101 (2026-07-27, passe adversariale) : restaure la vraie
                                    // carte d'objectif si elle a été sauvegardée (voir wizardParams
                                    // ci-dessus) - le repli 'autre' ne s'applique plus qu'aux prompts
                                    // sauvegardés AVANT ce fix (jamais de selectedTask en base).
                                    if (p.selectedTask) self.selectedTask = p.selectedTask;
                                    self.selectedTask = self.selectedTask || 'autre';
                                    // Round 152 (2026-08-01) : les 5 blocs de l'écran 3 sont désormais
                                    // TOUJOURS visibles - plus d'accordéons internes à rouvrir un par un.
                                    self.step = 2;
                                    self._editingId = found.public_id || found.id;
                                }
                                self.editLoading = false;
                            })
                            .catch(function() {
                                self.editLoading = false;
                                // Round 6 (2026-07-26) : le throw ajouté round 5 sur !r.ok n'avait
                                // aucun .catch() - échec silencieux (prompt supprimé/IDOR/réseau),
                                // le wizard restait vierge sans jamais informer l'utilisateur.
                                self._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.loadError) || 'Impossible de charger ce prompt pour édition.');
                            });
                    } else {
                        // Round 40 (2026-07-27) : "Mon profil" (/user/prompts) sauvegardait
                        // profile_role/profile_style/profile_constraints avec un message de succès
                        // annonçant que ça "pré-remplit vos futurs prompts", mais le wizard ne lisait
                        // jamais cette clé - promesse non tenue. Uniquement pour un NOUVEAU prompt
                        // (pas ?edit=ID, traité ci-dessus) et seulement si les champs sont encore
                        // vierges, pour ne jamais écraser un choix déjà fait par l'utilisateur.
                        fetch('/api/tool-preferences/constructeur-prompts', { headers: self._headers() })
                            .then(function(r) { return r.ok ? r.json() : null; })
                            .then(function(data) {
                                var profile = data && data.preferences && data.preferences.prompt_profile;
                                if (!profile) return;
                                // Round 43 (2026-07-27) : la garde ne vérifiait que personaCustom==='' et
                                // personaType==='preset' - si l'utilisateur avait déjà cliqué une carte
                                // d'objectif associée à une persona preset (selectTask() met personaType=
                                // 'preset' + personaPreset=valeur, SANS jamais passer par 'custom') avant
                                // que ce fetch ne résolve, le profil écrasait silencieusement ce choix
                                // explicite (personaType→'custom', personaPreset orphelin, jamais réinitialisé).
                                if (profile.profile_role && self.personaCustom === '' && self.personaType === 'preset' && self.personaPreset === '') {
                                    self.personaCustom = profile.profile_role;
                                    self.personaType = 'custom';
                                }
                                var extra = [];
                                if (profile.profile_style) extra.push('Style d\'écriture préféré : ' + profile.profile_style);
                                if (profile.profile_constraints) extra.push(profile.profile_constraints);
                                if (extra.length > 0 && self.constraintCustom === '') {
                                    self.constraintCustom = extra.join('\n');
                                }
                            })
                            .catch(function() {});
                    }
                } else {
                    try { this.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { this.history = []; }
                    this.historyLoaded = true;
                }

                // Erreur de partage public (?share_error=notfound) - Phase 1 permalien public
                // (2026-08-05). Posé par PublicPromptController::show() quand /p/{publicId} est
                // invalide/privé. Contourne délibérément le flash de session ->with('error', ...)
                // du contrôleur (inutile ici : cette page passe par cacheResponse:600, la réponse
                // HTML est un snapshot en cache qui ne "voit" jamais le flash) - lu depuis l'URL
                // à CHAQUE chargement, cache ou non. history.replaceState nettoie l'URL ensuite
                // pour qu'un refresh ou un retour arrière ne réaffiche pas le message.
                var shareError = new URLSearchParams(window.location.search).get('share_error');
                if (shareError === 'notfound') {
                    this._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.shareNotFoundError) || "Ce prompt n'existe pas ou n'est plus public.");
                    history.replaceState(null, '', window.location.pathname + window.location.hash);
                }

                // Charger un prompt PUBLIC pour remix (?remix=ID) - Phase 1 permalien public
                // (2026-08-05). Contrairement au bloc ?edit=ID ci-dessus (à l'intérieur du
                // if (this.isAuthenticated), réservé aux connectés propriétaires via GET
                // /api/prompts/{id}), ce bloc tourne pour TOUT visiteur, connecté ou non - c'est
                // un permalien public accessible sans compte. Source de données différente : GET
                // /p/{publicId}/remix-data (PublicPromptController::remixData, jamais scopé par
                // user_id, uniquement is_public=true - IDOR impossible). Logique de mapping
                // params → formulaire copiée 1:1 depuis le bloc ?edit=ID. Différence volontaire :
                // self._editingId n'est JAMAIS renseigné ici - un "Enregistrer" après un remix
                // crée toujours un NOUVEAU prompt, jamais une mise à jour du prompt original (qui
                // peut appartenir à quelqu'un d'autre).
                var remixId = new URLSearchParams(window.location.search).get('remix');
                if (remixId) {
                    this.editLoading = true;
                    fetch('/p/' + encodeURIComponent(remixId) + '/remix-data', { headers: { 'Accept': 'application/json' } })
                        .then(function(r) { if (!r.ok) throw new Error('http_' + r.status); return r.json(); })
                        .then(function(found) {
                            if (found && found.params) {
                                var p = found.params;
                                if (p.personaType) self.personaType = p.personaType;
                                if (p.personaPreset) self.personaPreset = p.personaPreset;
                                if (p.personaCustom) {
                                    self.personaCustom = p.personaCustom;
                                    self.personaType = 'custom';
                                }
                                if (p.verbType) self.verbType = p.verbType;
                                if (p.verb) self.verb = p.verb;
                                if (p.verbCustom) {
                                    self.verbCustom = p.verbCustom;
                                    self.verbType = 'custom';
                                }
                                if (p.taskObject) self.taskObject = p.taskObject;
                                if (p.audienceType) self.audienceType = p.audienceType;
                                if (Array.isArray(p.audiencePresets)) { self.audiencePresets = p.audiencePresets; } else if (p.audiencePreset) { self.audiencePresets = [p.audiencePreset]; }
                                if (p.audienceCustom) {
                                    self.audienceCustom = p.audienceCustom;
                                    self.audienceType = 'custom';
                                }
                                // LOT 1 (2026-08-06) : même migration scalaire → tableau que le
                                // bloc ?edit=ID ci-dessus.
                                if (Array.isArray(p.formats)) { self.formatsSelected = p.formats; } else if (p.format) { self.formatsSelected = [p.format]; }
                                if (p.formatCustom) self.formatCustom = p.formatCustom;
                                if (p.length) self.length = p.length;
                                if (p.tone) self.tone = p.tone;
                                if (p.language) self.language = p.language;
                                if (p.technique) self.technique = p.technique;
                                if (p.constraintAntiAI !== undefined) self.constraintAntiAI = p.constraintAntiAI;
                                if (p.cadreStrict !== undefined) self.cadreStrict = p.cadreStrict;
                                if (p.profile) { self.profile = p.profile; self.profileTouched = true; }
                                if (p.constraintTypo !== undefined) self.constraintTypo = p.constraintTypo;
                                if (p.constraintChainOfThought !== undefined) self.constraintChainOfThought = p.constraintChainOfThought;
                                if (p.constraintAskIfUnclear !== undefined) self.constraintAskIfUnclear = p.constraintAskIfUnclear;
                                if (p.constraintCustom) self.constraintCustom = p.constraintCustom;
                                if (p.useDelimiters !== undefined) self.useDelimiters = p.useDelimiters;
                                if (p.examples) self.examples = p.examples;
                                if (p.constraintCanvas) self.constraintCanvas = p.constraintCanvas;
                                if (p.canvasAI) {
                                    if (p.canvasAI === 'custom') { self.canvasAI = 'chatgpt'; self.formatMode = 'custom'; }
                                    else self.canvasAI = p.canvasAI;
                                }
                                if (p.canvasFormat) self.canvasFormat = p.canvasFormat;
                                if (p.canvasCustomFormat) self.canvasCustomFormat = p.canvasCustomFormat;
                                if (p.formatMode) self.formatMode = p.formatMode;
                                // Décision de conception (non précisée dans le plan approuvé) :
                                // préfixe "Remix de " sur le nom repris, pour que la personne sache
                                // d'où vient ce brouillon avant de l'enregistrer sous son propre nom.
                                self.saveName = found.name ? ('Remix de ' + found.name) : self.saveName;
                                if (p.selectedTask) self.selectedTask = p.selectedTask;
                                self.selectedTask = self.selectedTask || 'autre';
                                self.step = 2;
                                // self._editingId volontairement NON renseigné : voir commentaire ci-dessus.
                            }
                            self.editLoading = false;
                        })
                        .catch(function() {
                            self.editLoading = false;
                            self._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.loadError) || 'Impossible de charger ce prompt à remixer.');
                        });
                }

                this._loadCustomCards();
            },

            // 2026-08-04, round 2 du club des sages (5 IA, unanimité) : alternative 100% accessible
            // au réordonnancement par glisser-déposer des 2 tâches, rejeté car non conforme WCAG AAA
            // (critères 2.1.1 Clavier et 2.5.7 Mouvements de glissement). Ce bouton natif, utilisable
            // au clavier sans équivalent supplémentaire à construire, suffit pour une séquence bornée
            // à 2 éléments (2 permutations possibles).
            swapTaskOrder: function() {
                var currentVerbType = this.verbType;
                var currentVerb = this.verb;
                var currentVerbCustom = this.verbCustom;

                this.verbType = this.verbType2;
                this.verb = this.verb2;
                this.verbCustom = this.verbCustom2;

                this.verbType2 = currentVerbType;
                this.verb2 = currentVerb;
                this.verbCustom2 = currentVerbCustom;
            },

            // Diagnostic rapide : fait défiler jusqu'au bloc correspondant. Bug trouvé et corrigé
            // le 2026-08-04 en retirant affinerOpen : cette fonction forçait TOUJOURS step=2
            // (« Tâche »), un reliquat de l'ancienne numérotation « écran 3 » (Round 152) jamais
            // mis à jour lors de la restauration du wizard à 4 étapes (2026-08-03) - le clic sur
            // un diagnostic n'atteignait donc jamais le bon bloc. 'audience' vit à l'étape 3,
            // 'format'/'contraintes' à l'étape 4 (voir x-tools::prompt-block dans le Blade).
            openDiagnosticSection: function(key) {
                var targetStep = key === 'audience' ? 3 : 4;
                if (this.step !== targetStep) this.step = targetStep;
                if (targetStep === 4) this.step4Visited = true;
                var targetId = key === 'audience' ? 'cpAudienceBlock' : ('cpSection' + key.charAt(0).toUpperCase() + key.slice(1));
                this.$nextTick(function() {
                    var el = document.getElementById(targetId);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },

            // Round 152 (2026-08-01, section 7 du plan) : correspondance par MOTS-CLÉS, jamais de
            // « compréhension » (aucune IA dans l'outil) - pré-sélectionne un profil TOUJOURS
            // corrigeable d'un clic (voir profileTouched). Les cartes système « coder »/« traduire »
            // (écran 1) sont un signal plus fort que les mots-clés et priment sur eux.
            _detectProfileFromText: function(text) {
                var t = (text || '').toLowerCase();
                var traductionKeywords = ['traduire', 'traduction', 'translate', 'version anglaise', 'version espagnole', 'en anglais', 'en espagnol', 'en français'];
                for (var i = 0; i < traductionKeywords.length; i++) { if (t.indexOf(traductionKeywords[i]) !== -1) return 'traduction'; }
                var programmationKeywords = ['code', 'coder', 'fonction', 'script', 'bug', 'débogu', 'debogu', 'programme', 'api', 'sql', 'python', 'javascript', 'algorithme', 'classe', 'variable', 'régression', 'framework'];
                for (var j = 0; j < programmationKeywords.length; j++) { if (t.indexOf(programmationKeywords[j]) !== -1) return 'programmation'; }
                return 'texte';
            },
            _autoDetectProfile: function() {
                if (this.profileTouched) return;
                if (this.selectedTask === 'coder') { this.profile = 'programmation'; return; }
                if (this.selectedTask === 'traduire') { this.profile = 'traduction'; return; }
                this.profile = this._detectProfileFromText(this.taskObject);
            },

            // Phase 1 : clic sur une carte d'objectif → pré-sélection intelligente de la persona et
            // du verbe (mapping simple, pas d'IA), puis avance à l'étape suivante. Le générateur de
            // prompt (get prompt()) n'est jamais touché : on ne fait qu'assigner ses entrées en amont.
            selectTask: function(card) {
                // Round 64 (2026-07-27) : bloque tant que le chargement ?edit=ID est en vol - sinon
                // ce clic était écrasé silencieusement par la réponse tardive du GET
                // /api/prompts/{id} (voir editLoading ci-dessus).
                if (this.editLoading) return;
                this.selectedTask = card.id;
                if (card.personaValue) { this.personaType = 'preset'; this.personaPreset = card.personaValue; }
                if (card.verb) { this.verbType = 'preset'; this.verb = card.verb; }
                // Cartes personnalisées (Option D) : le gabarit de requête pré-remplit directement
                // la demande - pas de mapping persona/verbe (les cartes perso n'en ont pas).
                // Le garde extérieur reste indispensable : les cartes SYSTÈME n'ont pas de
                // query_template, et sans lui on écraserait le champ avec undefined.
                if (card.query_template) {
                    // Round 128 (2026-07-30, passe adversariale) : l'affectation était directe, donc
                    // revenir à l'étape 1 et recliquer une carte (volontairement ou par erreur)
                    // détruisait TOUT le texte rédigé à l'étape 2 - parfois des centaines de mots
                    // collés par l'utilisateur - sans avertissement ni annulation possible.
                    //
                    // On ne remplace donc que si le champ ne contient aucun travail : vide, ou
                    // strictement égal au gabarit d'une carte connue (= gabarit jamais retouché,
                    // le remplacer ne détruit rien). Dès que l'utilisateur a écrit son propre
                    // texte, on le CONSERVE et on le dit, plutôt que d'effacer en silence.
                    var current = (this.taskObject || '').trim();
                    var knownCards = this.customCards.concat(this.taskCards);
                    var isUntouchedTemplate = current === '';

                    for (var k = 0; !isUntouchedTemplate && k < knownCards.length; k++) {
                        if (knownCards[k].query_template && knownCards[k].query_template.trim() === current) {
                            isUntouchedTemplate = true;
                        }
                    }

                    if (isUntouchedTemplate) {
                        this.taskObject = card.query_template;
                    } else {
                        this._showTaskNotice();
                    }
                }
                this.nextStep();
            },

            // Restauré en wizard 4 étapes (2026-08-03, fidélité à la version pré-refonte du
            // 26 juillet 2026, sur demande explicite de l'utilisateur). Validation par étape :
            // 1=Persona (personaText requis), 2=Tâche (verbe + taskObject requis), 3=Audience
            // (optionnelle), 4=Options avancées (tout optionnel, dernière étape).
            nextStep: function() {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                if (this.step === 1 && !this.personaText) { this.showValidation = true; return; }
                if (this.step === 2 && (!hasVerb || !this.taskObject)) { this.showValidation = true; return; }
                this.showValidation = false;
                if (this.step === 2) this._autoDetectProfile();
                if (this.step < 4) { this.step++; if (this.step === 4) this.step4Visited = true; }
            },
            canGoToStep: function(s) {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                if (s <= 1) return true;
                if (!this.personaText) return false;
                if (s <= 2) return true;
                if (!hasVerb || !this.taskObject) return false;
                return true;
            },
            goToStep: function(s) {
                if (this.canGoToStep(s)) { this.showValidation = false; this.step = s; if (s === 4) this.step4Visited = true; }
                else { this.showValidation = true; }
            },
            prevStep: function() { if (this.step > 1) this.step--; },
            // Correctif #4 (2026-08-05, indicateur de complétion par étape) : 1=Persona (rôle choisi),
            // 2=Tâche (verbe + description remplis), 3=Audience (optionnelle - complète dès qu'une
            // audience est choisie), 4=Options avancées (tout optionnel - complète dès la 1re visite,
            // voir step4Visited armé par nextStep()/goToStep() ci-dessus). Affiché en coche ✓ dans le
            // cercle du stepper (voir .ct-stepper__btn--done, Blade).
            stepComplete: function(n) {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                if (n === 1) return !!this.personaText;
                if (n === 2) return hasVerb && !!this.taskObject;
                if (n === 3) return !!this.audienceText;
                if (n === 4) return !!this.step4Visited;
                return false;
            },

            copy: function() {
                var self = this;
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                this.track('prompt_copy', { tool: 'constructeur-prompts' });
                // Round 94 (2026-07-27, passe adversariale) : copied=true (bouton "Copié !") et le
                // toast de succès ne s'affichent plus QUE si l'écriture presse-papiers a RÉELLEMENT
                // réussi - window.copyToClipboard() attend la Promise réelle (échec = toast d'erreur
                // explicite déjà géré par le helper), au lieu du try/catch synchrone précédent qui
                // n'interceptait jamais un rejet asynchrone et affichait "Copié !" à tort.
                window.copyToClipboard(this.prompt, i18n.promptCopied || 'Prompt copié').then(function(ok) {
                    if (!ok) return;
                    self.copied = true;
                    setTimeout(function() { self.copied = false; }, 2000);
                });
            },

            track: function(event, params) {
                try {
                    if (typeof window.gtag === 'function') {
                        window.gtag('event', event, params || {});
                    }
                } catch (e) {}
            },

            // DRY (Option 3 hybride, Partie B) : mécanisme unique "ouvrir dans une IA", réutilisé
            // tel quel pour le prompt normal (appel sans 2e argument) ET pour le méta-prompt
            // "Améliorer avec mon IA" (appel avec text = this.metaPrompt). Rien n'est dupliqué.
            openIn: function(target, text) {
                var payload = text || this.prompt;
                if (!payload) return;
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var baseUrl = '';
                switch (target) {
                    case 'chatgpt':
                        baseUrl = 'https://chatgpt.com/?q=';
                        break;
                    case 'claude':
                        baseUrl = 'https://claude.ai/new?q=';
                        break;
                    case 'perplexity':
                        baseUrl = 'https://www.perplexity.ai/search?q=';
                        break;
                    case 'gemini':
                        baseUrl = 'https://gemini.google.com/app';
                        break;
                    // Round 126 (2026-07-30, passe adversariale) : Mistral était proposé comme
                    // « Destination » du prompt (le texte généré dit « crée un nouveau espace de
                    // travail de Mistral ») sans qu'aucun bouton ne permette d'y aller. Toutes les
                    // autres destinations avaient le leur. L'absence de pré-remplissage par URL
                    // n'était pas le motif : Gemini est dans le même cas et a quand même son bouton.
                    case 'mistral':
                        baseUrl = 'https://chat.mistral.ai/chat';
                        break;
                    default:
                        return;
                }
                var encodedPrompt = encodeURIComponent(payload);
                var url = baseUrl;
                var msg = i18n.openInGeneric || 'Prompt copié : ouverture de la conversation…';
                if (target === 'gemini') {
                    // Gemini ne pré-remplit pas via URL → on ouvre l'app, le prompt est copié.
                    msg = i18n.openInGemini || 'Prompt copié : colle-le dans Gemini (Ctrl/Cmd + V).';
                } else if (target === 'mistral') {
                    // Même cas que Gemini : pas de pré-remplissage par URL, donc on ouvre le chat
                    // et on annonce explicitement qu'il faut coller - jamais un « ouverture de la
                    // conversation » trompeur qui laisserait croire que le prompt est déjà là.
                    msg = i18n.openInMistral || 'Prompt copié : colle-le dans Mistral (Ctrl/Cmd + V).';
                } else if (encodedPrompt.length <= 4000) {
                    url += encodedPrompt;
                } else {
                    msg = i18n.openInTooLong || 'Prompt trop long pour le lien : il est copié, colle-le (Ctrl/Cmd + V).';
                }
                this.track('prompt_open_in', { tool: 'constructeur-prompts', target: target, meta: !!text });
                // Round 94 (2026-07-27, passe adversariale) : window.open() reste synchrone, dans la
                // même pile d'appel que le clic (jamais dans un .then()) pour ne jamais risquer un
                // blocage popup. Seul le message affiché attend désormais la résolution RÉELLE de la
                // copie presse-papiers via window.copyToClipboard() (succès = message contextuel
                // "openInGeneric/openInGemini/openInTooLong" ci-dessus ; échec = toast d'erreur déjà
                // géré par le helper), au lieu d'annoncer "Prompt copié" à tort sur un rejet silencieux.
                window.open(url, '_blank', 'noopener');
                window.copyToClipboard(payload, msg);
            },

            copyText: function(text) { window.copyToClipboard(text, (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.promptCopied) || 'Prompt copié'); },

            armReset: function() {
                if (this.resetArmed) { this.resetAll(); return; }
                this.resetArmed = true;
                var self = this;
                setTimeout(function() { self.resetArmed = false; }, 4000);
            },
            resetAll: function() { window.location.href = window.location.pathname; },

            addToHistory: function() {
                // Round 63 (2026-07-27) : bloque toute sauvegarde tant que l'historique initial
                // (GET /api/prompts dans init()) n'a pas résolu - sinon l'écho tardif de ce GET
                // écrasait le prompt fraîchement sauvegardé (voir historyLoaded ci-dessus).
                if (this.saving || !this.historyLoaded) return;
                var self = this;
                var title = this.saveName.trim() || this.personaText || 'Prompt';
                if (this.isAuthenticated) {
                    this.saving = true;
                    var isEdit = !!this._editingId;
                    var url = isEdit ? '/api/prompts/' + this._editingId : '/api/prompts';
                    var method = isEdit ? 'PUT' : 'POST';
                    fetch(url, {
                        method: method, headers: this._headers(),
                        body: JSON.stringify({ name: title, prompt_text: this.prompt, params: this.wizardParams })
                    })
                    .then(function(r) {
                        if (!r.ok) {
                            // Round 35 (2026-07-27) : lire le corps JSON de l'erreur AVANT de rejeter
                            // - sinon le message précis (ex. "params trop volumineux", round 34)
                            // n'atteint jamais l'utilisateur, qui ne voit qu'un message générique.
                            // `serverMessage` distingue un message serveur légitime (à afficher tel
                            // quel) d'une simple erreur réseau/JS (toujours repli générique).
                            // Round 82 (2026-07-27) : restreint à 422 (validation applicative, seul
                            // cas aujourd'hui traduit via __()) - un 429 (throttle) renvoie le texte
                            // anglais fixe du framework Laravel ("Too Many Attempts.", jamais
                            // traduit), qui ne doit jamais être affiché tel quel à un utilisateur FR.
                            return r.json().catch(function() { return {}; }).then(function(body) {
                                var err = new Error((body && body.message) || ('http_' + r.status));
                                err.serverMessage = r.status === 422 && !!(body && body.message);
                                throw err;
                            });
                        }
                        return r.json();
                    })
                    .then(function(data) {
                        if (isEdit) {
                            var pid = data.public_id || data.id;
                            var idx = self.history.findIndex(function(h) { return h.id == pid; });
                            if (idx >= 0) self.history[idx] = { id: pid, prompt: data.prompt_text, name: data.name, date: new Date(data.updated_at).toLocaleString('fr-CA'), params: data.params };
                            // Round 98 (2026-07-27, passe adversariale) : remettre _editingId à null
                            // ici faisait basculer un 2e clic sur "Sauvegarder" (sans recharger/
                            // revenir à ?edit=ID) vers un POST au lieu d'un PUT - un vrai doublon du
                            // prompt était créé en base au lieu de mettre à jour l'enregistrement déjà
                            // édité. En restant sur pid (écho serveur), le mode "mise à jour" persiste
                            // tant que l'utilisateur reste sur cette session d'édition.
                            self._editingId = pid;
                        } else {
                            self.history.unshift({ id: data.public_id || data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.created_at).toLocaleString('fr-CA'), params: data.params });
                        }
                        self.saveName = '';
                        self.saving = false;
                        if (typeof window.toast === 'function') {
                            window.toast((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.promptSaved) || 'Prompt sauvegardé', 'success');
                        }
                    })
                    .catch(function(err) {
                        self.saving = false;
                        self._showSaveError(err && err.serverMessage ? err.message : undefined);
                    });
                } else {
                    this.$dispatch('open-auth-modal');
                }
            },
            deletePrompt: function(id, index) {
                var self = this;
                // Round 63 (2026-07-27) : défense en profondeur - avec addToHistory()/
                // importLocalStorage() désormais bloqués tant que !historyLoaded, cette garde est
                // redondante en pratique (self.history ne peut pas contenir d'item avant le
                // chargement initial), mais protège contre tout futur point d'entrée qui peuplerait
                // history plus tôt.
                if (!this.historyLoaded) return;
                if (this.isAuthenticated && id) {
                    // Round 36 (2026-07-27) : sans cette garde, un double-clic envoie 2 DELETE -
                    // le 1er réussit (204), le 2e tombe sur un id déjà supprimé (404) et affichait
                    // une erreur trompeuse pour une suppression qui avait pourtant pleinement réussi.
                    if (this._deletingIds.indexOf(id) !== -1) return;
                    this._deletingIds.push(id);
                    fetch('/api/prompts/' + id, { method: 'DELETE', headers: this._headers() })
                        .then(function(r) {
                            if (!r.ok) throw new Error('http_' + r.status);
                            self.history.splice(index, 1);
                        })
                        .catch(function() {
                            // Round 9 (2026-07-26) : retirait la carte de l'UI même sur échec
                            // serveur (403/404/500), sans jamais notifier l'utilisateur.
                            self._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.deleteError) || 'Erreur lors de la suppression. Réessayez.');
                        })
                        .finally(function() {
                            self._deletingIds = self._deletingIds.filter(function(dId) { return dId !== id; });
                        });
                } else {
                    this.history.splice(index, 1);
                    // Round 66 (2026-07-27) : même risque que les sites déjà protégés au round 65 -
                    // localStorage.setItem() non protégé faisait lever une exception non interceptée
                    // (mode privé, quota dépassé) APRÈS que l'item ait déjà disparu visuellement
                    // (splice ci-dessus), sans jamais persister réellement la suppression.
                    try { localStorage.setItem('pb_history', JSON.stringify(this.history)); } catch (e) {}
                }
            },
            importLocalStorage: function() {
                var self = this;
                // Round 36 (2026-07-27) : sans cette garde, un double-clic/double-tap sur
                // « Importer » relit le même tableau localStorage et poste chaque item une 2e
                // fois - aucune contrainte d'unicité en base, donc duplication réelle des prompts.
                // Round 63 (2026-07-27) : même garde historyLoaded qu'addToHistory() - sinon l'écho
                // tardif du GET initial (init()) écrasait les prompts importés avant qu'il résolve.
                if (this.importing || !this.historyLoaded) return;
                var local = [];
                try { local = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { return; }
                // Round 90 (2026-07-27, passe adversariale) : défense en profondeur - si
                // hasLocalData était resté bloqué à true par erreur (cf. fix init() ci-dessus),
                // ce retour anticipé le laissait bloqué indéfiniment sans jamais atteindre le
                // .then() qui le remet à jour (ligne ~783).
                if (local.length === 0) { this.hasLocalData = false; return; }
                this.importing = true;
                // Round 8 (2026-07-26) : Promise.all était all-or-nothing - un seul échec sur N
                // rejetait tout SANS retirer les items déjà importés avec succès de localStorage,
                // donc un nouvel essai les repostait en double. Chaque item est traité
                // indépendamment (allSettled) : seuls les items encore en échec restent en local.
                var i18nImport = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var settlements = local.map(function(item) {
                    return fetch('/api/prompts', {
                        method: 'POST', headers: self._headers(),
                        body: JSON.stringify({ name: item.name || i18nImport.importedPromptName || 'Prompt importé', prompt_text: item.prompt, params: {} })
                    })
                    .then(function(r) { if (!r.ok) throw new Error('http_' + r.status); return r.json(); })
                    .then(function(data) { return { ok: true, data: data }; })
                    .catch(function() { return { ok: false, item: item }; });
                });
                Promise.all(settlements).then(function(results) {
                    var remaining = [];
                    var anyFailed = false;
                    results.forEach(function(res) {
                        if (res.ok) {
                            // Round 142 (2026-07-30) : public_id d'abord. L'API supprime par public_id (SavedPromptController
                            // ::destroy), jamais par id interne. Le chemin de sauvegarde ordinaire le faisait déjà ;
                            // seul l'import avait gardé res.data.id, si bien qu'un prompt importé ne pouvait pas être
                            // supprimé de l'historique avant un rechargement complet de la page.
                            self.history.push({ id: res.data.public_id || res.data.id, prompt: res.data.prompt_text, name: res.data.name, date: new Date(res.data.created_at).toLocaleString('fr-CA'), params: res.data.params });
                        } else {
                            remaining.push(res.item);
                            anyFailed = true;
                        }
                    });
                    // Round 65 (2026-07-27) : localStorage.setItem/removeItem peuvent lever (storage
                    // plein/indisponible) - sans ce try/catch, l'exception interrompait ce callback
                    // AVANT self.importing = false, bloquant le bouton "Importer" en permanence même
                    // si tous les imports avaient déjà réussi côté serveur (fetch déjà résolus ok).
                    try {
                        if (remaining.length > 0) {
                            localStorage.setItem('pb_history', JSON.stringify(remaining));
                        } else {
                            localStorage.removeItem('pb_history');
                        }
                    } catch (e) {}
                    self.hasLocalData = remaining.length > 0;
                    self.importing = false;
                    if (anyFailed) {
                        self._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.importError) || "Erreur lors de l'importation. Réessayez.");
                    }
                });
            },
            // Round 66 (2026-07-27) : même risque que deletePrompt() ci-dessus - removeItem() non
            // protégé faisait lever une exception non interceptée APRÈS que history ait déjà été
            // vidé visuellement, sans jamais persister réellement le vidage côté navigateur.
            clearHistory: function() { this.history = []; if (!this.isAuthenticated) { try { localStorage.removeItem('pb_history'); } catch (e) {} } },

            // === Cartes de démarrage personnalisées (Option D, 2026-07-26) ===
            // Même contrat de persistance que minuteur-visuel (custom_colors/custom_durations) :
            // GET au chargement, POST à chaque mutation, retour serveur = source de vérité (le
            // contrôleur peut tronquer/filtrer - on réaffiche toujours ce qu'il a réellement gardé).
            _genCardId: function() {
                return 'custom_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
            },

            _readLocalCustomCards: function() {
                try {
                    var raw = JSON.parse(localStorage.getItem('cp_custom_cards') || 'null');
                    if (raw && raw.version === 1 && Array.isArray(raw.cards)) return raw.cards;
                } catch (e) {}
                return [];
            },

            _saveLocalCustomCards: function() {
                try { localStorage.setItem('cp_custom_cards', JSON.stringify({ version: 1, cards: this.customCards })); } catch (e) {}
            },

            // Round 118 (2026-07-27, passe adversariale) : ce point réseau était le SEUL du fichier
            // à ne jamais signaler son échec (les 7 autres appellent _showSaveError). Sur 401/403/
            // 429/500, `r.ok` était faux mais la chaîne continuait avec data=null : customCards
            // retombait à [] et customCardsLoaded passait quand même à true. L'utilisateur croyait
            // simplement n'avoir aucune carte, ajoutait la sienne (addCustomCard ne teste que
            // !customCardsLoaded), et persistCustomCards envoyait custom_cards = [1 seule carte].
            // Côté serveur, ToolPreferenceController::update() fait array_merge(..., [$key => $value])
            // = remplacement COMPLET de la clé, pas une fusion élément par élément : toutes les
            // cartes déjà enregistrées étaient écrasées et perdues, sans qu'aucune erreur n'ait
            // jamais été affichée. On lève désormais sur !r.ok, et en cas d'échec customCardsLoaded
            // reste FALSE (le bouton « Ajouter une carte » demeure désactivé, donc aucune écriture
            // destructrice possible) avec un avertissement persistant + réessai.
            _loadCustomCards: function() {
                var self = this;
                if (this.isAuthenticated) {
                    this.customCardsLoadFailed = false;
                    fetch('/api/tool-preferences/constructeur-prompts', { headers: this._headers() })
                        .then(function(r) {
                            if (!r.ok) { throw new Error('HTTP ' + r.status); }
                            return r.json();
                        })
                        .then(function(data) {
                            var serverCards = (data && data.preferences && Array.isArray(data.preferences.custom_cards)) ? data.preferences.custom_cards : [];
                            self.customCards = serverCards;
                            // Offre d'import UNIQUEMENT si le compte n'a encore aucune carte perso ET
                            // qu'il existe des cartes invité locales - geste explicite requis, jamais
                            // de fusion automatique silencieuse (demande utilisateur 2026-07-26).
                            if (serverCards.length === 0) {
                                var local = self._readLocalCustomCards();
                                if (local.length > 0) {
                                    self._localCardsToImport = local;
                                    self.customCardsImportAvailable = true;
                                }
                            }
                            self.customCardsLoaded = true;
                        })
                        .catch(function() {
                            // customCardsLoaded reste volontairement FALSE : c'est LUI qui garde
                            // addCustomCard() et le bouton « Ajouter une carte ». Tant qu'on ignore
                            // ce que le serveur détient, on n'écrit rien.
                            self.customCardsLoadFailed = true;
                        });
                } else {
                    this.customCards = this._readLocalCustomCards();
                    this.customCardsLoaded = true;
                }
            },

            // Round 118 (2026-07-27, passe adversariale) : réessai explicite depuis l'avertissement.
            retryLoadCustomCards: function() {
                this.customCardsLoadFailed = false;
                this._loadCustomCards();
            },

            persistCustomCards: function() {
                if (this.isAuthenticated) {
                    var self = this;
                    // Round 37 (2026-07-27) : chaîner sur _cardsPersistQueue sérialise les appels -
                    // un seul POST en vol à la fois, chacun capturant l'état customCards le plus
                    // récent au moment de son envoi (jamais un snapshot obsolète en file d'attente).
                    this._cardsPersistQueue = this._cardsPersistQueue.then(function() {
                        // Round 61 (2026-07-27) : snapshot de l'état ENVOYÉ, pris ici (pas relu plus
                        // tard) - sert à détecter si l'utilisateur a continué de taper (title/
                        // query_template, x-model direct) pendant que ce POST était en vol.
                        var sentSnapshot = JSON.stringify(self.customCards);
                        return fetch('/api/tool-preferences/constructeur-prompts', {
                            method: 'POST', headers: self._headers(),
                            body: JSON.stringify({ key: 'custom_cards', value: self.customCards })
                        })
                        .then(function(r) { if (!r.ok) throw new Error('http_' + r.status); return r.json(); })
                        .then(function(data) {
                            if (data && data.preferences && Array.isArray(data.preferences.custom_cards)) {
                                // N'appliquer l'écho serveur QUE si rien n'a changé localement depuis
                                // l'envoi - sinon la frappe en cours serait écrasée silencieusement par
                                // une valeur périmée (ex. addCustomCard() focus le titre immédiatement
                                // après ce POST, l'utilisateur tape pendant qu'il est en vol).
                                if (JSON.stringify(self.customCards) === sentSnapshot) {
                                    self.customCards = data.preferences.custom_cards;
                                }
                            }
                        })
                        .catch(function() {
                            // Round 8 (2026-07-26) : seul point d'écriture de toute mutation de carte
                            // (ajout/suppression/réordonnancement/édition) - un échec silencieux ici
                            // laissait croire la modification sauvegardée alors qu'elle ne l'était pas.
                            self._showSaveError();
                        });
                    });
                } else {
                    this._saveLocalCustomCards();
                }
            },

            addCustomCard: function() {
                if (!this.customCardsLoaded || this.customCards.length >= 10) return;
                var self = this;
                // Round 78 (2026-07-27, passe adversariale) : titre traduit via i18n, repli français.
                var i18nNewCard = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var card = { id: this._genCardId(), title: i18nNewCard.newCardTitle || 'Nouvelle carte', icon: '⭐', query_template: '', hidden: false };
                this.customCards.push(card);
                // Round 7 (2026-07-26) : le bandeau d'import écrase custom_cards en entier (pas un
                // merge) - une carte ajoutée ici serait silencieusement perdue si l'utilisateur
                // cliquait ensuite "Importer mes cartes locales" avec un snapshot figé plus ancien.
                this.customCardsImportAvailable = false;
                this.persistCustomCards();
                this.editingCardTitleSnapshot = card.title;
                this.editingCardId = card.id;
                this.$nextTick(function() {
                    var el = document.getElementById('cpCardTitleInput-' + card.id);
                    if (el) el.focus();
                });
            },

            startEditCardTitle: function(card) {
                this.editingCardTitleSnapshot = card.title;
                this.editingCardId = card.id;
                this.$nextTick(function() {
                    var el = document.getElementById('cpCardTitleInput-' + card.id);
                    if (el) el.focus();
                });
            },

            commitCardTitle: function(card) {
                var t = (card.title || '').trim();
                var i18nUntitled = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                card.title = t === '' ? (i18nUntitled.untitledCard || 'Carte sans titre') : t;
                this.editingCardId = null;
                this.persistCustomCards();
                this._restoreFocusIfLost('cpCardTitleBtn-' + card.id);
            },

            cancelEditCardTitle: function(card) {
                card.title = this.editingCardTitleSnapshot;
                this.editingCardId = null;
                this._restoreFocusIfLost('cpCardTitleBtn-' + card.id);
            },

            // Round 105 (2026-07-27, passe adversariale) : commitCardTitle/cancelEditCardTitle/
            // setCardIcon/cancelEditCardPanel retirent du DOM (x-if, pas x-show) l'élément qui a le
            // focus clavier au moment de l'action (Enter/Escape sur l'input titre ou le textarea du
            // gabarit, clic sur une icône) - le focus tombait silencieusement sur <body>, échec WCAG
            // 2.4.3. Mirroir du pattern déjà établi dans action-menu.blade.php:101
            // ($refs.trigger.focus() après Escape) : ne restaure le focus QUE si document.activeElement
            // est bien tombé sur <body> (perte réelle) - si l'utilisateur a cliqué ailleurs
            // volontairement (cas du @blur qui appelle aussi commitCardTitle), le nouveau focus a déjà
            // été posé avant ce code et ne doit jamais être écrasé.
            _restoreFocusIfLost: function(elementId) {
                this.$nextTick(function() {
                    if (document.activeElement === document.body) {
                        var el = document.getElementById(elementId);
                        if (el) el.focus();
                    }
                });
            },

            // Enrichissement 2026-07-31 : la recherche (iconSearchQuery) est un état partagé - un
            // seul sélecteur d'icône peut être ouvert à la fois (iconPickerOpenId est un id unique),
            // donc pas de risque de fuite entre cartes. Réinitialisée à CHAQUE ouverture/fermeture
            // pour ne jamais rouvrir un sélecteur avec une recherche périmée d'une autre carte. Le
            // focus part directement dans le champ de recherche à l'ouverture (pattern combobox
            // standard) pour permettre de taper immédiatement au clavier.
            toggleIconPicker: function(cardId) {
                var opening = this.iconPickerOpenId !== cardId;
                this.iconPickerOpenId = opening ? cardId : null;
                this.iconSearchQuery = '';
                if (opening) {
                    this.$nextTick(function() {
                        var el = document.getElementById('cpIconSearch-' + cardId);
                        if (el) el.focus();
                    });
                }
            },

            // Round 106 (2026-07-27, passe adversariale) : le sélecteur d'icône (.ct-emoji-grid,
            // template x-if) se fermait aussi via @keydown.escape.window sur le conteneur racine
            // (assignation Alpine inline directe, hors setCardIcon()) - un 5e chemin qui contournait
            // entièrement _restoreFocusIfLost() (round 105), laissant le focus tomber sur <body>
            // quand Échap est pressé pendant qu'un bouton emoji a le focus. Fixé : ce handler global
            // appelle désormais closeIconPicker() qui réutilise _restoreFocusIfLost() déjà établie.
            closeIconPicker: function() {
                if (!this.iconPickerOpenId) return;
                var id = this.iconPickerOpenId;
                this.iconPickerOpenId = null;
                this.iconSearchQuery = '';
                this._restoreFocusIfLost('cpCardIconBtn-' + id);
            },

            setCardIcon: function(card, icon) {
                card.icon = icon;
                this.iconPickerOpenId = null;
                this.iconSearchQuery = '';
                this.persistCustomCards();
                this._restoreFocusIfLost('cpCardIconBtn-' + card.id);
            },

            toggleCardPanel: function(card) {
                if (this.editingCardPanelId === card.id) {
                    this.editingCardPanelId = null;
                } else {
                    this.editingCardPanelSnapshot = card.query_template;
                    this.editingCardPanelId = card.id;
                }
            },

            cancelEditCardPanel: function(card) {
                card.query_template = this.editingCardPanelSnapshot;
                this.editingCardPanelId = null;
                this._restoreFocusIfLost('cpCardPanelBtn-' + card.id);
            },

            // Round 95 (2026-07-27, passe adversariale) : rafraîchit le snapshot d'annulation à
            // CHAQUE blur du textarea (pas seulement à l'OUVERTURE du panneau via toggleCardPanel).
            // Le panneau reste ouvert après un blur (editingCardPanelId n'est remis à null que par
            // toggleCardPanel()/cancelEditCardPanel()) - un clic sur un autre bouton de la MÊME
            // carte (↑/↓/👁️/🗑️) pendant l'édition déclenche un blur intermédiaire qui persiste la
            // valeur courante côté serveur SANS jamais rafraîchir editingCardPanelSnapshot. Un
            // Échap ultérieur restaurait alors le snapshot pris à l'ouverture (plus ancien que ce
            // qui est réellement persisté) - désync client/serveur silencieuse.
            commitCardPanelBlur: function(card) {
                this.editingCardPanelSnapshot = card.query_template;
                this.persistCustomCards();
            },

            toggleCardHidden: function(card) {
                card.hidden = !card.hidden;
                this.persistCustomCards();
            },

            // Confirmation 2 temps via le pattern global du site (jamais de confirm() natif) :
            // clic sur 🗑️ ouvre la modale globale, le clic "Confirmer" dans la modale déclenche
            // le callback ci-dessous - voir Modules/FrontTheme master.blade.php.
            confirmDeleteCard: function(card) {
                var self = this;
                var i18nDelete = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                this.$dispatch('open-confirm-global', {
                    message: i18nDelete.deleteCardConfirm || 'Supprimer cette carte ?',
                    callback: function() { self.deleteCustomCard(card.id); }
                });
            },

            deleteCustomCard: function(cardId) {
                var idx = this.customCards.findIndex(function(c) { return c.id === cardId; });
                if (idx === -1) return;
                this.customCards.splice(idx, 1);
                if (this.selectedTask === cardId) this.selectedTask = '';
                this.persistCustomCards();
            },

            // Réordonnancement : boutons ↑/↓ (alternative clavier WCAG 2.2 obligatoire au
            // glisser-déposer ci-dessous) - direction -1 = plus tôt, +1 = plus tard.
            moveCustomCard: function(card, direction) {
                var idx = this.customCards.findIndex(function(c) { return c.id === card.id; });
                var newIdx = idx + direction;
                if (idx === -1 || newIdx < 0 || newIdx >= this.customCards.length) return;
                var tmp = this.customCards[idx];
                this.customCards[idx] = this.customCards[newIdx];
                this.customCards[newIdx] = tmp;
                this.persistCustomCards();
            },

            // Glisser-déposer natif HTML5 (même pattern léger que generateur-equipes.blade.php -
            // aucune bibliothèque tierce ajoutée, DRY avec l'existant sur ce site).
            // Round 53 (2026-07-27) : l'ancienne version gardait l'id de la carte glissée dans
            // _draggedCardId (état JS mutable) - un self-drop (carte déposée sur elle-même)
            // retournait tôt SANS jamais réinitialiser cette variable, et aucun handler dragend
            // ne la nettoyait sur un drag annulé. N'importe quel drag-and-drop natif SANS
            // RAPPORT déposé plus tard sur une carte (texte sélectionné, image, lien) déclenchait
            // alors un réordonnancement silencieux basé sur cet id périmé, persisté au serveur.
            // Fix : plus aucun état JS entre le début et la fin du drag - dataTransfer (avec un
            // type MIME propre à l'app, jamais posé par un drag natif non lié) est la SEULE
            // source de vérité, lue directement dans dropOnCustomCard().
            dragStartCustomCard: function(event, card) {
                try { event.dataTransfer.setData('application/x-cp-custom-card-id', card.id); } catch (e) {}
            },

            dropOnCustomCard: function(event, targetCard) {
                event.preventDefault();
                var draggedId = '';
                try { draggedId = event.dataTransfer.getData('application/x-cp-custom-card-id'); } catch (e) {}
                if (!draggedId || draggedId === targetCard.id) return;
                var fromIdx = this.customCards.findIndex(function(c) { return c.id === draggedId; });
                var toIdx = this.customCards.findIndex(function(c) { return c.id === targetCard.id; });
                if (fromIdx === -1 || toIdx === -1) return;
                var moved = this.customCards.splice(fromIdx, 1)[0];
                this.customCards.splice(toIdx, 0, moved);
                this.persistCustomCards();
            },

            // Import invité → compte (geste explicite uniquement, voir _loadCustomCards) : envoie
            // les cartes locales au backend via le MÊME endpoint que persistCustomCards, puis vide
            // le localStorage et affiche un toast (window.toast, helper global du site).
            importLocalCustomCards: function() {
                var self = this;
                var local = this._localCardsToImport || [];
                // Round 97 (2026-07-27, passe adversariale) : sans cette garde, un double-clic sur
                // "Importer mes X cartes locales" déclenchait 2 appels concurrents - le 2e capturait
                // la même liste `local` (fermeture non rafraîchie avant résolution du 1er) et la
                // fusionnait à nouveau avec l'écho serveur du 1er, créant de VRAIS doublons persistés
                // en base (sanitizeCustomCards() génère un nouvel id sur collision au lieu de rejeter).
                if (this.importingCards || local.length === 0) return;
                this.importingCards = true;
                // Round 38 (2026-07-27) : ce fetch écrivait directement (hors _cardsPersistQueue,
                // round 37) vers le MÊME endpoint/clé que persistCustomCards() ET remplaçait
                // aveuglément custom_cards par `local` seul - un clic concurrent sur "Ajouter une
                // carte" (les deux boutons sont actifs en même temps quand customCards est vide)
                // perdait silencieusement l'une des deux mutations selon l'ordre de résolution
                // réseau. Chaîner sur la file d'attente ET fusionner avec l'état courant (au lieu
                // d'écraser) élimine les deux pertes : la carte ajoutée pendant l'attente ET les
                // cartes importées restent toutes présentes, plafonnées à 10 (limite d'addCustomCard).
                this._cardsPersistQueue = this._cardsPersistQueue.then(function() {
                    // Round 49 (2026-07-27) : nombre de cartes locales RÉELLEMENT conservées après le
                    // plafond de 10 (customCards.length peut déjà être > 0 si une carte a été ajoutée
                    // pendant que cet import était en file d'attente, round 38) - calculé AVANT l'appel
                    // réseau pour refléter fidèlement ce qui sera vraiment envoyé/persisté.
                    var spaceAvailable = Math.max(0, 10 - self.customCards.length);
                    var importedCount = Math.min(local.length, spaceAvailable);
                    var truncatedCount = local.length - importedCount;
                    var merged = self.customCards.concat(local).slice(0, 10);
                    // Round 62 (2026-07-27) : même risque que round 61 (persistCustomCards) - si
                    // l'utilisateur clique "Ajouter une carte" (addCustomCard(), mutation SYNCHRONE
                    // et immédiate de customCards) pendant que CE fetch d'import est en vol, appliquer
                    // l'écho serveur sans vérifier écrasait silencieusement la carte fraîchement
                    // ajoutée - elle disparaissait de l'écran, puis le persistCustomCards() suivant en
                    // file d'attente re-persistait cet état déjà amputé (perte définitive, sans erreur).
                    var sentSnapshot = JSON.stringify(self.customCards);
                    return fetch('/api/tool-preferences/constructeur-prompts', {
                        method: 'POST', headers: self._headers(),
                        body: JSON.stringify({ key: 'custom_cards', value: merged })
                    })
                    .then(function(r) { if (!r.ok) throw new Error('http_' + r.status); return r.json(); })
                    .then(function(data) {
                        // N'applique l'écho serveur à customCards que si rien n'a changé localement
                        // depuis l'envoi - sinon on garde l'état local (plus récent, ex. une carte
                        // ajoutée entre-temps) et on laisse le persistCustomCards() suivant (déjà en
                        // file d'attente) le re-synchroniser côté serveur normalement. Le reste du
                        // traitement (troncature, localStorage, toast) continue dans tous les cas :
                        // l'import a bel et bien réussi côté serveur avec la valeur envoyée.
                        if (JSON.stringify(self.customCards) === sentSnapshot) {
                            self.customCards = (data.preferences && data.preferences.custom_cards) || [];
                        }
                        // Round 49 : si le plafond de 10 a tronqué certaines cartes locales, elles
                        // restent en attente d'import (jamais effacées silencieusement) - l'utilisateur
                        // peut supprimer une carte existante puis relancer l'import pour les récupérer.
                        if (truncatedCount > 0) {
                            self._localCardsToImport = local.slice(importedCount);
                            self.customCardsImportAvailable = self._localCardsToImport.length > 0;
                            // Round 54 (2026-07-27) : cp_custom_cards est TOUJOURS stocké sous la forme
                            // enveloppée {version:1, cards:[...]} (voir _readLocalCustomCards/
                            // _saveLocalCustomCards), jamais un tableau brut. L'ancien code lisait un
                            // tableau brut et appelait .slice() dessus - TypeError silencieusement
                            // avalé par le catch, le trim n'avait donc JAMAIS lieu en pratique : les
                            // cartes déjà importées avec succès restaient dupliquées en localStorage.
                            try {
                                localStorage.setItem('cp_custom_cards', JSON.stringify({ version: 1, cards: self._localCardsToImport }));
                            } catch (e) {}
                        } else {
                            // Round 65 (2026-07-27) : même risque que la branche if ci-dessus (round 54,
                            // déjà protégée) - removeItem() non protégé faisait échouer ce .then() sur
                            // storage indisponible, tombant dans le .catch() qui affiche un toast
                            // d'ERREUR trompeur alors que l'import a réellement réussi côté serveur.
                            try { localStorage.removeItem('cp_custom_cards'); } catch (e) {}
                            self.customCardsImportAvailable = false;
                            self._localCardsToImport = [];
                        }
                        try {
                            var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                            if (importedCount === 0) {
                                window.toast(i18n.customCardsImportLimitReached || 'Limite de 10 cartes atteinte - aucune carte importée. Supprimez-en une puis réessayez.', 'warning', 5000);
                            } else if (truncatedCount > 0) {
                                var partialMsg = (i18n.customCardsImportedPartial || '{imported} carte(s) importée(s) - {remaining} en attente (limite de 10 atteinte).')
                                    .replace('{imported}', importedCount).replace('{remaining}', truncatedCount);
                                window.toast(partialMsg, 'warning', 5000);
                            } else {
                                // Round 25 (2026-07-27) : accord singulier/pluriel - l'ancien template unique
                                // "{count} cartes importées" restait au pluriel même pour 1 carte ("1 cartes importées").
                                var msg = importedCount === 1
                                    ? (i18n.customCardsImportedOne || '1 carte importée')
                                    : (i18n.customCardsImportedMany || '{count} cartes importées').replace('{count}', importedCount);
                                window.toast(msg, 'success', 3000);
                            }
                        } catch (e) {}
                        self.importingCards = false;
                    })
                    .catch(function() {
                        // Round 9 (2026-07-26) : même endpoint que persistCustomCards (fix round 8),
                        // mais sur échec HTTP non-ok le localStorage était quand même vidé et un toast
                        // "succès" affiché malgré la perte réelle des cartes locales - jamais corrigé ici.
                        // Le snapshot local (_localCardsToImport) et cp_custom_cards restent intacts,
                        // rien n'est effacé tant que le serveur n'a pas confirmé.
                        self.importingCards = false;
                        self._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.importError) || "Erreur lors de l'importation. Réessayez.");
                    });
                });
            },

            exportPrompt: function() {
                try {
                    var blob = new Blob([this.prompt], { type: 'text/plain' });
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'prompt.txt';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    this.track('prompt_export', { tool: 'constructeur-prompts' });
                } catch (e) {
                    this._showSaveError((window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.exportError) || "Impossible d'exporter le fichier. Réessayez.");
                }
            },

            // Affiche/masque le panneau "Améliorer avec mon IA" (Option 3 hybride, Partie B) —
            // aucun appel réseau : ne fait qu'afficher les boutons "Ouvrir dans"/"Copier"
            // reparamétrés avec le méta-prompt (get metaPrompt() ci-dessus).
            toggleMetaPrompt: function() {
                this.metaPromptShown = !this.metaPromptShown;
            }
        };
    });
});
