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
                <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="flex: 0 0 200px;">
                        <img src="/images/shop/tshirt-size-guide.svg" alt="Comment mesurer un t-shirt : longueur et largeur poitrine" style="width: 200px; height: auto;">
                    </div>
                    <div style="flex: 0 1 auto;">
                        <table style="width: auto; border-collapse: collapse; font-size: 13px; background-color: white;">
                            <thead>
                                <tr style="background-color: #0B7285; color: white;">
                                    <th style="padding: 8px;">Taille</th>
                                    <th style="padding: 8px;">Longueur (cm)</th>
                                    <th style="padding: 8px;">Largeur poitrine (cm)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td style="padding: 8px; font-weight: 700;">S</td><td style="padding: 8px;">71</td><td style="padding: 8px;">46</td></tr>
                                <tr><td style="padding: 8px; font-weight: 700;">M</td><td style="padding: 8px;">74</td><td style="padding: 8px;">51</td></tr>
                                <tr><td style="padding: 8px; font-weight: 700;">L</td><td style="padding: 8px;">76</td><td style="padding: 8px;">56</td></tr>
                                <tr><td style="padding: 8px; font-weight: 700;">XL</td><td style="padding: 8px;">79</td><td style="padding: 8px;">61</td></tr>
                                <tr><td style="padding: 8px; font-weight: 700;">2XL</td><td style="padding: 8px;">81</td><td style="padding: 8px;">66</td></tr>
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
