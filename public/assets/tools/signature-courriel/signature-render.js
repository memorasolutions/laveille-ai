/*
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Moteur de rendu CANONIQUE de l'aperçu, de la copie et du téléchargement (section 4 du plan) :
 * une seule fonction, renderSignature(state), appelée trois fois avec la même entrée.
 *
 * REFONTE LOT 2 (2026-09-25) : ce moteur ne connaît plus AUCUN gabarit par son nom. Il lit
 * l'agencement (`layout` et ses propriétés) dans window.SIGNATURE_TEMPLATES - le registre PHP
 * (Modules\Signature\Services\SignatureTemplateRegistry) sérialisé en JSON par le contrôleur qui
 * sert l'éditeur (voir Modules/Signature/resources/views/public/partials/editor.blade.php).
 * Ajouter un gabarit ne demande donc plus de fonction JS dédiée : seule la définition PHP change.
 *
 * Composants (blocImage, blocIdentite, blocContact, blocReseaux, blocMentions, blocCta,
 * blocBanniere) et assembleurs (assembleStandard, assembleCompact, assembleVertical) sont le
 * jumeau exact des méthodes du même nom dans SignatureRenderer.php - toute modification d'un
 * gabarit doit être répercutée dans LES DEUX fichiers (voir le docblock PHP pour l'écart assumé :
 * deux moteurs, un par runtime, jamais un seul across langages).
 *
 * LOT 3 (2026-09-25) : pronoms, forme du portrait, échelle de police, sécurité mode sombre et
 * renforcement du gabarit `social` - jumeau exact du docblock de classe de SignatureRenderer.php,
 * voir ce fichier pour le détail de chaque décision.
 */
