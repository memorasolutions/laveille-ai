<div x-data="{ open: true }" style="background-color: #f1f5f9; padding: 20px; border-radius: 8px; margin-top: 20px;">
    <button
        @click="open = !open"
        style="width: 100%; text-align: left; background-color: #0B7285; color: white; border: none; padding: 15px; font-size: 18px; border-radius: 4px; cursor: pointer;"
    >
        <span style="font-weight: bold;">Guide des tailles</span>
        <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="float: right;"></i>
    </button>

    <div x-show="open" x-cloak style="margin-top: 20px;">
        @if($product->category === 't-shirts')
            <div style="color: #475569; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #0B7285;">T-shirt unisex</h4>
                <p><strong>Matériau :</strong> 100 % coton prérétréci, 153 g/m²</p>
                <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 0 0 220px;">
                        <img src="/images/shop/tshirt-size-guide.svg" alt="Comment mesurer un t-shirt" style="width: 100%; height: auto;">
                    </div>
                    <div style="flex: 1; min-width: 280px;">
                        <table style="width:100%; border-collapse:separate; border-spacing:0; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden; font-size:14px; background:#fff;">
                            <thead>
                                <tr>
                                    <th style="background:#0B7285; color:#fff; padding:10px 16px; text-align:center; border-right:1px solid rgba(255,255,255,0.15);">Taille</th>
                                    <th style="background:#0B7285; color:#fff; padding:10px 16px; text-align:center; border-right:1px solid rgba(255,255,255,0.15);">Longueur (cm / po)</th>
                                    <th style="background:#0B7285; color:#fff; padding:10px 16px; text-align:center;">Largeur poitrine (cm / po)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background:#f8fafc;"><td style="padding:10px 16px; text-align:center; font-weight:700; color:#1e293b; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">S</td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">71 <span style="color:#94a3b8; font-size:12px;">(28 po)</span></td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0;">46 <span style="color:#94a3b8; font-size:12px;">(18 po)</span></td></tr>
                                <tr><td style="padding:10px 16px; text-align:center; font-weight:700; color:#1e293b; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">M</td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">74 <span style="color:#94a3b8; font-size:12px;">(29 po)</span></td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0;">51 <span style="color:#94a3b8; font-size:12px;">(20 po)</span></td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:10px 16px; text-align:center; font-weight:700; color:#1e293b; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">L</td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">76 <span style="color:#94a3b8; font-size:12px;">(30 po)</span></td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0;">56 <span style="color:#94a3b8; font-size:12px;">(22 po)</span></td></tr>
                                <tr><td style="padding:10px 16px; text-align:center; font-weight:700; color:#1e293b; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">XL</td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">79 <span style="color:#94a3b8; font-size:12px;">(31 po)</span></td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0;">61 <span style="color:#94a3b8; font-size:12px;">(24 po)</span></td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:10px 16px; text-align:center; font-weight:700; color:#1e293b; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">2XL</td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">81 <span style="color:#94a3b8; font-size:12px;">(32 po)</span></td><td style="padding:10px 16px; text-align:center; color:#475569; border-top:1px solid #e2e8f0;">66 <span style="color:#94a3b8; font-size:12px;">(26 po)</span></td></tr>
                            </tbody>
                        </table>
                        <p style="font-size: 12px; color: #94a3b8; font-style: italic; margin-top: 8px;">Mesures prises à plat. Tolérance ± 2 cm.</p>
                    </div>
                </div>
            </div>
        @elseif($product->category === 'mugs')
            <div style="color: #475569; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #0B7285;">Tasse en céramique 11 oz</h4>
                <p><strong>Matériau :</strong> céramique de qualité supérieure</p>
                <ul style="padding-left: 20px;">
                    <li>Hauteur : 9,8 cm</li>
                    <li>Diamètre : 8,5 cm</li>
                    <li>Contenance : 325 ml</li>
                    <li>Compatible lave-vaisselle et micro-ondes</li>
                </ul>
            </div>
        @elseif($product->category === 'tote-bags')
            <div style="color: #475569; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #0B7285;">Sac fourre-tout en coton</h4>
                <p><strong>Matériau :</strong> 100 % coton</p>
                <ul style="padding-left: 20px;">
                    <li>Dimensions : environ 42 × 38 cm</li>
                    <li>Poignées : environ 30 cm</li>
                </ul>
            </div>
        @endif

        <div style="color: #475569; padding: 15px; background-color: white; border-radius: 4px; margin-top: 15px;">
            <h4 style="margin-top: 0; color: #0B7285;">
                <i class="fa fa-truck" style="margin-right: 10px;"></i> Livraison
            </h4>
            <p style="margin-bottom: 0;">{{ __('Les délais et frais de livraison sont calculés au moment de la commande selon votre destination. Production dans plus de 30 pays.') }}</p>
        </div>
    </div>
</div>
