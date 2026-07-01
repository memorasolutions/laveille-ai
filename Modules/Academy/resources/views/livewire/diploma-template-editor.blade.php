{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     Éditeur visuel de gabarits de diplômes (Phase 1 — diplomation moderne).
     Konva.js positionne/redimensionne les éléments (canevas) ; le RENDU FINAL
     reste HTML/CSS (aperçu en direct + PDF via DiplomaRenderService/dompdf).
     Gâté par academy.diploma_editor_enabled (404 si désactivé, voir mount()).
     Charte #064E5A, WCAG 2.2 AAA, aucune popup native (confirmations inline). --}}

@assets
    <script defer src="https://cdn.jsdelivr.net/npm/konva@9/konva.min.js"></script>
@endassets

@assets
<script>
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('diplomaKonvaEditor', function () {
            return {
                stage: null,
                layer: null,
                transformer: null,
                width: 900,
                height: 700,

                init(container) {
                    if (typeof Konva === 'undefined' || !container) {
                        return;
                    }

                    this.stage = new Konva.Stage({ container: container, width: this.width, height: this.height });
                    this.layer = new Konva.Layer();
                    this.stage.add(this.layer);

                    this.transformer = new Konva.Transformer({
                        boundBoxFunc: function (oldBox, newBox) {
                            return (newBox.width < 20 || newBox.height < 20) ? oldBox : newBox;
                        },
                    });
                    this.layer.add(this.transformer);

                    // Clic sur le fond du canevas → désélection.
                    this.stage.on('click tap', (e) => {
                        if (e.target === this.stage) {
                            this.transformer.nodes([]);
                            this.layer.draw();
                            this.$wire.selectElement(null);
                        }
                    });

                    this.rebuild(this.$wire.elements || []);

                    // Reconstruit le canevas à CHAQUE changement d'état serveur (ajout,
                    // suppression, chargement d'un autre gabarit, style édité en panneau
                    // latéral) — source de vérité = état serveur, jamais de dérive client.
                    this.$wire.$watch('elements', (value) => this.rebuild(value || []));
                },

                rebuild(elements) {
                    if (!this.layer) {
                        return;
                    }

                    this.layer.find('.diploma-el').forEach((node) => node.destroy());
                    this.transformer.nodes([]);

                    elements.forEach((el) => this.addNode(el));
                    this.layer.draw();
                },

                addNode(el) {
                    const w = this.width * (Number(el.width) || 15) / 100;
                    const h = this.height * (Number(el.height) || 10) / 100;
                    const x = this.width * (Number(el.x) || 0) / 100;
                    const y = this.height * (Number(el.y) || 0) / 100;

                    const group = new Konva.Group({
                        id: el.id, name: 'diploma-el', x: x, y: y, width: w, height: h, draggable: true,
                        dragBoundFunc: (pos) => ({
                            x: Math.max(0, Math.min(this.width - w, pos.x)),
                            y: Math.max(0, Math.min(this.height - h, pos.y)),
                        }),
                    });

                    const isQr = el.kind === 'qr';
                    const isImage = el.kind === 'image';

                    group.add(new Konva.Rect({
                        width: w, height: h,
                        fill: isQr || isImage ? '#F3F4F6' : 'rgba(6,78,90,0.04)',
                        stroke: '#064E5A', strokeWidth: 1, dash: [4, 3], cornerRadius: 4,
                    }));

                    const label = isQr ? 'QR' : (isImage ? 'IMAGE' : String(el.content || 'Texte').slice(0, 40));
                    group.add(new Konva.Text({
                        text: label, width: w, height: h, padding: 6,
                        align: 'center', verticalAlign: 'middle', fontSize: 13, fill: '#374151',
                        listening: false,
                    }));

                    group.on('click tap', (e) => {
                        e.cancelBubble = true;
                        this.transformer.nodes([group]);
                        this.layer.draw();
                        this.$wire.selectElement(el.id);
                    });

                    group.on('dragend transformend', () => this.commit(group));

                    this.layer.add(group);
                },

                commit(node) {
                    const scaleX = node.scaleX();
                    const scaleY = node.scaleY();
                    const w = Math.max(10, node.width() * scaleX);
                    const h = Math.max(10, node.height() * scaleY);
                    node.scaleX(1);
                    node.scaleY(1);
                    node.width(w);
                    node.height(h);

                    const xPct = Math.max(0, Math.min(100, (node.x() / this.width) * 100));
                    const yPct = Math.max(0, Math.min(100, (node.y() / this.height) * 100));
                    const wPct = Math.max(1, Math.min(100, (w / this.width) * 100));
                    const hPct = Math.max(1, Math.min(100, (h / this.height) * 100));

                    this.$wire.updateElementGeometry(node.id(), xPct, yPct, wPct, hPct);
                },
            };
        });
    });
