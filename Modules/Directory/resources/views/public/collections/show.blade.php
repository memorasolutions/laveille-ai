<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::layouts.master')

@section('title', $collection->name)
@section('meta_description', $collection->description ?: 'Collection d\'outils ' . $collection->name . ' — ' . $collection->tools->count() . ' outils sélectionnés.')

@section('content')
<section class="wpo-blog-pg-section section-padding">
    <div class="container">
        <div class="row" style="margin-bottom: 40px;">
            <div class="col-lg-12">
                <div style="background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; padding: 30px;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <div style="flex: 1;">
                            <h2 style="color: var(--c-primary, #0B7285); margin: 0 0 10px 0; font-size: 28px;">{{ $collection->name }}</h2>
                            @if($collection->description)
                                <p style="color: #666; font-size: 15px; line-height: 1.7; margin-bottom: 12px;">{{ $collection->description }}</p>
                            @endif
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <span class="badge" style="background-color: var(--c-primary, #0B7285); color: #fff; font-size: 13px; padding: 5px 14px; border-radius: 12px;">
                                    {{ $collection->tools->count() }} {{ __('outils') }}
                                </span>
                                <span style="font-size: 14px; color: #999;">
                                    <i class="ti-user" style="margin-right: 4px;"></i>
                                    {{ __('Par') }} <strong style="color: #555;">{{ $collection->user->name }}</strong>
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('collections.index') }}" class="btn btn-sm" style="background-color: #f5f5f5; color: #555; border: 1px solid #ddd; border-radius: 4px; padding: 8px 18px; font-size: 13px;">
                            <i class="ti-arrow-left" style="margin-right: 4px;"></i> {{ __('Retour') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($collection->tools->count())
            <div class="row">
                @foreach($collection->tools as $tool)
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                        <div class="entry-details" style="border: 1px solid #e8e8e8; border-radius: 8px; overflow: hidden; background: #fff; height: 100%;">
                            <div class="wpo-blog-img">
                                <a href="{{ route('directory.show', $tool->slug) }}">
                                    @if($tool->screenshot_path)
                                        <img src="{{ asset($tool->screenshot_path) }}" alt="{{ $tool->name }}" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                                    @else
                                        <div style="width: 100%; height: 180px; background: var(--c-primary, #0B7285); display: flex; align-items: center; justify-content: center;">
                                            <span style="color: #fff; font-size: 36px; font-weight: 700; opacity: 0.7;">{{ strtoupper(substr($tool->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <div style="padding: 20px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                    <h5 style="margin: 0; font-size: 16px; font-weight: 600;">
                                        <a href="{{ route('directory.show', $tool->slug) }}" style="color: #333; text-decoration: none;">{{ $tool->name }}</a>
                                    </h5>
                                    @if($tool->pricing)
                                        <span class="badge" style="background-color: {{ $tool->pricing === 'free' ? '#27ae60' : ($tool->pricing === 'freemium' ? '#f39c12' : '#e74c3c') }}; color: #fff; font-size: 11px; padding: 3px 8px; border-radius: 3px;">
                                            {{ $tool->pricing }}
                                        </span>
                                    @endif
                                </div>
                                <p style="color: #666; font-size: 13px; line-height: 1.5; margin: 0;">{{ Str::limit($tool->short_description, 100) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
                        <i class="ti-package" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
                        <p style="color: #999; font-size: 16px; margin: 0;">{{ __('Cette collection est vide.') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
