<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Méthodologie & charte éditoriale') . ' - ' . config('app.name'))
@section('meta_description', __('Comment La veille évalue les outils, source les actualités et garantit son indépendance éditoriale. Méthodologie transparente, scoring rigoureux, fraîcheur des données.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Méthodologie')])
@endsection

@push('head')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Méthodologie & charte éditoriale — La veille",
  "description": "Comment La veille évalue les outils IA, source les actualités et garantit son indépendance éditoriale.",
  "datePublished": "2026-05-09",
  "dateModified": "{{ now()->toIso8601String() }}",
  "inLanguage": "fr-CA",
  "author": {
    "@type": "Person",
    "name": "Stéphane Lapointe",
    "url": "{{ url('/auteur/stephane-lapointe') }}",
    "jobTitle": "Veille IA & Transformation numérique Québec"
  },
  "publisher": {
    "@type": "Organization",
    "name": "La veille",
    "url": "{{ url('/') }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('images/logo-horizontal-white.svg') }}"
    }
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ url()->current() }}"
  }
}
</script>
@endpush

@section('content')
<section style="padding: 60px 0 40px; background: #fff;">
    <div class="container">
        <div style="max-width: 820px; margin: 0 auto;">

            <header style="margin-bottom: 36px;">
                <p style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px; font-weight: 700; color: var(--c-primary, #064E5A); margin: 0 0 10px;">
                    {{ __('Charte éditoriale') }}
                </p>
                <h1 style="margin: 0 0 14px; font-weight: 800; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; letter-spacing: -0.5px; color: var(--c-dark, #1a1d23);">
                    {{ __('Méthodologie') }} <span style="color: var(--c-primary, #064E5A);">&amp; transparence</span>
                </h1>
                <p style="font-size: 18px; color: #4b5563; margin: 0; line-height: 1.55;">
                    {{ __("Comment La veille sélectionne les outils, source les actualités et garde son indépendance. Voici les règles que je m'impose pour que ce site reste utile et crédible.") }}
                </p>
                <p style="font-size: 13px; color: #6b7280; margin: 16px 0 0;">
                    📅 {{ __('Dernière mise à jour') }} : <time datetime="{{ now()->toDateString() }}">{{ now()->isoFormat('LL') }}</time>
                </p>
            </header>

            <div style="background: linear-gradient(135deg, #F0F4F8 0%, #ffffff 100%); border-left: 4px solid var(--c-primary, #064E5A); padding: 22px 26px; border-radius: 8px; margin-bottom: 40px;">
                <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #1f2937;">
                    <strong>{{ __('Promesse en une ligne') }} :</strong>
                    {{ __("La veille est rédigé à 100 % par un humain (Stéphane Lapointe), assisté de l'IA pour la recherche et la rédaction, vérifié manuellement avant publication, et financé exclusivement par la newsletter, l'affiliation transparente et la boutique. Aucun outil ne paie pour figurer.") }}
                </p>
            </div>

            <article style="font-size: 16px; line-height: 1.7; color: #1f2937;">

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('1. Qui rédige') }}</h2>
                <p>
                    {{ __("Tous les contenus (articles, fiches outils, glossaire, tutoriels) sont sélectionnés et publiés par Stéphane Lapointe, fondateur de Memora Solutions, basé au Québec. Profil, expertise et historique de publication sont disponibles sur la page auteur.") }}
                </p>
                <p>
                    <a href="{{ url('/auteur/stephane-lapointe') }}" style="color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: underline;">→ {{ __('Voir le profil auteur') }}</a>
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('2. Comment les outils sont choisis') }}</h2>
                <p>{{ __("Un outil entre dans l'annuaire si au moins 3 critères sur 5 sont vérifiés :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __('Pertinence pour un public francophone (Québec, France, Belgique, Suisse) ou utilité reconnue mondialement.') }}</li>
                    <li>{{ __("Qualité de la documentation officielle (site, pricing, conditions d'utilisation accessibles).") }}</li>
                    <li>{{ __("Existence d'une version testable (essai gratuit, freemium ou démo) ou réputation marché établie.") }}</li>
                    <li>{{ __('Conformité minimale RGPD / Loi 25 Québec sur la protection des données.') }}</li>
                    <li>{{ __("Cas d'usage clair pour enseignants, créateurs, professionnels ou étudiants.") }}</li>
                </ul>
                <p>
                    {{ __("Les 282+ outils du répertoire ont été soit ajoutés manuellement, soit issus d'audits sectoriels (Top 50 EdTech, IA générative, accessibilité). Aucun ajout n'est rémunéré : les liens d'affiliation sont signalés et n'influencent pas la sélection.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('3. Comment les fiches sont notées') }}</h2>
                <p>{{ __("Chaque fiche outil comprend, lorsque l'information est disponible :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li><strong>{{ __('Tarification éducation') }}</strong> {{ __('— vérifiée annuellement (gratuit, freemium, rabais ou vérification requise).') }}</li>
                    <li><strong>{{ __('Niveau de langue') }}</strong> {{ __('— interface FR / EN / multilingue, validé par test direct.') }}</li>
                    <li><strong>{{ __('Catégorie') }}</strong> {{ __('— assignée selon une taxonomie de 12 catégories majeures.') }}</li>
                    <li><strong>{{ __('Tutoriels associés') }}</strong> {{ __("— curatés depuis YouTube (chaînes francophones reconnues : École branchée, Alloprof, RÉCIT, Ludovic Nédélec, Le Prof Connecté), validés via YouTube oEmbed.") }}</li>
                    <li><strong>{{ __('Cycle de vie') }}</strong> {{ __('— actif, en déclin ou fermé (signalé visuellement).') }}</li>
                </ul>
                <p>
                    {{ __("Les avis utilisateurs visibles sur les fiches sont modérés : ils n'influencent pas la sélection mais éclairent le choix.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('4. Fraîcheur des données') }}</h2>
                <p>
                    {{ __("Les outils en évolution rapide (IA générative surtout) sont revérifiés régulièrement : URL, pricing, présence d'un programme éducation, statut du projet (actif / fermé / racheté). Les fiches affichent visuellement la date de dernière vérification et signalent les changements importants par un badge dédié.") }}
                </p>
                <p>
                    {{ __("La veille publie également un Concentré IA hebdomadaire (chaque dimanche) qui résume les nouveautés vérifiées de la semaine. Les flux RSS permettent de suivre automatiquement les concentrés et les nouveautés du répertoire.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('5. Sources et références') }}</h2>
                <p>{{ __("Pour chaque actualité, La veille s'appuie sur :") }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __('Sources primaires : annonces officielles des fournisseurs, dépôts GitHub, documentation API.') }}</li>
                    <li>{{ __("Médias spécialisés reconnus : The Verge, TechCrunch, Le Devoir (volet techno), Radio-Canada, École branchée.") }}</li>
                    <li>{{ __("Recherche académique : Google Scholar, Semantic Scholar, arXiv pour les sujets de fond.") }}</li>
                    <li>{{ __("Pour les guides Québec : MEQ, RÉCIT, Université Laval, Université de Montréal, CDPDJ pour les cadres éthiques.") }}</li>
                </ul>
                <p>
                    {{ __("Les outils d'IA générative (ChatGPT, Claude, Perplexity) sont utilisés comme assistants de recherche et de rédaction, jamais comme source unique. Toute affirmation factuelle est vérifiée avant publication.") }}
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('6. Indépendance éditoriale') }}</h2>
                <p>{{ __('La veille fonctionne sans dépendance directe à un fournisseur. Le modèle de revenus est transparent :') }}</p>
                <ul style="margin: 12px 0 16px; padding-left: 24px;">
                    <li>{{ __("Newsletter gratuite (modèle attention).") }}</li>
                    <li>{{ __("Liens d'affiliation signalés explicitement (mention en pied de page de chaque article concerné).") }}</li>
                    <li>{{ __("Boutique de produits dérivés (chandails, mugs, etc.).") }}</li>
                    <li>{{ __("Aucun publi-reportage déguisé. Tout contenu commandité serait clairement étiqueté « Sponsorisé ».") }}</li>
                </ul>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('7. Corrections et signalement') }}</h2>
                <p>
                    {{ __("Une erreur ou un outil obsolète ? Tout retour est bienvenu via la page Contact ou le bouton de signalement présent sur chaque fiche outil. Les corrections importantes sont datées dans une section de mise à jour visible.") }}
                </p>
                <p>
                    {{ __("Engagement : toute correction d'erreur factuelle est traitée sous 7 jours ouvrables.") }}
                </p>
                <p>
                    <a href="{{ route('contact') }}" style="color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: underline;">→ {{ __('Signaler une erreur ou suggérer un outil') }}</a>
                </p>

                <h2 style="font-weight: 800; font-size: 24px; margin: 32px 0 14px; color: var(--c-dark);">{{ __('8. Accessibilité') }}</h2>
                <p>
                    {{ __("La veille vise le standard WCAG 2.2 niveau AAA pour la conformité d'accessibilité numérique : contrastes 7:1 minimum, cibles cliquables 44 px, navigation clavier complète, sous-titres pour les vidéos intégrées. Les écarts résiduels sont audités et corrigés en continu.") }}
                </p>

            </article>

            <div style="background: #F0F4F8; border-radius: 12px; padding: 26px 28px; margin-top: 44px; text-align: center;">
                <p style="margin: 0 0 14px; font-weight: 700; font-size: 17px; color: var(--c-dark);">
                    {{ __('Une question sur cette méthodologie ?') }}
                </p>
                <a href="{{ route('contact') }}" style="display: inline-block; background: var(--c-primary, #064E5A); color: #fff; font-weight: 700; padding: 12px 26px; border-radius: 50px; text-decoration: none; min-height: 44px; line-height: 20px;">
                    📩 {{ __('Me contacter') }}
                </a>
            </div>

        </div>
    </div>
</section>
@endsection
