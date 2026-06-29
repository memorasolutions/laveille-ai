{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:60px">{{ __('Couleur') }}</th>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="width:100px" class="text-center">{{ __('Articles') }}</th>
                    <th style="width:120px" class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                <tr>
                    <td>
                        <div class="rounded-circle" style="width:20px;height:20px;background-color:{{ $tag->color }}"></div>
                    </td>
                    <td><strong>{{ $tag->name }}</strong></td>
                    <td class="text-muted">{{ Str::limit($tag->description, 60) }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary">{{ $tag->articles_count }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.blog.tags.edit', $tag) }}"
                           class="btn btn-sm btn-outline-primary me-1"
                           title="{{ __('Modifier') }}">
                            <i data-lucide="pencil"></i>
                        </a>
                        <button
                            wire:click="deleteTag({{ $tag->id }})"
                            wire:confirm="{{ __('Supprimer ce tag ?') }}"
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            title="{{ __('Supprimer') }}"
                        >
                            <i data-lucide="trash-2"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('Aucun tag pour le moment.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tags->hasPages())
        <div class="px-4 py-3">
            @include('backoffice::partials.infinite-scroll', ['paginator' => $tags])
        </div>
    @endif
</div>
