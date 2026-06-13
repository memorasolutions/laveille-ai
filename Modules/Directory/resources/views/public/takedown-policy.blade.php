@extends(fronttheme_layout())
@section('title', __('Politique de retrait de contenu') . ' - ' . config('app.name'))
@section('meta_description', __('Comment soumettre une demande de retrait de contenu (droit d\'auteur, marque, données personnelles) et comment nous la traitons.'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Politique de retrait')])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2 style="color:var(--c-dark);">Politique de retrait de contenu</h2>
                        <div style="background:var(--c-primary-light,#F0FAFB);border:1px solid #cfe9ee;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:var(--c-dark);">
                            ⚠️ « Ce document décrit notre processus de traitement des demandes de retrait. Il ne constitue pas un avis juridique et sera revu par un conseiller juridique. »
                        </div>
                        <h3 style="color:var(--c-dark);">1. Objet</h3>
                        <p style="color:var(--c-text-secondary);">
                            Cette politique s'adresse aux titulaires de droits (droit d'auteur, marque de commerce) et aux personnes concernées par la diffusion de leurs données personnelles. Elle vise le contenu publié dans l'annuaire d'outils (fiches descriptives, captures d'écran, logos).
                        </p>
                        <h3 style="color:var(--c-dark);">2. Comment soumettre une demande</h3>
                        <p style="color:var(--c-text-secondary);">
                            Utilisez notre <a href="{{ route('directory.takedown.create') }}" style="color:var(--c-primary);">formulaire de demande de retrait</a>. Le formulaire vous guide à travers les éléments requis.
                        </p>
                        <h3 style="color:var(--c-dark);">3. Éléments requis d'un avis recevable</h3>
                        <ul style="color:var(--c-text-secondary);">
                            <li>Identité complète du demandeur (et, le cas échéant, preuve du mandat) ;</li>
                            <li>L’URL exacte du contenu visé ;</li>
                            <li>Le droit invoqué et une preuve (n° d’enregistrement de marque, lien vers l’œuvre originale, date de création) ;</li>
                            <li>Une déclaration de bonne foi.</li>
                        </ul>
                        <p style="color:var(--c-text-secondary);">
                            Les avis anonymes ou manifestement incomplets ne sont pas traités.
                        </p>
                        <h3 style="color:var(--c-dark);">4. Traitement de la demande</h3>
                        <p style="color:var(--c-text-secondary);">
                            Conformément au régime canadien dit « avis et avis » (Loi sur le droit d’auteur), nous examinons chaque demande de bonne foi. Nous pouvons transmettre l’avis à la personne concernée, y répondre, retirer ou modifier le contenu, ou refuser une demande manifestement non fondée ou incomplète. Aucun retrait n’est automatique. Délai indicatif de réponse : quelques jours ouvrables. Lorsque c’est possible, nous privilégions les visuels fournis par les éditeurs (logo officiel, image de partage).
                        </p>
                        <h3 style="color:var(--c-dark);">5. Bonne foi</h3>
                        <p style="color:var(--c-text-secondary);">
                            Toute fausse déclaration ou demande abusive (par exemple pour faire taire une critique légitime) engage la responsabilité de son auteur.
                        </p>
                        <h3 style="color:var(--c-dark);">6. Nous joindre</h3>
                        <p style="color:var(--c-text-secondary);">
                            Pour toute question, passez par le <a href="{{ route('directory.takedown.create') }}" style="color:var(--c-primary);">formulaire de demande de retrait</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
