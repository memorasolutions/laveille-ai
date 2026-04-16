{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Bouton "Ajouter à une collection" avec dropdown Alpine.js --}}
@if(!auth()->check())
    <a href="{{ route('login') }}"
       style="display:inline-block;padding:8px 16px;background:#fff;border:1px solid #ccc;border-radius:4px;color:#555;font-size:14px;text-decoration:none;cursor:pointer;transition:background .2s;"
       onmouseover="this.style.background='#f5f5f5'"
       onmouseout="this.style.background='#fff'">
        <i class="fa fa-sign-in" style="margin-right:6px;"></i>{{ __('Se connecter pour sauvegarder') }}
    </a>
@else
    <div x-data="{
            open: false,
            collections: [],
            loading: false,
            fetched: false,
            newName: '',
            creating: false,
            togglingIds: [],
            toolId: {{ $tool->id }},
            get csrf() { return document.querySelector('meta[name=&quot;csrf-token&quot;]').content; },
            async fetchCollections() {
                if (this.fetched) return;
                this.loading = true;
                try {
                    const res = await fetch('/user/collections/list?tool_id=' + this.toolId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }});
                    if (!res.ok) throw new Error('Erreur réseau');
                    this.collections = await res.json();
                    this.fetched = true;
                } catch (e) {
                    console.error(e);
                } finally { this.loading = false; }
            },
            async toggleTool(collection) {
                if (this.togglingIds.includes(collection.id)) return;
                this.togglingIds.push(collection.id);
                try {
                    const res = await fetch('/api/collections/toggle-tool', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ collection_id: collection.id, tool_id: this.toolId })
                    });
                    if (!res.ok) throw new Error('Erreur');
                    const data = await res.json();
                    collection.has_tool = data.added;
                } catch (e) { console.error(e); }
                finally { this.togglingIds = this.togglingIds.filter(id => id !== collection.id); }
            },
            async createAndAdd() {
                if (this.creating || !this.newName.trim()) return;
                this.creating = true;
                try {
                    const fd = new FormData();
                    fd.append('name', this.newName.trim());
                    fd.append('is_public', '1');
                    fd.append('_token', this.csrf);
                    const resCreate = await fetch('/user/collections', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
                    if (!resCreate.ok) throw new Error('Création échouée');
                    this.newName = '';
                    this.fetched = false;
                    await this.fetchCollections();
                    const last = this.collections[0];
                    if (last) await this.toggleTool(last);
                } catch (e) { console.error(e); }
                finally { this.creating = false; }
            },
            toggle() {
                this.open = !this.open;
                if (this.open && !this.fetched) this.fetchCollections();
            }
         }"
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         style="position:relative;display:inline-block;">

        <button @click="toggle()" type="button"
                style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ccc;border-radius:4px;color:#555;font-size:14px;cursor:pointer;transition:background .2s;outline:none;"
                onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
            <i class="fa fa-folder" style="font-size:15px;"></i>
            <span>{{ __('Ajouter à une collection') }}</span>
            <i class="fa fa-caret-down" style="font-size:12px;margin-left:2px;"></i>
        </button>

        <div x-show="open" x-cloak
             style="position:absolute;top:100%;left:0;margin-top:4px;min-width:280px;max-width:340px;background:#fff;border:1px solid #ddd;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:100;">
            <div style="padding:10px 14px;border-bottom:1px solid #eee;font-size:13px;font-weight:600;color:#333;">
                <i class="fa fa-folder-open" style="margin-right:6px;color:#888;"></i>{{ __('Mes collections') }}
            </div>
            <div x-show="loading" style="padding:20px;text-align:center;color:#999;font-size:13px;">
                <i class="fa fa-spinner fa-spin" style="margin-right:6px;"></i>{{ __('Chargement...') }}
            </div>
            <div x-show="!loading" style="max-height:220px;overflow-y:auto;">
                <div x-show="collections.length === 0 && fetched" style="padding:16px 14px;text-align:center;color:#999;font-size:13px;">
                    {{ __('Aucune collection. Créez-en une ci-dessous.') }}
                </div>
                <template x-for="collection in collections" :key="collection.id">
                    <label @click.prevent="toggleTool(collection)"
                           style="display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;transition:background .15s;border-bottom:1px solid #f5f5f5;margin:0;"
                           onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'"
                           :style="togglingIds.includes(collection.id) ? 'opacity:.5;pointer-events:none;' : ''">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border:2px solid #ccc;border-radius:3px;flex-shrink:0;"
                              :style="collection.has_tool ? 'background:var(--c-primary,#0B7285);border-color:var(--c-primary,#0B7285);' : ''">
                            <i x-show="collection.has_tool" class="fa fa-check" style="color:#fff;font-size:11px;"></i>
                        </span>
                        <span x-text="collection.name" style="font-size:13px;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;"></span>
                        <i x-show="togglingIds.includes(collection.id)" class="fa fa-spinner fa-spin" style="font-size:11px;color:#999;"></i>
                    </label>
                </template>
            </div>
            <div style="border-top:1px solid #eee;padding:10px 14px;">
                <div style="font-size:12px;color:#888;margin-bottom:6px;">{{ __('Nouvelle collection') }}</div>
                <div style="display:flex;gap:6px;">
                    <input x-model="newName" @keydown.enter.prevent="createAndAdd()" type="text"
                           placeholder="{{ __('Nom de la collection...') }}" maxlength="100" :disabled="creating"
                           style="flex:1;padding:6px 10px;border:1px solid #ccc;border-radius:3px;font-size:13px;outline:none;">
                    <button @click="createAndAdd()" :disabled="creating || !newName.trim()" type="button"
                            style="padding:6px 12px;background:var(--c-primary,#0B7285);color:#fff;border:none;border-radius:3px;font-size:12px;cursor:pointer;white-space:nowrap;"
                            :style="(creating || !newName.trim()) ? 'opacity:.5;cursor:not-allowed;' : 'opacity:1;'">
                        <i x-show="!creating" class="fa fa-plus" style="margin-right:4px;"></i>
                        <i x-show="creating" class="fa fa-spinner fa-spin" style="margin-right:4px;"></i>
                        <span x-text="creating ? '{{ __('Création...') }}' : '{{ __('Créer') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
