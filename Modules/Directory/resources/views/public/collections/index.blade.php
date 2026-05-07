<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::layouts.master')

@section('title', 'Collections d\'outils')
@section('meta_description', 'Découvrez les collections d\'outils IA et tech créées par la communauté de La veille.')

@section('content')
<section class="wpo-blog-pg-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="wpo-section-title" style="margin-bottom: 40px;">
                    <h2 style="color: var(--c-primary, #064E5A);">Collections de la communauté</h2>
                </div>
            </div>
        </div>

        @if($collections->count())
            <div class="row">
                @foreach($collections as $collection)
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                        <div class="entry-details" style="border: 1px solid #e8e8e8; border-radius: 8px; padding: 25px; height: 100%; background: #fff;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                <h4 style="margin: 0; font-size: 18px; color: #333; font-weight: 600;">
                                    <a href="{{ route('collections.show', $collection->slug) }}" style="color: #333; text-decoration: none;">
                                        {{ $collection->name }}
                                    </a>
                                </h4>
                                <span class="badge" style="background-color: var(--c-primary, #064E5A); color: #fff; font-size: 12px; padding: 4px 10px; border-radius: 12px;">
                                    {{ $collection->tools_count }} {{ __('outils') }}
                                </span>
                            </div>
                            <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 15px; min-height: 44px;">
                                {{ Str::limit($collection->description, 100) }}
                            </p>
                            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                                <span style="font-size: 13px; color: #595959;">
                                    <i class="ti-user" style="margin-right: 4px;"></i>
                                    {{ $collection->user->name }}
                                </span>
                                <a href="{{ route('collections.show', $collection->slug) }}" class="btn btn-sm" style="background-color: var(--c-primary, #064E5A); color: #fff; border: none; border-radius: 4px; padding: 6px 16px; font-size: 13px;">
                                    {{ __('Voir') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-lg-12 text-center" style="margin-top: 20px;">
                    {{ $collections->links() }}
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
                        <i class="ti-folder" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
                        <p style="color: #595959; font-size: 16px; margin: 0;">{{ __('Aucune collection publique pour le moment.') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
