{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Conditions de vente') . ' - ' . config('app.name'))
@section('meta_description', __('Conditions de vente de la boutique laveille.ai — produits imprimés à la demande, livraison, remboursement, LPC Québec.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Conditions de vente'), 'breadcrumbItems' => [__('Boutique'), __('Conditions de vente')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Conditions de vente') }}</h2>
                        <p style="color: #999; font-size: 13px;">
                            <strong>{{ __('Date d\'entrée en vigueur') }}&nbsp;:</strong> {{ __('8 avril 2026') }}
                        </p>

                        <div class="entry-details" style="line-height: 1.8;">

                            <p>{{ __('Ces conditions de vente régissent l\'achat de produits sur la boutique de laveille.ai. En passant une commande, vous acceptez les présentes conditions.') }}</p>

                            <h3 id="produits">{{ __('Nos produits') }}</h3>
                            <p>{{ __('Tous nos articles (t-shirts, tasses, hoodies, etc.) sont fabriqués sur demande (impression à la demande). Chaque produit est imprimé spécialement pour vous après que votre commande soit passée. Le délai de production habituel est de 2 à 5 jours ouvrables.') }}</p>

                            <h3 id="livraison">{{ __('Livraison') }}</h3>
                            <p>{{ __('Les délais de livraison sont estimés comme suit :') }}</p>
                            <ul>
                                <li>{{ __('Livraison standard au Canada : 6 à 12 jours ouvrables après production.') }}</li>
                                <li>{{ __('Livraison express au Canada : 3 à 5 jours ouvrables après production.') }}</li>
                            </ul>
                            <p>{{ __('Ces délais sont des estimations et ne sont pas garantis. Conformément à l\'article 54.4 de la Loi sur la protection du consommateur (LPC), si nous ne livrons pas le bien dans le délai convenu, vous avez le droit d\'annuler votre commande sans frais et d\'obtenir un remboursement intégral.') }}</p>

                            <h3 id="prix">{{ __('Prix et taxes') }}</h3>
                            <p>{{ __('Tous les prix sont indiqués en dollars canadiens (CAD). Les taxes applicables (TPS de 5 % et TVQ de 9,975 %) seront ajoutées pour les résidents canadiens. Les frais de livraison sont calculés lors du processus de paiement selon votre adresse de livraison.') }}</p>

                            <h3 id="remboursement">{{ __('Remboursement et annulation') }}</h3>
                            <p>{{ __('Vous avez droit à un remboursement intégral dans les cas suivants :') }}</p>
                            <ul>
                                <li>{{ __('Le délai de livraison convenu n\'est pas respecté.') }}</li>
                                <li>{{ __('Le produit reçu est défectueux ou non conforme à la description.') }}</li>
                            </ul>
                            <p>{{ __('Conformément à l\'article 54.4 de la LPC, aucuns frais d\'annulation ne peuvent être facturés. Puisque nos produits sont fabriqués sur demande, ils ne sont généralement pas remboursables sauf en cas de défaut de fabrication ou de non-conformité.') }}</p>

                            <h3 id="paiement">{{ __('Paiement') }}</h3>
                            <p>{{ __('Nous acceptons les paiements par Visa, Mastercard et American Express via Stripe. Vos transactions sont sécurisées grâce au protocole SSL. Le descripteur sur votre relevé bancaire sera MEMORA* LAVEILLE.AI.') }}</p>

                            <h3 id="contact">{{ __('Contact') }}</h3>
                            <p>{{ __('Pour toute question concernant votre commande ou ces conditions, contactez-nous à') }} <a href="mailto:info@laveille.ai">info@laveille.ai</a>.</p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