(function (global) {
    'use strict';

    var FONT_STACK = {
        Arial: "Arial, Helvetica, sans-serif",
        Helvetica: "Helvetica, Arial, sans-serif",
        Verdana: "Verdana, Geneva, sans-serif",
        Georgia: "Georgia, 'Times New Roman', serif",
        Tahoma: "Tahoma, Geneva, sans-serif"
    };

    var SOCIAL_ICONS = { linkedin: '💼', facebook: '📘', instagram: '📸', x: '✖️', youtube: '▶️', website: '🌐' };
    var SOCIAL_LABELS = { linkedin: 'LinkedIn', facebook: 'Facebook', instagram: 'Instagram', x: 'X', youtube: 'YouTube', website: 'Site web' };

    // LOT 3 - jumeau exact de SignatureRenderer::FONT_SCALE_FACTORS/PORTRAIT_SHAPES.
    var FONT_SCALE_FACTORS = { petite: 0.9, moyenne: 1.0, grande: 1.15 };
    var PORTRAIT_SHAPES = ['carre', 'rond'];

    // Jumeau du gabarit `minimal` de SignatureTemplateRegistry - dernier repli si
    // window.SIGNATURE_TEMPLATES n'a pas été injecté par la vue (défense en profondeur, jamais
    // rencontré en usage normal : les 3 contrôleurs qui servent l'éditeur l'injectent toujours).
    var FALLBACK_DEF = {
        layout: 'standard', name_size: '15px', image_role: 'logo', image_alt: 'logo_fullname',
        image_style: '', image_side: 'left', image_gap: '10px', valign: 'middle',
        frame_border_top: null, text_divider_with_image: true, cell_padding_top: null
    };

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = String(value == null ? '' : value).trim();
        return div.innerHTML;
    }

    function isSafeUrl(url) {
        url = String(url == null ? '' : url).trim();
        if (url === '') { return true; }
        try {
            var parsed = new URL(url, window.location.origin);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) {
            return false;
        }
    }

    function safeHex(hex) {
        hex = String(hex == null ? '' : hex).trim();
        return /^#[0-9A-Fa-f]{6}$/.test(hex) ? hex : null;
    }

    function normalize(content) {
        content = content || {};
        return {
            first_name: String(content.first_name || '').trim(),
            last_name: String(content.last_name || '').trim(),
            job_title: String(content.job_title || '').trim(),
            organization: String(content.organization || '').trim(),
            email: String(content.email || '').trim(),
            phone: String(content.phone || '').trim(),
            mobile: String(content.mobile || '').trim(),
            website: isSafeUrl(content.website) ? String(content.website || '').trim() : '',
            address: String(content.address || '').trim(),
            tagline: String(content.tagline || '').trim(),
            cta_text: String(content.cta_text || '').trim(),
            cta_url: isSafeUrl(content.cta_url) ? String(content.cta_url || '').trim() : '',
            accent_color: content.accent_color || null,
            font_family: content.font_family || 'Arial',
            social_links: Array.isArray(content.social_links) ? content.social_links : [],
            mention_lines: Array.isArray(content.mention_lines) ? content.mention_lines : [],
            // LOT 3 (2026-09-25).
            pronouns: String(content.pronouns || '').trim(),
            portrait_shape: PORTRAIT_SHAPES.indexOf(content.portrait_shape) !== -1 ? content.portrait_shape : 'carre',
            font_scale: typeof content.font_scale === 'string' && FONT_SCALE_FACTORS.hasOwnProperty(content.font_scale) ? content.font_scale : 'moyenne'
        };
    }

    function fullName(f) { return (f.first_name + ' ' + f.last_name).trim(); }

    // LOT 3 - jumeau exact de SignatureRenderer::scalePx().
    function scalePx(sizePx, scale) {
        var base = parseInt(String(sizePx).replace(/\D+/g, ''), 10) || 0;
        return Math.max(1, Math.round(base * scale)) + 'px';
    }

    // LOT 3 - jumeau exact de SignatureRenderer::pronounsHtml().
    function pronounsHtml(f, scale) {
        if (!f.pronouns) { return ''; }
        return ' <span style="font-weight:normal;font-size:' + scalePx('12px', scale) + ';color:#374151;">(' + esc(f.pronouns) + ')</span>';
    }

    // LOT 3 - jumeau exact de SignatureRenderer::resolveImageStyle().
    function resolveImageStyle(imageRole, def, f) {
        var style = def.image_style || '';
        if (imageRole === 'portrait' && f.portrait_shape === 'rond') { style += 'border-radius:50%;'; }
        return style;
    }

    function imageAlt(mode, f) {
        switch (mode) {
            case 'logo_organization': return 'Logo ' + f.organization;
            case 'portrait_fullname': return 'Photo de ' + fullName(f);
            // Bannière (LOT 2) : texte alternatif OBLIGATOIRE, jamais vide - voir SignatureRenderer::imageAlt().
            case 'banner_cta': return f.cta_text !== '' ? f.cta_text : 'Bannière de ' + fullName(f);
            default: return 'Logo ' + fullName(f);
        }
    }

    // ------------------------------------------------------------------
    // Composants de bloc (jumeaux exacts des méthodes bloc*() de SignatureRenderer.php)
    // ------------------------------------------------------------------

    function blocImage(image, altMode, extraStyle, f) {
        if (!image || !image.url || !image.width || !image.height) { return ''; }
        return '<img src="' + esc(image.url) + '" width="' + parseInt(image.width, 10) + '" height="' + parseInt(image.height, 10) +
            '" alt="' + esc(imageAlt(altMode, f)) + '" style="display:block;border:0;outline:none;text-decoration:none;' + (extraStyle || '') + '">';
    }

    // `emphasis` (LOT 2, gabarit `social`) : rendu EN AVANT. RENFORCÉ AU LOT 3 - chaque réseau sur
    // SA PROPRE ligne, icône nettement agrandie (changement de STRUCTURE, jamais utilisé par les 7
    // autres gabarits - sortie inchangée pour eux). Liens en couleur EXPLICITE (#374151), jamais
    // `color:inherit` - sécurité mode sombre (LOT 3).
    function blocReseaux(links, fontStack, accent, emphasis, fontScale) {
        links = (links || []).filter(function (l) { return l && l.url && isSafeUrl(l.url); });
        if (links.length === 0) { return ''; }

        if (!emphasis) {
            var parts = links.map(function (l) {
                var icon = SOCIAL_ICONS[l.platform] || '🔗';
                var label = SOCIAL_LABELS[l.platform] || esc(l.platform);
                return '<a href="' + esc(l.url) + '" style="color:#374151;text-decoration:none;font-family:' + fontStack + ';font-size:' + scalePx('12px', fontScale) + ';">' + icon + ' ' + label + '</a>';
            });
            return '<tr><td style="padding-top:6px;">' + parts.join(' &nbsp;·&nbsp; ') + '</td></tr>';
        }

        var accentSafe = esc(accent || '#064E5A');
        return links.map(function (l, index) {
            var icon = SOCIAL_ICONS[l.platform] || '🔗';
            var label = SOCIAL_LABELS[l.platform] || esc(l.platform);
            var cellStyle = index === 0 ? 'padding-top:10px;border-top:1px solid ' + accentSafe + ';' : 'padding-top:6px;';
            return '<tr><td style="' + cellStyle + '"><a href="' + esc(l.url) + '" style="color:#374151;text-decoration:none;font-family:' + fontStack + ';font-size:' + scalePx('13px', fontScale) + ';"><span style="font-size:' + scalePx('20px', fontScale) + ';">' + icon + '</span> ' + label + '</a></td></tr>';
        }).join('');
    }

    function blocMentions(lines, fontStack, fontScale) {
        lines = (lines || []).map(function (l) { return String(l || '').trim(); }).filter(function (l) { return l !== ''; }).slice(0, 6);
        // M4.5 : #374151 (deja utilise par blocContact/titleRow), jamais #6b7280 - sous le ratio
        // 7:1 AAA a cette taille.
        var size = scalePx('10px', fontScale);
        return lines.map(function (l) {
            return '<tr><td style="font-family:' + fontStack + ';font-size:' + size + ';color:#374151;padding-top:2px;">' + esc(l) + '</td></tr>';
        }).join('');
    }

    function blocCta(f, accent, fontStack, fontScale) {
        if (f.cta_text === '' || f.cta_url === '') { return ''; }
        return '<tr><td style="padding-top:8px;"><a href="' + esc(f.cta_url) + '" style="display:inline-block;padding:8px 14px;background-color:' + esc(accent) +
            ';color:#ffffff;font-family:' + fontStack + ';font-size:' + scalePx('12px', fontScale) + ';font-weight:bold;text-decoration:none;border-radius:4px;">' + esc(f.cta_text) + '</a></td></tr>';
    }

    // LOT 3 - liens en couleur EXPLICITE (#374151), jamais `color:inherit` - sécurité mode sombre.
    function blocContact(f, fontStack, fontScale) {
        var rows = '';
        var size = scalePx('12px', fontScale);
        function line(html) { rows += '<tr><td style="font-family:' + fontStack + ';font-size:' + size + ';color:#374151;padding-top:2px;">' + html + '</td></tr>'; }
        if (f.phone) { line('☎ ' + esc(f.phone)); }
        if (f.mobile) { line('📱 ' + esc(f.mobile)); }
        if (f.email) { line('✉ <a href="mailto:' + esc(f.email) + '" style="color:#374151;text-decoration:none;">' + esc(f.email) + '</a>'); }
        if (f.website) { line('🔗 <a href="' + esc(f.website) + '" style="color:#374151;text-decoration:none;">' + esc(f.website) + '</a>'); }
        if (f.address) { line('📍 ' + esc(f.address)); }
        return rows;
    }

    // `divider` (LOT 2, gabarit `executive`) ajoute un filet sous le nom - à faux, sortie
    // identique au LOT 1. Pronoms (LOT 3) ajoutés APRÈS le nom - voir pronounsHtml().
    function nameRow(f, accent, fontStack, size, divider, fontScale) {
        var style = 'font-family:' + fontStack + ';font-size:' + scalePx(size, fontScale) + ';font-weight:bold;color:' + esc(accent) + ';';
        style += divider ? 'padding-bottom:8px;border-bottom:2px solid ' + esc(accent) + ';' : 'padding-bottom:1px;';
        return '<tr><td style="' + style + '">' + esc(fullName(f)) + pronounsHtml(f, fontScale) + '</td></tr>';
    }

    function titleRow(f, fontStack, fontScale) {
        var bits = [f.job_title, f.organization].filter(Boolean);
        if (bits.length === 0) { return ''; }
        // Regle 10 : jamais de tiret cadratin - un simple tiret entoure d'espaces a la place.
        return '<tr><td style="font-family:' + fontStack + ';font-size:' + scalePx('13px', fontScale) + ';color:#374151;padding-bottom:4px;">' + esc(bits.join(' - ')) + '</td></tr>';
    }

    function blocIdentite(f, accent, fontStack, nameSize, nameDivider, fontScale) {
        return nameRow(f, accent, fontStack, nameSize, !!nameDivider, fontScale) + titleRow(f, fontStack, fontScale);
    }

    function blocTagline(f, fontStack, fontScale) {
        if (f.tagline === '') { return ''; }
        return '<tr><td style="font-family:' + fontStack + ';font-size:' + scalePx('11px', fontScale) + ';font-style:italic;color:#374151;padding-top:4px;">' + esc(f.tagline) + '</td></tr>';
    }

    // Bloc bannière (LOT 2, gabarit `banniere`) - jumeau de SignatureRenderer::blocBanniere().
    // Réutilise blocImage() (mode banner_cta) plutôt que de dupliquer la construction du <img>.
    function blocBanniere(image, f, ctaUrl) {
        var img = blocImage(image, 'banner_cta', 'max-width:100%;', f);
        if (img === '') { return ''; }
        var content = ctaUrl ? '<a href="' + esc(ctaUrl) + '" style="display:block;border:0;text-decoration:none;">' + img + '</a>' : img;
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;margin-top:10px;"><tr><td>' + content + '</td></tr></table>';
    }

    // LOT 3 - fond blanc EXPLICITE (sécurité mode sombre) - jumeau exact de SignatureRenderer::wrap().
    function wrap(inner) {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;"><tr><td style="background-color:#ffffff;">' + inner + '</td></tr></table>';
    }

    function blocColonneTexte(f, accent, fontStack, nameSize, socialEmphasis, nameDivider, fontScale) {
        var html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        html += blocIdentite(f, accent, fontStack, nameSize, nameDivider, fontScale);
        html += blocContact(f, fontStack, fontScale);
        html += blocTagline(f, fontStack, fontScale);
        html += blocCta(f, accent, fontStack, fontScale);
        html += blocReseaux(f.social_links, fontStack, accent, !!socialEmphasis, fontScale);
        html += blocMentions(f.mention_lines, fontStack, fontScale);
        html += '</table>';
        return html;
    }

    // LOT 3 - pronoms insérés APRÈS le nom (jamais après le poste/organisation) : nom et bits
    // échappés séparément - jumeau exact de SignatureRenderer::blocEnTeteCompact().
    function blocEnTeteCompact(f, accent, fontStack, fontScale) {
        var bits = [f.job_title, f.organization].filter(Boolean);
        // Regle 10 : jamais de tiret cadratin.
        var header = esc(fullName(f)) + pronounsHtml(f, fontScale) + (bits.length ? ' - ' + esc(bits.join(' - ')) : '');
        return '<tr><td style="font-family:' + fontStack + ';font-size:' + scalePx('13px', fontScale) + ';font-weight:bold;color:' + esc(accent) + ';">' + header + '</td></tr>';
    }

    // LOT 3 - liens en couleur EXPLICITE (#374151), jamais `color:inherit` - sécurité mode sombre.
    function blocContactCompact(f, fontStack, fontScale) {
        var contact = [
            f.phone ? '☎ ' + esc(f.phone) : '',
            f.email ? '✉ <a href="mailto:' + esc(f.email) + '" style="color:#374151;text-decoration:none;">' + esc(f.email) + '</a>' : '',
            f.website ? '🔗 <a href="' + esc(f.website) + '" style="color:#374151;text-decoration:none;">' + esc(f.website) + '</a>' : ''
        ].filter(Boolean);
        if (!contact.length) { return ''; }
        return '<tr><td style="font-family:' + fontStack + ';font-size:' + scalePx('11px', fontScale) + ';color:#374151;">' + contact.join(' &nbsp;|&nbsp; ') + '</td></tr>';
    }

    // ------------------------------------------------------------------
    // Assembleurs (un par famille d'agencement du registre - jumeaux exacts de SignatureRenderer.php)
    // ------------------------------------------------------------------

    function assembleStandard(def, f, images, accent, fontStack, fontScale) {
        var imageRole = def.image_role || null;
        var image = imageRole ? blocImage(images[imageRole], def.image_alt, resolveImageStyle(imageRole, def, f), f) : '';

        var paddingTop = def.cell_padding_top ? 'padding-top:' + def.cell_padding_top + ';' : '';
        var gapSide = def.image_side === 'left' ? 'padding-right:' : 'padding-left:';
        var imageCellStyle = paddingTop + gapSide + (def.image_gap || '0') + ';vertical-align:' + (def.valign || 'top') + ';';

        var textCellStyle = paddingTop + 'vertical-align:' + (def.valign || 'top') + ';';
        if (def.text_divider_with_image) {
            textCellStyle += image !== '' ? 'border-left:2px solid ' + esc(accent) + ';padding-left:10px;' : 'border-left:0;';
        }

        var textColumn = blocColonneTexte(f, accent, fontStack, def.name_size, def.social_emphasis, def.name_divider, fontScale);
        var imageCell = image !== '' ? '<td style="' + imageCellStyle + '">' + image + '</td>' : '';
        var textCell = '<td style="' + textCellStyle + '">' + textColumn + '</td>';
        var cells = def.image_side === 'left' ? imageCell + textCell : textCell + imageCell;

        var tableAttrs = 'role="presentation" cellpadding="0" cellspacing="0" border="0"';
        if (def.frame_border_top) { tableAttrs += ' style="border-top:' + def.frame_border_top + ' solid ' + esc(accent) + ';"'; }

        return wrap('<table ' + tableAttrs + '><tr>' + cells + '</tr></table>');
    }

    function assembleCompact(def, f, images, accent, fontStack, fontScale) {
        var imageRole = def.image_role || null;
        var image = imageRole ? blocImage(images[imageRole], def.image_alt, resolveImageStyle(imageRole, def, f), f) : '';

        var inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if (image !== '') { inner += '<td style="padding-right:' + (def.image_gap || '8px') + ';vertical-align:middle;">' + image + '</td>'; }
        inner += '<td style="vertical-align:middle;">';
        inner += '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        inner += blocEnTeteCompact(f, accent, fontStack, fontScale);
        inner += blocContactCompact(f, fontStack, fontScale);
        inner += blocReseaux(f.social_links, fontStack, accent, false, fontScale);
        inner += '</table>';
        inner += '</td></tr></table>';

        return wrap(inner);
    }

    // Agencement `vertical` (LOT 2) : blocs EMPILÉS - image, puis identité, contact, accroche,
    // réseaux, mentions et CTA. Bon pour mobile et les signatures longues.
    function assembleVertical(def, f, images, accent, fontStack, fontScale) {
        var imageRole = def.image_role || null;
        var image = imageRole ? blocImage(images[imageRole], def.image_alt, resolveImageStyle(imageRole, def, f), f) : '';

        var html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        if (image !== '') { html += '<tr><td style="padding-bottom:' + (def.image_gap || '10px') + ';">' + image + '</td></tr>'; }
        html += blocIdentite(f, accent, fontStack, def.name_size || '16px', def.name_divider, fontScale);
        html += blocContact(f, fontStack, fontScale);
        html += blocTagline(f, fontStack, fontScale);
        html += blocReseaux(f.social_links, fontStack, accent, !!def.social_emphasis, fontScale);
        html += blocMentions(f.mention_lines, fontStack, fontScale);
        html += blocCta(f, accent, fontStack, fontScale);
        html += '</table>';

        return wrap(html);
    }

    function renderSignature(state) {
        var registry = global.SIGNATURE_TEMPLATES || {};
        var def = registry[state.template] || FALLBACK_DEF;
        var f = normalize(state.content || {});
        var accent = safeHex(f.accent_color) || '#064E5A';
        var fontStack = FONT_STACK[f.font_family] || FONT_STACK.Arial;
        // LOT 3 : facteur d'échelle - 1.0 (moyenne, défaut) ne change rien à la sortie.
        var fontScale = FONT_SCALE_FACTORS[f.font_scale] || 1.0;
        var images = state.images || {};

        var body;
        switch (def.layout) {
            case 'compact': body = assembleCompact(def, f, images, accent, fontStack, fontScale); break;
            case 'vertical': body = assembleVertical(def, f, images, accent, fontStack, fontScale); break;
            default: body = assembleStandard(def, f, images, accent, fontStack, fontScale);
        }

        // Propriété TRANSVERSE (LOT 2) : n'importe quel agencement peut porter une bannière
        // cliquable en plus de son corps habituel - voir SignatureRenderer::render().
        if (def.banner_role) {
            body += blocBanniere(images[def.banner_role], f, f.cta_url);
        }

        return body;
    }

    global.renderSignature = renderSignature;
    global.signatureIsSafeUrl = isSafeUrl;
})(window);
