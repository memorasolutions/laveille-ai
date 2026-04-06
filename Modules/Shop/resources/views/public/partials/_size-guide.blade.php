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
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background: #0B7285; color: #fff;">
                                    <th style="padding: 10px 16px; text-align: center;">Taille</th>
                                    <th style="padding: 10px 16px; text-align: center;">Longueur (cm)</th>
                                    <th style="padding: 10px 16px; text-align: center;">Largeur poitrine (cm)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="background: #f8fafc;"><td style="padding: 10px 16px; font-weight: 700; text-align: center; color: #1e293b;">S</td><td style="padding: 10px 16px; text-align: center; color: #475569;">71</td><td style="padding: 10px 16px; text-align: center; color: #475569;">46</td></tr>
                                <tr><td style="padding: 10px 16px; font-weight: 700; text-align: center; color: #1e293b;">M</td><td style="padding: 10px 16px; text-align: center; color: #475569;">74</td><td style="padding: 10px 16px; text-align: center; color: #475569;">51</td></tr>
                                <tr style="background: #f8fafc;"><td style="padding: 10px 16px; font-weight: 700; text-align: center; color: #1e293b;">L</td><td style="padding: 10px 16px; text-align: center; color: #475569;">76</td><td style="padding: 10px 16px; text-align: center; color: #475569;">56</td></tr>
                                <tr><td style="padding: 10px 16px; font-weight: 700; text-align: center; color: #1e293b;">XL</td><td style="padding: 10px 16px; text-align: center; color: #475569;">79</td><td style="padding: 10px 16px; text-align: center; color: #475569;">61</td></tr>
                                <tr style="background: #f8fafc;"><td style="padding: 10px 16px; font-weight: 700; text-align: center; color: #1e293b;">2XL</td><td style="padding: 10px 16px; text-align: center; color: #475569;">81</td><td style="padding: 10px 16px; text-align: center; color: #475569;">66</td></tr>
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
            <p style="margin-bottom: 0;">Produit imprimé à la demande au Canada. Expédition sous 3 à 5 jours ouvrables. Livraison standard avec suivi.</p>
        </div>
    </div>
</div>
