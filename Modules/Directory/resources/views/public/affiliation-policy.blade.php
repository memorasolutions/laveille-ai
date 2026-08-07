@extends(fronttheme_layout())
@section('title', __('Politique de liens d\'affiliation') . ' - ' . config('app.name'))
@section('meta_description', __('Comment fonctionnent les liens d\'affiliation dans l\'annuaire d\'outils IA, et comment nous les identifions.'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Politique de liens d\'affiliation')])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2 style="color:var(--c-dark);">Politique de liens d'affiliation</h2>
                        <div style="background:var(--c-primary-light,#F0FAFB);border:1px solid #cfe9ee;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:var(--c-dark);">
                            ℹ️ « Cette page explique de façon transparente comment fonctionnent certains liens de l'annuaire. »
                        </div>
                        <h3 style="color:var(--c-dark);">1. Qu'est-ce qu'un lien d'affiliation ?</h3>
                        <p style="color:var(--c-text-secondary);">
                            Certains liens « Visiter le site » de notre annuaire d'outils sont des liens d'affiliation. Cela signifie que si vous cliquez sur un tel lien et que vous vous abonnez ou achetez un produit auprès de l'éditeur concerné, La veille peut recevoir une commission de la part de cet éditeur. Cette commission n'entraîne aucun coût additionnel pour vous : le prix que vous payez reste exactement le même que si vous étiez arrivé directement sur le site de l'éditeur.
                        </p>
                        <h3 style="color:var(--c-dark);">2. Comment reconnaître un lien d'affiliation</h3>
                        <p style="color:var(--c-text-secondary);">
                            Chaque lien d'affiliation est identifié par un badge « Lien affilié » affiché juste à côté du bouton « Visiter le site », directement sur la fiche de l'outil concerné. Aucun lien d'affiliation n'est dissimulé : si le badge n'apparaît pas, le lien pointe directement vers le site de l'éditeur, sans commission.
                        </p>
                        <h3 style="color:var(--c-dark);">3. Notre engagement d'indépendance éditoriale</h3>
                        <p style="color:var(--c-text-secondary);">
                            Nos évaluations et sélections sont réalisées de façon indépendante : l'existence ou non d'un programme d'affiliation ne fait pas partie de nos critères de sélection.
                        </p>
                        <h3 style="color:var(--c-dark);">4. Pourquoi des liens d'affiliation</h3>
                        <p style="color:var(--c-text-secondary);">
                            Ces commissions contribuent à financer le travail de veille, de test et de rédaction que nous consacrons à l'annuaire, sans faire reposer les coûts sur nos lecteurs. Cette approche est courante chez les médias et annuaires spécialisés, et nous choisissons de la rendre entièrement visible plutôt que de la dissimuler.
                        </p>
                        <h3 style="color:var(--c-dark);">5. Nous joindre</h3>
                        <p style="color:var(--c-text-secondary);">
                            Pour toute question sur cette politique, vous pouvez utiliser notre <a href="{{ route('contact') }}" style="color:var(--c-primary);">formulaire de contact</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
