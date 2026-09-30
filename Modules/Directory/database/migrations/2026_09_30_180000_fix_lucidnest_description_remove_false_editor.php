<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca>
 * @project laveille.ai
 *
 * Retire de la fiche LucidNest la phrase fausse « LucidNest est édité par MEMORA
 * solutions, qui édite aussi laveille.ai » : c'est le fondateur personnellement
 * qui édite laveille.ai, pas MEMORA. La migration d'origine a déjà écrit cette
 * phrase en base ; on la retire ici sans rien réintroduire. Idempotent.
 */
return new class extends Migration
{
    private const FAUX = "\n\n_LucidNest est édité par MEMORA solutions, qui édite aussi laveille.ai._";

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return; // la fiche n'existe pas dans la base de test
        }

        $row = DB::table('directory_tools')->where('url', 'like', '%lucidnest.io%')->first();
        if (! $row) {
            return;
        }

        $desc = json_decode($row->description, true);
        if (! is_array($desc) || ! isset($desc['fr_CA']) || ! str_contains($desc['fr_CA'], self::FAUX)) {
            return;
        }

        $desc['fr_CA'] = str_replace(self::FAUX, '', $desc['fr_CA']);

        DB::table('directory_tools')->where('id', $row->id)->update([
            'description' => json_encode($desc, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No-op volontaire : on ne réintroduit pas une phrase factuellement fausse.
    }
};
