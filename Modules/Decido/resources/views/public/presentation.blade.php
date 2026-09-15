{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Page PUBLIQUE de présentation de Décido, servie aux visiteurs non connectés par
     PollManageController::index(). Un connecté voit son tableau de bord (decido::manage.index).

     Motif (mesuré le 2026-09-15) : /decido et /decido/creer répondaient TOUS DEUX 302 vers /login.
     Un visiteur ne voyait jamais à quoi sert l'outil - il recevait la friction du compte sans
     l'argumentaire qui la justifie, ce qui rendait l'outil inannonçable sur les réseaux.

     Cette page est volontairement INDEXABLE (pas de page_noindex) : c'est une page d'acquisition.
     Les pages de VOTE, elles, restent noindex - elles exposent des pseudonymes et des choix. --}}
@extends(fronttheme_layout())

@section('title', 'Décido - Sondages collectifs · ' . config('app.name'))
@section('meta_description', "Décido permet de trouver une date de rencontre qui convient à tout le monde, ou de faire trancher un choix par un groupe. Répondre ne demande aucun compte.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Décido - Sondages collectifs', 'breadcrumbItems' => [__('Outils'), 'Décido - Sondages collectifs']])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1>Décido, l'outil de sondage collectif de La veille de Stef</h1>
        <p class="mb-4">
            Avec Décido, tu trouves une date de rencontre qui convient à tout le monde, sans la
            douzaine de courriels habituels. Tu peux aussi proposer des options et laisser ton
            groupe trancher.
        </p>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h4">Sondage de dates</h2>
                        <p class="mb-0">
                            Tu proposes des plages horaires et chaque personne indique oui,
                            peut-être ou non pour chaque créneau. Une grille rassemble les réponses
                            et fait ressortir le meilleur moment.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h4">Sondage classique</h2>
                        <p class="mb-0">
                            Tu proposes des options et ton groupe choisit. Tu décides si chaque
                            personne retient une seule option, ou si elle peut en approuver
                            plusieurs.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-4">Ce qui rend Décido différent</h2>
        <ul>
            <li>Les personnes qui répondent n'ont besoin d'aucun compte : le lien suffit.</li>
            <li>
                Chacune reçoit un lien privé qui lui est propre, donc elle modifie sa réponse plus
                tard sans jamais se tromper de ligne ni écraser celle d'une autre.
            </li>
            <li>Les résultats s'exportent en CSV, et la date retenue s'ajoute au calendrier en un clic.</li>
            <li>
                Les sondages ne restent pas en ligne éternellement : ils expirent d'eux-mêmes, et un
                courriel te prévient 14 jours avant.
            </li>
        </ul>

        <h2 class="mt-4">Pourquoi créer un compte pour lancer un sondage</h2>
        <ul>
            <li>Tu retrouves tous tes sondages au même endroit.</li>
            <li>Tu vois qui n'a pas encore répondu, et tu peux relancer ces personnes.</li>
            <li>Tu prolonges un sondage qui approche de son échéance.</li>
        </ul>

        <div class="mt-4">
            <a href="{{ route('decido.create') }}" class="ct-btn ct-btn-primary">Créer un sondage</a>
            <p class="mt-3">
                <small>La création demande un compte gratuit. Répondre à un sondage n'en demande aucun.</small>
            </p>
        </div>
    </div>
</section>
@endsection
