<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::themes.gosass.layouts.app')

@section('title', 'Paiement réussi - '.config('app.name'))

@section('content')
<section>
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <div class="cs_white_bg cs_radius_15 p-5">
                    <i class="fa-solid fa-circle-check fa-4x cs_accent_color mb-4"></i>
                    <h1 class="cs_fs_50 cs_semibold mb-3">Merci !</h1>
                    <p class="cs_fs_16 mb-4">
                        Votre abonnement a été activé avec succès. Vous pouvez maintenant profiter de toutes les fonctionnalités de votre plan.
                    </p>
                    <a href="{{ route('user.subscription') }}" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                        <span>Voir mon abonnement</span>
                        <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>
@endsection
