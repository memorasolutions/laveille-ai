<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller;
use Modules\Shop\Gelato\OrderItemRouter;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderItem;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\CanadianProvince;
use Modules\Shop\Services\CartService;
use Modules\Shop\Services\GelatoService;
use Modules\Shop\Services\StripeService;
use Modules\Shop\Events\ShopOrderCreated;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected StripeService $stripeService,
    ) {}

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'shipping_cost' => 'required|numeric|min:1|max:100',
            'shipping_method_uid' => 'nullable|string|max:255',
            'newsletter' => 'sometimes|boolean',
            'save_address' => 'sometimes|boolean',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.address_line1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string|size:2',
        ]);

        $cart = $this->cartService->getCart();
        if (! $cart) {
            return back()->with('error', __('Votre panier est vide.'));
        }

        // Verrou PAR PANIER : deux requêtes simultanées (double clic, deux onglets) ne créent jamais deux commandes.
        // Le panier est relu APRÈS l'obtention du verrou : la 2e requête voit le panier déjà vidé par la 1re.
        $lock = Cache::lock('shop:checkout:cart:'.$cart->id, 120);
        if (! $lock->get()) {
            return back()->with('error', __('Votre commande est déjà en cours de traitement. Patientez un instant.'));
        }

        try {
            return $this->placeOrder($request);
        } finally {
            $lock->release();
        }
    }

    private function placeOrder(Request $request)
    {
        // INSTANTANÉ du panier, pris sous le verrou : articles, sous-total, taxes, commande et session Stripe en dérivent
        // TOUS. Une mutation du panier (changement de quantité depuis un autre onglet) après cette ligne ne peut donc plus
        // désaccorder la commande et le montant encaissé : le verrou exclut un autre checkout, pas CartService.
        $this->cartService->revalidatePrices();
        $cartItems = $this->cartService->getContent();

        if (empty($cartItems)) {
            return back()->with('error', __('Votre panier est vide.'));
        }

        // Disponibilité : produit retiré/dépublié (y compris côté Gelato) ou variante inexistante = jamais encaissé.
        foreach ($cartItems as $cartItem) {
            if ($this->cartService->unavailableReason($cartItem) !== null) {
                return back()->with('error', __('Un article de votre panier n\'est plus disponible. Retirez-le puis réessayez.'));
            }
        }

        // On n'encaisse jamais un article que Gelato ne pourra pas honorer (catalogue = mapping store,
        // éditeur = fichier d'impression approuvé). Résolveur unique, indépendant du drapeau « zéro erreur ».
        $router = app(OrderItemRouter::class);
        $frozenStoreVariants = []; // identifiant catalogue résolu MAINTENANT, figé sur l'article (le mapping peut changer avant/après le paiement)
        foreach ($cartItems as $cartKey => $cartItem) {
            $probe = (new OrderItem)->forceFill([
                'product_id' => $cartItem['product_id'],
                'quantity' => $cartItem['quantity'] ?? 1,
                'gelato_variant_id' => $cartItem['gelato_variant_id'] ?? null,
            ]);
            $probe->id = 0;
            $probe->setRelation('product', Product::find($cartItem['product_id']));
            if ($router->validate($probe) !== null) {
                return back()->with('error', __('Un article de votre panier n\'est pas disponible à la commande pour le moment.'));
            }
            $frozenStoreVariants[$cartKey] = $router->frozenStoreVariantId($probe);
        }

        $subtotal = $this->cartService->subtotalOf($cartItems);
        $country = $request->input('shipping_address.country', 'CA');
        $shippingAddress = $request->input('shipping_address');

        // Province AUTORITATIVE : dérivée du code postal, JAMAIS du champ « state » déclaré (un client de Laval pouvait
        // forcer state=AB et payer 5 % au lieu de 14,975 %). La valeur déclarée est écrasée par celle du code postal,
        // pour que la taxe, le devis de livraison et l'adresse envoyée à Gelato racontent la même province.
        // M3 (DÉCISION LÉGALE DU FONDATEUR, non tranchée ici) : le barème complet reste à décider avant ouverture publique
        // (provinces à TVH ON 13 % / NB-NL-NS-PE 15 % actuellement facturées 5 %, taxabilité de la livraison).
        $province = '';
        if ($country === 'CA') {
            $province = CanadianProvince::resolve($shippingAddress['postal_code'] ?? '', $shippingAddress['state'] ?? '');
            if ($province === null) {
                return back()->withInput()->withErrors(['shipping_address.postal_code' => __('Le code postal canadien est invalide.')]);
            }
            $shippingAddress['state'] = $province;
            $request->merge(['shipping_address' => $shippingAddress]);
        }

        $taxAmount = match (true) {
            $country === 'CA' && $province === 'QC' => $this->cartService->taxAmountOf($subtotal),
            $country === 'CA' => $this->cartService->tpsOf($subtotal),
            default => 0,
        };
        // Frais de livraison : JAMAIS crus sur parole. Recalculés côté serveur (devis Gelato) ; la valeur du client
        // doit correspondre à l'un des modes de livraison réellement offerts, sinon la commande est refusée.
        $clientShipping = (float) $request->input('shipping_cost', 0);
        $quote = app(GelatoService::class)->getQuoteFromCart($cartItems, $request->input('shipping_address'), $request->input('email'));
        $offered = array_map(fn ($m) => (float) $m['price'], $quote['methods'] ?? []);

        if ($offered === []) {
            return back()->withInput()->withErrors(['shipping_cost' => __('Les frais de livraison n\'ont pas pu être confirmés. Veuillez réessayer dans un instant.')]);
        }

        // Mode de livraison : celui que le client a CHOISI (uid) doit exister dans le devis ET correspondre au prix payé.
        // Sans uid (ancien formulaire), le premier mode offert à ce prix. L'uid retenu est transmis à Gelato.
        $shippingCost = null;
        $shippingMethodUid = null;
        $requestedUid = trim((string) $request->input('shipping_method_uid', ''));
        foreach ($quote['methods'] ?? [] as $method) {
            $price = (float) $method['price'];
            if (abs($price - $clientShipping) >= 0.01) {
                continue;
            }
            if ($requestedUid !== '' && ($method['uid'] ?? '') !== $requestedUid) {
                continue;
            }
            $shippingCost = round($price, 2); // valeur SERVEUR, pas celle du client
            $shippingMethodUid = ($method['uid'] ?? '') !== '' ? $method['uid'] : null;
            break;
        }

        if ($shippingCost === null || $shippingCost <= 0) {
            return back()->withInput()->withErrors(['shipping_cost' => __('Les frais de livraison ont changé. Veuillez recalculer la livraison avec votre code postal.')]);
        }

        $total = round($subtotal + $taxAmount + $shippingCost, 2);

        // Créer la commande
        $order = Order::create([
            'user_id' => auth()->id(),
            'email' => $request->input('email'),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'shipping_method_uid' => $shippingMethodUid,
            'total' => $total,
            'shipping_address' => $request->input('shipping_address'),
        ]);

        // Créer les items de commande
        foreach ($cartItems as $itemKey => $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'variant_label' => $item['variant_label'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'gelato_variant_id' => $item['gelato_variant_id'] ?? null,
                'gelato_store_product_variant_id' => $frozenStoreVariants[$itemKey] ?? null,
            ]);
        }

        // Capacité de consultation de CETTE commande pour CE navigateur (confirmation/paiement), jamais pour un autre.
        session()->push('shop.owned_orders', $order->id);
        session()->put('shop.pay_order_id', $order->id); // conservé pour les sessions déjà en vol; la clé par commande ci-dessous fait foi

        event(new ShopOrderCreated($order));

        // Inscription newsletter si coché (LCAP conforme — opt-in explicite)
        if ($request->input('newsletter') && class_exists(\Modules\Newsletter\Models\Subscriber::class)) {
            \Modules\Newsletter\Models\Subscriber::updateOrCreate(
                ['email' => $request->input('email')],
                ['status' => 'subscribed', 'subscribed_at' => now()]
            );
        }

        // Sauvegarder l'adresse dans le profil si le client a coché (Loi 25 — opt-in)
        if ($request->input('save_address') && auth()->check()) {
            auth()->user()->update(['shipping_address' => $request->input('shipping_address')]);
        }

        // Créer session Stripe Checkout (mode embedded)
        $returnUrl = route('shop.confirmation', $order) . '?session_id={CHECKOUT_SESSION_ID}';
        $taxLabel = ($country === 'CA' && $province === 'QC') ? 'TPS + TVQ' : 'TPS';
        $checkout = $this->stripeService->createCheckoutSession(
            $cartItems,
            $returnUrl,
            $request->input('email'),
            $taxAmount,
            $shippingCost,
            $taxLabel
        );

        if (! $checkout) {
            $order->update(['status' => 'cancelled', 'notes' => 'Stripe checkout session creation failed']);
            return back()->with('error', __('Erreur lors de la création du paiement. Veuillez réessayer.'));
        }

        // Sauvegarder le session ID Stripe
        $order->update(['stripe_session_id' => $checkout['session_id']]);

        // Vider le panier
        $this->cartService->clear();

        // Secret de paiement rangé PAR COMMANDE (et non en flash) : un rafraîchissement de la page de paiement ne le perd plus,
        // et un 2e onglet (autre commande) n'écrase plus celui du premier.
        session()->put('shop.pay_secrets.'.$order->id, $checkout['client_secret']);

        return redirect()->route('shop.checkout.pay', $order);
    }

    public function pay(Order $order)
    {
        // Le secret de paiement appartient à la commande créée dans CETTE session : jamais à une autre (énumération d'id).
        $clientSecret = session('shop.pay_secrets.'.$order->id);

        if (! $clientSecret && ($legacy = session('stripe_client_secret'))) {
            // Session ouverte avant ce correctif (secret en flash + une seule commande mémorisée).
            abort_unless((int) session('shop.pay_order_id') === (int) $order->id, 404);
            $clientSecret = $legacy;
        }

        if (! $clientSecret) {
            return redirect()->route('shop.cart')->with('error', __('Session de paiement expirée. Veuillez réessayer.'));
        }

        return view('shop::public.checkout-pay', [
            'order' => $order,
            'clientSecret' => $clientSecret,
            'stripeKey' => config('shop.stripe.publishable_key'),
        ]);
    }

    /**
     * Confirmation de commande : données personnelles (courriel, achats, montant, adresse) = Loi 25.
     * Accessible seulement au propriétaire connecté, au porteur du session_id Stripe de CETTE commande (jeton
     * imprévisible renvoyé par Stripe au retour du paiement) ou au navigateur qui l'a créée. Sinon 404 (jamais 403 :
     * on ne confirme pas l'existence d'une commande).
     */
    public function success(Request $request, Order $order)
    {
        abort_unless($this->mayViewOrder($request, $order), 404);
        session()->forget('shop.pay_secrets.'.$order->id); // le paiement est terminé : le secret n'a plus d'utilité

        return view('shop::public.confirmation', compact('order'));
    }

    private function mayViewOrder(Request $request, Order $order): bool
    {
        if (auth()->check() && $order->user_id !== null && (int) $order->user_id === (int) auth()->id()) {
            return true;
        }

        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId !== '' && ! empty($order->stripe_session_id) && hash_equals((string) $order->stripe_session_id, $sessionId)) {
            return true;
        }

        return in_array($order->id, (array) session('shop.owned_orders', []), true);
    }
}
