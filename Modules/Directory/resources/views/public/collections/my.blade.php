<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::layouts.master')

@section('title', 'Mes collections')

@section('content')
<section class="wpo-blog-pg-section section-padding" x-data="{ showForm: false }">
    <div class="container">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col-lg-12">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                    <h2 style="color: var(--c-primary, #0B7285); margin: 0;">{{ __('Mes collections') }}</h2>
                    <button class="btn" @click="showForm = !showForm" style="background-color: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 4px; padding: 10px 22px; font-size: 14px; cursor: pointer;">
                        <i class="ti-plus" style="margin-right: 5px;"></i>
                        <span x-text="showForm ? '{{ __('Annuler') }}' : '{{ __('Créer une collection') }}'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row" x-show="showForm" x-cloak style="margin-bottom: 30px;">
            <div class="col-lg-8 col-lg-offset-2">
                <div style="background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; padding: 30px;">
                    <h4 style="margin: 0 0 20px 0; color: #333; font-size: 18px;">{{ __('Nouvelle collection') }}</h4>
                    <form action="{{ route('collections.store') }}" method="POST">
                        @csrf
                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="name" style="font-weight: 600; color: #444; font-size: 14px; margin-bottom: 6px; display: block;">
                                {{ __('Nom') }} <span style="color: #B91C1C;">*</span>
                            </label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('Ex: Mes outils de productivité') }}" style="border-radius: 4px; border: 1px solid #ddd; padding: 10px 14px; font-size: 14px;">
                            @error('name')<span style="color: #B91C1C; font-size: 12px;">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="description" style="font-weight: 600; color: #444; font-size: 14px; margin-bottom: 6px; display: block;">{{ __('Description') }}</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('Décrivez votre collection...') }}" style="border-radius: 4px; border: 1px solid #ddd; padding: 10px 14px; font-size: 14px; resize: vertical;">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 400; color: #555; font-size: 14px;">
                                <input type="checkbox" name="is_public" value="1" checked style="display: inline-block; appearance: checkbox; width: 16px; height: 16px;">
                                {{ __('Rendre cette collection publique') }}
                            </label>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn" style="background-color: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 4px; padding: 10px 28px; font-size: 14px;">{{ __('Créer') }}</button>
                            <button type="button" @click="showForm = false" class="btn" style="background-color: #f5f5f5; color: #555; border: 1px solid #ddd; border-radius: 4px; padding: 10px 28px; font-size: 14px;">{{ __('Annuler') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-lg-12">
                    <div class="alert alert-success" style="border-radius: 4px; border: none; background-color: #d4edda; color: #155724; padding: 14px 20px; font-size: 14px;">
                        {{ session('success') }}
                    </div>
                </div>
            </div>
        @endif

        @if($collections->count())
            <div class="row">
                @foreach($collections as $collection)
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                        <div class="entry-details" style="border: 1px solid #e8e8e8; border-radius: 8px; padding: 25px; height: 100%; background: #fff; position: relative;">
                            <div style="position: absolute; top: 15px; right: 15px;">
                                @if($collection->is_public)
                                    <span class="badge" style="background-color: #27ae60; color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 3px;"><i class="ti-world"></i> {{ __('Public') }}</span>
                                @else
                                    <span class="badge" style="background-color: #95a5a6; color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 3px;"><i class="ti-lock"></i> {{ __('Privé') }}</span>
                                @endif
                            </div>
                            <h4 style="margin: 0 0 10px 0; font-size: 18px; color: #333; font-weight: 600; padding-right: 70px;">
                                <a href="{{ route('collections.show', $collection->slug) }}" style="color: #333; text-decoration: none;">{{ $collection->name }}</a>
                            </h4>
                            @if($collection->description)
                                <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 15px;">{{ Str::limit($collection->description, 100) }}</p>
                            @endif
                            <div style="margin-bottom: 18px;">
                                <span class="badge" style="background-color: var(--c-primary, #0B7285); color: #fff; font-size: 12px; padding: 4px 10px; border-radius: 12px;">{{ $collection->tools_count }} {{ __('outils') }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                                <a href="{{ route('collections.show', $collection->slug) }}" class="btn btn-sm" style="background-color: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 4px; padding: 6px 16px; font-size: 13px; flex: 1; text-align: center;">
                                    <i class="ti-eye" style="margin-right: 4px;"></i> {{ __('Voir') }}
                                </a>
                                <form action="{{ route('collections.destroy', $collection) }}" method="POST" onsubmit="return confirm('{{ __('Supprimer cette collection ?') }}')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="background-color: #fff; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 4px; padding: 6px 14px; font-size: 13px; cursor: pointer;">
                                        <i class="ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-lg-12 text-center" style="margin-top: 20px;">{{ $collections->links() }}</div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
                        <i class="ti-folder" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
                        <p style="color: #999; font-size: 16px; margin-bottom: 15px;">{{ __('Vous n\'avez pas encore de collection.') }}</p>
                        <button @click="showForm = true" class="btn" style="background-color: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 4px; padding: 10px 22px; font-size: 14px; cursor: pointer;">
                            <i class="ti-plus" style="margin-right: 5px;"></i> {{ __('Créer ma première collection') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
