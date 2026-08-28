<div class="bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold text-[#064E5A] mb-4">📚 Tous les auteurs Memora</h1>

    <div class="flex flex-wrap gap-3 mb-4">
        <input type="search" wire:model.live.debounce.300ms="search"
            placeholder="🔍 Rechercher (slug, nom)…"
            class="flex-1 min-w-[250px] px-3 py-2 border border-gray-300 rounded min-h-[44px] focus:outline-3 focus:outline-[#9A2A06]"
            aria-label="Rechercher auteurs">

        <select wire:model.live="tierFilter"
            class="px-3 py-2 border border-gray-300 rounded min-h-[44px]"
            aria-label="Filtrer par tier">
            <option value="all">Tous tiers</option>
            <option value="free">Free</option>
            <option value="premium">Premium</option>
            <option value="premium_manual">Premium manuel</option>
            <option value="education">Education</option>
        </select>
    </div>

    @if($authors->isEmpty())
        <p class="text-center py-8 text-gray-600">Aucun auteur trouvé.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" role="table" aria-label="Liste auteurs">
                <thead class="bg-[#F8FAFB]">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">ID</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Slug</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Nom</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Tier</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-[#064E5A]">Posts</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-[#064E5A]">Comments</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold text-[#064E5A]">Abonnés</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($authors as $author)
                        <tr class="border-t border-gray-200" wire:key="author-{{ $author->id }}">
                            <td class="px-4 py-3 font-mono text-xs">{{ $author->id }}</td>
                            <td class="px-4 py-3 font-mono text-[#9A2A06]">@<a href="{{ url('/@'.$author->slug) }}" target="_blank" class="underline">{{ $author->slug }}</a></td>
                            <td class="px-4 py-3">{{ $author->display_name ?? '–' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-[#F0FAFB] text-[#064E5A] rounded text-xs">{{ $author->tier }}</span></td>
                            <td class="px-4 py-3 text-right">{{ $stats[$author->id]['published_count'] ?? 0 }}<small class="text-gray-500"> /{{ $stats[$author->id]['posts_count'] ?? 0 }}</small></td>
                            <td class="px-4 py-3 text-right">{{ $stats[$author->id]['comments_count'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-right">{{ $stats[$author->id]['subscribers_count'] ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ url('/@'.$author->slug) }}" target="_blank" class="text-[#064E5A] hover:underline mr-3 min-h-[44px] inline-flex items-center" aria-label="Voir mini-site de {{ $author->slug }}">👁️ Voir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('backoffice::partials.infinite-scroll', ['paginator' => $authors])
    @endif
</div>
