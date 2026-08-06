<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// ACTION: mettre à jour la ligne settings tools.prompt_builder.audiences vers la liste recalibrée
// SELF: migration de données sensible (table settings, local ET prod) - supervision directe
// RAISON: le seeder du 2026-07-26 a figé l'ancienne liste en DB via updateOrCreate ; sans cette
// migration, le nouveau défaut du code (tâche 1633, consensus panel du 2026-08-06) reste invisible
// car Settings::get() sert toujours la valeur DB. Réversible : down() restaure l'ancienne liste.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY = 'tools.prompt_builder.audiences';

    private const NOUVELLES = [
        ['value' => 'eleves_primaire', 'label' => 'Élèves du primaire'],
        ['value' => 'eleves_secondaire', 'label' => 'Élèves du secondaire'],
        ['value' => 'etudiants', 'label' => 'Étudiants'],
        ['value' => 'parents', 'label' => 'Parents'],
        ['value' => 'collegues', 'label' => 'Collègues de travail'],
        ['value' => 'direction', 'label' => 'Direction ou gestionnaires'],
        ['value' => 'clients', 'label' => 'Clients'],
        ['value' => 'grand_public', 'label' => 'Grand public'],
    ];

    private const ANCIENNES = [
        ['value' => 'pro', 'label' => 'Professionnels du secteur'],
        ['value' => 'debutants', 'label' => 'Débutants'],
        ['value' => 'entrepreneurs', 'label' => 'Entrepreneurs et dirigeants'],
        ['value' => 'etudiants', 'label' => 'Étudiants universitaires'],
        ['value' => 'grand_public', 'label' => 'Grand public'],
        ['value' => 'techniques', 'label' => 'Collègues techniques'],
        ['value' => 'direction', 'label' => 'Direction générale'],
    ];

    public function up(): void
    {
        $this->setValue(self::NOUVELLES);
    }

    public function down(): void
    {
        $this->setValue(self::ANCIENNES);
    }

    private function setValue(array $audiences): void
    {
        DB::table('settings')->where('key', self::KEY)->update([
            'value' => json_encode($audiences, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        // Le modèle Setting met chaque clé en cache sous « setting.{clé} » (voir
        // Modules/Settings/app/Models/Setting.php) : purge obligatoire, sinon l'ancienne
        // liste survit à la migration jusqu'à l'expiration du cache.
        \Illuminate\Support\Facades\Cache::forget('setting.'.self::KEY);
    }
};
