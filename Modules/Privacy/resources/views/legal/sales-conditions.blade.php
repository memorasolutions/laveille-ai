{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Conditions de vente') . ' - ' . config('app.name'))
@section('meta_description', __('Conditions générales de vente de la boutique laveille.ai — produits imprimés à la demande, livraison, remboursement, garantie légale, LPC Québec.'))

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
                        <h2>{{ __('Conditions générales de vente') }}</h2>
                        <p style="color: #999; font-size: 13px;">
                            <strong>{{ __('Date d\'entrée en vigueur') }}&nbsp;:</strong> {{ __('29 mai 2026') }}
                        </p>

                        <div class="entry-details" style="line-height: 1.8;">

                            <p>{{ __('En naviguant sur laveille.ai et en passant une commande, vous acceptez les présentes conditions générales de vente. Nous vous invitons à les lire attentivement.') }}</p>

                            {{-- 1. Identification du commerçant --}}
                            <h3 id="identification">{{ __('1. Identification du commerçant') }}</h3>
                            <p>{{ __('Le site laveille.ai est exploité par :') }}</p>
                            <ul>
                                <li><strong>{{ __('Entreprise') }} :</strong> MEMORA solutions (incorporation)</li>
                                <li><strong>{{ __('Site') }} :</strong> laveille.ai</li>
                                <li><strong>{{ __('Adresse') }} :</strong> 1501, rue Saint-Benoit, L'Ancienne-Lorette (Québec) G2E 1P2, Canada</li>
                                <li><strong>{{ __('Téléphone') }} :</strong> 418-800-6656 / {{ __('sans frais') }} : 1-833-363-6672</li>
                                <li><strong>{{ __('Courriel') }} :</strong> <a href="mailto:politiques@memora.ca">politiques@memora.ca</a></li>
                                <li><strong>NEQ :</strong> 1170260492</li>
                                <li><strong>TPS :</strong> 839145984 RT0001</li>
                                <li><strong>TVQ :</strong> 1221788059 TQ0001</li>
                            </ul>
                            <p style="font-size: 13px; color: #64748b;">{{ __('Ces informations sont fournies conformément à l\'article 54.4 de la LPC et à l\'article 442 de la Loi sur la taxe de vente du Québec.') }}</p>

                            {{-- 2. Produits --}}
                            <h3 id="produits">{{ __('2. Produits') }}</h3>
                            <p>{{ __('Les produits offerts sur laveille.ai sont fabriqués selon le modèle d\'impression à la demande (POD). Chaque article est produit individuellement après réception de votre commande par notre partenaire de production tiers.') }}</p>
                            <ul>
                                <li>{{ __('Délai de production : 2 à 5 jours ouvrables.') }}</li>
                                <li>{{ __('Les couleurs affichées sur votre écran peuvent légèrement varier par rapport au produit final. Ces variations sont normales et ne constituent pas un défaut de fabrication.') }}</li>
                            </ul>

                            {{-- 3. Livraison --}}
                            <h3 id="livraison">{{ __('3. Livraison') }}</h3>
                            <p>{{ __('Nous livrons nos produits dans plus de 200 pays. La disponibilité de la livraison pour votre adresse est vérifiée au moment de finaliser votre commande.') }}</p>
                            <table class="table" style="margin-bottom: 16px;">
                                <thead><tr><th>{{ __('Zone') }}</th><th>{{ __('Délai estimé (jours ouvrables)') }}</th></tr></thead>
                                <tbody>
                                    <tr><td>{{ __('Canada') }}</td><td>8 – 20</td></tr>
                                    <tr><td>{{ __('États-Unis') }}</td><td>7 – 18</td></tr>
                                    <tr><td>{{ __('Europe') }}</td><td>10 – 25</td></tr>
                                    <tr><td>{{ __('International') }}</td><td>15 – 35</td></tr>
                                </tbody>
                            </table>
                            <p>{{ __('Ces délais incluent la production et l\'expédition. Le délai précis est communiqué au moment du paiement. Conformément à l\'article 54.4 de la LPC, si le délai de livraison convenu est dépassé, vous pouvez annuler votre commande sans frais.') }}</p>

                            {{-- 4. Prix et taxes --}}
                            <h3 id="prix">{{ __('4. Prix et taxes') }}</h3>
                            <p>{{ __('Tous les prix sont en dollars canadiens (CAD). La TPS (5 %) et la TVQ (9,975 %) sont ajoutées aux commandes livrées au Canada. Les frais de livraison sont calculés et affichés au checkout selon votre adresse.') }}</p>

                            {{-- 5. Droit de résolution --}}
                            <h3 id="resolution">{{ __('5. Droit de résolution') }}</h3>
                            <p>{{ __('Conformément aux articles 54.1 à 54.16 de la Loi sur la protection du consommateur (LPC), vous bénéficiez d\'un droit de résolution dans des cas précis. Si nous ne remplissons pas nos obligations — notamment en omettant de vous remettre un exemplaire du contrat dans les 15 jours suivant sa conclusion (article 54.7 LPC), en ne respectant pas nos devoirs d\'information (article 54.4 LPC) ou en livrant un produit non conforme (article 54.6 LPC) — vous pouvez résoudre le contrat dans les 7 jours suivant la réception de l\'exemplaire du contrat, ou dans les 30 jours si cet exemplaire ne vous a pas été transmis à temps (article 54.8 LPC). De plus, si nous ne livrons pas votre commande dans les 30 jours suivant la date convenue ou, à défaut, suivant la conclusion du contrat, vous pouvez également résoudre celui-ci (article 54.9 LPC).') }}</p>
                            <p>{{ __('La LPC ne prévoit aucun droit général d\'annulation pour simple changement d\'avis. Toutefois, le fait qu\'un produit soit personnalisé ou fabriqué selon vos spécifications ne prive jamais d\'effet les droits de résolution prévus par la loi en cas de manquement du commerçant à ses obligations légales.') }}</p>

                            {{-- 6. Garantie légale --}}
                            <h3 id="garantie">{{ __('6. Garantie légale') }}</h3>
                            <p>{{ __('Tous les biens vendus sur laveille.ai sont couverts par la garantie légale de qualité (articles 37 et 38 LPC). Le bien doit servir à l\'usage auquel il est normalement destiné, être durable et utilisable pendant une durée raisonnable. La garantie légale ne peut être exclue ni limitée.') }}</p>

                            {{-- 7. Remboursement et annulation --}}
                            <h3 id="remboursement">{{ __('7. Remboursement et annulation') }}</h3>
                            <ul>
                                <li><strong>{{ __('Dépassement du délai de livraison') }} :</strong> {{ __('remboursement intégral.') }}</li>
                                <li><strong>{{ __('Produit défectueux ou non conforme') }} :</strong> {{ __('remplacement ou remboursement au titre de la garantie légale (articles 37 et 38 LPC), sur présentation de photos claires du problème dans un délai raisonnable suivant la réception.') }}</li>
                                <li><strong>{{ __('Résolution conforme à la LPC') }} :</strong> {{ __('remboursement intégral lorsque vous exercez un droit de résolution reconnu par la loi (articles 54.8 et 54.9 LPC).') }}</li>
                                <li><strong>{{ __('Changement d\'avis') }} :</strong> {{ __('un produit fabriqué sur commande (POD) n\'est pas repris ni remboursé pour un simple changement d\'avis, sauf dans la mesure où la loi l\'exige; cette pratique ne limite jamais la garantie légale ni les droits de résolution prévus par la LPC.') }}</li>
                            </ul>
                            <p>{{ __('Aucuns frais d\'annulation ne seront facturés pour une annulation effectuée conformément à la loi.') }}</p>

                            {{-- 8. Responsabilité --}}
                            <h3 id="responsabilite">{{ __('8. Responsabilité') }}</h3>
                            <p>{{ __('À l\'égard des consommateurs, rien dans les présentes conditions ne limite la responsabilité du vendeur ni les recours que vous confèrent la Loi sur la protection du consommateur et le Code civil du Québec. La garantie légale de qualité et de durabilité prévue aux articles 37 et 38 de la LPC s\'applique pleinement et ne peut être exclue. Conformément à l\'article 10 de la LPC, le vendeur ne peut se dégager de sa responsabilité pour son propre fait ou celui de ses préposés ou mandataires.') }}</p>
                            <p>{{ __('Dans les seuls cas où la relation contractuelle n\'est pas visée par la LPC (notamment hors contexte de consommation), la responsabilité du vendeur est limitée au prix payé pour les biens ou services concernés, et les dommages indirects, spéciaux, accessoires ou consécutifs sont exclus. La fabrication est assurée par un partenaire tiers. En tout état de cause et conformément à l\'article 1474 du Code civil du Québec, le vendeur n\'exclut jamais sa responsabilité en cas de préjudice corporel ou moral, ni en cas de faute lourde ou intentionnelle. Toute clause abusive au sens de l\'article 1437 du Code civil du Québec est réputée non écrite.') }}</p>

                            {{-- 9. Force majeure --}}
                            <h3 id="force-majeure">{{ __('9. Force majeure') }}</h3>
                            <p>{{ __('Le vendeur n\'est pas responsable des retards ou défauts d\'exécution causés par des événements indépendants de sa volonté : pandémies, grèves, catastrophes naturelles, défaillances de transporteurs, cyberattaques ou toute autre cause échappant à son contrôle (article 1470 CCQ).') }}</p>

                            {{-- 10. Clients hors Canada --}}
                            <h3 id="hors-canada">{{ __('10. Clients hors Canada') }}</h3>
                            <ul>
                                <li>{{ __('Le droit de rétractation de 14 jours prévu par le droit européen (article L.221-28 du code de la consommation) ne s\'applique pas aux produits fabriqués à la demande.') }}</li>
                                <li>{{ __('La garantie légale de conformité reste applicable selon les lois locales.') }}</li>
                                <li>{{ __('Nous ne vendons pas de produits aux mineurs de moins de 18 ans.') }}</li>
                            </ul>

                            {{-- 11. Droit de refus --}}
                            <h3 id="droit-refus">{{ __('11. Droit de refus') }}</h3>
                            <p>{{ __('Le vendeur se réserve le droit de refuser ou d\'annuler toute commande en cas de suspicion de fraude, d\'abus ou de violation des présentes conditions.') }}</p>

                            {{-- 12. Rétrofacturation --}}
                            <h3 id="retrofacturation">{{ __('12. Rétrofacturation') }}</h3>
                            <p>{{ __('Vous avez le droit de demander une rétrofacturation auprès de l\'émetteur de votre carte de crédit (articles 54.14 à 54.16 LPC). Nous vous encourageons toutefois à nous contacter d\'abord pour résoudre tout problème.') }}</p>

                            {{-- 13. Paiement et sécurité --}}
                            <h3 id="paiement">{{ __('13. Paiement et sécurité') }}</h3>
                            <p>{{ __('Les paiements sont traités via Stripe, plateforme certifiée PCI-DSS niveau 1. Toutes les communications sont sécurisées par le protocole SSL/TLS.') }}</p>
                            <p>{{ __('Modes de paiement acceptés : Visa, Mastercard, American Express, Apple Pay, Google Pay.') }}</p>
                            <p>{{ __('Le descripteur sur votre relevé bancaire sera : MEMORA* LAVEILLE.AI') }}</p>

                            {{-- 14. Propriété intellectuelle --}}
                            <h3 id="propriete-intellectuelle">{{ __('14. Propriété intellectuelle') }}</h3>
                            <p>{{ __('Tous les designs, textes, images et contenus présents sur laveille.ai sont la propriété de MEMORA solutions ou de ses partenaires et sont protégés par les lois sur le droit d\'auteur.') }}</p>
                            <p>{{ __('Le client est responsable de tout contenu qu\'il soumet et garantit détenir les droits nécessaires sur ce contenu.') }}</p>

                            {{-- 15. Confidentialité --}}
                            <h3 id="confidentialite">{{ __('15. Confidentialité') }}</h3>
                            <p>{{ __('Vos renseignements personnels sont protégés conformément à la Loi 25 du Québec et à la LPRPDE. Consultez notre') }} <a href="{{ route('legal.privacy') }}">{{ __('politique de confidentialité') }}</a> {{ __('pour plus de détails.') }}</p>

                            {{-- 16. Communications --}}
                            <h3 id="communications">{{ __('16. Communications') }}</h3>
                            <p>{{ __('Le consentement est requis pour recevoir des communications commerciales (LCAP). Vous pouvez vous désabonner à tout moment en un clic via le lien prévu dans chaque courriel.') }}</p>

                            {{-- 17. Juridiction --}}
                            <h3 id="juridiction">{{ __('17. Juridiction') }}</h3>
                            <p>{{ __('Les présentes conditions sont régies par les lois du Québec et les lois fédérales applicables au Canada. Tout litige sera soumis à la compétence exclusive des tribunaux du district judiciaire de Québec.') }}</p>

                            {{-- 18. Modifications --}}
                            <h3 id="modifications">{{ __('18. Modifications') }}</h3>
                            <p>{{ __('Le vendeur peut modifier les présentes conditions à tout moment. La version en vigueur au moment de votre commande est celle qui s\'applique à votre transaction.') }}</p>

                            {{-- 19. Contact --}}
                            <h3 id="contact">{{ __('19. Contact') }}</h3>
                            <p>{{ __('Pour toute question concernant ces conditions ou une commande :') }}</p>
                            <ul>
                                <li><strong>{{ __('Courriel') }} :</strong> <a href="mailto:politiques@memora.ca">politiques@memora.ca</a></li>
                                <li><strong>{{ __('Téléphone') }} :</strong> 418-800-6656</li>
                                <li><strong>{{ __('Sans frais') }} :</strong> 1-833-363-6672</li>
                            </ul>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
