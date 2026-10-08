<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shop\Models\Order;

class OrderLookupController extends Controller
{
    public function index()
    {
        return view('shop::public.order-lookup');
    }

    public function search(Request $request)
    {
        // Anti-énumération : deux compteurs indépendants (par adresse IP ET par courriel visé, pour contrer une attaque répartie).
        $request->validate([
            'order_number' => 'required|string|max:64',
            'email' => 'required|email',
        ]);

        $ipKey = 'order-lookup:ip:' . $request->ip();
        $emailKey = 'order-lookup:email:' . sha1(mb_strtolower((string) $request->input('email')));

        if (RateLimiter::tooManyAttempts($ipKey, 10) || RateLimiter::tooManyAttempts($emailKey, 10)) {
            return back()->with('error', __('Trop de tentatives. Veuillez réessayer dans quelques minutes.'));
        }

        // Le numéro de commande (aléatoire, non devinable pour les nouvelles commandes) remplace l'identifiant séquentiel :
        // il sert de preuve de possession en plus du courriel. Réponse identique que la commande existe ou non (aucun oracle).
        $order = Order::with(['items.product'])
            ->where('order_number', trim((string) $request->input('order_number')))
            ->where('email', $request->input('email'))
            ->first();

        if (! $order) {
            RateLimiter::hit($ipKey, 300);
            RateLimiter::hit($emailKey, 300);
            return back()->with('error', __('Aucune commande trouvée avec ces informations.'));
        }

        // Succès : on ne remet à zéro QUE le compteur du courriel visé. Remettre l'IP à zéro permettrait d'alterner
        // 9 essais sur une victime et 1 sur sa propre commande pour effacer indéfiniment le compteur.
        RateLimiter::clear($emailKey);

        return view('shop::public.order-lookup', compact('order'));
    }
}