</script>
@endassets

<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- Bannières de statut (jamais de popup native) --}}
    @if (session('diploma_editor_status'))
        <div role="status" aria-live="polite"
             style="background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46; border-radius:8px; padding:10px 16px; font-size:0.9rem;">
            {{ session('diploma_editor_status') }}
        </div>
    @endif
    @if (session('diploma_editor_error'))
        <div role="alert" aria-live="assertive"
             style="background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; border-radius:8px; padding:10px 16px; font-size:0.9rem;">
            {{ session('diploma_editor_error') }}
        </div>
    @endif

    {{-- Barre d'en-tête : nom du gabarit + actions --}}
    <form wire:submit.prevent="save"
          style="display:flex; flex-wrap:wrap; align-items:end; gap:16px; border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); padding:16px;">
        <div style="flex:1 1 260px;">
            <label for="diploma-name" style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:4px; color:var(--sys-text-default, #1A1D23);">
                Nom du gabarit
            </label>
            <input id="diploma-name" type="text" wire:model.live.blur="name" maxlength="120" class="form-control">
            @error('name') <div style="color:#B91C1C; font-size:0.8rem;">{{ $message }}</div> @enderror
        </div>

        <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; padding-bottom:8px;">
            <input type="checkbox" wire:model.live="isDefault">
            Gabarit par défaut
        </label>

        <div style="display:flex; gap:10px; margin-left:auto;">
            <x-core::button type="button" wire:click="newTemplate" variant="ghost" icon="➕">Nouveau</x-core::button>
            <x-core::button type="submit" variant="primary" icon="💾">Enregistrer</x-core::button>
        </div>
    </form>

    <div style="display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start;">

        {{-- Colonne gauche : mes gabarits + insertion d'éléments --}}
        <div style="flex:0 0 260px; display:flex; flex-direction:column; gap:20px;">

            <section aria-labelledby="diploma-my-templates-h" style="border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); padding:14px;">
                <h2 id="diploma-my-templates-h" style="font-family:var(--f-heading); font-size:0.95rem; margin:0 0 10px; color:var(--sys-text-default, #1A1D23);">
                    Mes gabarits
                </h2>
                @forelse ($this->myTemplates as $tpl)
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; padding:6px 0; border-bottom:1px solid #F3F4F6;">
                        <button type="button" wire:click="loadTemplate({{ $tpl->id }})"
                                style="background:none; border:none; padding:0; text-align:left; cursor:pointer; font-size:0.85rem; color:{{ $templateId === $tpl->id ? 'var(--sys-action-primary, #064E5A)' : '#374151' }}; font-weight:{{ $templateId === $tpl->id ? '700' : '400' }};">
                            {{ $tpl->name }}{{ $tpl->is_default ? ' ⭐' : '' }}
                        </button>

                        @if ($confirmingDeleteId === $tpl->id)
                            <span style="display:flex; gap:4px;">
                                <button type="button" wire:click="delete({{ $tpl->id }})" aria-label="Confirmer la suppression de {{ $tpl->name }}"
                                        style="background:none; border:none; color:#B91C1C; font-size:0.78rem; cursor:pointer;">Confirmer</button>
                                <button type="button" wire:click="cancelDelete" style="background:none; border:none; color:#6B7280; font-size:0.78rem; cursor:pointer;">Annuler</button>
                            </span>
                        @else
                            <button type="button" wire:click="confirmDelete({{ $tpl->id }})" aria-label="Supprimer {{ $tpl->name }}"
                                    style="background:none; border:none; color:#9CA3AF; font-size:0.78rem; cursor:pointer;">✕</button>
                        @endif
                    </div>
                @empty
                    <p style="font-size:0.82rem; color:#6B7280; margin:0;">Aucun gabarit pour l'instant.</p>
                @endforelse
            </section>

            <section aria-labelledby="diploma-insert-h" style="border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); padding:14px;">
                <h2 id="diploma-insert-h" style="font-family:var(--f-heading); font-size:0.95rem; margin:0 0 10px; color:var(--sys-text-default, #1A1D23);">
                    Insérer un élément
                </h2>

                @foreach ($this->variableCatalog as $familyKey => $family)
                    <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#6B7280; margin:10px 0 4px;">
                        {{ $family['label'] }}
                    </p>
                    @forelse ($family['variables'] as $varKey => $varLabel)
                        <button type="button" wire:click="addElement('text', '{{ $familyKey }}.{{ $varKey }}')"
                                style="display:block; width:100%; text-align:left; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:6px; padding:6px 10px; margin-bottom:4px; font-size:0.8rem; cursor:pointer; color:#374151;">
                            + {{ $varLabel }}
                        </button>
                    @empty
                        <button type="button" wire:click="addElement('text', null)"
                                style="display:block; width:100%; text-align:left; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:6px; padding:6px 10px; margin-bottom:4px; font-size:0.8rem; cursor:pointer; color:#374151;">
                            + Texte libre
                        </button>
                    @endforelse
                @endforeach

                <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#6B7280; margin:14px 0 4px;">
                    Autres éléments
                </p>
                <button type="button" wire:click="addElement('qr')"
                        style="display:block; width:100%; text-align:left; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:6px; padding:6px 10px; margin-bottom:4px; font-size:0.8rem; cursor:pointer; color:#374151;">
                    + QR de vérification
                </button>
                <button type="button" wire:click="addElement('image', 'organizational.logo_url')"
                        style="display:block; width:100%; text-align:left; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:6px; padding:6px 10px; cursor:pointer; color:#374151;">
                    + Image (logo)
                </button>
            </section>
        </div>

        {{-- Colonne centrale : canevas Konva.js (positionnement/redimensionnement) --}}
        <div style="flex:1 1 480px; min-width:320px;">
            <p style="font-size:0.8rem; color:#6B7280; margin:0 0 8px;">
                Glissez-déposez les éléments pour les positionner ; utilisez les poignées pour les redimensionner.
            </p>
            <div
                x-data="diplomaKonvaEditor()"
                x-init="init($refs.stage)"
                style="border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); overflow:hidden; background:#fff; display:inline-block;"
            >
                <div wire:ignore x-ref="stage" id="diploma-konva-stage" style="width:900px; height:700px; max-width:100%;"></div>
            </div>
        </div>

        {{-- Colonne droite : propriétés de l'élément sélectionné --}}
        <div style="flex:0 0 260px;">
            <section aria-labelledby="diploma-props-h" style="border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); padding:14px;">
                <h2 id="diploma-props-h" style="font-family:var(--f-heading); font-size:0.95rem; margin:0 0 10px; color:var(--sys-text-default, #1A1D23);">
                    Propriétés
                </h2>

                @if ($this->selectedIndex !== null)
                    @php($idx = $this->selectedIndex)

                    <label for="diploma-el-content" style="display:block; font-weight:600; font-size:0.82rem; margin-bottom:4px;">Contenu</label>
                    <input id="diploma-el-content" type="text" wire:model.live.debounce.400ms="elements.{{ $idx }}.content" class="form-control" style="margin-bottom:10px; font-size:0.85rem;">

                    <label for="diploma-el-size" style="display:block; font-weight:600; font-size:0.82rem; margin-bottom:4px;">Taille de police</label>
                    <input id="diploma-el-size" type="number" min="6" max="120" wire:model.live.debounce.400ms="elements.{{ $idx }}.font_size" class="form-control" style="margin-bottom:10px;">

                    <label for="diploma-el-color" style="display:block; font-weight:600; font-size:0.82rem; margin-bottom:4px;">Couleur</label>
                    <input id="diploma-el-color" type="color" wire:model.live="elements.{{ $idx }}.color" style="margin-bottom:10px; width:100%; height:36px;">

                    <label for="diploma-el-align" style="display:block; font-weight:600; font-size:0.82rem; margin-bottom:4px;">Alignement</label>
                    <select id="diploma-el-align" wire:model.live="elements.{{ $idx }}.align" class="form-select" style="margin-bottom:10px;">
                        <option value="left">Gauche</option>
                        <option value="center">Centré</option>
                        <option value="right">Droite</option>
                    </select>

                    <x-core::button type="button" wire:click="removeElement('{{ $elements[$idx]['id'] }}')" variant="ghost" size="sm">
                        Retirer l'élément
                    </x-core::button>
                @else
                    <p style="font-size:0.82rem; color:#6B7280; margin:0;">Cliquez sur un élément du canevas pour l'éditer.</p>
                @endif
            </section>
        </div>
    </div>

    {{-- Aperçu en direct (données d'exemple — jamais une vraie donnée d'apprenant) --}}
    <section aria-labelledby="diploma-preview-h">
        <h2 id="diploma-preview-h" style="font-family:var(--f-heading); font-size:1rem; margin:0 0 8px; color:var(--sys-text-default, #1A1D23);">
            Aperçu en direct (données d'exemple)
        </h2>
        <iframe title="Aperçu du diplôme" srcdoc="{{ $this->previewHtml }}"
                style="width:100%; max-width:900px; aspect-ratio:11/8.5; border:1px solid #E5E7EB; border-radius:var(--sys-radius-md, 0.75rem); background:#fff;"></iframe>
    </section>
</div>
