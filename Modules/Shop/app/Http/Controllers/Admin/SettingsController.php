<?php

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingsController extends Controller
{
    public function index()
    {
        return view('shop::admin.settings', [
            'config' => config('shop'),
            'hasSettingsModule' => class_exists(\Modules\Settings\Models\Setting::class),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|size:3',
            'tax_tps' => 'required|numeric|min:0|max:100',
            'tax_tvq' => 'required|numeric|min:0|max:100',
            'pagination' => 'required|integer|min:1|max:100',
        ]);

        // Sauvegarder via le module Settings s'il est disponible
        if (class_exists(\Modules\Settings\Models\Setting::class)) {
            $settings = app(\Modules\Settings\Models\Setting::class);
            $settings::set('shop.currency', $request->input('currency'));
            $settings::set('shop.tax.tps', $request->input('tax_tps'));
            $settings::set('shop.tax.tvq', $request->input('tax_tvq'));
            $settings::set('shop.pagination', $request->input('pagination'));

            return back()->with('success', __('Paramètres sauvegardés.'));
        }

        return back()->with('info', __('Modifiez les variables SHOP_* dans le fichier .env pour changer les paramètres.'));
    }
}
