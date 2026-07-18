<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mes collections') . ' - ' . config('app.name'))

@section('user-content')
<div x-data="{ showForm: false }">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
        <h1 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); margin: 0;">{{ __('Mes collections') }}</h1>
        <button class="ct-btn ct-btn-primary" @click="showForm = !showForm" style="border: none; cursor: pointer;">
            <i class="ti-plus" style="margin-right: 5px;"></i>
            <span x-text="showForm ? '{{ __('Annuler') }}' : '{{ __('Créer une collection') }}'"></span>
        </button>
    </div>

    <div x-show="showForm" x-cloak style="margin-bottom: 28px;">
        <div style="background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; padding: 24px;">
            <h2 style="margin: 0 0 18px 0; color: #333; font-size: 18px;">{{ __('Nouvelle collection') }}</h2>
            <form action="{{ route('collections.store') }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom: 18px;">
                    <label for="name" style="font-weight: 600; color: #444; font-size: 14px; margin-bottom: 6px; display: block;">
                        {{ __('Nom') }} <span style="color: #B91C1C;">*</span>
                    </label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('Ex: Mes outils de productivité') }}" style="border-radius: 4px; border: 1px solid #ddd; padding: 10px 14px; font-size: 14px; width: 100%;">
                    @error('name')<span style="color: #B91C1C; font-size: 12px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label for="description" style="font-weight: 600; color: #444; font-size: 14px; margin-bottom: 6px; display: block;">{{ __('Description') }}</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('Décrivez votre collection...') }}" style="border-radius: 4px; border: 1px solid #ddd; padding: 10px 14px; font-size: 14px; resize: vertical; width: 100%;">{{ old('description') }}</textarea>
                </div>
                <div class="form-group" style="margin-bottom: 22px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 400; color: #555; font-size: 14px;">
                        <input type="checkbox" name="is_public" value="1" checked style="display: inline-block; appearance: checkbox; width: 16px; height: 16px;">
                        {{ __('Rendre cette collection publique') }}
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="ct-btn ct-btn-primary" style="border: none;">{{ __('Créer') }}</button>
                    <button type="button" @click="showForm = false" class="ct-btn ct-btn-ghost" style="border: 1px solid #ddd;">{{ __('Annuler') }}</button>
                </div>
            </form>
        </div>
    </div>

    @if($collections->count())
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px;">
            @foreach($collections as $collection)
                <div style="border: 1px solid #e8e8e8; border-radius: 8px; padding: 22px; background: #fff; position: relative; display: flex; flex-direction: column;">
                    <div style="position: absolute; top: 14px; right: 14px;">
                        @if($collection->is_public)
                            <span class="badge" style="background-color: #27ae60; color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 3px;"><i class="ti-world"></i> {{ __('Public') }}</span>
                        @else
                            <span class="badge" style="background-color: #95a5a6; color: #fff; font-size: 11px; padding: 3px 10px; border-radius: 3px;"><i class="ti-lock"></i> {{ __('Privé') }}</span>
                        @endif
                    </div>
                    <h2 style="margin: 0 0 10px 0; font-size: 18px; color: #333; font-weight: 600; padding-right: 64px;">
                        <a href="{{ route('collections.show', $collection->slug) }}" style="color: #333; text-decoration: none;">{{ $collection->name }}</a>
                    </h2>
                    @if($collection->description)
                        <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 15px;">{{ Str::limit($collection->description, 100) }}</p>
                    @endif
                    <div style="margin-bottom: 18px; margin-top: auto;">
                        <span class="badge" style="background-color: var(--c-primary, #064E5A); color: #fff; font-size: 12px; padding: 4px 10px; border-radius: 12px;">{{ $collection->tools_count }} {{ __('outils') }}</span>
                    </div>
                    @php
                        $collectionActions = [
                            ['label' => __('Voir'), 'icon' => 'eye', 'url' => route('collections.show', $collection->slug)],
                            ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('collections.destroy', $collection), 'method' => 'DELETE', 'confirm' => __('Supprimer cette collection ?'), 'danger' => true],
                        ];
                    @endphp
                    <div style="display: flex; align-items: center; justify-content: flex-end; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                        @include('core::components.action-menu', ['actions' => $collectionActions])
                    </div>
                </div>
            @endforeach
        </div>
        <div style="margin-top: 28px; text-align: center;">{{ $collections->links() }}</div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
            <i class="ti-folder" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
            <p style="color: #595959; font-size: 16px; margin-bottom: 15px;">{{ __('Vous n\'avez pas encore de collection.') }}</p>
            <button @click="showForm = true" class="ct-btn ct-btn-primary" style="border: none; cursor: pointer;">
                <i class="ti-plus" style="margin-right: 5px;"></i> {{ __('Créer ma première collection') }}
            </button>
        </div>
    @endif
</div>
@endsection
