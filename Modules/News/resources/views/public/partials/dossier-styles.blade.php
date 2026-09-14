{{--
    Styles partagés par les deux vues de dossier thématique (index et détail).

    Utilisation :
        @include('news::public.partials.dossier-styles')

    Même parti pris que article-card.blade.php : les styles vivent AVEC le composant,
    sous @once, pour que deux vues qui les utilisent n'en portent qu'une seule copie.
    « .nw-chip » et « .nw-page-intro » ne sont PAS redéfinis ici : ces classes existent
    déjà dans trois vues du site, en ajouter une quatrième copie ferait diverger les
    quatre. Les classes ci-dessous sont propres aux dossiers.

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}

@once
@push('styles')
<style>
    .nw-dossier-intro { color: #6b7280; margin-bottom: 1.5rem; font-size: 1rem; }
    .nw-dossier-card {
        height: 100%;
        border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .nw-dossier-card:hover { border-color: var(--c-primary); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .nw-dossier-card-link { display: block; text-decoration: none; color: inherit; }
    .nw-dossier-card-link:hover { text-decoration: none; color: inherit; }
    .nw-dossier-card-title {
        font-family: var(--f-heading);
        font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0 0 0.25rem;
    }
    .nw-dossier-card-count { font-size: 0.875rem; color: #6b7280; }
    .nw-dossier-voisins { margin-top: 2rem; }
    .nw-dossier-voisins h2 { font-family: var(--f-heading); font-size: 1.25rem; margin-bottom: 0.75rem; }
    .nw-dossier-voisins-liste { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .nw-dossier-lien {
        display: inline-flex; align-items: center;
        padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.8125rem;
        background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;
        text-decoration: none; transition: background 0.15s, color 0.15s;
    }
    .nw-dossier-lien:hover { background: #e5e7eb; color: #1f2937; text-decoration: none; }
    .nw-dossier-retour { display: inline-block; margin-top: 1.5rem; color: var(--c-primary); }
</style>
@endpush
@endonce
