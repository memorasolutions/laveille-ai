{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    D11 - Gabarit PDF AUTONOME du certificat (rendu par dompdf, jamais le thème).
    dompdf ne gère ni les variables CSS, ni color-mix(), ni les feuilles externes :
    tout est en CSS « simple » inline, couleurs résolues côté PHP. Anti-XSS : nom,
    titres et signature échappés via {{ }} ; le message rich-text est déjà nettoyé
    (html_input=strip) en amont, rendu via {!! !!}.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Certificat - {{ $certificate->course->title }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 28px;
            font-family: DejaVu Sans, sans-serif;
            color: #1A1D23;
            background: #ffffff;
        }
        .card {
            border: 3px solid {{ $accent }};
            border-radius: 14px;
            padding: 40px 36px;
            text-align: center;
        }
        .logo {
            color: {{ $accent }};
            font-size: 26px;
            font-weight: bold;
            letter-spacing: -1px;
            margin-bottom: 4px;
        }
        .label {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #6B7280;
            margin-bottom: 22px;
        }
        .badge {
            display: inline-block;
            border: 1px solid {{ $accent }};
            color: {{ $accent }};
            border-radius: 100px;
            padding: 5px 16px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 22px;
        }
        .muted { font-size: 13px; color: #6B7280; margin: 4px 0; }
        .name {
            font-size: 30px;
            font-weight: bold;
            color: {{ $accent }};
            margin: 4px 0 14px;
        }
        .course-title {
            font-size: 20px;
            font-weight: bold;
            color: #1A1D23;
            margin: 4px 0 22px;
        }
        .message { font-size: 13px; color: #6B7280; margin: 0 auto 18px; }
        .divider { border: none; border-top: 1px solid #E5E7EB; margin: 18px 0; }
        .meta-table { width: 100%; margin: 0 auto 14px; }
        .meta-cell { text-align: center; }
        .meta-label {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6B7280;
        }
        .meta-value { font-size: 13px; font-weight: bold; color: #374151; }
        .signature-line {
            width: 200px;
            border-top: 1px solid {{ $accent }};
            margin: 26px auto 6px;
        }
        .signature-name { font-size: 13px; font-weight: bold; color: #374151; }
        .serial { font-size: 11px; color: #6B7280; margin-top: 22px; font-family: DejaVu Sans Mono, monospace; }
        .verify { font-size: 10px; color: #6B7280; font-family: DejaVu Sans Mono, monospace; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">{{ config('app.name') }}</div>
        <div class="label">{{ $certTitle }}</div>

        <div class="badge">&#10003; Formation completee</div>

        <p class="muted">Delivre a</p>
        <div class="name">{{ $certificate->user->name }}</div>

        <p class="muted">pour avoir complete avec succes le cours</p>
        <div class="course-title">{{ $certificate->course->title }}</div>

        @if($certMessageHtml)
            <div class="message">{!! $certMessageHtml !!}</div>
        @endif

        <hr class="divider">

        <table class="meta-table">
            <tr>
                <td class="meta-cell">
                    <div class="meta-label">Date d'emission</div>
                    <div class="meta-value">{{ $certificate->issued_at ? $certificate->issued_at->locale('fr_CA')->translatedFormat('j F Y') : '-' }}</div>
                </td>
                @if($certificate->hours_earned)
                    <td class="meta-cell">
                        <div class="meta-label">Duree</div>
                        <div class="meta-value">{{ $certificate->hours_earned }} heure{{ $certificate->hours_earned > 1 ? 's' : '' }}</div>
                    </td>
                @endif
                @if($certificate->final_score !== null)
                    <td class="meta-cell">
                        <div class="meta-label">Score final</div>
                        <div class="meta-value">{{ $certificate->final_score }} %</div>
                    </td>
                @endif
            </tr>
        </table>

        @if($certSignature)
            <div class="signature-line"></div>
            <div class="signature-name">{{ $certSignature }}</div>
        @endif

        <div class="serial">N&deg; {{ $certificate->serial }}</div>
        <div class="verify">Verifier : {{ url(route('academy.certificates.show', $certificate->public_url_slug)) }}</div>
    </div>
</body>
</html>
