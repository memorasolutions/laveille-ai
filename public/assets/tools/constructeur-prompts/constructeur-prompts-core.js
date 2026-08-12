// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// constructeur-prompts-core.js — logique Alpine.js du wizard « Constructeur de prompts » (4 étapes).
// Extrait de resources/views/public/tools/constructeur-prompts.blade.php (Phase 0 audit 2026-07-26)
// pour permettre au navigateur de mettre ce JS en cache (auparavant inline, jamais caché).
// Les données dynamiques (personas/verbes/audiences configurables + i18n) sont injectées par
// le Blade via window.promptBuilderConfig (même pattern que window.taxConfig sur calculatrice-taxes).
// Fichier servi tel quel (pas de build Vite pour Modules/Tools — même convention que
// minuteur-visuel-core.js, anonymizer-core.js, etc.) : cache-busting via ?v={semver} en query string.
document.addEventListener('alpine:init', function() {
    // ACTION: remapper les anciennes valeurs d'audience (liste pré-2026-08-06) vers les nouvelles
    // MCP: openrouter→deepseek-v4-flash (Hermes indisponible), validé par Opus
    // RAISON: les prompts sauvegardés avec pro/debutants/entrepreneurs/techniques doivent rester restaurables
    const migrateAudienceValues = (values) => {
        if (!Array.isArray(values)) return [];
        const mapping = {
            'pro': 'collegues',
            'debutants': 'grand_public',
            'entrepreneurs': 'direction',
            'techniques': 'collegues'
        };
        const seen = new Set();
        return values.reduce((acc, val) => {
            const mapped = mapping[val] ?? val;
            if (!seen.has(mapped)) {
                seen.add(mapped);
                acc.push(mapped);
            }
            return acc;
        }, []);
    };
    // Zones géographiques / verbes de recherche (tâche 2026-08-12) : les 3 valeurs LITTÉRALES
    // injectées dans le prompt (this.verb / this.verb2) qui déclenchent la phrase de date et/ou le
    // champ Zones - copie volontaire des `value` de $defaultVerbs (Blade), jamais les labels (même
    // convention que _formatExclusiveValues/_formatStructureValues plus bas dans ce fichier : une
    // valeur qui pilote un comportement spécial du gabarit de prompt garde sa propre copie ici,
    // jamais une dépendance runtime au Blade).
    const SEARCH_VERB_PLAIN = 'Recherche';
    const SEARCH_VERB_WEB = 'Recherche sur Internet, en priorisant les sites officiels et pertinents';
    const SEARCH_VERB_DEEP = 'Recherche en profondeur, Internet inclus';
    const SEARCH_VERBS_ALL = [SEARCH_VERB_PLAIN, SEARCH_VERB_WEB, SEARCH_VERB_DEEP];
    const SEARCH_VERBS_DATED = [SEARCH_VERB_WEB, SEARCH_VERB_DEEP];
    Alpine.data('promptBuilder', function() {
        return {
            step: 1,
            // Brouillon local (2026-08-11) : persiste le wizard en cours dans localStorage
            // (cpDraft_v1) pour survivre à une fermeture accidentelle d'onglet - voir
            // _loadDraft()/_saveDraftNow()/_scheduleDraftSave() plus bas. draftRestored pilote la
            // bannière discrète du Blade (template x-if="draftRestored").
            _draftKey: 'cpDraft_v1',
            _draftMaxAgeMs: 24 * 60 * 60 * 1000,
            _draftSaveTimer: null,
            _draftDisabled: false,
            _hashStepApplied: false,
            draftRestored: false,
            // Correctif régression prod (2026-08-11) : instantané JSON de wizardParams pris sur le
            // formulaire VIERGE, tout au début d'init() (voir plus bas) - AVANT _loadDraft() et avant
            // toute restauration (?edit=/?remix=). _hasSignificantDraftContent() compare l'état courant
            // à cet instantané plutôt que d'énumérer des champs à la main : la liste codée en dur
            // (taskObject/contextInfo/personaCustom/...) ignorait tous les champs à SÉLECTION
            // (personaPreset, verb, tone, technique, formats, cases à cocher...) - un rôle choisi au
            // menu déroulant de l'étape 1 sans texte tapé était donc jugé "rien à sauvegarder" et perdu
            // au refresh. Reste null tant qu'init() n'a pas tourné (garde défensive ci-dessous).
            _draftDefaultSnapshot: null,
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
            // Bonification « QCM forcé » / « Répéter pour ma liste » (2026-08-07, Options avancées) :
            // 2 cases indépendantes, chacune ajoute UN segment fixe en TOUTE FIN du prompt généré
            // (après l'ancrage « Produis maintenant », voir get promptSegments() plus bas) - ordre
            // documenté : répéter-pour-liste avant QCM forcé si les deux sont cochées.
            constraintForceQcm: false,
            constraintRepeatList: false,
            constraintCustom: '',
            technique: 'zero-shot',
            examples: '',
            // Contexte additionnel (#1593a, 2026-08-07) : champ facultatif DISTINCT de la tâche
            // (taskObject), sur le même modèle qu'examples ci-dessus - informations de fond
            // (ce qui a déjà été essayé, contraintes, contexte du projet...) plutôt que la
            // demande elle-même. Voir la section CONTEXTE ADDITIONNEL de get promptSegments().
            contextInfo: '',
            // Zones géographiques (tâche 2026-08-12, verbes de recherche Internet) : champ
            // conditionnel, visible et injecté dans le prompt uniquement quand un verbe de
            // recherche est choisi (voir isSearchVerbActive/isDatedSearchVerbActive plus bas).
            // Plafond de 5 - au-delà, un prompt à sections multiples devient long à lire pour peu
            // de gain réel ; message d'atteinte de plafond affiché via zoneLimitMessage (voir
            // _addZoneEntries/removeZone plus bas).
            zones: [],
            zoneInput: '',
            zoneLimitMessage: false,
            _zonesMax: 5,
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
            // IA préférée mémorisée (2026-08-07) : dernier choix réel d'« Ouvrir dans » persisté en
            // localStorage (clé versionnée, même garde try/catch que le reste du fichier - voir
            // _loadOpenTargetPref()/_recordOpenTargetPref() plus bas). openTargetHasPref distingue le
            // tout premier passage (aucune préférence enregistrée : 5 boutons à plat, comportement
            // inchangé) du passage suivant (bouton principal + "Autres choix" replié, voir Blade).
            _openTargetPrefKey: 'cpOpenTargetPref_v1',
            openTargetHasPref: false,
            openTargetNames: { chatgpt: 'ChatGPT', claude: 'Claude', perplexity: 'Perplexity', gemini: 'Gemini', mistral: 'Mistral' },
            get openTargetLabel() { return this.openTargetNames[this.openTarget] || 'ChatGPT'; },
            showHelp: { persona: false, contextInfo: false, cadreStrict: false },
            // Round 77 (2026-07-27, passe adversariale) : repli français en dur, mais valeur réelle
            // toujours prise dans window.promptBuilderConfig.helps (injecté par le Blade via __(),
            // même pattern que i18n.* juste au-dessus) - donc traduit en EN/ES quand la locale change.
            helps: (window.promptBuilderConfig && window.promptBuilderConfig.helps) || {
                persona: 'Donner un rôle à l\'IA oriente le ton, le style et le vocabulaire de sa réponse - mais ne la rend ni plus experte ni plus fiable. Ex: « Tu es un expert marketing » donnera un ton plus stratégique ; pour la justesse, donnez du contexte et des consignes précises.',
                verb: 'Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.',
                taskObject: 'Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de détails, meilleur sera le résultat. Astuce : sélectionnez un mot de votre texte et cliquez sur « En faire un espace à remplir » - à chaque réutilisation, l\'outil vous demandera la nouvelle valeur (par exemple le sujet de la semaine) sans que vous ayez à réécrire le reste.',
                contextInfo: 'Informations de fond utiles que l\'IA doit connaître sans qu\'elles fassent partie de la demande elle-même : ce qui a déjà été essayé, des contraintes, le contexte du projet... Ici aussi, vous pouvez sélectionner un mot et cliquer sur « En faire un espace à remplir » pour pouvoir le changer facilement la prochaine fois.',
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
            // Variables réutilisables {{...}} (#1593b, 2026-08-07) : valeurs saisies pour remplir
            // les motifs {{nom}} détectés dans le prompt (voir get promptVariables()/get
            // promptFilled() plus bas). Clé = nom exact de la variable détectée (espaces/accents
            // acceptés), valeur = texte saisi. Jamais persisté séparément : les {{...}} restent
            // tels quels dans le prompt sauvegardé (aucune modification du schéma DB voulue).
            varValues: {},
            // Espaces à remplir (tâches 1660-1665, design panel 2026-08-07) : ancrage PAR CHAÎNE
            // EXACTE, aucune position stockée (voir spec §Modèle de données). `spaces` alimente la
            // bande de repérage (étape 2) ET le bloc de remplissage ; `text` est à la fois le nom
            // affiché, l'exemple et la valeur de repli - renommer une pastille remplace la chaîne
            // dans les textareas eux-mêmes (voir _renameSpaceOccurrences). `pending: true` = espace
            // tout juste inséré par le bouton « + Ajouter un espace à remplir », dont le nom n'a pas
            // encore été précisé (pastille « à préciser »).
            spaces: [],
            // Valeurs de remplissage de la SESSION en cours seulement - jamais persistées avec le
            // prompt (contrairement à `spaces`, qui ne stocke que les chaînes ancrées, pas les
            // réponses). Clé = space.text exact.
            spaceValues: {},
            // Cache « espace non retrouvé dans le texte » - recalculé au blur des 2 textareas et
            // juste avant copie/ouverture (JAMAIS à chaque frappe, pour éviter un clignotement des
            // pastilles pendant la rédaction - voir _refreshSpaceMissing()).
            spaceMissingCache: {},
            // Renommage inline (UI - création, point C) : un seul espace CONFIRMÉ (non pending) en
            // édition à la fois - les espaces `pending`, eux, portent chacun leur propre champ
            // ouvert nativement (sp.draftText), pas besoin de cet index partagé pour eux.
            spaceEditingIndex: null,
            spaceEditingText: '',
            // Bulle de sélection (geste A, desktop + mobile) : état pur, indépendant du DOM réel -
            // testable sans navigateur. `fieldId` distingue #cpTaskObject de #cpContextInfo pour
            // afficher la bulle au bon endroit (une bulle inline sous chaque champ, jamais une bulle
            // positionnée en pixels - plus robuste, même esprit que « près du champ » de la spec).
            spaceBubble: { show: false, text: '', fieldId: '' },
            // Dernier textarea focalisé parmi les 2 champs concernés - sert de défaut au bouton
            // « + Ajouter un espace à remplir » (spec : insère au curseur du dernier focalisé,
            // repli taskObject si aucun focus).
            _lastFocusedSpaceField: 'cpTaskObject',
            // Éclaire dans l'aperçu les segments correspondant au champ actuellement en train d'être
            // rempli (voir le bloc « Remplis tes espaces » plus bas, @focus/@blur).
            focusedSpaceText: '',
            // Mots-outils français trop courts/ambigus pour servir d'ancrage fiable (spec §UI -
            // création, point A) - comparaison sur le texte EXACT trimé, jamais une recherche de
            // sous-chaîne (« de » dans « demande » ne doit jamais être refusé).
            _spaceStopWords: ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'à', 'de', 'du', 'ce', 'ça'],
            // Mémoire inter-sessions (spec §UI - remplissage) : dernière valeur saisie par espace,
            // pour le bouton « Reprendre : ... ». Distincte de spaceValues (session en cours) et de
            // `spaces` (jamais de valeurs de remplissage dans le prompt sauvegardé).
            _spaceLastValuesKey: 'cpSpaceLastValues_v1',
            spaceLastValues: {},
            // Rétention locale invités (#1580, 2026-08-07) : historique AUTOMATIQUE des derniers
            // prompts générés, pour les visiteurs NON connectés uniquement (les connectés ont déjà
            // « Mes prompts » en base). Clé localStorage DISTINCTE et versionnée (cpGuestHistory_v1,
            // voir _guestHistoryKey plus bas) - volontairement séparée du tableau `history`/
            // `pb_history` ci-dessus, qui reste lié au bouton "Sauvegarder" (exige un compte, voir
            // addToHistory()) et n'est jamais alimenté automatiquement pour un invité.
            guestHistory: [],
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
            // ACTION: pattern select + pastilles amovibles (2026-08-06, demande explicite :
            // « garder l'ancien menu déroulant, mais chaque sélection devient une pastille »)
            // MCP: openrouter→deepseek-v4-flash, validé par Opus
            // RAISON: remplace les grilles de cartes-checkbox pour Format de sortie et audiences.
            addFormatFromSelect: function (value) {
                if (!value) return;
                if (this.isFormatDisabled(value)) return;
                if (this.formatsSelected.indexOf(value) === -1) {
                    this.formatsSelected.push(value);
                    this.handleFormatChange(value);
                }
            },
            removeFormat: function (value) {
                var index = this.formatsSelected.indexOf(value);
                if (index !== -1) this.formatsSelected.splice(index, 1);
            },
            formatLabel: function (value) {
                var found = this.formats.find(function (f) { return f.value === value; });
                return found ? found.label : value;
            },
            addAudienceFromSelect: function (value) {
                if (!value) return;
                if (this.audiencePresets.indexOf(value) === -1) {
                    this.audiencePresets.push(value);
                }
            },
            removeAudience: function (value) {
                var index = this.audiencePresets.indexOf(value);
                if (index !== -1) this.audiencePresets.splice(index, 1);
            },
            audienceLabel: function (value) {
                var found = this.audiences.find(function (a) { return a.value === value; });
                return found ? found.label : value;
            },
            // Zones géographiques (tâche 2026-08-12) : clé de DÉDOUBLONNAGE normalisée (minuscules,
            // accents retirés, espaces réduits) - jamais utilisée pour l'affichage, seulement pour
            // comparer. Le libellé EXACTEMENT tel que saisi par la personne reste dans `zones`.
            // Réutilise _normalizeIconText (DRY, déjà défini plus haut pour la recherche d'icônes -
            // même besoin exact : comparaison insensible aux accents et à la casse) plutôt que de
            // dupliquer une 3e fois la logique NFD/diacritiques déjà présente 2 fois dans ce fichier
            // (voir aussi _taskWithoutLeadingVerb) ; seul l'espace réduit est ajouté par-dessus, ce
            // que _normalizeIconText ne fait pas (pas nécessaire à la recherche d'icônes).
            _normalizeZoneKey: function (str) {
                return this._normalizeIconText(str).trim().replace(/\s+/g, ' ');
            },
            // Ajout interne partagé par addZoneFromInput() (une seule entrée, jamais découpée) et
            // handleZonePaste() (plusieurs entrées, déjà découpées sur virgule/point-virgule) -
            // applique le plafond (_zonesMax) et le dédoublonnage (clé normalisée) une seule fois.
            _addZoneEntries: function (rawList) {
                var self = this;
                var added = 0;
                (rawList || []).forEach(function (raw) {
                    var text = String(raw == null ? '' : raw).trim();
                    if (!text) return;
                    if (self.zones.length >= self._zonesMax) { self.zoneLimitMessage = true; return; }
                    var key = self._normalizeZoneKey(text);
                    var exists = self.zones.some(function (z) { return self._normalizeZoneKey(z) === key; });
                    if (exists) return;
                    self.zones.push(text);
                    added++;
                });
                return added;
            },
            // Ajout MANUEL (bouton « Ajouter » ou touche Entrée) : jamais découpé sur la virgule -
            // permet de saisir un nom contenant une virgule légitime (ex. « Washington, D.C. »,
            // « Montréal, Québec ») sans le casser. Voir handleZonePaste ci-dessous pour le seul cas
            // où la virgule sépare (collage d'une liste) - compromis documenté dans le rapport.
            addZoneFromInput: function () {
                if (!this.zoneInput || !this.zoneInput.trim()) return;
                this._addZoneEntries([this.zoneInput]);
                this.zoneInput = '';
            },
            // Collage d'une liste séparée par virgules/points-virgules : découpage automatique
            // UNIQUEMENT si le texte collé contient au moins un de ces séparateurs (un collage sans
            // virgule se comporte comme une saisie normale, non intercepté). preventDefault()
            // seulement dans ce cas - le collage normal (une seule valeur) n'est jamais bloqué.
            handleZonePaste: function (event) {
                var clip = event.clipboardData || window.clipboardData;
                var text = clip ? clip.getData('text') : '';
                if (text && /[,;]/.test(text)) {
                    event.preventDefault();
                    this._addZoneEntries(text.split(/[,;]+/));
                    this.zoneInput = '';
                }
            },
            removeZone: function (idx) {
                var wasAtLimit = this.zones.length >= this._zonesMax;
                this.zones.splice(idx, 1);
                if (wasAtLimit) this.zoneLimitMessage = false;
            },
            _isSearchVerbValue: function (v) { return SEARCH_VERBS_ALL.indexOf(v) !== -1; },
            _isDatedSearchVerbValue: function (v) { return SEARCH_VERBS_DATED.indexOf(v) !== -1; },
            // Pilote la visibilité du champ Zones (Blade, x-show) ET l'injection dans le prompt
            // (get promptSegments()) - un verbe personnalisé (verbType==='custom') n'est jamais
            // reconnu comme verbe de recherche, seul un verbe PRÉDÉFINI l'est.
            get isSearchVerbActive() {
                return this._isSearchVerbValue(this.verbType === 'preset' ? this.verb : '') ||
                    (this.secondTaskEnabled && this._isSearchVerbValue(this.verbType2 === 'preset' ? this.verb2 : ''));
            },
            // Pilote la phrase de date (get promptSegments()) - sous-ensemble d'isSearchVerbActive
            // (verbes 2 et 3 seulement, jamais le verbe 1 "Recherche" seul).
            get isDatedSearchVerbActive() {
                return this._isDatedSearchVerbValue(this.verbType === 'preset' ? this.verb : '') ||
                    (this.secondTaskEnabled && this._isDatedSearchVerbValue(this.verbType2 === 'preset' ? this.verb2 : ''));
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
                return (i18n.addedAudience || 'Sera inclus dans ton prompt : un niveau de langage adapté à ') + txt + '.';
            },
            get feedbackResultat() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                if (this.verbText) parts.push((i18n.fragVerb || 'verbe ') + '« ' + this.verbText + ' »');
                if (this.formatText) parts.push((i18n.fragFormat || 'format ') + this.formatText.toLowerCase());
                if (this.length) parts.push((i18n.fragLength || 'longueur ') + this.length.toLowerCase());
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Sera inclus dans ton prompt : ') + parts.join(', ') + '.';
            },
            get feedbackTon() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var parts = [];
                if (this.personaText) parts.push((i18n.fragRole || 'rôle ') + '« ' + this.personaText + ' »');
                if (this.tone) parts.push((i18n.fragTone || 'ton ') + this.tone.toLowerCase());
                if (!parts.length) return '';
                return (i18n.addedPrefix || 'Sera inclus dans ton prompt : ') + parts.join(', ') + '.';
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
                return (i18n.addedPrefix || 'Sera inclus dans ton prompt : ') + parts.join(', ') + '.';
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
                return (i18n.addedPrefix || 'Sera inclus dans ton prompt : ') + parts.join(', ') + '.';
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
                    parts.push((i18nSummary.summaryAction || 'Tâche demandée : ') + actionVerb + ' ' + this._fillSpacesInText(this._taskWithoutLeadingVerb(actionVerb, this.taskObject)) + '.');
                } else if (this.taskObject) {
                    parts.push((i18nSummary.summarySubject || 'Sujet : ') + this._fillSpacesInText(this.taskObject) + '.');
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
                var self = this;
                var segs = [];
                var firstSection = true;
                function tool(s) { if (s) segs.push({ text: s, kind: 'tool' }); }
                function user(s) { if (s) segs.push({ text: s, kind: 'user' }); }
                function startSection() {
                    if (!firstSection) tool('\n\n');
                    firstSection = false;
                }
                // Espaces à remplir (tâches 1660-1665) : les segments 'user' issus de taskObject et
                // contextInfo sont SOUS-DÉCOUPÉS - toute occurrence EXACTE d'un spaces[].text devient
                // un segment de type 'space' distinct, le reste reste 'user'. Découpage par balayage
                // de chaîne à CHAQUE rendu (réactif, aucune position mémorisée) - restreint donc
                // naturellement le remplacement aux champs de la personne, jamais aux gabarits de
                // l'outil (qui passent par tool(), jamais par userSpace()). Les textes les plus longs
                // sont testés en premier pour qu'un espace court ne masque jamais un espace plus long
                // dont il est une sous-chaîne (ex. « fractions » vs « les fractions »).
                var spaceTexts = (self.spaces || [])
                    .map(function(sp) { return sp.text; })
                    .filter(function(t) { return !!t; })
                    .sort(function(a, b) { return b.length - a.length; });
                // Couche 2 (canonKey, 2026-08-09) : forme canonique de RECHERCHE précalculée pour
                // chaque espace - remplacement 1:1 en longueur (voir _canonSearchText), donc les
                // indices restent valides sur `str` (texte BRUT) à chaque position testée ci-dessous.
                // Un collage d'apostrophe courbe ou d'espace insécable dans le texte de la personne
                // matche désormais le même espace créé avec l'apostrophe droite/l'espace simple.
                var canonSpaceTexts = spaceTexts.map(function(t) { return self._canonSearchText(t); });
                function userSpace(str) {
                    if (!str) return;
                    if (spaceTexts.length === 0) { user(str); return; }
                    var canonStr = self._canonSearchText(str);
                    var i = 0, buffer = '';
                    while (i < str.length) {
                        var matched = null;
                        for (var k = 0; k < spaceTexts.length; k++) {
                            var t = spaceTexts[k];
                            var ct = canonSpaceTexts[k];
                            // Frontières de mots aux 2 bords (round adversarial DeepSeek 2026-08-07) :
                            // « son » ne matche jamais au milieu de « maison ». Comparaison sur la
                            // forme canonique (ct, même longueur que t) - la frontière se vérifie
                            // toujours sur `str` brut, aux mêmes indices.
                            if (t && canonStr.substr(i, ct.length) === ct && self._isSpaceBoundary(str, i - 1) && self._isSpaceBoundary(str, i + t.length)) { matched = t; break; }
                        }
                        if (matched) {
                            if (buffer) { user(buffer); buffer = ''; }
                            // RÈGLE D'OR : le segment affiché/copié garde le texte RAW réellement tapé
                            // par la personne à cette position (byte pour byte - garantie core.js
                            // ~1038-1040), jamais la forme canonique du dictionnaire. Seul `spaceRef`
                            // (clé de recherche dans spaceValues, via spaceValueForText()) référence la
                            // forme canonique de l'espace.
                            segs.push({ text: str.substr(i, matched.length), kind: 'space', spaceRef: matched });
                            i += matched.length;
                        } else {
                            buffer += str.charAt(i);
                            i += 1;
                        }
                    }
                    if (buffer) user(buffer);
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
                    // G1 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : le boilerplate
                    // "expertise approfondie / communiques de manière claire et efficace" ne disait
                    // rien de concret à l'IA - remplacé par une consigne courte et actionnable.
                    tool('. Écris de façon claire, précise et adaptée à ton lecteur.');
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
                    userSpace(this._taskWithoutLeadingVerb(actionVerb, this.taskObject));
                    tool('.\n2) ');
                    if (secondActionVerbIsUser) { user(secondActionVerb); } else { tool(secondActionVerb); }
                    // G2 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : héritage EXPLICITE
                    // de l'étape 2 sur l'étape 1 (même lecteur, même esprit), au lieu d'un simple
                    // "à partir du résultat" qui ne précisait rien sur la continuité attendue.
                    tool(' le résultat de l\'étape 1, pour le même lecteur et dans le même esprit, sauf indication contraire dans le contexte.');
                } else if (actionVerb && this.taskObject) {
                    startSection();
                    tool('Ta tâche : ');
                    if (actionVerbIsUser) { user(actionVerb); } else { tool(actionVerb); }
                    tool(' ');
                    // Sans ce retrait, une demande commençant déjà par le verbe donnait
                    // « Ta tâche : Rédige rédige un courriel » dans le prompt envoyé à l'IA.
                    userSpace(this._taskWithoutLeadingVerb(actionVerb, this.taskObject));
                    tool('.');
                } else if (this.taskObject) {
                    startSection();
                    tool('Ta tâche : ');
                    userSpace(this.taskObject);
                    tool('.');
                }

                // === RECHERCHE INTERNET : DATE DU JOUR === (tâche 2026-08-12, verbes de recherche
                // datés seulement - voir isDatedSearchVerbActive). La date est TOUJOURS relue depuis
                // window.promptBuilderConfig.today À CHAQUE accès de ce getter - jamais copiée dans
                // this.verb ni dans wizardParams (voir _applyWizardParams : verb reste le libellé
                // statique du verbe). EXIGENCE CRITIQUE (prouvée) : _applyWizardParams() restaure
                // `verb` tel quel aussi bien pour le brouillon local (24h) que pour un prompt
                // SAUVEGARDÉ rouvert via ?edit= (durée indéfinie) - une date figée dans l'un ou
                // l'autre réapparaîtrait périmée des mois plus tard. Puisque window.promptBuilderConfig
                // est réinjecté par le SERVEUR à CHAQUE chargement de page (jamais mis en cache dans
                // l'état du composant), rouvrir la page un autre jour relit automatiquement la date de
                // ce jour-là.
                if (this.isDatedSearchVerbActive) {
                    var todayCfg = (window.promptBuilderConfig && window.promptBuilderConfig.today) || {};
                    if (todayCfg.long && todayCfg.iso) {
                        startSection();
                        tool('Nous sommes le ' + todayCfg.long + ' (' + todayCfg.iso + '). Utilise les informations les plus récentes disponibles à cette date et signale explicitement si une source te semble périmée.');
                    }
                }

                // === RECHERCHE INTERNET : ZONES GÉOGRAPHIQUES === (tâche 2026-08-12, champ
                // conditionnel - voir isSearchVerbActive, les 3 verbes de recherche). Une seule zone :
                // phrase courte. Plusieurs zones : SECTIONS DISTINCTES exigées explicitement, pour
                // éviter qu'un modèle mélange les contextes juridiques/culturels de plusieurs zones
                // dans une réponse générique (risque documenté dans la tâche).
                if (this.isSearchVerbActive && this.zones.length > 0) {
                    startSection();
                    if (this.zones.length === 1) {
                        tool('Concentre ta recherche sur : ');
                        user(this.zones[0]);
                        tool('.');
                    } else {
                        tool('Couvre les zones suivantes dans des sections distinctes : ');
                        this.zones.forEach(function (z, i) {
                            if (i > 0) tool(', ');
                            user(z);
                        });
                        tool('. Pour chacune, adapte le contenu à ses spécificités locales.');
                    }
                }

                // === CONTEXTE ADDITIONNEL === (#1593a, 2026-08-07) : informations de fond
                // (ce qui a déjà été essayé, contraintes, contexte du projet...) distinctes de la
                // tâche elle-même - jamais mélangées au bloc TÂCHE ci-dessus, sous un intitulé
                // séparé pour que l'IA fasse clairement la différence entre la demande et le
                // contexte qui l'entoure.
                if (this.contextInfo) {
                    startSection();
                    // G3 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : le contexte est
                    // balisé comme DONNÉES (""" ... """), pas comme des consignes - et le prompt
                    // demande explicitement de signaler une contradiction plutôt que de trancher
                    // en silence.
                    tool('Contexte (informations de fond, à ne pas confondre avec les consignes) :\n"""\n');
                    userSpace(this.contextInfo);
                    tool('\n"""\nTiens-en compte dans tes choix de rédaction ; si un élément du contexte contredit une consigne ci-dessous, signale-le au lieu de trancher en silence.');
                }

                // === AUDIENCE ===
                if (this.audienceText) {
                    startSection();
                    tool('Audience cible : ');
                    if (this.audienceType === 'custom') { user(this.audienceText); } else { tool(this.audienceText); }
                    // G4 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : phrase raccourcie -
                    // "assure-toi que le contenu soit pertinent" ne disait rien de plus que l'adaptation
                    // déjà demandée juste avant, retirée.
                    tool('. Adapte ton vocabulaire, tes exemples et ton niveau de détail à ce lecteur.');
                }

                // === FORMAT DE SORTIE ===
                // LOT 1 (2026-08-06) : formatBulletText() encapsule les règles de composition
                // multi-format (1 format inchangé / plusieurs structures / plusieurs livrables /
                // mélange) - voir sa définition plus haut, juste avant get isValid().
                var outputRuleSegs = [];
                var formatBullet = this.formatBulletText;
                if (formatBullet) outputRuleSegs.push([{ t: 'tool', s: formatBullet }]);
                // G5 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : avec 2 tâches, "Longueur
                // visée" est ambiguë (étape 1 ou 2 ?) - précisé "pour le livrable principal" quand
                // la 2e tâche est RÉELLEMENT active (même condition que le bloc TÂCHE plus haut : un
                // interrupteur activé sans verbe 2 valide reste, comme avant, un prompt à une tâche).
                var secondTaskActive = this.secondTaskEnabled && secondActionVerb && actionVerb && this.taskObject;
                if (this.length && secondTaskActive) outputRuleSegs.push([{ t: 'tool', s: 'Longueur visée : ' }, { t: 'tool', s: this.length }, { t: 'tool', s: ' (pour le livrable principal)' }]);
                else if (this.length) outputRuleSegs.push([{ t: 'tool', s: 'Longueur visée : ' }, { t: 'tool', s: this.length }]);
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
                // G6a (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : consigne anti-IA rendue
                // concrète (voix active, verbe plutôt que substantif) - sans amorce à citer ni
                // interdiction générale des listes, qui produisaient parfois l'effet inverse.
                if (this.cadreStrict && this.constraintAntiAI && stylistRulesApply) constraintSegs.push([{ t: 'tool', s: 'Écriture naturelle : varie la longueur des phrases, préfère la voix active et le verbe au substantif, va droit au but. Aucune formule d\'ouverture creuse, aucun jargon générique.' }]);
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
                // G6e/G9b (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : VERROU - la
                // contrainte « chaîne de pensée » et la technique « zero-shot-cot » disaient la même
                // chose deux fois quand les deux étaient actives ; si les deux sont actives, une
                // SEULE instruction est émise ici et celle de la technique (plus bas) est coupée.
                var cotLock = this.constraintChainOfThought && this.technique === 'zero-shot-cot';
                if (this.constraintChainOfThought) constraintSegs.push([{ t: 'tool', s: cotLock ? 'Réfléchis étape par étape et montre ton raisonnement avant ta réponse finale.' : 'Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.' }]);
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

                // === CRITÈRES DE QUALITÉ === (Round 151 : scaffolding 100% automatique, coupé par
                // Cadre strict) G7 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : reformulé
                // en critères de réussite nommant les valeurs réelles (ton/audience/longueur), avec
                // consigne explicite si un critère ne peut pas être satisfait, et vérification
                // silencieuse avant livraison (jamais affichée dans la réponse).
                if (this.cadreStrict) {
                    var qualitySegs = [];
                    if (this.tone) qualitySegs.push([{ t: 'tool', s: 'le ton ' + this.tone + ' est tenu du début à la fin' }]);
                    if (this.audienceText) {
                        var audienceQualityParts = [{ t: 'tool', s: 'elle est directement utilisable par ' }];
                        audienceQualityParts.push({ t: this.audienceType === 'custom' ? 'user' : 'tool', s: this.audienceText });
                        qualitySegs.push(audienceQualityParts);
                    }
                    if (this.length) qualitySegs.push([{ t: 'tool', s: 'la longueur visée (' + this.length + ') est respectée' }]);
                    if (this.constraintAntiAI && stylistRulesApply) qualitySegs.push([{ t: 'tool', s: 'elle se lit comme un texte écrit par un humain attentif, sans formules toutes faites' }]);
                    if (qualitySegs.length > 0) {
                        startSection();
                        tool('La réponse est réussie si :\n- ');
                        qualitySegs.forEach(function (q, i) {
                            if (i > 0) tool('\n- ');
                            q.forEach(function (part) { if (part.t === 'user') { user(part.s); } else { tool(part.s); } });
                        });
                        tool('\nSi tu ne peux pas satisfaire un critère, dis-le explicitement au lieu de le contourner.\nAvant de livrer, vérifie silencieusement ta réponse contre ces critères et corrige ce qui ne passe pas ; n\'affiche pas cette vérification.');
                    }
                }

                // === DÉLIMITEURS ===
                if (this.useDelimiters) {
                    startSection();
                    // G8 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : instruction précisée
                    // (où placer le ###, avec un titre court) plutôt qu'une consigne générique.
                    tool('Dans ta réponse, sépare chaque grande section par une ligne ### suivie d\'un titre court.');
                }

                // === TECHNIQUE ===
                // G9b (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : coupée par le VERROU
                // cotLock défini plus haut - si la contrainte « chaîne de pensée » est aussi active,
                // l'instruction unique est déjà émise dans les Contraintes, on ne la répète pas ici.
                if (this.technique === 'zero-shot-cot' && !cotLock) {
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
                // G10 (gabarits v2, tâche 1653, panel multi-IA 2026-08-07) : ancrage final qui
                // redit le livrable attendu (verbe + objet tronqué à ~80 caractères, coupé au
                // dernier mot entier), pour qu'un modèle ne perde pas le fil sur un prompt long.
                // VERROU anti-contradiction : si constraintAskIfUnclear est actif, l'ancrage ne peut
                // pas dire "produis maintenant" sans condition - il devient conditionnel à la clarté.
                if (segs.length > 0) {
                    startSection();
                    var truncateAtWord = function (text, maxLen) {
                        if (!text || text.length <= maxLen) return text;
                        var cut = text.slice(0, maxLen);
                        var lastSpace = cut.lastIndexOf(' ');
                        return (lastSpace > 0 ? cut.slice(0, lastSpace) : cut) + '…';
                    };
                    var livrableVerb = actionVerb ? (actionVerb.charAt(0).toLowerCase() + actionVerb.slice(1)) : '';
                    var livrableObject = this.taskObject ? truncateAtWord(this._taskWithoutLeadingVerb(actionVerb, this.taskObject), 80) : '';
                    var hasLivrable = !!(livrableVerb && livrableObject);
                    tool(this.constraintAskIfUnclear ? 'Si tout est clair, produis maintenant : ' : 'Produis maintenant : ');
                    if (hasLivrable) {
                        if (actionVerbIsUser) { user(livrableVerb); } else { tool(livrableVerb); }
                        tool(' ');
                        userSpace(livrableObject);
                    } else {
                        tool('la demande ci-dessus');
                    }
                    tool(this.constraintAskIfUnclear ? '. Sinon, pose d\'abord tes questions de clarification, groupées en un seul message.' : '.');
                }

                // Bonification « Répéter pour ma liste » / « QCM forcé » (2026-08-07, Options
                // avancées) : TOUJOURS les tout derniers segments du prompt généré, après l'ancrage
                // « Produis maintenant » ci-dessus - voulu et documenté par la recherche (une
                // consigne de méthode/format placée en toute fin est celle que le modèle applique le
                // plus fidèlement). Ordre fixe si les deux cases sont cochées : répéter-pour-liste
                // avant QCM forcé.
                if (this.constraintRepeatList) {
                    startSection();
                    tool('Applique la même demande séparément à chaque élément de la liste fournie : produis un résultat distinct par élément, clairement séparé des autres.');
                }
                if (this.constraintForceQcm) {
                    startSection();
                    tool('Avant de produire ta réponse finale : propose-moi d\'abord 3 pistes ou approches possibles sous forme de liste numérotée (1, 2, 3), puis attends que je te réponde par un chiffre avant de rédiger le résultat complet.');
                }

                return segs;
            },

            get prompt() {
                return this.promptSegments.map(function(s) { return s.text; }).join('');
            },

            // Variables réutilisables {{...}} (#1593b, 2026-08-07) : détection PAR RÈGLES, zéro IA,
            // des motifs {{nom}} présents dans le prompt généré (ex: {{sujet}}, {{ date limite }}).
            // Dédupliquée, ordre d'apparition conservé. Aucune modification du schéma DB : le
            // prompt sauvegardé garde les {{...}} tels quels (voir wizardParams) - la zone de
            // remplissage se recalcule simplement à chaque affichage, y compris à la réouverture
            // d'un prompt déjà sauvegardé.
            get promptVariables() {
                var text = this.prompt;
                if (!text) return [];
                var re = /\{\{\s*([\p{L}0-9_ -]+)\s*\}\}/gu;
                var out = [];
                var m;
                while ((m = re.exec(text)) !== null) {
                    var name = m[1].trim();
                    if (name && out.indexOf(name) === -1) out.push(name);
                }
                return out;
            },

            // Prompt avec les variables ET les espaces à remplir remplis (utilisé par copy()/
            // openIn() ci-dessous, jamais par l'aperçu technique qui doit continuer à montrer le
            // gabarit tel quel). Espaces à remplir (tâches 1660-1665) : chaque segment 'space' est
            // remplacé par spaceValues[text], ou par le mot d'origine (text) si laissé vide - le
            // prompt reste TOUJOURS grammatical, jamais bloquant (spec §Intégration au moteur,
            // point 4-5). Variables {{...}} : une variable non remplie (valeur vide/absente) reste
            // affichée telle quelle ({{nom}}), comportement inchangé.
            get promptFilled() {
                var self = this;
                var text = this.promptSegments.map(function(s) {
                    if (s.kind === 'space') {
                        var spaceVal = self.spaceValueForText(s.spaceRef);
                        return (spaceVal !== undefined && spaceVal !== null && String(spaceVal).trim() !== '') ? spaceVal : s.text;
                    }
                    return s.text;
                }).join('');
                if (!text) return text;
                return text.replace(/\{\{\s*([\p{L}0-9_ -]+)\s*\}\}/gu, function(match, rawName) {
                    var name = rawName.trim();
                    var val = self.varValues ? self.varValues[name] : undefined;
                    return (val !== undefined && val !== null && String(val).trim() !== '') ? val : match;
                });
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
                // #1593a (2026-08-07) : `contextInfo` ajouté au même titre que les autres champs
                // texte - même piège round 42 documenté ci-dessus (tout champ oublié ici se perd
                // à la réouverture).
                // Espaces à remplir (tâches 1660-1665) : `spaces` sérialise UNIQUEMENT les chaînes
                // ancrées ({text}) - jamais `pending` (état de création transitoire, sans objet une
                // fois le prompt sauvegardé) ni spaceValues (valeurs de remplissage, jamais
                // persistées avec le prompt, voir spec §Persistance).
                return { selectedTask: this.selectedTask, personaType: this.personaType, personaPreset: this.personaPreset, personaCustom: this.personaCustom, verbType: this.verbType, verb: this.verb, verbCustom: this.verbCustom, taskObject: this.taskObject, contextInfo: this.contextInfo, spaces: this.spaces.map(function(s) { return { text: s.text }; }), audienceType: this.audienceType, audiencePresets: this.audiencePresets, audienceCustom: this.audienceCustom, formats: this.formatsSelected, formatCustom: this.formatCustom, length: this.length, tone: this.tone, language: this.language, technique: this.technique, constraintAntiAI: this.constraintAntiAI, constraintTypo: this.constraintTypo, constraintCanvas: this.constraintCanvas, canvasAI: this.canvasAI, canvasFormat: this.canvasFormat, formatMode: this.formatMode, canvasCustomFormat: this.canvasCustomFormat, constraintChainOfThought: this.constraintChainOfThought, constraintAskIfUnclear: this.constraintAskIfUnclear, constraintCustom: this.constraintCustom, useDelimiters: this.useDelimiters, examples: this.examples, cadreStrict: this.cadreStrict, profile: this.profile, zones: this.zones.slice() };
            },
            // Extraction DRY (2026-08-11) : les TROIS points de restauration de l'état du wizard
            // (?edit=ID, ?remix=ID, loadGuestHistoryEntry() pour l'historique invité) appliquaient
            // le même bloc de ~35 lignes en trois exemplaires - risque de divergence à chaque futur
            // champ ajouté à wizardParams (voir piège round 42 documenté sur le getter ci-dessus).
            // `p` = objet params (même contrat que wizardParams). `opts.legacy` (vrai par défaut)
            // active les filets de rétrocompatibilité utiles à des données SERVEUR potentiellement
            // anciennes (?edit=ID/?remix=ID) : scalaire `audiencePreset`, scalaire `format`,
            // correctifs de cohérence type/valeur (personaType/verbType/audienceType forcés à
            // 'custom' si le champ *Custom associé est rempli, au cas où d'anciennes données
            // n'auraient pas le type en phase avec la valeur custom), migration
            // canvasAI='custom'→'chatgpt', et repli selectedTask→'autre'. loadGuestHistoryEntry()
            // appelle avec `legacy:false` : vérifié champ par champ contre l'ancien bloc dédié
            // (jamais ces filets) - l'historique invité est écrit par CE MÊME code (wizardParams)
            // l'instant d'avant, donc toujours au schéma courant et déjà cohérent, aucun filet
            // n'y était appliqué.
            _applyWizardParams: function (p, opts) {
                var self = this;
                var legacy = !opts || opts.legacy !== false;
                if (p.selectedTask) self.selectedTask = p.selectedTask;
                if (p.personaType) self.personaType = p.personaType;
                if (p.personaPreset) self.personaPreset = p.personaPreset;
                if (p.personaCustom) {
                    self.personaCustom = p.personaCustom;
                    if (legacy) self.personaType = 'custom';
                }
                if (p.verbType) self.verbType = p.verbType;
                if (p.verb) self.verb = p.verb;
                if (p.verbCustom) {
                    self.verbCustom = p.verbCustom;
                    if (legacy) self.verbType = 'custom';
                }
                if (p.taskObject) self.taskObject = p.taskObject;
                // #1593a (2026-08-07) : contexte additionnel, même piège round 42 que les autres
                // champs texte restaurés ici (constraintCustom, examples...) - un oubli le perd
                // silencieusement à la réouverture.
                if (p.contextInfo) self.contextInfo = p.contextInfo;
                // Espaces à remplir (tâches 1660-1665) : restaure UNIQUEMENT les chaînes ancrées,
                // jamais pending (toujours faux à la réouverture - la personnalisation a déjà eu
                // lieu lors de la sauvegarde).
                if (Array.isArray(p.spaces)) { self.spaces = p.spaces.map(function(s) { return { text: s.text, pending: false }; }); self._refreshSpaceMissing(); }
                if (p.audienceType) self.audienceType = p.audienceType;
                // Round 151 (2026-08-01) : migration de LECTURE de l'ancien scalaire `audiencePreset`
                // vers le tableau `audiencePresets` - filet legacy, voir opts.legacy ci-dessus.
                if (Array.isArray(p.audiencePresets)) { self.audiencePresets = migrateAudienceValues(p.audiencePresets); } else if (legacy && p.audiencePreset) { self.audiencePresets = migrateAudienceValues([p.audiencePreset]); }
                if (p.audienceCustom) {
                    self.audienceCustom = p.audienceCustom;
                    if (legacy) self.audienceType = 'custom';
                }
                // LOT 1 (2026-08-06) : migration de l'ancien scalaire `format` vers le tableau
                // formatsSelected - filet legacy, voir opts.legacy ci-dessus.
                if (Array.isArray(p.formats)) { self.formatsSelected = p.formats; } else if (legacy && p.format) { self.formatsSelected = [p.format]; }
                if (p.formatCustom) self.formatCustom = p.formatCustom;
                if (p.length) self.length = p.length;
                if (p.tone) self.tone = p.tone;
                if (p.language) self.language = p.language;
                if (p.technique) self.technique = p.technique;
                if (p.constraintAntiAI !== undefined) self.constraintAntiAI = p.constraintAntiAI;
                // Round 151 (2026-08-01) : Cadre strict doit survivre à une réédition comme les
                // autres réglages, sinon rouvrir un prompt sauvegardé avec le cadre désactivé le
                // réactiverait silencieusement (repli à `true`).
                if (p.cadreStrict !== undefined) self.cadreStrict = p.cadreStrict;
                // Round 152 (2026-08-01) : restaure le profil sauvegardé ET marque profileTouched -
                // un prompt déjà sauvegardé porte un choix DÉJÀ FAIT par la personne, la détection
                // par mots-clés ne doit plus jamais l'écraser.
                if (p.profile) { self.profile = p.profile; self.profileTouched = true; }
                // Round 42 (2026-07-27) : ces champs manquaient à la restauration - le prompt
                // rouvrait avec ces options réinitialisées, et un "Enregistrer" ultérieur écrasait
                // silencieusement la version en base (perte de donnée, ex. constraintCustom peut
                // contenir des instructions longues, examples rend "few-shot" non fonctionnel une
                // fois vidé).
                if (p.constraintTypo !== undefined) self.constraintTypo = p.constraintTypo;
                if (p.constraintChainOfThought !== undefined) self.constraintChainOfThought = p.constraintChainOfThought;
                if (p.constraintAskIfUnclear !== undefined) self.constraintAskIfUnclear = p.constraintAskIfUnclear;
                if (p.constraintCustom) self.constraintCustom = p.constraintCustom;
                if (p.useDelimiters !== undefined) self.useDelimiters = p.useDelimiters;
                if (p.examples) self.examples = p.examples;
                if (p.constraintCanvas) self.constraintCanvas = p.constraintCanvas;
                if (p.canvasAI) {
                    // 2026-05-05 #104 : migration anciens prompts canvasAI='custom' → canvasAI='chatgpt'
                    // + formatMode='custom' - filet legacy, voir opts.legacy ci-dessus.
                    if (legacy && p.canvasAI === 'custom') { self.canvasAI = 'chatgpt'; self.formatMode = 'custom'; }
                    else self.canvasAI = p.canvasAI;
                }
                if (p.canvasFormat) self.canvasFormat = p.canvasFormat;
                if (p.canvasCustomFormat) self.canvasCustomFormat = p.canvasCustomFormat;
                if (p.formatMode) self.formatMode = p.formatMode;
                // Zones géographiques (tâche 2026-08-12) : restaure les libellés TELS QUE saisis,
                // jamais la phrase datée (celle-ci n'est jamais persistée - voir get promptSegments()).
                if (Array.isArray(p.zones)) self.zones = p.zones.slice();
                // Round 101 (2026-07-27, passe adversariale) : le repli 'autre' ne s'applique
                // qu'aux prompts sauvegardés AVANT l'ajout de selectedTask à wizardParams - filet
                // legacy, voir opts.legacy ci-dessus.
                if (legacy) self.selectedTask = self.selectedTask || 'autre';
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
                // Correctif régression prod (2026-08-11) : instantané pris EN PREMIER, avant tout
                // chargement (_loadSpaceLastValues/_loadOpenTargetPref/_loadDraft ci-dessous, et avant
                // les restaurations ?edit=/?remix= plus bas dans cette fonction) - sinon l'instantané
                // refléterait un formulaire déjà restauré au lieu du formulaire vierge, et plus rien
                // ne serait jamais considéré comme "significatif". Voir _hasSignificantDraftContent().
                try { this._draftDefaultSnapshot = JSON.stringify(this.wizardParams); } catch (e) {}
                // Espaces à remplir (tâches 1660-1665) : mémoire des dernières valeurs saisies,
                // indépendante du compte (invité ou connecté - c'est un confort de navigateur, pas
                // une donnée de compte). Voir _loadSpaceLastValues()/_recordSpaceLastValues().
                this._loadSpaceLastValues();
                // IA préférée mémorisée (2026-08-07) : voir _loadOpenTargetPref() plus bas.
                this._loadOpenTargetPref();
                // Brouillon local (2026-08-11) : restaure AVANT _applyStepFromHash() ci-dessous -
                // si l'URL porte un hash #etape-N, il doit primer sur l'étape mémorisée dans le
                // brouillon (même principe que ?edit=/?remix= : l'URL est toujours prioritaire).
                // _loadDraft() se retire elle-même si ?edit=/?remix= est présent dans l'URL.
                this._loadDraft();
                // Tâche #1699 : reflète l'étape courante dans l'URL (hash, replaceState = zéro
                // pollution de l'historique de navigation, zéro impact serveur ou cache).
                this._applyStepFromHash();
                if (typeof this.$watch === 'function') {
                    this.$watch('step', function(s) {
                        if (typeof history !== 'undefined' && history.replaceState && typeof window !== 'undefined' && window.location) {
                            if (s > 1) {
                                history.replaceState(null, '', '#etape-' + s);
                            } else {
                                history.replaceState(null, '', window.location.pathname + window.location.search);
                            }
                        }
                    });
                    // Brouillon local : ré-écriture anti-rebond (~600 ms, voir _scheduleDraftSave())
                    // à chaque changement d'un champ du wizard. Expression évaluée (pas un simple nom
                    // de propriété) - Alpine.js le permet, $watch() évalue son 1er argument comme les
                    // autres directives (x-text, x-if...), voir wizardParams ci-dessus pour la liste
                    // des champs couverts.
                    this.$watch('JSON.stringify(wizardParams)', function() { self._scheduleDraftSave(); });
                }
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
                                    self._applyWizardParams(found.params, { legacy: true });
                                    self.saveName = found.name;
                                    // Prompt existant chargé pour édition : on saute l'étape « objectif »
                                    // (déjà répondue par un précédent passage) et on ouvre directement
                                    // toutes les divulgations locales (Phase 2 : ex-showAdvanced unique),
                                    // car un prompt sauvegardé utilise typiquement des valeurs
                                    // personnalisées qui vivent dans ces sections repliées par défaut.
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
                    // Rétention locale invités (#1580, 2026-08-07) : charge cpGuestHistory_v1,
                    // uniquement pour un visiteur non connecté (cette branche else n'est atteinte
                    // que si !isAuthenticated, voir plus haut).
                    this._loadGuestHistory();
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
                                self._applyWizardParams(found.params, { legacy: true });
                                // Décision de conception (non précisée dans le plan approuvé) :
                                // préfixe "Remix de " sur le nom repris, pour que la personne sache
                                // d'où vient ce brouillon avant de l'enregistrer sous son propre nom.
                                self.saveName = found.name ? ('Remix de ' + found.name) : self.saveName;
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

            // Tâche #1699 (2026-08-09) : au chargement, restaure l'étape du hash (#etape-2 à 4)
            // seulement si les prérequis des étapes précédentes sont remplis - jamais de saut
            // arbitraire.
            // REGRESSION CORRIGEE v1.164.4 (2026-08-11) : cette fonction s'appuie sur
            // canGoToStep(), qui lit les CHAMPS (personaText, verbe, taskObject). Or depuis
            // v1.164.2 la restauration du brouillon est reportee a $nextTick : au moment de
            // l'appel synchrone d'init(), les champs sont encore vides, canGoToStep() refuse,
            // et #etape-N etait perdu a chaque rafraichissement. D'ou _hashStepApplied : tant
            // que l'etape n'a pas ete APPLIQUEE, _loadDraft() peut retenter une fois les champs
            // en place. Le drapeau n'est pose qu'en cas de succes - un echec laisse la porte
            // ouverte a la seconde tentative, jamais a un saut d'etape plus tard.
            _applyStepFromHash: function() {
                if (this._hashStepApplied) return;
                var hash = '';
                if (typeof window !== 'undefined' && window.location && typeof window.location.hash === 'string') {
                    hash = window.location.hash;
                }
                var match = hash.match(/^#etape-([2-4])$/);
                if (match) {
                    var n = parseInt(match[1], 10);
                    if (this.canGoToStep(n)) {
                        this.step = n;
                        this._hashStepApplied = true;
                        if (n === 4) {
                            this.step4Visited = true;
                        }
                    }
                }
            },
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
                // Espaces à remplir (tâches 1660-1665) : recalcule "non retrouvé" et mémorise les
                // valeurs de remplissage AVANT copie (spec : évaluée au blur des textareas ET avant
                // copie, jamais à chaque frappe).
                this._refreshSpaceMissing();
                this._recordSpaceLastValues();
                // Round 94 (2026-07-27, passe adversariale) : copied=true (bouton "Copié !") et le
                // toast de succès ne s'affichent plus QUE si l'écriture presse-papiers a RÉELLEMENT
                // réussi - window.copyToClipboard() attend la Promise réelle (échec = toast d'erreur
                // explicite déjà géré par le helper), au lieu du try/catch synchrone précédent qui
                // n'interceptait jamais un rejet asynchrone et affichait "Copié !" à tort.
                // #1593b (2026-08-07) : promptFilled (variables {{...}} substituées quand
                // remplies) plutôt que prompt brut - une variable non remplie reste affichée
                // telle quelle (voir get promptFilled()).
                window.copyToClipboard(this.promptFilled, i18n.promptCopied || 'Prompt copié').then(function(ok) {
                    if (!ok) return;
                    self.copied = true;
                    // #1580 (2026-08-07) : enregistre ce prompt dans l'historique local invité
                    // (no-op si connecté ou si le prompt n'est pas valide, voir _recordGuestHistory()).
                    self._recordGuestHistory();
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
                // #1593b (2026-08-07) : payload par défaut = promptFilled (variables {{...}}
                // substituées) - seul le méta-prompt "Améliorer avec mon IA" (appel avec `text`
                // explicite) échappe à ce remplacement, il embarque déjà this.prompt tel quel.
                // Espaces à remplir (tâches 1660-1665) : même rafraîchissement "non retrouvé" +
                // mémorisation des valeurs qu'avant copy() ci-dessus (jamais pour le méta-prompt).
                if (!text) { this._refreshSpaceMissing(); this._recordSpaceLastValues(); this._recordOpenTargetPref(target); }
                var payload = text || this.promptFilled;
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
                // #1580 (2026-08-07) : seul le prompt principal (pas le méta-prompt, `text` non
                // fourni ici) alimente l'historique local invité.
                if (!text) { this._recordGuestHistory(); }
            },

            copyText: function(text) { window.copyToClipboard(text, (window.promptBuilderConfig && window.promptBuilderConfig.i18n && window.promptBuilderConfig.i18n.promptCopied) || 'Prompt copié'); },

            // La double confirmation par re-clic (armReset) a été remplacée le 2026-08-12 par
            // une modale centrée (#resetConfirmModal dans la vue), qui dispatche l'évènement
            // cp-reset-confirmed sur window pour déclencher resetAll() ci-dessous.
            // Brouillon local (2026-08-11) : purge cpDraft_v1 AVANT le rechargement - sinon
            // _loadDraft() (voir init()) restaurerait au prochain chargement le brouillon que ce
            // bouton est censé effacer (piège central de la persistance de formulaire).
            // 3e defaut de prod (2026-08-11) : « Recommencer » ne remettait rien a zero.
            // Deux causes cumulees, corrigees ensemble :
            // (1) `window.location.href = pathname` depuis une URL portant un fragment
            //     (#etape-N, ajoute en v1.155.0) ne recharge PAS le document - le navigateur
            //     se contente de retirer le fragment. L'etat Alpine survivait donc intact.
            // (2) L'anti-rebond de 600 ms etait toujours arme : meme purgee, la cle etait
            //     reecrite juste apres par le watcher, avec l'etat inchange.
            // D'ou l'ordre : desarmer la sauvegarde, purger, nettoyer le fragment SANS
            // navigation (replaceState), puis forcer un vrai rechargement.
            resetAll: function () {
                this._draftDisabled = true;
                clearTimeout(this._draftSaveTimer);
                this._draftSaveTimer = null;
                try { localStorage.removeItem(this._draftKey); } catch (e) {}
                try { window.history.replaceState(null, '', window.location.pathname); } catch (e) {}
                window.location.reload();
            },

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

            // === Rétention locale invités (#1580, 2026-08-07) ===
            // Historique AUTOMATIQUE (max 10) des derniers prompts générés par un visiteur NON
            // connecté, dans une clé localStorage DISTINCTE et versionnée - jamais mélangée au
            // tableau `history`/`pb_history` ci-dessus (lié au bouton "Sauvegarder", qui exige un
            // compte). Rien n'est envoyé au serveur : lecture/écriture 100% locales, mêmes gardes
            // try/catch que le reste de ce fichier (mode privé, quota plein, storage désactivé).
            _guestHistoryKey: 'cpGuestHistory_v1',
            _loadGuestHistory: function() {
                try {
                    var raw = localStorage.getItem(this._guestHistoryKey);
                    var list = raw ? JSON.parse(raw) : [];
                    this.guestHistory = Array.isArray(list) ? list : [];
                } catch (e) { this.guestHistory = []; }
            },
            // Appelé au moment de la génération/copie (copy(), openIn() pour le prompt principal
            // uniquement) - jamais à chaque frappe. Anti-doublon CONSÉCUTIF : si le prompt généré
            // est identique (même état sérialisé) à la dernière entrée déjà en tête de liste, on
            // n'ajoute rien (évite de spammer l'historique quand on clique Copier deux fois de suite
            // sans rien changer).
            _recordGuestHistory: function() {
                if (this.isAuthenticated || !this.isValid) return;
                try {
                    var stateNow = this.wizardParams;
                    var stateJson = JSON.stringify(stateNow);
                    var list = Array.isArray(this.guestHistory) ? this.guestHistory.slice() : [];
                    if (list.length > 0 && list[0] && JSON.stringify(list[0].state) === stateJson) return;
                    var title = (this.taskObject || '').trim().slice(0, 60) || 'Prompt';
                    list.unshift({ date: new Date().toISOString(), title: title, state: stateNow });
                    if (list.length > 10) list = list.slice(0, 10);
                    localStorage.setItem(this._guestHistoryKey, JSON.stringify(list));
                    this.guestHistory = list;
                } catch (e) {}
            },
            // Recharge une entrée dans le wizard - même liste de champs que la désérialisation
            // ?edit=ID/?remix=ID (init() plus haut), mais fonction dédiée et volontairement séparée
            // (doctrine incrémentale du projet : on n'a pas touché aux blocs ?edit=ID/?remix=ID
            // stabilisés par des dizaines de rounds adversariaux pour en extraire un helper partagé).
            loadGuestHistoryEntry: function(index) {
                var entry = this.guestHistory && this.guestHistory[index];
                if (!entry || !entry.state) return;
                var p = entry.state;
                var self = this;
                // Voir _applyWizardParams() (getter wizardParams ci-dessus, doctrine incrémentale) -
                // legacy:false car cet état a été sérialisé par CE code l'instant d'avant, jamais de
                // données anciennes ici.
                self._applyWizardParams(p, { legacy: false });
                self.step = 2;
                self.previewOpen = true;
            },
            deleteGuestHistoryEntry: function(index) {
                if (!Array.isArray(this.guestHistory)) return;
                this.guestHistory.splice(index, 1);
                try { localStorage.setItem(this._guestHistoryKey, JSON.stringify(this.guestHistory)); } catch (e) {}
            },
            clearGuestHistory: function() {
                this.guestHistory = [];
                try { localStorage.removeItem(this._guestHistoryKey); } catch (e) {}
            },

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
            },

            // === Espaces à remplir (tâches 1660-1665, design panel 2026-08-07, panel multi-IA
            // 5 rounds approuvé par le fondateur) - RÈGLE D'OR : l'utilisateur ne voit jamais de
            // syntaxe (aucune accolade/crochet visible ici), on n'ajoute jamais de symbole à son
            // texte, seulement du français normal sur geste explicite. ===

            // Couche 2 (canonKey, tâches 1660-1665, boucle 5 oracles 2026-08-09, spec TR15
            // unicode.org) : normalisation de COMPARAISON UNIQUEMENT - le texte tapé par la
            // personne n'est JAMAIS modifié (ni ici ni ailleurs), seules les ÉGALITÉS et
            // RECHERCHES passent par ces 2 fonctions. RÈGLE D'OR : `_canonKey()` (avec NFC) sert
            // aux égalités strictes de dictionnaire (créer/renommer/fusionner un espace, clés des
            // caches spaceValues/spaceMissingCache/spaceLastValues) - NFC peut changer la longueur
            // d'une chaîne, donc jamais utilisée pour indexer une position. `_canonSearchText()`
            // (sans NFC, remplacement 1:1 en longueur) sert aux RECHERCHES DE POSITION dans le
            // texte de la personne (indexOf/substr) - les indices trouvés restent valides tels
            // quels sur le texte BRUT. Cas NFD (collage décomposé, rare) : la recherche de
            // position peut manquer une occurrence dans ce cas précis - limite documentée et
            // acceptée (arbitrage du panel), l'égalité de dictionnaire (_canonKey, avec NFC) la
            // couvre malgré tout.
            _canonSearchText: function(str) {
                // Remplacement 1:1 en LONGUEUR (aucune composition/decomposition Unicode) :
                // apostrophe courbe U+2019 et apostrophe modificative U+02BC -> apostrophe droite ;
                // espace insecable U+00A0 et espace insecable etroit U+202F -> espace simple.
                // RIEN d'autre (pas de casse, pas d'accents - preserves tels quels). Echappe en
                // \u pour eviter tout risque d'alteration silencieuse par un editeur/encodage futur
                // (les caracteres insecables sont visuellement indiscernables d'un espace normal).
                return (str == null ? '' : String(str)).replace(/[\u2019\u02BC]/g, '\'').replace(/[\u00A0\u202F]/g, ' ');
            },
            _canonKey: function(str) {
                var s = str == null ? '' : String(str);
                if (typeof s.normalize === 'function') {
                    try { s = s.normalize('NFC'); } catch (e) {}
                }
                return this._canonSearchText(s);
            },

            // Refuse une sélection trop courte ou réduite à un mot-outil (comparaison sur le texte
            // EXACT trimé, jamais une recherche de sous-chaîne).
            _isValidSpaceSelection: function(text) {
                var t = (text || '').trim();
                if (t.length < 3) return false;
                return this._spaceStopWords.indexOf(t.toLowerCase()) === -1;
            },

            _findSpaceByText: function(text) {
                var key = this._canonKey(text);
                for (var i = 0; i < this.spaces.length; i++) { if (this._canonKey(this.spaces[i].text) === key) return this.spaces[i]; }
                return null;
            },

            // Recalcule la liste des espaces « non retrouvés » (spec §Modèle de données, point
            // spaceMissing) - appelé au blur des 2 textareas et juste avant copie/ouverture,
            // JAMAIS à chaque frappe (évite le clignotement des pastilles pendant la rédaction).
            _refreshSpaceMissing: function() {
                var combined = (this.taskObject || '') + '\n' + (this.contextInfo || '');
                var cache = {};
                for (var i = 0; i < this.spaces.length; i++) {
                    // Même règle de frontières de mots que le découpage des segments (round
                    // adversarial DeepSeek 2026-08-07) : « son » présent uniquement DANS « maison »
                    // est bien « non retrouvé » - sinon la pastille dirait le contraire d'un
                    // remplacement qui n'aura jamais lieu. Couche 2 (canonKey, 2026-08-09) : clé du
                    // cache canonique (voir spaceMissing()/removeSpace() ci-dessous, lecture/écriture
                    // toujours symétriques) - l'affichage garde sp.text brut, jamais cette clé.
                    cache[this._canonKey(this.spaces[i].text)] = !this._hasBoundedOccurrence(combined, this.spaces[i].text);
                }
                this.spaceMissingCache = cache;
            },
            // Vrai si `needle` apparaît dans `source` avec des FRONTIÈRES DE MOTS aux deux bords
            // (caractère adjacent ni lettre ni chiffre Unicode). Balayage indexOf - jamais de regex
            // construite depuis un texte utilisateur. Couche 2 (canonKey, 2026-08-09) : comparaison
            // sur la forme canonique de RECHERCHE (_canonSearchText, jamais NFC - voir RÈGLE D'OR
            // ci-dessus), mais toute POSITION reste calculée/retournée sur `source` BRUT - le
            // remplacement est 1:1 en longueur, donc les indices trouvés dans la version canonique
            // restent valides tels quels sur `source`.
            _hasBoundedOccurrence: function(source, needle) {
                if (!source || !needle) return false;
                var canonSource = this._canonSearchText(source);
                var canonNeedle = this._canonSearchText(needle);
                if (!canonNeedle) return false;
                var index = canonSource.indexOf(canonNeedle);
                while (index !== -1) {
                    if (this._isSpaceBoundary(source, index - 1) && this._isSpaceBoundary(source, index + needle.length)) return true;
                    index = canonSource.indexOf(canonNeedle, index + 1);
                }
                return false;
            },
            // Frontière de mot Unicode : vrai hors bornes, ou si le caractère (paire de substitution
            // gérée) n'est ni une lettre ni un chiffre. Repli latin-1 si \p{} n'est pas supporté.
            _isSpaceBoundary: function(str, index) {
                if (index < 0 || index >= str.length) return true;
                var character = str.charAt(index);
                var code = str.charCodeAt(index);
                if (code >= 0xDC00 && code <= 0xDFFF && index > 0) {
                    var previousCode = str.charCodeAt(index - 1);
                    if (previousCode >= 0xD800 && previousCode <= 0xDBFF) character = str.substr(index - 1, 2);
                } else if (code >= 0xD800 && code <= 0xDBFF && index + 1 < str.length) {
                    var nextCode = str.charCodeAt(index + 1);
                    if (nextCode >= 0xDC00 && nextCode <= 0xDFFF) character = str.substr(index, 2);
                }
                try {
                    return !(new RegExp('[\\p{L}\\p{N}]', 'u')).test(character);
                } catch (e) {
                    return !/[A-Za-z0-9À-ÖØ-öø-ÿ]/.test(character);
                }
            },
            // Remplacement aux frontières de mots (les 2 bords) - balayage indexOf, jamais de regex
            // construite depuis un texte utilisateur. Une occurrence collée à une lettre (« son »
            // dans « maison ») est laissée intacte. Couche 2 (canonKey, 2026-08-09) : les
            // OCCURRENCES sont repérées sur la forme canonique de recherche (une apostrophe courbe
            // ou un espace insécable tapés par la personne matchent quand même `oldText`), mais le
            // texte réellement inséré est TOUJOURS `newText` tel que fourni - aucune trace de la
            // forme canonique n'est jamais écrite dans le texte de la personne.
            _replaceWithBoundaries: function(source, oldText, newText) {
                if (!source || !oldText || oldText === newText) return source;
                var canonSource = this._canonSearchText(source);
                var canonOld = this._canonSearchText(oldText);
                if (!canonOld) return source;
                var result = '';
                var cursor = 0;
                var index = canonSource.indexOf(canonOld, cursor);
                while (index !== -1) {
                    if (this._isSpaceBoundary(source, index - 1) && this._isSpaceBoundary(source, index + oldText.length)) {
                        result += source.slice(cursor, index) + newText;
                        cursor = index + oldText.length;
                    } else {
                        result += source.slice(cursor, index + 1);
                        cursor = index + 1;
                    }
                    index = canonSource.indexOf(canonOld, cursor);
                }
                return result + source.slice(cursor);
            },
            // Utilisé par promptSummary pour que l'aperçu « Voici ce qui sera envoyé à l'IA »
            // reflète les valeurs remplies (mêmes règles de frontières et de priorité que le
            // prompt copié). Signalement du 2026-08-09.
            _fillSpacesInText: function(str) {
                if (!str || !this.spaces || this.spaces.length === 0) {
                    return str;
                }
                var sortedSpaces = this.spaces.slice().sort(function(a, b) {
                    return b.text.length - a.text.length;
                });
                var result = str;
                for (var i = 0; i < sortedSpaces.length; i++) {
                    var space = sortedSpaces[i];
                    var value = this.spaceValueForText(space.text);
                    if (value) {
                        result = this._replaceWithBoundaries(result, space.text, value);
                    }
                }
                return result;
            },
            // Couche 2 (canonKey, 2026-08-09) : lecture du cache "non retrouvé" par clé canonique -
            // symétrique de l'écriture dans _refreshSpaceMissing() ci-dessus. Réutilisé tel quel par
            // le Blade (voir constructeur-prompts.blade.php) à la place de l'ancien accès direct
            // spaceMissingCache[sp.text], qui pouvait rater un espace dont le texte contient une
            // apostrophe courbe ou un espace insécable.
            spaceMissing: function(space) {
                return !!(space && this.spaceMissingCache[this._canonKey(space.text)]);
            },
            // Couche 2 (canonKey, 2026-08-09) : helpers d'accès canonique à spaceValues/
            // spaceLastValues EXPOSÉS pour le Blade (constructeur-prompts.blade.php) - le template
            // n'y implémente jamais la normalisation lui-même (aucun _canonKey inline dans la vue),
            // il appelle ces méthodes. Nécessaires car une fusion au renommage (voir
            // _renameSpaceOccurrences plus bas) peut écrire sous une clé canonique différente de la
            // forme littérale exacte de sp.text pour cette même pastille (2 formes Unicode
            // équivalentes du même texte) - un accès brut spaceValues[sp.text] raterait alors la
            // valeur. `spaceValueFor`/`setSpaceValue` remplacent x-model="spaceValues[sp.text]" par
            // :value/@input (voir Blade) ; `spaceValueForText` sert à l'aperçu colorisé où seul
            // seg.spaceRef (une chaîne) est disponible, pas l'objet sp.
            spaceValueForText: function(text) {
                return this.spaceValues[this._canonKey(text)];
            },
            spaceValueFor: function(sp) {
                return sp ? this.spaceValueForText(sp.text) : undefined;
            },
            setSpaceValue: function(sp, val) {
                if (!sp) return;
                this.spaceValues[this._canonKey(sp.text)] = val;
            },
            spaceLastValuesFor: function(sp) {
                return (sp && this.spaceLastValues[this._canonKey(sp.text)]) || [];
            },

            // Au blur d'un des 2 textareas concernés : rafraîchit le cache "non retrouvé" ET retire
            // SILENCIEUSEMENT tout espace encore `pending` dont le texte inséré ("information à
            // préciser"...) a disparu SANS avoir été renommé via la pastille - règle simple retenue
            // par le panel (détecter "quel nouveau mot a remplacé le fragment" a été jugé trop
            // fragile, voir spec §UI - création, point B). Un espace CONFIRMÉ (non pending) qui
            // devient introuvable n'est JAMAIS retiré automatiquement : il devient "missing"
            // (pastille grise), retrait laissé au geste explicite de la personne (le ×).
            handleSpaceFieldBlur: function() {
                var combined = (this.taskObject || '') + '\n' + (this.contextInfo || '');
                var canonCombined = this._canonSearchText(combined);
                var self = this;
                this.spaces = this.spaces.filter(function(sp) {
                    return !(sp.pending && canonCombined.indexOf(self._canonSearchText(sp.text)) === -1);
                });
                this._refreshSpaceMissing();
            },

            // Geste A (sélection) : suit le champ le plus récemment focalisé/sélectionné - sert de
            // repli au bouton « + Ajouter un espace à remplir » (spec : insère au curseur du
            // dernier textarea focalisé, taskObject par défaut si aucun focus).
            handleSpaceFieldFocus: function(fieldId) {
                this._lastFocusedSpaceField = fieldId;
            },
            // Sur select/mouseup/keyup des 2 textareas : affiche la bulle « En faire un espace à
            // remplir » si une sélection non vide existe. État pur (indépendant du DOM réel au-delà
            // de selectionStart/selectionEnd déjà exposés nativement par l'événement) - testable
            // sans navigateur réel via createSpaceFromSelection() directement.
            handleSpaceFieldSelect: function(event) {
                var el = event && event.target;
                this._lastFocusedSpaceField = (el && el.id) || this._lastFocusedSpaceField;
                if (!el || typeof el.selectionStart !== 'number') return;
                var start = el.selectionStart, end = el.selectionEnd;
                if (end <= start) { this.spaceBubble.show = false; return; }
                var text = el.value.substring(start, end).trim();
                if (!text) { this.spaceBubble.show = false; return; }
                this.spaceBubble = { show: true, text: text, fieldId: el.id };
            },
            hideSpaceBubble: function() {
                this.spaceBubble.show = false;
            },
            // Crée l'espace depuis la bulle de sélection - dédoublonné (texte identique déjà
            // présent : toast doux, aucune 2e entrée) ; refuse toute sélection trop courte ou
            // réduite à un mot-outil (toast doux, action refusée, LE TEXTE DU CHAMP NE CHANGE PAS).
            createSpaceFromSelection: function() {
                var text = (this.spaceBubble.text || '').trim();
                this.spaceBubble.show = false;
                if (!text) return;
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                if (!this._isValidSpaceSelection(text)) {
                    if (typeof window.toast === 'function') window.toast(i18n.spaceTooShort || 'Choisis un mot plus précis pour éviter les remplacements imprévus.', 'warning', 4000);
                    return;
                }
                if (this._findSpaceByText(text)) {
                    if (typeof window.toast === 'function') window.toast(i18n.spaceAlreadyExists || 'Cet espace existe déjà.', 'info', 3000);
                    return;
                }
                this.spaces.push({ text: text, pending: false });
                this._refreshSpaceMissing();
                // Signalement 2026-08-09 (#1697) : le remplacement est volontairement global (publipostage).
                // Si le texte choisi apparaît plusieurs fois, on l'annonce - information, jamais un blocage.
                var occ = this._countBoundedOccurrences(this.taskObject || '', text) + this._countBoundedOccurrences(this.contextInfo || '', text);
                if (occ > 1 && typeof window.toast === 'function') {
                    window.toast((i18n.spaceMultiOccurrences || 'Ce texte apparaît :count fois : chaque endroit sera remplacé par ta réponse.').replace(':count', occ), 'info', 5000);
                }
            },

            // Geste B (bouton) : insère « information à préciser » (suffixé « 2 », « 3 »... si
            // plusieurs coexistent déjà) au curseur du dernier textarea focalisé, crée l'espace
            // `pending`, et pré-sélectionne le fragment inséré - la première frappe l'écrase. Sur
            // pointeur tactile (mobile), ouvre directement le renommage dans la pastille plutôt que
            // de dépendre d'une sélection tactile.
            addSpaceAtCursor: function() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var baseLabel = i18n.spaceNewLabel || 'information à préciser';
                var label = baseLabel;
                var n = 2;
                while (this._findSpaceByText(label)) { label = baseLabel + ' ' + n; n++; }

                var fieldId = this._lastFocusedSpaceField === 'cpContextInfo' ? 'cpContextInfo' : 'cpTaskObject';
                var el = (typeof document !== 'undefined' && document.getElementById) ? document.getElementById(fieldId) : null;
                var self = this;
                var newSpace = { text: label, pending: true, draftText: label };

                if (el && typeof el.selectionStart === 'number') {
                    var start = el.selectionStart, end = el.selectionEnd;
                    var current = fieldId === 'cpContextInfo' ? this.contextInfo : this.taskObject;
                    var nextValue = current.slice(0, start) + label + current.slice(end);
                    if (fieldId === 'cpContextInfo') { this.contextInfo = nextValue; } else { this.taskObject = nextValue; }
                    this.spaces.push(newSpace);
                    var isCoarsePointer = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
                    if (typeof this.$nextTick === 'function') {
                        this.$nextTick(function() {
                            if (isCoarsePointer) {
                                var idx = self.spaces.indexOf(newSpace);
                                var input = document.getElementById('cpSpacePendingInput-' + idx);
                                if (input) input.focus();
                            } else if (el.setSelectionRange) {
                                el.focus();
                                el.setSelectionRange(start, start + label.length);
                            }
                        });
                    }
                } else {
                    // Aucun textarea focalisé (ou environnement sans DOM, ex. tests) : ajoute à la
                    // fin de taskObject, repli explicitement voulu par la spec.
                    var sep = this.taskObject && !/\s$/.test(this.taskObject) ? ' ' : '';
                    this.taskObject = this.taskObject + sep + label;
                    this.spaces.push(newSpace);
                }
                this._refreshSpaceMissing();
            },

            // Valide le renommage d'un espace ENCORE pending (champ ouvert nativement dans sa
            // pastille, voir sp.draftText) - remplace toutes les occurrences du placeholder inséré
            // par le nouveau nom dans les 2 textareas, puis lève le drapeau pending. Un champ laissé
            // vide ne commit rien (l'espace reste pending, retirable par le blur du textarea ou le ×).
            // Garde-fou fusion (C2-2, couche 2, 2026-08-09) : voir _confirmRenameMergeIfNeeded() -
            // si le nouveau texte existe déjà ailleurs, la fusion réelle attend la confirmation.
            commitPendingSpaceRename: function(idx) {
                var space = this.spaces[idx];
                if (!space || !space.pending) return;
                var newText = (space.draftText || '').trim();
                if (!newText) { space.draftText = space.text; return; }
                if (newText === space.text) {
                    space.pending = false;
                    delete space.draftText;
                    this._refreshSpaceMissing();
                    return;
                }
                var self = this;
                this._confirmRenameMergeIfNeeded(newText, function() { self._applyPendingRenameSpace(space, newText); });
            },
            // Applique réellement le renommage d'un espace pending (après confirmation de fusion si
            // nécessaire) - relocalise l'espace par RÉFÉRENCE (jamais par idx figé, qui peut avoir
            // dérivé pendant l'attente d'une confirmation asynchrone).
            _applyPendingRenameSpace: function(space, newText) {
                var idx = this.spaces.indexOf(space);
                if (idx === -1) return;
                this._renameSpaceOccurrences(space.text, newText);
                // Même règle de fusion que commitRenameSpace : renommer un pending vers le
                // texte d'un espace déjà confirmé ne crée jamais de pastille en double.
                if (this._findOtherSpaceIndex(idx, newText) !== -1) {
                    this.spaces.splice(idx, 1);
                    this._refreshSpaceMissing();
                    return;
                }
                space.text = newText;
                space.pending = false;
                delete space.draftText;
                this._refreshSpaceMissing();
            },

            // Renommage par pastille (UI - création, point C) : clic sur le nom d'un espace
            // CONFIRMÉ (non pending) → champ texte inline dans la bande.
            startRenameSpace: function(idx) {
                var space = this.spaces[idx];
                if (!space || space.pending) return;
                this.spaceEditingIndex = idx;
                this.spaceEditingText = space.text;
                var self = this;
                if (typeof this.$nextTick === 'function') {
                    this.$nextTick(function() {
                        var el = (typeof document !== 'undefined' && document.getElementById) ? document.getElementById('cpSpaceRename-' + idx) : null;
                        if (el) el.focus();
                    });
                }
            },
            // Valider = remplacer TOUTES les occurrences de l'ancien texte par le nouveau dans les
            // 2 textareas (seul cas où l'outil modifie le texte de la personne - un renommage
            // explicitement demandé) + mettre à jour l'espace. Échap/blur vide (ou inchangé) = annule.
            // Garde-fou fusion (C2-2, couche 2, 2026-08-09) : voir _confirmRenameMergeIfNeeded().
            commitRenameSpace: function(idx) {
                if (this.spaceEditingIndex !== idx) return;
                var space = this.spaces[idx];
                var newText = (this.spaceEditingText || '').trim();
                this.spaceEditingIndex = null;
                if (!space || !newText || newText === space.text) return;
                var self = this;
                this._confirmRenameMergeIfNeeded(newText, function() { self._applyRenameSpace(space, newText); });
            },
            // Applique réellement le renommage d'un espace confirmé (après confirmation de fusion si
            // nécessaire) - relocalise l'espace par RÉFÉRENCE (jamais par idx figé).
            _applyRenameSpace: function(space, newText) {
                var idx = this.spaces.indexOf(space);
                if (idx === -1) return;
                this._renameSpaceOccurrences(space.text, newText);
                // Renommage vers le texte d'un AUTRE espace existant : fusion (les occurrences
                // pointent déjà sur la même chaîne, un doublon de pastille serait ambigu) - round
                // adversarial DeepSeek 2026-08-07.
                if (this._findOtherSpaceIndex(idx, newText) !== -1) {
                    this.spaces.splice(idx, 1);
                } else {
                    space.text = newText;
                }
                this._refreshSpaceMissing();
            },
            // Index d'un espace (différent de exceptIdx) portant exactement ce texte (comparaison
            // canonique - couche 2, canonKey), sinon -1.
            _findOtherSpaceIndex: function(exceptIdx, text) {
                var key = this._canonKey(text);
                for (var i = 0; i < this.spaces.length; i++) {
                    if (i !== exceptIdx && this._canonKey(this.spaces[i].text) === key) return i;
                }
                return -1;
            },
            cancelRenameSpace: function() {
                this.spaceEditingIndex = null;
                this.spaceEditingText = '';
            },
            // Texte combiné des 2 champs concernés par les espaces à remplir (DRY - même combinaison
            // que _refreshSpaceMissing()/handleSpaceFieldBlur() ci-dessus).
            _combinedSpaceFieldsText: function() {
                return (this.taskObject || '') + '\n' + (this.contextInfo || '');
            },
            // Nombre d'occurrences BORNÉES de `needle` dans `source` (même logique de frontières que
            // _hasBoundedOccurrence, jamais un indexOf naïf - « client » ne compte jamais
            // « clientèle ») - alimente le garde-fou de fusion C2-2 ci-dessous.
            _countBoundedOccurrences: function(source, needle) {
                if (!source || !needle) return 0;
                var canonSource = this._canonSearchText(source);
                var canonNeedle = this._canonSearchText(needle);
                if (!canonNeedle) return 0;
                var count = 0;
                var index = canonSource.indexOf(canonNeedle);
                while (index !== -1) {
                    if (this._isSpaceBoundary(source, index - 1) && this._isSpaceBoundary(source, index + needle.length)) count++;
                    index = canonSource.indexOf(canonNeedle, index + 1);
                }
                return count;
            },
            // Garde-fou de fusion au renommage (C2-2, couche 2, tâches 1660-1665, boucle 5 oracles
            // 2026-08-09) : le renommage FUSIONNE silencieusement toutes les occurrences existantes
            // du nouveau texte avec la pastille renommée (comportement « mail merge » déjà en place,
            // voir _applyRenameSpace/_applyPendingRenameSpace ci-dessus) - si ce nouveau texte a déjà
            // au moins une occurrence BORNÉE ailleurs dans les 2 champs, ce serait une surprise. Une
            // confirmation UNIQUE et non punitive est demandée via le mécanisme de dialogue MODAL du
            // thème (jamais confirm() natif - règle 7, voir Modules/Core/resources/views/components/
            // confirm-modal.blade.php et son instance dédiée <x-core::confirm-modal
            // name="cp-rename-merge"> dans constructeur-prompts.blade.php) avant de procéder ; sans
            // occurrence existante, `onProceed` est appelé immédiatement et SYNCHRONE (comportement
            // inchangé, zéro interruption).
            _confirmRenameMergeIfNeeded: function(newText, onProceed) {
                var n = this._countBoundedOccurrences(this._combinedSpaceFieldsText(), newText);
                if (n === 0) { onProceed(); return; }
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var template = n === 1
                    ? (i18n.spaceRenameMergeOne || 'Ce texte apparaît déjà 1 fois dans ta demande - toutes les occurrences seront remplies ensemble.')
                    : (i18n.spaceRenameMergeMany || 'Ce texte apparaît déjà {count} fois dans ta demande - toutes les occurrences seront remplies ensemble.');
                this.$dispatch('open-confirm-cp-rename-merge', {
                    message: template.replace('{count}', n),
                    callback: onProceed
                });
            },
            // Remplacement EXACT (split/join, jamais une regex construite depuis un texte
            // utilisateur) de toutes les occurrences de l'ancien texte par le nouveau dans les 2
            // textareas - comportement « mail merge » (spec §Modèle de données). Propage aussi la
            // valeur de remplissage déjà saisie (spaceValues) sous la nouvelle clé, pour ne jamais
            // perdre une réponse déjà donnée à cause d'un simple renommage. Couche 2 (canonKey,
            // 2026-08-09) : la clé cible (newText) peut différer littéralement de la forme Unicode
            // du texte tapé dans le champ de renommage - passer par _canonKey() garantit que la
            // valeur atterrit sous la MÊME clé que celle lue par spaceValueFor()/spaceValueForText()
            // pour l'espace survivant.
            _renameSpaceOccurrences: function(oldText, newText) {
                if (!oldText || oldText === newText) return;
                this.taskObject = this._replaceWithBoundaries(this.taskObject || '', oldText, newText);
                this.contextInfo = this._replaceWithBoundaries(this.contextInfo || '', oldText, newText);
                var oldKey = this._canonKey(oldText);
                var newKey = this._canonKey(newText);
                if (Object.prototype.hasOwnProperty.call(this.spaceValues, oldKey)) {
                    // Ne jamais écraser une valeur déjà saisie sous la clé cible (cas de fusion).
                    if (!Object.prototype.hasOwnProperty.call(this.spaceValues, newKey) || String(this.spaceValues[newKey] || '').trim() === '') {
                        this.spaceValues[newKey] = this.spaceValues[oldKey];
                    }
                    delete this.spaceValues[oldKey];
                }
            },

            // Retrait en 1 clic (pastille × - fonctionne pour un espace confirmé, pending ou
            // "non retrouvé") - AUCUNE corruption possible : rien n'est retiré du texte lui-même.
            removeSpace: function(idx) {
                var space = this.spaces[idx];
                if (!space) return;
                this.spaces.splice(idx, 1);
                var key = this._canonKey(space.text);
                delete this.spaceValues[key];
                delete this.spaceMissingCache[key];
                if (this.spaceEditingIndex === idx) { this.spaceEditingIndex = null; this.spaceEditingText = ''; }
            },

            // Liste affichée dans le bloc « Remplis tes espaces » (UI - remplissage) : uniquement
            // les espaces NON "non retrouvés" - un espace missing n'a nulle part où être rempli, sa
            // seule action possible reste le × de la pastille dans la bande.
            get fillableSpaces() {
                var self = this;
                return this.spaces.filter(function(sp) { return !self.spaceMissing(sp); });
            },
            // Nombre d'espaces remplissables laissés vides - alimente la mention discrète
            // « N espace(s) non rempli(s), on garde le(s) mot(s) de départ » (spec point 5, affichée
            // uniquement si > 0).
            get unfilledSpacesCount() {
                var self = this;
                var count = 0;
                for (var i = 0; i < this.spaces.length; i++) {
                    var sp = this.spaces[i];
                    if (self.spaceMissing(sp)) continue;
                    var val = self.spaceValueFor(sp);
                    if (val === undefined || val === null || String(val).trim() === '') count++;
                }
                return count;
            },
            get unfilledSpacesMessage() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var n = this.unfilledSpacesCount;
                if (n === 0) return '';
                var template = n === 1
                    ? (i18n.spaceUnfilledOne || '1 espace non rempli, on garde le mot de départ.')
                    : (i18n.spaceUnfilledMany || '{count} espaces non remplis, on garde les mots de départ.');
                return template.replace('{count}', n);
            },
            // C2-3 (couche 2, tâches 1660-1665, 2026-08-09) : avis de LECTURE SEULE affiché près des
            // boutons Copier/« Ouvrir dans » (voir constructeur-prompts.blade.php) - réutilise
            // spaceMissingCache (couche 1, rafraîchi au blur ET juste avant copie/ouverture, voir
            // copy()/openIn() plus haut). AUCUNE mutation ici, la copie part quand même même si des
            // pastilles sont orphelines - purement informatif.
            get orphanSpacesCount() {
                var self = this;
                var count = 0;
                for (var i = 0; i < this.spaces.length; i++) {
                    if (self.spaceMissing(self.spaces[i])) count++;
                }
                return count;
            },
            get orphanSpacesMessage() {
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var n = this.orphanSpacesCount;
                if (n === 0) return '';
                var template = n === 1
                    ? (i18n.spaceOrphanOne || "1 espace à remplir n'est plus dans ton texte.")
                    : (i18n.spaceOrphanMany || '{count} espaces à remplir ne sont plus dans ton texte.');
                return template.replace('{count}', n);
            },

            // Correctif UX « lien persistant pendant le nommage » (2026-08-09, capture fondateur) :
            // le plus récent espace encore `pending` (fin du tableau - addSpaceAtCursor() pousse
            // toujours en fin), alimente la ligne persistante affichée juste sous la bande de
            // pastilles. Une seule ligne même si plusieurs pending coexistent (spec : la plus
            // récente). PAS un toast (éphémère, réfuté par un oracle UX) : le lien reste visible
            // tant que le nommage n'est pas fait, disparaît uniquement quand plus aucune pastille
            // n'est pending (getter réévalué à chaque rendu Alpine, aucun état à synchroniser).
            get mostRecentPendingSpaceText() {
                for (var i = this.spaces.length - 1; i >= 0; i--) {
                    if (this.spaces[i].pending) return this.spaces[i].text;
                }
                return null;
            },
            get spacePendingHintMessage() {
                var text = this.mostRecentPendingSpaceText;
                if (!text) return '';
                var i18n = (window.promptBuilderConfig && window.promptBuilderConfig.i18n) || {};
                var template = i18n.spacePendingHint || 'Le texte « {text} » vient d\'être ajouté dans ta demande ci-dessus - donne-lui un nom parlant.';
                return template.replace('{text}', text);
            },

            // Mémoire inter-sessions (localStorage cpSpaceLastValues_v1, spec §UI - remplissage).
            // Pastilles du déjà-dit (bonification 2026-08-07) : jusqu'à 3 valeurs par espace (plus
            // récente en premier), au lieu d'une seule auparavant. Rétrocompatible : une ancienne
            // entrée v1 (chaîne simple) est migrée SILENCIEUSEMENT en tableau à 1 élément dès la
            // lecture, et ré-écrite au prochain enregistrement - même garde try/catch que le reste du
            // fichier (rounds 65-66, mode privé/quota plein).
            _loadSpaceLastValues: function() {
                try {
                    var raw = localStorage.getItem(this._spaceLastValuesKey);
                    var parsed = raw ? JSON.parse(raw) : {};
                    var result = {};
                    var migrated = false;
                    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                        for (var key in parsed) {
                            if (!Object.prototype.hasOwnProperty.call(parsed, key)) continue;
                            var v = parsed[key];
                            if (Array.isArray(v)) {
                                result[key] = v.filter(function(x) { return typeof x === 'string' && x.trim() !== ''; }).slice(0, 3);
                            } else if (typeof v === 'string' && v.trim() !== '') {
                                result[key] = [v];
                                migrated = true;
                            }
                        }
                    }
                    // Couche 2 (canonKey, 2026-08-09) : regroupe les clés RAW par forme canonique -
                    // une même valeur logique enregistrée sous 2 formes Unicode différentes
                    // (apostrophe courbe une fois, apostrophe droite une autre) formait 2 entrées
                    // localStorage distinctes avant ce correctif. Migration ADDITIVE, jamais
                    // destructrice : aucune clé pleine n'est jamais écrasée par une clé vide ; en
                    // collision réelle (2 formes du même texte), la variante dont la forme LITTÉRALE
                    // existe ENCORE dans le texte courant l'emporte, sinon la plus récente (ordre de
                    // l'objet d'origine fait foi - JS préserve l'ordre d'insertion des clés string).
                    var combinedText = this._combinedSpaceFieldsText();
                    var canonResult = {};
                    var canonRawKey = {};
                    for (var rawKey in result) {
                        if (!Object.prototype.hasOwnProperty.call(result, rawKey)) continue;
                        var ck = this._canonKey(rawKey);
                        var candidateList = result[rawKey];
                        if (!Object.prototype.hasOwnProperty.call(canonResult, ck)) {
                            canonResult[ck] = candidateList;
                            canonRawKey[ck] = rawKey;
                            continue;
                        }
                        migrated = true;
                        var existingList = canonResult[ck];
                        var existingRawKey = canonRawKey[ck];
                        var existingEmpty = !existingList || existingList.length === 0;
                        var candidateEmpty = !candidateList || candidateList.length === 0;
                        var candidateWins;
                        if (existingEmpty && !candidateEmpty) {
                            candidateWins = true;
                        } else if (!existingEmpty && candidateEmpty) {
                            candidateWins = false;
                        } else {
                            // IMPORTANT : comparaison LITTÉRALE (indexOf brut, PAS _canonSearchText)
                            // - existingRawKey et rawKey sont par définition canoniquement égaux (ils
                            // ont collisionné sur `ck` ci-dessus) ; une comparaison canonique donnerait
                            // donc TOUJOURS le même résultat pour les 2 et ne distinguerait jamais
                            // « quelle forme précise est encore là ».
                            var existingPresent = combinedText.indexOf(existingRawKey) !== -1;
                            var candidatePresent = combinedText.indexOf(rawKey) !== -1;
                            // La plus récente l'emporte par défaut (ordre d'itération = ordre
                            // d'origine, le candidat rencontré en 2e est réputé plus récent) - sauf
                            // si SEULE l'existante est encore présente littéralement dans le texte.
                            candidateWins = !(existingPresent && !candidatePresent);
                        }
                        if (candidateWins) {
                            canonResult[ck] = candidateList;
                            canonRawKey[ck] = rawKey;
                        }
                    }
                    this.spaceLastValues = canonResult;
                    if (migrated) { try { localStorage.setItem(this._spaceLastValuesKey, JSON.stringify(this.spaceLastValues)); } catch (e2) {} }
                } catch (e) { this.spaceLastValues = {}; }
            },
            // Mise à jour au moment de copier/ouvrir (valeurs non vides seulement) - jamais à chaque
            // frappe. Déduplication : une valeur déjà présente est retirée puis replacée en tête
            // (plus récente en premier), tableau plafonné à 3 entrées.
            _recordSpaceLastValues: function() {
                if (!this.spaces || this.spaces.length === 0) return;
                var changed = false;
                for (var i = 0; i < this.spaces.length; i++) {
                    var sp = this.spaces[i];
                    var val = this.spaceValueFor(sp);
                    if (val !== undefined && val !== null && String(val).trim() !== '') {
                        var ck = this._canonKey(sp.text);
                        var list = this.spaceLastValues[ck];
                        if (!Array.isArray(list)) list = list ? [list] : [];
                        var idx = list.indexOf(val);
                        if (idx !== -1) list.splice(idx, 1);
                        list.unshift(val);
                        if (list.length > 3) list = list.slice(0, 3);
                        this.spaceLastValues[ck] = list;
                        changed = true;
                    }
                }
                if (!changed) return;
                try { localStorage.setItem(this._spaceLastValuesKey, JSON.stringify(this.spaceLastValues)); } catch (e) {}
            },
            // IA préférée mémorisée (localStorage cpOpenTargetPref_v1, bonification 2026-08-07) :
            // même garde try/catch que le reste du fichier. openTargetHasPref reste faux tant
            // qu'aucune préférence valide n'a été trouvée - premier passage = comportement inchangé
            // (voir Blade, bloc « Ouvrir dans »).
            _loadOpenTargetPref: function() {
                try {
                    var stored = localStorage.getItem(this._openTargetPrefKey);
                    if (stored && Object.prototype.hasOwnProperty.call(this.openTargetNames, stored)) {
                        this.openTarget = stored;
                        this.openTargetHasPref = true;
                    }
                } catch (e) {}
            },
            _recordOpenTargetPref: function(target) {
                if (!target || !Object.prototype.hasOwnProperty.call(this.openTargetNames, target)) return;
                this.openTarget = target;
                this.openTargetHasPref = true;
                try { localStorage.setItem(this._openTargetPrefKey, target); } catch (e) {}
            },

            // === Brouillon local (2026-08-11) ===
            // Persiste l'état complet du wizard (cpDraft_v1, localStorage) pour survivre à une
            // fermeture accidentelle d'onglet ou de navigateur - AUCUNE des 5 clés localStorage déjà
            // existantes (pb_history, cpGuestHistory_v1, cp_custom_cards, cpSpaceLastValues_v1,
            // cpOpenTargetPref_v1) ne couvrait ce cas : un formulaire en cours de rédaction, jamais
            // copié ni sauvegardé, était perdu. Durée de vie bornée à 24h et purge à l'écriture d'un
            // formulaire redevenu vierge : ce poste sert aussi des écoles (postes partagés), le
            // brouillon ne doit jamais devenir une fuite de contexte personnel qui traîne.
            //
            // Ne détermine PAS si le formulaire est vierge par une énumération codée en dur des
            // champs "texte libre" (ancien code, régression prod 2026-08-11) : cette liste ignorait
            // silencieusement tout champ à SÉLECTION (personaPreset, verb, tone, technique, formats,
            // cases à cocher constraintAntiAI/constraintTypo/...) - choisir un rôle au menu déroulant
            // de l'étape 1 sans taper de texte était jugé "rien à sauvegarder", et un refresh perdait
            // ce choix. Comparaison à l'instantané du formulaire vierge à la place (_draftDefaultSnapshot,
            // capturé tout au début d'init(), avant _loadDraft() et avant ?edit=/?remix=) : couvre
            // AUTOMATIQUEMENT tout champ actuel ET tout champ ajouté plus tard à wizardParams - même
            // contrat que le getter déjà utilisé pour la sauvegarde en base. Si l'instantané n'a pas pu
            // être pris (init() pas encore passé, ou JSON.stringify a levé) : repli conservateur sur
            // false, jamais d'écriture localStorage sans base de comparaison fiable.
            _hasSignificantDraftContent: function () {
                if (this._draftDefaultSnapshot === null || this._draftDefaultSnapshot === undefined) return false;
                var current;
                try { current = JSON.stringify(this.wizardParams); } catch (e) { return false; }
                return current !== this._draftDefaultSnapshot;
            },
            // Anti-rebond ~600 ms (voir $watch('JSON.stringify(wizardParams)', ...) dans init()) -
            // jamais une écriture localStorage à chaque frappe.
            _scheduleDraftSave: function () {
                // Verrou pose par resetAll() : entre le clic sur « Recommencer » et le
                // rechargement effectif, plus aucune ecriture ne doit ressusciter le brouillon.
                if (this._draftDisabled) return;
                var self = this;
                clearTimeout(this._draftSaveTimer);
                this._draftSaveTimer = setTimeout(function () { self._saveDraftNow(); }, 600);
            },
            _saveDraftNow: function () {
                if (this._draftDisabled) return;
                try {
                    if (!this._hasSignificantDraftContent()) {
                        // Formulaire vierge (jamais rempli, ou revenu vierge après effacement manuel) :
                        // rien à laisser derrière soi - et on purge un brouillon déjà présent, sinon un
                        // visiteur qui vide son formulaire resterait piégé par l'ancien contenu au
                        // prochain chargement.
                        localStorage.removeItem(this._draftKey);
                        return;
                    }
                    var payload = { v: 1, savedAt: Date.now(), step: this.step, params: this.wizardParams };
                    localStorage.setItem(this._draftKey, JSON.stringify(payload));
                } catch (e) {}
            },
            // Lue une seule fois, dans init(), et UNIQUEMENT si ni ?edit= ni ?remix= n'est présent
            // dans l'URL - l'URL est toujours prioritaire sur le brouillon (même règle que pour la
            // restauration ?edit=ID/?remix=ID plus bas). Un brouillon corrompu ou trop vieux (> 24h)
            // est purgé et ignoré, jamais laissé bloquer le chargement de la page.
            _loadDraft: function () {
                var self = this;
                try {
                    var params = new URLSearchParams(window.location.search);
                    if (params.get('edit') || params.get('remix')) return;
                    var raw = localStorage.getItem(this._draftKey);
                    if (!raw) return;
                    var draft = JSON.parse(raw);
                    if (!draft || draft.v !== 1 || !draft.params || typeof draft.params !== 'object') {
                        localStorage.removeItem(this._draftKey);
                        return;
                    }
                    var savedAt = Number(draft.savedAt);
                    if (!isFinite(savedAt) || (Date.now() - savedAt) > this._draftMaxAgeMs) {
                        localStorage.removeItem(this._draftKey);
                        return;
                    }
                    // Correctif 2e défaut de prod (2026-08-11) : `_applyWizardParams()` est reporté à
                    // $nextTick() - la restauration synchrone (avant ce correctif) affectait
                    // personaPreset/verb/tone/technique/... AVANT qu'Alpine ait fini son walk initial
                    // du DOM. Un <select x-model="personaPreset"> dont les <option> sont peuplées par
                    // <template x-for="p in personas"> (constructeur-prompts.blade.php ~L373-377, même
                    // motif pour verb/length/tone/technique/canvasFormat/...) initialise son binding
                    // x-model AVANT de descendre dans ses enfants - donc AVANT que x-for ait inséré les
                    // <option> correspondantes. Assigner personaPreset='enseignant' à ce moment-là fait
                    // que le <select> tente `.value = 'enseignant'` alors qu'aucune <option> de cette
                    // valeur n'existe encore dans le DOM : le navigateur ignore silencieusement
                    // l'affectation (repli sur ""), et plus rien ne la resynchronise ensuite - l'effet
                    // x-model ne se redéclenche que si la valeur RECHANGE réellement, jamais sur un
                    // nouvel appel avec la même valeur. Résultat observé : l'état interne Alpine était
                    // correct (personaPreset === 'enseignant', bannière affichée) mais le <select> à
                    // l'écran restait sur "-- Sélectionnez un rôle --". $nextTick() place cette
                    // affectation APRÈS la fin du walk initial complet d'Alpine (x-for compris, qui fait
                    // partie du MÊME walk synchrone que x-model, donc déjà terminé) : personaPreset
                    // passe alors RÉELLEMENT de sa valeur par défaut ('') à la valeur du brouillon, ce
                    // qui redéclenche l'effet x-model - et cette fois l'<option> existe déjà, donc le
                    // <select> se met correctement à jour. Couvre uniformément tous les champs à
                    // sélection (menus déroulants, pastilles, cases à cocher) puisqu'ils passent tous
                    // par cette même fonction _applyWizardParams().
                    // `this.step` (juste en dessous) N'EST PAS concerné par ce report : il reste
                    // affecté ICI, en synchrone, car _applyStepFromHash() (appelée juste après
                    // _loadDraft() dans init()) doit pouvoir l'écraser si #etape-N est présent dans
                    // l'URL - la priorité de l'URL sur l'étape mémorisée du brouillon (voir init())
                    // dépend de cet ordre synchrone exact ; la reporter aussi à $nextTick casserait
                    // cette priorité (le brouillon écraserait alors #etape-N après coup).
                    if (typeof self.$nextTick === 'function') {
                        self.$nextTick(function () {
                            // legacy:false - ce brouillon a été écrit par CE MÊME code (wizardParams) au
                            // plus 24h avant, toujours au schéma courant (même raisonnement que
                            // loadGuestHistoryEntry() ci-dessus, voir _applyWizardParams()).
                            self._applyWizardParams(draft.params, { legacy: false });
                            // Regression v1.164.4 : seconde (et derniere) tentative d'application
                            // de #etape-N, MAINTENANT que les champs du brouillon sont en place.
                            // L'appel synchrone d'init() a forcement echoue sur canGoToStep() quand
                            // le formulaire etait encore vierge - c'est ce qui renvoyait a l'etape 1
                            // a chaque rafraichissement. Sans effet si l'etape a deja ete appliquee
                            // (drapeau _hashStepApplied) ou si l'URL ne porte aucun fragment.
                            self._applyStepFromHash();
                        });
                    } else {
                        this._applyWizardParams(draft.params, { legacy: false });
                        this._applyStepFromHash();
                    }
                    if (typeof draft.step === 'number' && draft.step >= 1 && draft.step <= 4) {
                        this.step = draft.step;
                    }
                    this.draftRestored = true;
                } catch (e) {
                    try { localStorage.removeItem(this._draftKey); } catch (e2) {}
                }
            }
        };
    });
});
