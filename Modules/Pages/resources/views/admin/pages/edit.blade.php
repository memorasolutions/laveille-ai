@extends('backoffice::layouts.admin', ['title' => 'Modifier la page', 'subtitle' => 'Pages statiques'])

@section('content')

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)
                <li class="text-sm">{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.pages.update', $page->slug) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header sm:py-6 py-5 sm:px-[1.875rem] px-4 border-b border-border">
                    <h5 class="text-lg font-semibold text-heading">Contenu</h5>
                </div>
                <div class="sm:p-[1.875rem] p-4 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">
                            Titre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title"
                               class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 @error('title') border-red-400 @enderror"
                               value="{{ old('title', $page->title) }}" required>
                        @error('title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-editor::tiptap name="content" :value="old('content', $page->content ?? '')" label="Contenu" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">Extrait</label>
                        <textarea name="excerpt" rows="3" maxlength="500"
                                  class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none">{{ old('excerpt', $page->excerpt) }}</textarea>
                        <p class="text-xs text-secondary mt-1">Résumé court affiché dans les listes (max 500 caractères)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">
            {{-- Publication --}}
            <div class="card">
                <div class="card-header sm:py-5 py-4 sm:px-[1.875rem] px-4 border-b border-border">
                    <h5 class="text-base font-semibold text-heading">Publication</h5>
                </div>
                <div class="sm:p-[1.875rem] p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">Statut</label>
                        <select name="status" class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Publié</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">Slug</label>
                        <input type="text" class="w-full border border-border rounded-lg px-3 py-2 text-sm bg-neutral-50 text-secondary cursor-not-allowed"
                               value="{{ $page->slug }}" readonly>
                        <p class="text-xs text-secondary mt-1">Non modifiable après création</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary flex-1 py-2 text-sm rounded-lg">Enregistrer</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline flex-1 py-2 text-sm rounded-lg text-center">Annuler</a>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card">
                <div class="card-header sm:py-5 py-4 sm:px-[1.875rem] px-4 border-b border-border">
                    <h5 class="text-base font-semibold text-heading">SEO</h5>
                </div>
                <div class="sm:p-[1.875rem] p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">Meta titre</label>
                        <input type="text" name="meta_title"
                               class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                               value="{{ old('meta_title', $page->meta_title) }}" maxlength="255">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-heading mb-1.5">Meta description</label>
                        <textarea name="meta_description" rows="3" maxlength="500"
                                  class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
