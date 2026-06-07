<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Consolidation du doublon de glossaire MCP :
     * - Garde le terme canonique 'mcp' (slug fr_CA, acronyme, contenu propre)
     * - Dépublie le doublon 'mcp-model-context-protocol' (ajouté via l'admin sur prod ;
     *   la redirection 301 du long slug vers /glossaire/mcp est gérée côté routes)
     * - Fusionne les alias supplémentaires dans le terme canonique
     * Réversible : retire les alias ajoutés et republie le doublon. AUCUN DELETE.
     */
    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[mcp] Term model not found, skipping migration.\n";
            return;
        }

        [$canonicalSlug, $dupSlug, $extraAliases] = $this->getConstants();

        // Mise à jour du terme canonique
        $c = Term::where('slug->fr_CA', $canonicalSlug)->first();
        if ($c) {
            $cur = $c->aliases ?? [];
            $c->aliases = array_values(array_unique(array_merge($cur, $extraAliases)));
            $c->save();
            echo "[mcp] alias fusionnés.\n";
        }

        // Dépublication du doublon
        $d = Term::where('slug->fr_CA', $dupSlug)->first();
        if ($d && $d->is_published) {
            $d->is_published = false;
            $d->save();
            echo "[mcp] doublon dépublié id={$d->id}.\n";
        } else {
            echo "[mcp] doublon absent ou déjà dépublié.\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            echo "[mcp] Term model not found, skipping rollback.\n";
            return;
        }

        [$canonicalSlug, $dupSlug, $extraAliases] = $this->getConstants();

        // Restauration du terme canonique (retrait exact des extraAliases)
        $c = Term::where('slug->fr_CA', $canonicalSlug)->first();
        if ($c) {
            $c->aliases = array_values(array_diff($c->aliases ?? [], $extraAliases));
            $c->save();
        }

        // Republication du doublon
        $d = Term::where('slug->fr_CA', $dupSlug)->first();
        if ($d) {
            $d->is_published = true;
            $d->save();
        }
    }

    private function getConstants(): array
    {
        return [
            'mcp',
            'mcp-model-context-protocol',
            ['serveur MCP', 'MCP server', 'protocole MCP'],
        ];
    }
};
