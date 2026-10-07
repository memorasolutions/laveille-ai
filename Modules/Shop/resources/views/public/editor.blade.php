@extends(fronttheme_layout())

@section('title', __('Personnaliser') . ' ' . $product->name . ' - ' . __('Boutique'))
@section('meta_description', __('Conçois ton produit : texte et image, avec aperçu validé par le serveur.'))

@push('styles')
<style>
@foreach($fonts as $family => $url)
    @font-face { font-family: "{{ $family }}"; src: url("{{ $url }}") format("truetype"); font-weight: 400; font-display: block; }
@endforeach
    .ged-wrap { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; padding: 24px 0; }
    .ged-stage { position: relative; background: repeating-conic-gradient(#f1f3f5 0% 25%, #fff 0% 50%) 0 0 / 20px 20px; border: 1px solid #ced4da; line-height: 0; }
    .ged-safe { position: absolute; pointer-events: none; border: 2px dashed #c92a2a; box-sizing: border-box; }
    .ged-panel { flex: 1 1 280px; max-width: 420px; display: grid; gap: 12px; }
    .ged-panel label { display: grid; gap: 4px; font-weight: 600; }
    .ged-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .ged-toast { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); max-width: 90vw; padding: 12px 18px; border-radius: 8px; color: #fff; background: #1f2937; z-index: 2000; }
    .ged-toast[data-kind="error"] { background: #b42318; }
    .ged-toast[data-kind="ok"] { background: #067647; }
    .ged-mockup img { max-width: 100%; border: 1px solid #ced4da; }
    .ged-hint { font-size: .875rem; color: #495057; }
</style>
@endpush

@section('content')
<div class="container sp-container">
    <h1>{{ __('Personnaliser') }} : {{ $product->name }}</h1>
    <p class="ged-hint">{{ __('Reste dans la zone pointillée : tout ce qui sort de la zone de sécurité est refusé. L\'aperçu final est toujours recalculé par le serveur.') }}</p>

    <div class="ged-wrap" id="ged"
         data-area='@json($area)'
         data-fonts='@json(array_keys($fonts))'
         data-render-url="{{ route('shop.editor.render', $product) }}"
         data-upload-url="{{ route('shop.editor.upload') }}"
         data-approve-url="{{ route('shop.editor.approve') }}">
        <div>
            <div class="ged-stage" id="ged-stage"><canvas id="ged-canvas"></canvas><div class="ged-safe" id="ged-safe"></div></div>
        </div>

        <div class="ged-panel">
            @if($variants->count() > 1)
            <label>{{ __('Variante') }}
                <select id="ged-variant" class="form-select">
                    @foreach($variants as $v)
                        <option value="{{ $v['gelato_uid'] }}" @selected($v['gelato_uid'] === $defaultUid)>{{ $v['label'] ?? $v['gelato_uid'] }}</option>
                    @endforeach
                </select>
            </label>
            @else
                <input type="hidden" id="ged-variant" value="{{ $defaultUid }}">
            @endif

            <label>{{ __('Texte') }}
                <input type="text" id="ged-text" class="form-control" maxlength="500" placeholder="{{ __('Ton texte') }}">
            </label>
            <div class="ged-row">
                <label>{{ __('Police') }}
                    <select id="ged-font" class="form-select">
                        @foreach(array_keys($fonts) as $family)<option value="{{ $family }}" style="font-family:'{{ $family }}'">{{ $family }}</option>@endforeach
                    </select>
                </label>
                <label>{{ __('Taille (pt)') }}
                    <input type="number" id="ged-size" class="form-control" min="4" max="600" value="48" style="width:90px">
                </label>
                <label>{{ __('Couleur') }}
                    <input type="color" id="ged-color" class="form-control form-control-color" value="#111111">
                </label>
            </div>
            <div class="ged-row">
                <button type="button" class="btn btn-secondary" id="ged-add-text">{{ __('Ajouter le texte') }}</button>
                <label class="btn btn-secondary mb-0">{{ __('Téléverser une image') }}
                    <input type="file" id="ged-file" accept="image/png,image/jpeg,image/webp" hidden>
                </label>
                <button type="button" class="btn btn-outline-danger" id="ged-delete">{{ __('Supprimer la sélection') }}</button>
            </div>

            <button type="button" class="btn btn-primary" id="ged-render">{{ __('Générer l\'aperçu du serveur') }}</button>
            <div class="ged-mockup" id="ged-mockup" hidden>
                <img alt="{{ __('Aperçu du produit rendu par le serveur') }}" id="ged-mockup-img">
                <button type="button" class="btn btn-success mt-2" id="ged-approve">{{ __('J\'approuve cet aperçu et j\'ajoute au panier') }}</button>
            </div>
        </div>
    </div>
    <div class="ged-toast" id="ged-toast" role="status" aria-live="polite" hidden></div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    if (typeof fabric === 'undefined') { return; }
    var root = document.getElementById('ged');
    var area = JSON.parse(root.dataset.area);
    var stage = document.getElementById('ged-stage');
    var MM_PER_PT = 25.4 / 72;
    var csrf = @json(csrf_token());
    var designId = null, contentHash = null;

    // Échelle : px par mm, pour que le canvas ait exactement le ratio de la zone d'impression.
    var cw = Math.min(560, Math.max(260, root.clientWidth - 24));
    var S = cw / area.widthMm, ch = Math.round(area.heightMm * S);
    var canvas = new fabric.Canvas('ged-canvas', { width: cw, height: ch, preserveObjectStacking: true, backgroundColor: 'rgba(0,0,0,0)' });
    var safe = { l: area.safeMarginMm * S, t: area.safeMarginMm * S, r: cw - area.safeMarginMm * S, b: ch - area.safeMarginMm * S };
    var safeEl = document.getElementById('ged-safe');
    safeEl.style.cssText = 'left:' + safe.l + 'px;top:' + safe.t + 'px;width:' + (safe.r - safe.l) + 'px;height:' + (safe.b - safe.t) + 'px';

    function toast(msg, kind) {
        var t = document.getElementById('ged-toast');
        t.textContent = msg; t.dataset.kind = kind || 'info'; t.hidden = false;
        clearTimeout(toast._h); toast._h = setTimeout(function () { t.hidden = true; }, 6000);
    }

    function inside(o) {
        var r = o.getBoundingRect(true, true), e = 0.5;
        return r.left >= safe.l - e && r.top >= safe.t - e && r.left + r.width <= safe.r + e && r.top + r.height <= safe.b + e;
    }
    function remember(o) { o._ok = { left: o.left, top: o.top, scaleX: o.scaleX, scaleY: o.scaleY, angle: o.angle, width: o.width, fontSize: o.fontSize }; }
    function clamp(o) { if (inside(o)) { remember(o); } else if (o._ok) { o.set(o._ok); o.setCoords(); } }

    function invalidatePreview() {
        contentHash = null;
        document.getElementById('ged-approve').disabled = true;
    }

    canvas.on('object:moving', function (e) { clamp(e.target); });
    canvas.on('object:scaling', function (e) { clamp(e.target); });
    canvas.on('object:rotating', function (e) { clamp(e.target); });
    canvas.on('object:modified', function (e) {
        var o = e.target;
        if (o.type === 'textbox') { // le redimensionnement d'un texte change la taille de police, pas un étirement
            o.set({ fontSize: o.fontSize * o.scaleY, width: o.width * o.scaleX, scaleX: 1, scaleY: 1 }); o.setCoords();
            if (!inside(o) && o._ok) { o.set(o._ok); o.setCoords(); } else { remember(o); }
            document.getElementById('ged-size').value = Math.round(o.fontSize / S / MM_PER_PT);
        }
        invalidatePreview(); canvas.requestRenderAll();
    });

    function place(o) { // centre dans la zone de sécurité, rétrécit si trop grand
        canvas.add(o); o.setCoords();
        var maxW = safe.r - safe.l, maxH = safe.b - safe.t, r = o.getBoundingRect(true, true);
        var k = Math.min(1, maxW / r.width, maxH / r.height);
        if (k < 1) { o.scale(o.scaleX * k); }
        o.set({ left: (safe.l + safe.r) / 2, top: (safe.t + safe.b) / 2 }); o.setCoords();
        remember(o); canvas.setActiveObject(o); invalidatePreview(); canvas.requestRenderAll();
    }

    function fontPx(pt) { return pt * MM_PER_PT * S; }

    document.getElementById('ged-add-text').addEventListener('click', function () {
        var text = document.getElementById('ged-text').value.trim();
        if (!text) { toast('Écris d\'abord un texte.', 'error'); return; }
        var fam = document.getElementById('ged-font').value, pt = parseFloat(document.getElementById('ged-size').value) || 48;
        document.fonts.load('16px "' + fam + '"').then(function () {
            place(new fabric.Textbox(text, {
                fontFamily: fam, fontSize: fontPx(pt), fill: document.getElementById('ged-color').value,
                width: (safe.r - safe.l) * 0.8, originX: 'center', originY: 'center', textAlign: 'left',
                lockScalingFlip: true, editable: true
            }));
        });
    });

    // Modifie la sélection (police, taille, couleur)
    function onStyle() {
        var o = canvas.getActiveObject(); if (!o || o.type !== 'textbox') { return; }
        o.set({ fontFamily: document.getElementById('ged-font').value, fontSize: fontPx(parseFloat(document.getElementById('ged-size').value) || 48), fill: document.getElementById('ged-color').value });
        o.initDimensions(); o.setCoords();
        if (inside(o)) { remember(o); } else if (o._ok) { o.set(o._ok); o.initDimensions(); o.setCoords(); toast('Hors de la zone de sécurité : modification annulée.', 'error'); }
        invalidatePreview(); canvas.requestRenderAll();
    }
    ['ged-font', 'ged-size', 'ged-color'].forEach(function (id) { document.getElementById(id).addEventListener('change', onStyle); });
    canvas.on('text:changed', function (e) { clamp(e.target); invalidatePreview(); });

    document.getElementById('ged-delete').addEventListener('click', function () {
        var o = canvas.getActiveObject(); if (o) { canvas.remove(o); invalidatePreview(); }
    });

    document.getElementById('ged-file').addEventListener('change', function (ev) {
        var f = ev.target.files[0]; if (!f) { return; }
        var fd = new FormData(); fd.append('image', f);
        fetch(root.dataset.uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
            .then(api).then(function (j) {
                var url = URL.createObjectURL(f);
                fabric.Image.fromURL(url, function (img) {
                    img.set({ originX: 'center', originY: 'center', assetHash: j.assetHash });
                    img.scaleToWidth(Math.min((safe.r - safe.l) * 0.6, img.width));
                    place(img); toast('Image ajoutée.', 'ok');
                });
            }).catch(function (e) { toast(e.message, 'error'); });
        ev.target.value = '';
    });

    function api(res) {
        return res.json().catch(function () { return {}; }).then(function (j) {
            if (!res.ok) {
                var m = (j.error && j.error.message) || j.message || 'Erreur ' + res.status;
                if (j.errors) { m = Object.values(j.errors)[0][0] || m; }
                throw new Error(m);
            }
            return j;
        });
    }

    // Export de la SPEC déclarative (jamais un pixel) : mm, coin haut-gauche, rotation autour du centre.
    function buildSpec() {
        return { elements: canvas.getObjects().map(function (o) {
            var c = o.getCenterPoint(), el;
            if (o.type === 'textbox') {
                var w = o.width / S, h = o.height / S;
                el = { type: 'text', content: o.text, fontFamily: o.fontFamily, fontSizePt: Math.round(o.fontSize / S / MM_PER_PT * 10) / 10,
                       colorHex: o.fill, xMm: c.x / S - w / 2, yMm: c.y / S - h / 2, maxWidthMm: w, align: o.textAlign || 'left' };
            } else {
                var iw = o.getScaledWidth() / S, ih = o.getScaledHeight() / S;
                el = { type: 'image', assetHash: o.assetHash, xMm: c.x / S - iw / 2, yMm: c.y / S - ih / 2, widthMm: iw, heightMm: ih };
            }
            if (o.angle) { el.rotationDeg = o.angle; }
            return el;
        }) };
    }

    document.getElementById('ged-render').addEventListener('click', function () {
        if (!canvas.getObjects().length) { toast('Ajoute un texte ou une image.', 'error'); return; }
        canvas.discardActiveObject(); canvas.requestRenderAll();
        fetch(root.dataset.renderUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ variant_uid: document.getElementById('ged-variant').value, design_id: designId, spec: buildSpec() }) })
            .then(api).then(function (j) {
                designId = j.design_id; contentHash = j.content_hash;
                var box = document.getElementById('ged-mockup');
                document.getElementById('ged-mockup-img').src = j.mockup_url || '';
                box.hidden = false; document.getElementById('ged-approve').disabled = false;
                toast('Aperçu du serveur prêt : vérifie-le avant d\'approuver.', 'ok');
            }).catch(function (e) { toast(e.message, 'error'); });
    });

    document.getElementById('ged-approve').addEventListener('click', function () {
        if (!designId || !contentHash) { return; }
        fetch(root.dataset.approveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ design_id: designId, content_hash: contentHash }) })
            .then(api).then(function (j) { window.location.href = j.cart_url; })
            .catch(function (e) { toast(e.message, 'error'); });
    });

    document.getElementById('ged-approve').disabled = true;
})();
</script>
@endpush
