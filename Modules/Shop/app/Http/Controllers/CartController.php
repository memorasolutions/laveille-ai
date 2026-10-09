<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\CartService;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index()
    {
        // Un ancien panier (libellé doublé, identifiant d'une autre taille) est ré-aligné avant l'affichage.
        $this->cartService->revalidatePrices();

        // Pré-remplir : profil sauvegardé → dernière commande → nom du profil
        $savedAddress = [];
        if (Auth::check()) {
            $user = Auth::user();

            // Priorité 1 : adresse sauvegardée dans le profil (consentement Loi 25)
            $savedAddress = $user->shipping_address ?? [];

            // Priorité 2 : dernière commande réussie
            if (empty($savedAddress)) {
                $lastOrder = Order::where('user_id', $user->id)
                    ->whereNotNull('shipping_address')
                    ->whereIn('status', ['paid', 'processing', 'fulfilled', 'shipped', 'delivered'])
                    ->latest()
                    ->first();
                $savedAddress = $lastOrder?->shipping_address ?? [];
            }

            // Priorité 3 : nom depuis le profil
            if (empty($savedAddress['first_name'])) {
                $parts = explode(' ', $user->name ?? '', 2);
                $savedAddress['first_name'] = $parts[0] ?? '';
                $savedAddress['last_name'] = $parts[1] ?? '';
            }
        }

        return view('shop::public.cart', [
            'content' => $this->cartService->getContent(),
            'subtotal' => $this->cartService->getSubtotal(),
            'tax' => $this->cartService->getTaxAmount(),
            'total' => $this->cartService->getTotal(),
            'itemCount' => $this->cartService->itemCount(),
            'savedAddress' => $savedAddress,
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'integer|min:1',
            'variant_label' => 'nullable|string',
            'variant_gelato_uid' => 'nullable|string',
            'size_label' => 'nullable|string',
        ]);

        $variantLabel = $request->input('variant_label');
        $gelatoUid = $request->input('variant_gelato_uid');

        if ($request->filled('size_label')) {
            $size = trim((string) $request->input('size_label'));
            // Le formulaire envoie DÉJÀ « Couleur - Taille » : ne jamais redoubler la taille. Le résolveur serveur
            // retrouve ensuite l'identifiant Gelato et le prix de la variante exacte (aucune réécriture d'identifiant ici).
            $suffix = ' - '.$size;
            if ($variantLabel === null || $variantLabel === '') {
                $variantLabel = $size;
            } elseif ($variantLabel !== $size && ! str_ends_with($variantLabel, $suffix)) {
                $variantLabel .= $suffix;
            }
        }

        try {
            $this->cartService->add(
                $request->integer('product_id'),
                $request->integer('quantity', 1),
                $variantLabel,
                $gelatoUid
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', __('Cette variante n\'est pas disponible. Choisissez une autre couleur ou taille.'));
        }

        $product = \Modules\Shop\Models\Product::find($request->integer('product_id'));
        return back()->with('success', __('Produit ajouté au panier.'))->with('cart_added', [
            'name' => $product?->name ?? __('Produit'),
            'variant' => $request->input('variant_label'),
            'price' => $product?->price ?? 0,
            'image' => $product?->images[0] ?? null,
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:shop_products,id']);

        $this->cartService->remove(
            $request->integer('product_id'),
            $request->input('variant_label')
        );

        if ($request->wantsJson()) {
            return response()->json($this->cartTotals());
        }

        return back()->with('success', __('Produit retiré du panier.'));
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateQuantity(
            $request->integer('product_id'),
            $request->integer('quantity'),
            $request->input('variant_label')
        );

        if ($request->wantsJson()) {
            return response()->json($this->cartTotals());
        }

        return back()->with('success', __('Quantité mise à jour.'));
    }

    public function updateVariant(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:shop_products,id',
            'old_variant_label' => 'required|string',
            'new_variant_label' => 'required|string',
            'new_gelato_uid' => 'nullable|string',
        ]);

        try {
            $updated = $this->cartService->updateItemVariant(
                $request->integer('product_id'),
                $request->input('old_variant_label'),
                $request->input('new_variant_label'),
                $request->input('new_gelato_uid')
            );
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'variant_unavailable'], 422);
            }

            return back()->with('error', __('Cette variante n\'est pas disponible.'));
        }

        if ($request->wantsJson()) {
            // Source de vérité = le serveur : libellé canonique, prix et identifiant RÉSOLUS (pas ceux demandés par le navigateur).
            return response()->json($this->cartTotals() + [
                'new_variant_label' => $updated['variant_label'],
                'unit_price' => (float) $updated['unit_price'],
                'gelato_variant_id' => $updated['gelato_variant_id'] ?? null,
            ]);
        }

        return back()->with('success', __('Option mise à jour.'));
    }

    private function cartTotals(?string $country = null, ?string $province = null): array
    {
        $subtotal = $this->cartService->getSubtotal();
        $country = $country ?? request()->input('country', 'CA');
        $province = $province ?? request()->input('province', 'QC');

        // Estimation du panier : base = sous-total (la livraison n'est connue qu'au checkout, qui recalcule la taxe
        // définitive sur produit + livraison + manutention). Barème par province dans shop.tax.provinces.
        $calc = app(\Modules\Shop\Services\TaxCalculator::class)->compute($country, $province, $subtotal);
        $amountOf = fn (string $code): float => (float) (collect($calc['lines'])->firstWhere('code', $code)['amount'] ?? 0);

        return [
            'success' => true,
            'subtotal' => $subtotal,
            'tps' => $amountOf('TPS'),
            'tvq' => $amountOf('TVQ'),
            'hst' => $amountOf('TVH'),
            'pst' => $amountOf('TVP'),
            'tax' => $calc['total'],
            'tax_label' => $calc['label'],
            'total' => round($subtotal + $calc['total'], 2),
            'itemCount' => $this->cartService->itemCount(),
        ];
    }
}
