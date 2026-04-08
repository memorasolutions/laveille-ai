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

                            <p>{{ __('En passant commande sur laveille.ai, vous acceptez les présentes conditions.') }}</p>

                            <h3 id="produits">{{ __('Produits') }}</h3>
                            <p>{{ __('Tous nos produits sont imprimés à la demande via notre partenaire Gelato. La production prend généralement de 2 à 5 jours ouvrables. Les légères variations de couleur entre ce que vous voyez à l\'écran et le produit final sont normales et ne constituent pas un défaut.') }}</p>

                            <h3 id="livraison">{{ __('Livraison') }}</h3>
                            <p>{{ __('Les délais ci-dessous incluent la production et l\'expédition. Ils sont donnés à titre indicatif :') }}</p>
                            <table class="table" style="margin-bottom: 16px;">
                                <thead><tr><th>{{ __('Zone') }}</th><th>{{ __('Délai estimé (jours ouvrables)') }}</th></tr></thead>
                                <tbody>
                                    <tr><td>{{ __('Canada') }}</td><td>8 – 20</td></tr>
                                    <tr><td>{{ __('États-Unis') }}</td><td>7 – 18</td></tr>
                                    <tr><td>{{ __('Europe') }}</td><td>10 – 25</td></tr>
                                    <tr><td>{{ __('International') }}</td><td>15 – 35</td></tr>
                                </tbody>
                            </table>
                            <p>{{ __('Le délai précis est communiqué au moment du paiement. Conformément à l\'article 54.4 de la Loi sur la protection du consommateur (LPC) du Québec, si le délai de livraison convenu est dépassé, vous pouvez annuler votre commande sans frais.') }}</p>

                            <h3 id="prix">{{ __('Prix et taxes') }}</h3>
                            <p>{{ __('Tous les prix sont en dollars canadiens (CAD). Les taxes applicables au Canada (TPS 5 % + TVQ 9,975 %) sont ajoutées au moment du paiement. Les frais d\'expédition sont calculés et affichés au checkout selon votre adresse de livraison.') }}</p>

                            <h3 id="remboursement">{{ __('Remboursement et annulation') }}</h3>
                            <ul>
                                <li>{{ __('Si le délai de livraison est dépassé, vous avez droit à un remboursement intégral.') }}</li>
                                <li>{{ __('En cas de défaut de fabrication avéré, nous offrons soit un remplacement, soit un remboursement, sous réserve de la réception d\'une photo du produit dans les 7 jours suivant la réception.') }}</li>
                                <li>{{ __('Les demandes de remboursement fondées sur un simple changement d\'avis ne sont pas acceptées, car les produits sont fabriqués à la demande.') }}</li>
                            </ul>
                            <p>{{ __('Aucuns frais ne sont appliqués en cas d\'annulation conforme aux conditions ci-dessus.') }}</p>

                            <h3 id="responsabilite">{{ __('Responsabilité') }}</h3>
                            <p>{{ __('Notre responsabilité est limitée au montant que vous avez payé pour le produit concerné. Nous ne pouvons être tenus responsables des dommages indirects, accessoires ou consécutifs. La fabrication est assurée par un partenaire tiers, conformément aux normes de qualité en vigueur.') }}</p>

                            <h3 id="force-majeure">{{ __('Force majeure') }}</h3>
                            <p>{{ __('Nous ne sommes pas responsables des retards ou défauts d\'exécution causés par des événements indépendants de notre volonté, notamment : pandémies, grèves, catastrophes naturelles, perturbations liées aux transporteurs ou cyberattaques.') }}</p>

                            <h3 id="hors-canada">{{ __('Clients hors Canada') }}</h3>
                            <p>{{ __('Le droit de rétractation de 14 jours prévu par la législation européenne (art. L.221-28 du code de la consommation) ne s\'applique pas aux biens fabriqués à la demande ou personnalisés. Toutefois, la garantie légale de conformité reste applicable selon les lois locales.') }}</p>

                            <h3 id="droit-refus">{{ __('Droit de refus') }}</h3>
                            <p>{{ __('Nous nous réservons le droit de refuser ou d\'annuler toute commande en cas de suspicion de fraude, d\'activité abusive ou de violation de nos conditions.') }}</p>

                            <h3 id="paiement">{{ __('Paiement') }}</h3>
                            <p>{{ __('Les paiements sont traités de manière sécurisée via Stripe (chiffrement SSL). Nous acceptons Visa, Mastercard et American Express. Le descripteur sur votre relevé bancaire sera MEMORA* LAVEILLE.AI.') }}</p>

                            <h3 id="juridiction">{{ __('Juridiction') }}</h3>
                            <p>{{ __('Les présentes conditions sont régies par les lois de la province de Québec. Tout litige relèvera de la compétence exclusive des tribunaux du district de Montréal.') }}</p>

                            <h3 id="contact">{{ __('Contact') }}</h3>
                            <p>{{ __('Pour toute question concernant ces conditions ou une commande, veuillez écrire à') }} <a href="mailto:info@laveille.ai">info@laveille.ai</a>.</p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
