<div x-data="{ showMessage: false }"
     x-init="$wire.on('author-modules-saved', () => { showMessage = true; setTimeout(() => showMessage = false, 2000); })"
     class="max-w-3xl mx-auto p-6">

    @php
        $author = $authorProfileId ? \Modules\Authors\Models\AuthorProfile::find($authorProfileId) : null;
    @endphp

    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <div class="flex justify-between items-start mb-5">
            <div>
                <h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 700; color: #0B7285; margin: 0;">⚙️ Modules du mini-site</h2>
                <p style="font-family: 'DM Sans', sans-serif; color: #52586a; margin-top: 8px; font-size: 14px;">Active/désactive ce qui s'affiche sur ton mini-site public. Sauvegarde automatique.</p>
            </div>
            @if($author)
                <a href="{{ '/@'.$author->slug }}" target="_blank"
                   style="display: inline-flex; align-items: center; gap: 4px; font-size: 14px; font-weight: 600; color: #0B7285; text-decoration: none; padding: 8px 14px; border: 1px solid #0B7285; border-radius: 0.5rem;">
                    👁 Voir mini-site
                </a>
            @endif
        </div>

        <div x-show="showMessage" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="margin-bottom: 16px; padding: 12px; background: #DDF4F8; color: #064E5C; border-radius: 0.5rem; text-align: center; font-weight: 600;">
            ✓ Sauvegardé
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($togglableModules as $key => $label)
                @php $isOn = $modulesVisible[$key] ?? true; @endphp
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border-radius: 0.75rem; background: {{ $isOn ? '#F0FAFB' : '#F8FAFB' }}; border: 1px solid {{ $isOn ? '#0B7285' : '#E5E7EB' }}; opacity: {{ $isOn ? '1' : '0.6' }}; transition: all 200ms;">
                    <div style="flex: 1;">
                        <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px; font-weight: 700; color: #1A1D23; margin: 0;">{{ $label }}</h3>
                    </div>
                    <button type="button" wire:click="toggleModule('{{ $key }}')"
                            role="switch" aria-checked="{{ $isOn ? 'true' : 'false' }}"
                            aria-label="Activer/désactiver {{ $label }}"
                            style="position: relative; display: inline-flex; height: 28px; width: 52px; min-width: 44px; min-height: 44px; flex-shrink: 0; cursor: pointer; border-radius: 9999px; border: 2px solid transparent; background: {{ $isOn ? '#0B7285' : '#cbd5e1' }}; transition: background 200ms;">
                        <span style="pointer-events: none; display: inline-block; height: 24px; width: 24px; transform: translateX({{ $isOn ? '24px' : '0' }}); border-radius: 9999px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 200ms; margin-top: 0px;"></span>
                    </button>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: center;">
            <button type="button" wire:click="resetDefaults"
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; font-size: 14px; font-weight: 600; color: #C2410C; background: #FDF5ED; border: 1px solid #C2410C; border-radius: 9999px; cursor: pointer; min-height: 44px;">
                🔄 Restaurer les défauts
            </button>
        </div>
    </div>
</div>
