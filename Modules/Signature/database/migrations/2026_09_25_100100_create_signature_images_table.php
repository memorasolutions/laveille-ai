<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * .devis/outil_signature_html/PLAN-OUTIL-SIGNATURE.md, section 11.2. Répéteur TECHNIQUE (pas un
 * répéteur de contenu au sens de la section 3) : une ligne par image (logo/portrait/banniere).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_id')->constrained('signatures')->cascadeOnDelete();
            $table->string('role', 20);
            // Identifiant PUBLIC non séquentiel (correctif B3, 32 caractères hex CSPRNG) : seul
            // identifiant jamais exposé dans l'URL /signature-assets/{public_id}.{ext} - l'id
            // auto-incrémenté interne n'y apparaît plus jamais (énumération triviale bloquée).
            $table->string('public_id', 32)->unique();
            // Chemin de la DÉRIVÉE servie publiquement (dossier signature-derivatives) - nullable
            // après purge (section 6.3/6.4).
            $table->string('path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            // Taille D'AFFICHAGE choisie par l'utilisateur (section 5), indépendante du fichier
            // stocké à résolution plus élevée (sur-échantillonnage fixe x2).
            $table->unsignedInteger('display_width')->nullable();
            $table->unsignedInteger('display_height')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            // Index unique (signature_id, role) : au plus UNE image par rôle par signature - le
            // pipeline remplace déjà l'existant (SignatureImagePipeline::process()), cet index rend
            // l'invariant vrai aussi au niveau base, jamais seulement en PHP.
            $table->unique(['signature_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_images');
    }
};
