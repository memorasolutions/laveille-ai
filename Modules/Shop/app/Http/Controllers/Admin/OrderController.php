<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\GelatoService;
use Modules\Shop\Services\StripeService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        $orders = $query->paginate(20);
        $statuses = ['pending', 'paid', 'processing', 'fulfilled', 'shipped', 'delivered', 'cancelled', 'refunded'];

        return view('shop::admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user');

        return view('shop::admin.orders.show', compact('order'));
    }

    /**
     * Annulation admin d'une commande : stoppe le physique (Gelato) AVANT de rendre l'argent (Stripe).
     * Les appels externes ne sont pas transactionnels : aucun DB::transaction, un seul update() de statut à la fin.
     * Tout non-succès externe laisse le statut INCHANGÉ (rien n'est marqué annulé/remboursé sans preuve).
     */
    public function cancel(Order $order, GelatoService $gelato, StripeService $stripe)
    {
        if (in_array($order->status, ['shipped', 'delivered', 'refunded', 'fulfilled'], true)) {
            return back()->with('error', __('Impossible d\'annuler une commande déjà expédiée, livrée, traitée ou remboursée.'));
        }

        // Soumission Gelato en cours ou d'issue incertaine : une commande a peut-être été créée chez Gelato, on ne peut
        // ni l'annuler ni prouver son absence. Bloquer plutôt que rembourser un colis possiblement imprimé.
        if (in_array($order->gelato_submit_state, ['submitting', 'unknown'], true)) {
            return back()->with('error', __('L\'état de l\'envoi à Gelato est incertain ; résous-le d\'abord (réconciliation) avant d\'annuler cette commande.'));
        }

        // Étape b : Gelato d'abord. Fail-closed : dès qu'une commande Gelato EXISTE (gelato_order_id non vide), on EXIGE
        // son annulation confirmée avant tout remboursement, QUEL QUE SOIT submit_state. Aujourd'hui l'id et l'état
        // 'submitted' sont posés ensemble (atomique), mais on ne dépend pas de cet invariant : un id présent dans un état
        // futur imprévu ne doit jamais laisser rembourser un colis possiblement imprimé. Les états incertains
        // submitting/unknown sont déjà bloqués plus haut (aucun id fiable à annuler).
        if (! empty($order->gelato_order_id)) {
            $result = $gelato->cancelOrder($order->gelato_order_id);
            if ($result['status'] !== 'cancelled') {
                Log::warning('Annulation admin bloquée : Gelato n\'a pas confirmé l\'annulation', [
                    'order' => $order->id, 'gelato_order_id' => $order->gelato_order_id, 'result' => $result,
                ]);

                return back()->with('error', __('Gelato ne peut plus annuler cette commande (probablement déjà en production) ; elle sera expédiée. Gère le remboursement manuellement si nécessaire.'));
            }
        }

        // Étape c : remboursement si la commande a été payée.
        if (! empty($order->stripe_payment_intent_id)) {
            $refund = $stripe->refund($order->stripe_payment_intent_id, null, 'refund_order_'.$order->id);
            if (! $refund['ok']) {
                Log::error('Annulation admin bloquée : remboursement Stripe échoué', [
                    'order' => $order->id, 'payment_intent' => $order->stripe_payment_intent_id, 'error' => $refund['error'],
                ]);

                return back()->with('error', __('Le remboursement Stripe a échoué, la commande n\'a pas été annulée. Réessaie ou vérifie Stripe.'));
            }
            $order->update(['status' => 'refunded']);

            return back()->with('success', __('Commande annulée et remboursée.'));
        }

        // Étape d : jamais payée, rien à rembourser.
        $order->update(['status' => 'cancelled']);

        return back()->with('success', __('Commande annulée.'));
    }
}
