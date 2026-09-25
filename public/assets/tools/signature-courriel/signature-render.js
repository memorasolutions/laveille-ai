/*
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Moteur de rendu CANONIQUE de l'aperçu, de la copie et du téléchargement (section 4 du plan) :
 * une seule fonction, renderSignature(state), appelée trois fois avec la même entrée. Jumeau
 * volontaire de Modules\Signature\Services\SignatureRenderer.php (même contrat de champs, mêmes
 * 4 gabarits, même échappement, mêmes règles de dimensionnement) - voir le docblock de ce fichier
 * PHP pour l'écart assumé au plan (deux moteurs, un par runtime, jamais un seul across langages).
 * Toute modification d'un gabarit doit être répercutée dans LES DEUX fichiers.
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
            mention_lines: Array.isArray(content.mention_lines) ? content.mention_lines : []
        };
    }

    function fullName(f) { return (f.first_name + ' ' + f.last_name).trim(); }

    function imgTag(image, alt, extraStyle) {
        if (!image || !image.url || !image.width || !image.height) { return ''; }
        return '<img src="' + esc(image.url) + '" width="' + parseInt(image.width, 10) + '" height="' + parseInt(image.height, 10) +
            '" alt="' + esc(alt) + '" style="display:block;border:0;outline:none;text-decoration:none;' + (extraStyle || '') + '">';
    }

    function socialRow(links, fontStack) {
        links = (links || []).filter(function (l) { return l && l.url && isSafeUrl(l.url); });
        if (links.length === 0) { return ''; }
        var parts = links.map(function (l) {
            var icon = SOCIAL_ICONS[l.platform] || '🔗';
            var label = SOCIAL_LABELS[l.platform] || esc(l.platform);
            return '<a href="' + esc(l.url) + '" style="color:inherit;text-decoration:none;font-family:' + fontStack + ';font-size:12px;">' + icon + ' ' + label + '</a>';
        });
        return '<tr><td style="padding-top:6px;">' + parts.join(' &nbsp;·&nbsp; ') + '</td></tr>';
    }

    function mentionRows(lines, fontStack) {
        lines = (lines || []).map(function (l) { return String(l || '').trim(); }).filter(function (l) { return l !== ''; }).slice(0, 6);
        // M4.5 : #374151 (deja utilise par contactRows/titleRow), jamais #6b7280 - sous le ratio
        // 7:1 AAA a cette taille.
        return lines.map(function (l) {
            return '<tr><td style="font-family:' + fontStack + ';font-size:10px;color:#374151;padding-top:2px;">' + esc(l) + '</td></tr>';
        }).join('');
    }

    function ctaRow(f, accent, fontStack) {
        if (f.cta_text === '' || f.cta_url === '') { return ''; }
        return '<tr><td style="padding-top:8px;"><a href="' + esc(f.cta_url) + '" style="display:inline-block;padding:8px 14px;background-color:' + esc(accent) +
            ';color:#ffffff;font-family:' + fontStack + ';font-size:12px;font-weight:bold;text-decoration:none;border-radius:4px;">' + esc(f.cta_text) + '</a></td></tr>';
    }

    function contactRows(f, fontStack) {
        var rows = '';
        function line(html) { rows += '<tr><td style="font-family:' + fontStack + ';font-size:12px;color:#374151;padding-top:2px;">' + html + '</td></tr>'; }
        if (f.phone) { line('☎ ' + esc(f.phone)); }
        if (f.mobile) { line('📱 ' + esc(f.mobile)); }
        if (f.email) { line('✉ <a href="mailto:' + esc(f.email) + '" style="color:inherit;text-decoration:none;">' + esc(f.email) + '</a>'); }
        if (f.website) { line('🔗 <a href="' + esc(f.website) + '" style="color:inherit;text-decoration:none;">' + esc(f.website) + '</a>'); }
        if (f.address) { line('📍 ' + esc(f.address)); }
        return rows;
    }

    function nameRow(f, accent, fontStack, size) {
        return '<tr><td style="font-family:' + fontStack + ';font-size:' + size + ';font-weight:bold;color:' + esc(accent) + ';padding-bottom:1px;">' + esc(fullName(f)) + '</td></tr>';
    }

    function titleRow(f, fontStack) {
        var bits = [f.job_title, f.organization].filter(Boolean);
        if (bits.length === 0) { return ''; }
        // Regle 10 : jamais de tiret cadratin - un simple tiret entoure d'espaces a la place.
        return '<tr><td style="font-family:' + fontStack + ';font-size:13px;color:#374151;padding-bottom:4px;">' + esc(bits.join(' - ')) + '</td></tr>';
    }

    function taglineRow(f, fontStack) {
        if (f.tagline === '') { return ''; }
        return '<tr><td style="font-family:' + fontStack + ';font-size:11px;font-style:italic;color:#374151;padding-top:4px;">' + esc(f.tagline) + '</td></tr>';
    }

    function wrap(inner) {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;"><tr><td>' + inner + '</td></tr></table>';
    }

    function textColumn(f, accent, fontStack, nameSize) {
        var html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        html += nameRow(f, accent, fontStack, nameSize);
        html += titleRow(f, fontStack);
        html += contactRows(f, fontStack);
        html += taglineRow(f, fontStack);
        html += ctaRow(f, accent, fontStack);
        html += socialRow(f.social_links, fontStack);
        html += mentionRows(f.mention_lines, fontStack);
        html += '</table>';
        return html;
    }

    function renderMinimal(f, images, accent, fontStack) {
        var logo = imgTag(images.logo, 'Logo ' + fullName(f));
        var inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if (logo) { inner += '<td style="padding-right:10px;vertical-align:middle;">' + logo + '</td>'; }
        inner += '<td style="vertical-align:middle;border-left:' + (logo ? '2px solid ' + esc(accent) + ';padding-left:10px;' : '0;') + '">';
        inner += textColumn(f, accent, fontStack, '15px');
        inner += '</td></tr></table>';
        return wrap(inner);
    }

    function renderProfessionnel(f, images, accent, fontStack) {
        var logo = imgTag(images.logo, 'Logo ' + f.organization);
        var inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-top:3px solid ' + esc(accent) + ';"><tr>';
        inner += '<td style="padding-top:10px;vertical-align:top;">' + textColumn(f, accent, fontStack, '17px') + '</td>';
        if (logo) { inner += '<td style="padding-top:10px;padding-left:16px;vertical-align:top;">' + logo + '</td>'; }
        inner += '</tr></table>';
        return wrap(inner);
    }

    function renderPortrait(f, images, accent, fontStack) {
        var portrait = imgTag(images.portrait, 'Photo de ' + fullName(f), 'border-radius:6px;');
        var inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if (portrait) { inner += '<td style="padding-right:14px;vertical-align:top;">' + portrait + '</td>'; }
        inner += '<td style="vertical-align:top;">' + textColumn(f, accent, fontStack, '16px') + '</td></tr></table>';
        return wrap(inner);
    }

    function renderCompact(f, images, accent, fontStack) {
        var logo = imgTag(images.logo, 'Logo ' + f.organization, 'max-width:80px;');
        var bits = [f.job_title, f.organization].filter(Boolean);
        // Regle 10 : jamais de tiret cadratin.
        var header = esc((fullName(f) + (bits.length ? ' - ' + bits.join(' - ') : '')).trim());
        var inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if (logo) { inner += '<td style="padding-right:8px;vertical-align:middle;">' + logo + '</td>'; }
        inner += '<td style="vertical-align:middle;"><table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        inner += '<tr><td style="font-family:' + fontStack + ';font-size:13px;font-weight:bold;color:' + esc(accent) + ';">' + header + '</td></tr>';
        var contact = [
            f.phone ? '☎ ' + esc(f.phone) : '',
            f.email ? '✉ <a href="mailto:' + esc(f.email) + '" style="color:inherit;text-decoration:none;">' + esc(f.email) + '</a>' : '',
            f.website ? '🔗 <a href="' + esc(f.website) + '" style="color:inherit;text-decoration:none;">' + esc(f.website) + '</a>' : ''
        ].filter(Boolean);
        if (contact.length) {
            inner += '<tr><td style="font-family:' + fontStack + ';font-size:11px;color:#374151;">' + contact.join(' &nbsp;|&nbsp; ') + '</td></tr>';
        }
        inner += socialRow(f.social_links, fontStack);
        inner += '</table></td></tr></table>';
        return wrap(inner);
    }

    var TEMPLATES = ['minimal', 'professionnel', 'portrait', 'compact'];

    function renderSignature(state) {
        var template = TEMPLATES.indexOf(state.template) !== -1 ? state.template : 'minimal';
        var f = normalize(state.content || {});
        var accent = safeHex(f.accent_color) || '#064E5A';
        var fontStack = FONT_STACK[f.font_family] || FONT_STACK.Arial;
        var images = state.images || {};

        switch (template) {
            case 'professionnel': return renderProfessionnel(f, images, accent, fontStack);
            case 'portrait': return renderPortrait(f, images, accent, fontStack);
            case 'compact': return renderCompact(f, images, accent, fontStack);
            default: return renderMinimal(f, images, accent, fontStack);
        }
    }

    global.renderSignature = renderSignature;
    global.signatureIsSafeUrl = isSafeUrl;
})(window);
