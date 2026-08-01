<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai - rotation des encarts publicitaires internes (livres)
 */

namespace Modules\Books\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Books\Models\Book;

/**
 * Choisit, de façon DÉTERMINISTE, quel livre affiche un encart publicitaire interne.
 *
 * Pourquoi déterministe et non aléatoire : le rendu de l'encart est mis en cache
 * 600 secondes par clé d'emplacement (AdsRenderer, Cache::remember("ad_placement:{key}")),
 * et la page entière est mise en cache par spatie/laravel-responsecache. Un tirage
 * aléatoire serait donc figé par ces deux couches : le « hasard » serait tiré une
 * fois puis servi identiquement à tout le monde pendant toute la durée du cache.
 * La rotation est donc calculée à partir de (clé d'emplacement + jour) : elle avance
 * d'un cran chaque jour, reste stable à l'intérieur d'une journée, et coopère avec
 * les caches au lieu de lutter contre eux.
 */
class BookPromoRotator
{
    /** Clé de l'emplacement en cours de rendu (ex. « article-top »). */
    protected static ?string $placementKey = null;

    /**
     * Slugs déjà servis pendant la requête courante, pour ne jamais afficher
     * deux fois le même livre sur une seule page.
     *
     * @var array<int, string>
     */
    protected static array $servedThisRequest = [];

    /**
     * Rang stable de l'emplacement, quand l'appelant en connaît un (l'identifiant de
     * la ligne ads_placements, par exemple). Deux emplacements de rangs consécutifs
     * obtiennent alors deux livres consécutifs du pool : la distinction est GARANTIE
     * par construction, sans dépendre d'un hachage ni d'un état partagé de requête.
     * C'est indispensable ici, parce que chaque emplacement est mis en cache
     * séparément par AdsRenderer : le compteur de resolveDistinct() ne survit pas
     * d'un rendu mis en cache à l'autre.
     */
    protected static ?int $ordinal = null;

    public static function setContext(?string $placementKey, ?int $ordinal = null): void
    {
        self::$placementKey = $placementKey;
        self::$ordinal = $ordinal;
    }

    public static function clearContext(): void
    {
        self::$placementKey = null;
        self::$ordinal = null;
    }

    /** Vide le registre des slugs servis (appelé en début de requête et dans les tests). */
    public static function resetServed(): void
    {
        self::$servedThisRequest = [];
    }

    /**
     * Liste ordonnée des livres promus. Configurable (zéro contenu en dur imposé) :
     * config/books.php → promo_pool, avec repli si la config n'est pas publiée.
     *
     * @return array<int, string>
     */
    public static function pool(): array
    {
        $pool = config('books.promo_pool', [
            'ia-sans-se-faire-poursuivre',
            'ia-pour-les-parents',
            'nexus-neural-tome-1',
        ]);

        $pool = array_map(static fn ($slug): string => trim((string) $slug), (array) $pool);

        return array_values(array_filter($pool, static fn (string $slug): bool => $slug !== ''));
    }

    /**
     * Slug du jour pour l'emplacement courant.
     *
     * Honnêteté sur la limite : deux clés d'emplacement différentes ne donnent des
     * livres différents que si leurs crc32 ne sont pas congrus modulo la taille du
     * pool. Ce n'est donc PAS une garantie. C'est resolveDistinct() qui garantit
     * qu'une même page n'affiche jamais deux fois le même livre.
     */
    public static function currentSlug(): string
    {
        $pool = self::pool();
        $count = count($pool);

        if ($count === 0) {
            return '';
        }

        $now = Carbon::now('America/Toronto');

        // Numéro de jour absolu : le décalage avance d'un cran par jour et ne
        // retombe pas brutalement à zéro au 1er janvier.
        $absoluteDay = ((int) $now->format('Y')) * 366 + ((int) $now->format('z'));

        // Rang explicite si l'appelant en fournit un (distinction garantie), sinon
        // repli sur un hachage de la clé. crc32 renvoie un entier non signé
        // 0..4294967295 sur 64 bits ; le abs() protège le cas 32 bits où la valeur
        // peut revenir négative.
        $offset = self::$ordinal
            ?? (self::$placementKey === null ? 0 : abs(crc32(self::$placementKey)));

        return $pool[($offset + $absoluteDay) % $count];
    }

    /**
     * Comme currentSlug(), mais ne renvoie jamais deux fois le même slug dans une
     * même requête tant que le pool n'est pas épuisé. C'est ce qui corrige le
     * défaut constaté en production : les emplacements « article-top » et
     * « article-bottom » affichaient le MÊME livre sur la même page.
     */
    public static function resolveDistinct(): string
    {
        $pool = self::pool();
        $count = count($pool);

        if ($count === 0) {
            return '';
        }

        $index = (int) array_search(self::currentSlug(), $pool, true);

        for ($step = 0; $step < $count; $step++) {
            $candidate = $pool[($index + $step) % $count];

            if (! in_array($candidate, self::$servedThisRequest, true)) {
                self::$servedThisRequest[] = $candidate;

                return $candidate;
            }
        }

        // Pool épuisé sur cette page : on recommence un cycle propre.
        self::$servedThisRequest = [$pool[$index]];

        return $pool[$index];
    }

    /**
     * Props prêtes à être étalées dans <x-fronttheme::book-promo>, lues depuis la
     * table books : source unique partagée avec la bibliothèque /livres (DRY), donc
     * l'encart et la fiche du livre ne peuvent pas diverger.
     *
     * Retourne un tableau VIDE si le livre est introuvable, non publié, ou si quoi
     * que ce soit échoue : le composant retombe alors sur ses valeurs par défaut.
     * Ce service est appelé au rendu de pages publiques, il ne doit jamais pouvoir
     * faire tomber une page.
     *
     * @return array<string, mixed>
     */
    public static function props(?string $slug = null): array
    {
        $slug = $slug ?? self::currentSlug();

        if ($slug === '') {
            return [];
        }

        try {
            $book = Book::query()
                ->where('slug', $slug)
                ->where('is_published', 1)
                ->first();

            if (! $book) {
                return [];
            }

            $title = (string) $book->title;
            $subtitle = (string) $book->subtitle;

            // ATTENTION : seuls ces quatre fichiers existent réellement par livre
            // (vérifié sur disque dans public/images/livres). Il n'existe AUCUNE
            // variante -cover-1200.webp : ne pas en inventer une pour le srcset 2x,
            // cela produirait une image cassée en écran haute densité.
            $base = "/images/livres/{$slug}";

            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'description_short' => (string) $book->one_sentence_answer,
                'cover_url_webp' => "{$base}-cover-600.webp",
                'cover_url_webp_2x' => "{$base}-cover-600.webp",
                'cover_url_jpg' => "{$base}-cover-600.jpg",
                'cover_url_300' => "{$base}-cover-300.webp",
                'og_image' => "{$base}-og-1200x630.jpg",
                'cover_alt' => trim("{$title} - {$subtitle} - par Stéphane Lapointe"),
                'cta_url' => $book->amazon_url_paperback ?: $book->amazon_url_kindle,
                'date_published' => $book->date_published
                    ? (string) Carbon::parse($book->date_published)->year
                    : '2026',
            ];
        } catch (\Throwable $e) {
            Log::warning('BookPromoRotator : lecture du livre impossible, repli sur les valeurs par défaut du composant', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
