<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « média génératif » au glossaire (batch #11) :
 * Inpainting (cat 5 « Outils et techniques »), Upscaling/super-résolution (cat 5), Text-to-video (cat 1 « IA »).
 * Images via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'inpainting',
                'name' => 'Inpainting (retouche par masque)',
                'cat' => 5, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🖌️',
                'definition' => "L'inpainting (« remplissage » ou retouche par masque) est une technique qui consiste à reconstruire ou remplacer une zone d'une image — manquante, abîmée ou volontairement masquée — de façon cohérente avec le reste du contenu. L'utilisateur définit d'abord un masque délimitant la région à modifier ; le modèle analyse ensuite le contexte visuel environnant (textures, couleurs, formes, perspective) et génère de nouveaux pixels plausibles qui se fondent dans l'image existante, plutôt qu'un simple copier-coller ou un flou. Les méthodes modernes reposent sur des réseaux de neurones profonds — réseaux convolutifs, GAN, et surtout modèles de diffusion (Stable Diffusion, DALL·E) — entraînés sur d'immenses jeux d'images. Avec les modèles de diffusion, l'inpainting peut en outre être guidé par un prompt textuel : on décrit ce qui doit apparaître dans la zone masquée. Les usages sont nombreux : suppression d'objets ou de personnes (le « remplissage génératif » de Photoshop), restauration de photos anciennes (rayures, taches), ou modification créative d'une scène. C'est l'une des briques de l'édition d'images par IA, complémentaire de l'outpainting (extension hors cadre).",
                'analogy' => "C'est comme confier une photo déchirée à un restaurateur d'art : il observe ce qui entoure le trou — le ciel, un mur, un visage — et repeint la partie manquante pour qu'elle se fonde parfaitement, au lieu d'y coller un bout d'image étranger.",
                'example' => "Sur une photo de vacances, vous masquez un passant à l'arrière-plan ; l'outil d'inpainting analyse la plage et la mer autour, puis reconstruit le décor à sa place, comme si la personne n'avait jamais été là.",
                'did_you_know' => "Avec les modèles de diffusion, l'inpainting accepte un prompt : vous masquez une zone et demandez « ajoute un chapeau » ou « remplace par un ciel étoilé » — le modèle ne fait pas que boucher le trou, il y génère ce que vous décrivez.",
                'one_sentence_answer' => "L'inpainting est une technique d'IA qui reconstruit ou remplace une zone masquée d'une image de façon cohérente avec le reste, souvent guidée par un prompt.",
                'faq' => [
                    ['question' => "Quelle différence entre inpainting et outpainting ?", 'answer' => "L'inpainting remplit une zone à l'intérieur de l'image (objet supprimé, défaut) ; l'outpainting étend l'image au-delà de son cadre d'origine en générant un décor cohérent autour."],
                    ['question' => "L'inpainting fait-il un simple copier-coller ?", 'answer' => "Non : le modèle génère de nouveaux pixels en s'appuyant sur le contexte (textures, formes, lumière), pour que la zone retouchée se fonde naturellement, contrairement à un copier-coller ou un flou."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Inpainting", 'url' => "https://fr.wikipedia.org/wiki/Inpainting"],
                    ['label' => "arXiv — RePaint: Inpainting using Denoising Diffusion Probabilistic Models", 'url' => "https://arxiv.org/abs/2201.09865"],
                ],
            ],
            [
                'slug' => 'upscaling',
                'name' => 'Upscaling (super-résolution)',
                'cat' => 5, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🔍',
                'definition' => "L'upscaling (mise à l'échelle) par IA, ou super-résolution, regroupe les méthodes qui reconstruisent une image — ou une vidéo — de plus haute résolution (plus de pixels et de détails) à partir d'une version de basse résolution. Le problème est mal posé : une même image basse résolution peut correspondre à de nombreuses images haute résolution. Les approches modernes utilisent donc des réseaux de neurones profonds (CNN, GAN, transformeurs) entraînés sur d'énormes ensembles de couples « image nette / version dégradée ». Le modèle apprend à « halluciner » des détails plausibles — contours fins, textures — et à atténuer le flou et les artefacts de compression, plutôt que de simplement interpoler les pixels comme le ferait un agrandissement bicubique classique. On parle d'agrandissement ×2, ×4 voire davantage. Les usages sont variés : remasterisation de vieux films (1080p vers 4K), amélioration de photos de smartphone ou de visuels e-commerce, et super-résolution intégrée à certains téléviseurs et services de diffusion en continu. Des méthodes de référence comme ESRGAN ou EDSR obtiennent, sur des bancs d'essai standards, des gains nets de qualité (mesurés en PSNR et SSIM) par rapport aux méthodes traditionnelles. Limite essentielle : les détails ajoutés sont inventés, pas « récupérés ».",
                'analogy' => "C'est comme un dessinateur expert à qui l'on montre une photo floue et minuscule : à partir de son expérience de milliers d'images nettes, il redessine une version grande et détaillée — crédible, mais où une partie des détails est sa propre reconstitution.",
                'example' => "Vous agrandissez ×4 une vieille photo de famille basse résolution : l'IA reconstruit les contours du visage et la texture des vêtements pour un rendu net en haute définition, là où un simple agrandissement n'aurait donné qu'une image floue et pixelisée.",
                'did_you_know' => "L'upscaling IA n'ajoute pas d'information « cachée » dans l'image : il invente des détails plausibles appris sur d'autres images. C'est pourquoi un visage suragrandi peut paraître net mais légèrement différent de l'original — un point crucial en contexte médico-légal.",
                'one_sentence_answer' => "L'upscaling (super-résolution) par IA reconstruit une image de plus haute résolution à partir d'une version basse résolution, en générant des détails plausibles.",
                'faq' => [
                    ['question' => "L'upscaling restaure-t-il les vrais détails perdus ?", 'answer' => "Non : les détails manquants ne sont pas récupérés mais inventés de façon plausible à partir de ce que le modèle a appris ailleurs ; le résultat est crédible, pas forcément fidèle à l'original."],
                    ['question' => "Quelle différence avec un agrandissement classique ?", 'answer' => "Un agrandissement bicubique interpole les pixels et produit du flou ; la super-résolution par IA génère des contours et textures nets, pour un rendu beaucoup plus détaillé."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Super-resolution imaging", 'url' => "https://en.wikipedia.org/wiki/Super-resolution_imaging"],
                    ['label' => "arXiv — ESRGAN: Enhanced Super-Resolution Generative Adversarial Networks", 'url' => "https://arxiv.org/abs/1809.00219"],
                ],
            ],
            [
                'slug' => 'text-to-video',
                'name' => 'Text-to-video (texte vers vidéo)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🎬',
                'definition' => "Le text-to-video (texte vers vidéo) désigne les modèles d'IA générative capables de produire une séquence vidéo à partir d'une simple description textuelle (un prompt). C'est l'extension à la vidéo du text-to-image : au défi de générer des images plausibles s'ajoute celui de la cohérence temporelle — les objets, personnages et décors doivent rester stables et se déplacer de façon réaliste d'une image à l'autre, avec un mouvement fluide. Les architectures récentes combinent généralement des modèles de diffusion et des transformeurs : la vidéo est représentée comme une suite de « patches » spatio-temporels que le modèle apprend à débruiter, souvent dans un espace latent compressé pour réduire le coût de calcul. OpenAI a marqué les esprits en 2024 avec Sora ; Google (Veo), Runway, Pika ou Kling proposent des modèles comparables. Les clips générés restent courts (de quelques secondes à environ une minute) et peuvent présenter des incohérences physiques (objets qui apparaissent ou se déforment). Les usages explorés vont du prototypage publicitaire à l'illustration et au cinéma, mais soulèvent des questions de droits d'auteur, de désinformation (hypertrucages) et de coût énergétique. C'est l'une des frontières les plus actives de l'IA générative.",
                'analogy' => "C'est comme dicter un mini scénario à un studio d'animation instantané : vous décrivez « un chat astronaute qui flotte dans une cuisine », et la machine tourne et monte les images, plan par plan, en veillant à ce que le chat reste le même tout au long du clip.",
                'example' => "À partir du prompt « une vague géante déferle au ralenti sur une plage au coucher du soleil », un modèle text-to-video produit un clip de quelques secondes montrant la vague en mouvement, avec lumière et écume cohérentes d'une image à l'autre.",
                'did_you_know' => "Le plus dur en text-to-video n'est pas de faire de belles images, mais d'assurer la cohérence temporelle : sans elle, un personnage changerait de visage ou de vêtements à chaque image. C'est pourquoi ces modèles raisonnent sur des « morceaux » d'espace ET de temps à la fois.",
                'one_sentence_answer' => "Le text-to-video génère une séquence vidéo à partir d'une description textuelle, en assurant la cohérence temporelle entre les images.",
                'faq' => [
                    ['question' => "Quelle différence avec le text-to-image ?", 'answer' => "Le text-to-image produit une image fixe ; le text-to-video doit en plus garantir la cohérence dans le temps — un mouvement fluide et des objets stables d'une image à l'autre — ce qui est nettement plus difficile."],
                    ['question' => "Quelle est la durée des vidéos générées ?", 'answer' => "Encore courte en 2025-2026 : de quelques secondes à environ une minute selon les modèles (Sora, Veo, Runway, Kling), avec parfois des incohérences physiques sur les clips plus longs."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Text-to-video model", 'url' => "https://en.wikipedia.org/wiki/Text-to-video_model"],
                    ['label' => "Wikipédia — Sora (text-to-video model)", 'url' => "https://en.wikipedia.org/wiki/Sora_(text-to-video_model)"],
                ],
            ],
        ];
    }

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[glossaire] modèle Term absent — ignoré\n";
            return;
        }

        // Cette migration insère des données avec des FK vers dictionary_categories
        // qui n'existent que sur MySQL (seedées en prod). SQLite en tests = skip.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";
                continue;
            }
            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->dictionary_category_id = $t['cat'];
            $term->hero_image = 'images/glossaire/'.$t['slug'].'.webp';
            $term->is_published = true;
            $term->match_strategy = 'loose';
            $term->save();
            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }
        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
