<div x-data="{ open: false }" style="background-color: #f1f5f9; padding: 20px; border-radius: 8px; margin-top: 20px;">
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
                <h4 style="margin-top: 0; color: #0B7285;">T-shirts Gildan 64000</h4>
                <p><strong>Materiau :</strong> 100 % coton</p>
                <table class="table table-bordered" style="background-color: white;">
                    <thead>
                        <tr style="background-color: #0B7285; color: white;">
                            <th>Taille</th>
                            <th>Longueur (cm)</th>
                            <th>Largeur poitrine (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>S</td><td>71</td><td>46</td></tr>
                        <tr><td>M</td><td>74</td><td>51</td></tr>
                        <tr><td>L</td><td>76</td><td>56</td></tr>
                        <tr><td>XL</td><td>79</td><td>61</td></tr>
                        <tr><td>2XL</td><td>81</td><td>66</td></tr>
                    </tbody>
                </table>
            </div>
        @elseif($product->category === 'mugs')
            <div style="color: #475569; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #0B7285;">Tasse ceramique 11 oz</h4>
                <p><strong>Materiau :</strong> ceramique</p>
                <ul style="padding-left: 20px;">
                    <li>Hauteur : 9,8 cm</li>
                    <li>Diametre : 8,5 cm</li>
                    <li>Contenance : 325 ml</li>
                </ul>
            </div>
        @elseif($product->category === 'tote-bags')
            <div style="color: #475569; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #0B7285;">Sac fourre-tout en coton</h4>
                <p><strong>Materiau :</strong> 100 % coton</p>
                <ul style="padding-left: 20px;">
                    <li>Dimensions : environ 42 x 38 cm</li>
                    <li>Poignees : environ 30 cm</li>
                </ul>
            </div>
        @endif

        <div style="color: #475569; padding: 15px; background-color: white; border-radius: 4px; margin-top: 15px;">
            <h4 style="margin-top: 0; color: #0B7285;">
                <i class="fa fa-truck" style="margin-right: 10px;"></i> Livraison
            </h4>
            <p style="margin-bottom: 0;">Produit imprime a la demande au Canada. Expedition sous 3-5 jours ouvrables. Livraison standard avec suivi.</p>
        </div>
    </div>
</div>
