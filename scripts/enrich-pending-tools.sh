#!/bin/bash
# ============================================================
# La veille — Enrichissement automatique des outils pending
# Lancé par cron à 4h15 chaque jour (après la récolte n8n à 4h00)
# Utilise Claude Code CLI (pas d'API, utilise le compte existant)
# ============================================================

LOG="/tmp/laveille-enrich-$(date +%Y-%m-%d).log"
PROJECT="/Users/stephanelapointe/__IA__/_____SERVEUR_____/site_internet/la-veille-de-stef-v2"

echo "=== La veille — Enrichissement $(date) ===" >> "$LOG"

cd "$PROJECT" || exit 1

# Vérifier s'il y a des outils pending
PENDING_COUNT=$(php artisan tinker --execute="echo \Modules\Directory\Models\Tool::where('status','pending')->count();" 2>/dev/null)

if [ "$PENDING_COUNT" = "0" ] || [ -z "$PENDING_COUNT" ]; then
    echo "Aucun outil pending. Fin." >> "$LOG"
    exit 0
fi

echo "$PENDING_COUNT outil(s) pending à enrichir" >> "$LOG"

# Lancer Claude Code CLI pour enrichir les outils pending
# --dangerously-skip-permissions : pas de confirmation interactive (cron)
# Le prompt demande d'utiliser les MCP (sonar-pro, qwen3-max, playwright)
claude -p "Tu es le chef d'orchestre. Il y a $PENDING_COUNT outil(s) en statut 'pending' dans le répertoire laveille.ai (table directory_tools, status='pending').

Pour CHAQUE outil pending :
1. Recherche les informations complètes via openrouter/sonar-pro (fonctionnalités, pricing, alternatives, avis avril 2026)
2. Recherche 3 tutoriels YouTube en français via sonar-pro
3. Rédige la fiche complète via multi-ai-mcp/qwen3-max (description 800-1200 mots avec H2 Markdown, short_description, core_features, use_cases, pros, cons, how_to_use, target_audience, faq)
4. Accents français parfaits, espaces insécables avant : ; ? !
5. Vérifie la déduplication (similar_text avec les outils existants publiés)
6. Ajoute les tutoriels YouTube via resources() ou metadata
7. Passe le statut à 'published'
8. NE PAS capturer de screenshot (pas de Playwright pour cette tâche automatique)

À la fin, affiche le bilan : combien enrichis, lesquels, erreurs éventuelles.

IMPORTANT :
- Délègue TOUT aux MCP (sonar-pro pour recherche, qwen3-max pour rédaction)
- Ne génère PAS de code toi-même sauf corrections < 5 lignes
- Vérifie les accents et l'orthographe avant de publier
- Ne jamais créer de doublon
- Maximum 5 outils par exécution (pour ne pas dépasser les limites)" \
  --dangerously-skip-permissions \
  --output-format text \
  >> "$LOG" 2>&1

echo "=== Fin enrichissement $(date) ===" >> "$LOG"
