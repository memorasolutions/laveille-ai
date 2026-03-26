import { YoutubeTranscript } from 'youtube-transcript/dist/youtube-transcript.esm.js';

var url = process.argv[2];
var lang = process.argv[3] || 'fr';

function getVideoId(input) {
    if (!input) return null;
    if (/^[a-zA-Z0-9_-]{11}$/.test(input)) return input;
    try {
        var u = new URL(input);
        if (u.hostname === 'youtu.be') return (u.pathname || '').slice(1).split('/')[0] || null;
        if (/(^|\.)youtube\.com$/.test(u.hostname)) {
            var v = u.searchParams.get('v');
            if (v) return v;
            var parts = (u.pathname || '').split('/').filter(Boolean);
            var i = parts.indexOf('embed') >= 0 ? parts.indexOf('embed') : parts.indexOf('shorts');
            if (i >= 0 && parts[i + 1]) return parts[i + 1];
        }
    } catch (e) {}
    return null;
}

var video_id = getVideoId(url);
if (!video_id) {
    console.log(JSON.stringify({ success: false, error: 'URL YouTube invalide', video_id: null }));
    process.exit(0);
}

try {
    var segments = await YoutubeTranscript.fetchTranscript(video_id, { lang: lang });
    if (!segments || !segments.length) throw new Error('Aucun transcript disponible');
    var transcript = segments.map(function (s) { return (s.text || '').trim(); }).filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
    console.log(JSON.stringify({
        success: true,
        video_id: video_id,
        transcript: transcript,
        segments: segments.map(function (s) { return { text: s.text, offset: s.offset, duration: s.duration }; })
    }));
} catch (e) {
    console.log(JSON.stringify({ success: false, error: (e && e.message) ? e.message : String(e), video_id: video_id }));
}
