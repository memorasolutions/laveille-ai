{{-- MEMORA solutions <info@memora.ca> (https://memora.solutions) - feuille de cartes QR, document PRIVÉ. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 0.9cm 1cm; }
    body { font-family: DejaVu Sans, sans-serif; color: #1A1D23; margin: 0; }
    h1 { font-size: 15pt; margin: 0 0 1.5mm 0; }
    p.note { font-size: 8pt; color: #4A4F57; margin: 0 0 3mm 0; }
    table.grille { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grille td { width: 33.33%; height: 5.1cm; border: 1px dashed #8A8F98; text-align: center; vertical-align: middle; padding: 1mm; }
    table.grille td img { width: 3.3cm; height: 3.3cm; }
    .nom { font-size: 11pt; font-weight: bold; margin-top: 2mm; }
    .code { font-size: 8pt; color: #4A4F57; margin-top: 1mm; }
    .brandbar { margin: 0 0 2mm 0; padding: 0 0 1.5mm 0; border-bottom: 2pt solid #064E5A; }
    .brandbar img { height: 0.95cm; vertical-align: middle; }
    .brandbar .bn { font-size: 12pt; font-weight: bold; color: #064E5A; vertical-align: middle; margin-left: 3mm; }
    .ref { font-size: 7.5pt; color: #6B7280; margin: 2.5mm 0 0 0; }
    .saut { page-break-after: always; }
</style>
</head>
<body>
@php
    $faLogoFile = public_path('images/logo-eye-300.png');
    $faLogo = is_file($faLogoFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($faLogoFile)) : null;
    $faBrandName = config('file-attente.brand.name') ?: config('app.name');
    $faBrandUrl = preg_replace('#^https?://#', '', rtrim(config('file-attente.brand.url') ?: config('app.url'), '/'));
@endphp
@foreach ($pages as $i => $cards)
    <div @class(['saut' => ! $loop->last])>
        <div class="brandbar">
            @if ($faLogo)<img src="{{ $faLogo }}" alt="">@endif
            <span class="bn">{{ $faBrandName }}</span>
        </div>
        <h1>{{ $title }}</h1>
        <p class="note">{{ __('fileattente::ui.pdf.note', ['page' => $i + 1, 'total' => count($pages)]) }}</p>
        <table class="grille">
            @foreach (array_chunk($cards, 3) as $row)
                <tr>
                    @foreach ($row as $c)
                        <td>
                            <img src="{{ $c['qr'] }}" alt="{{ __('fileattente::ui.pdf.qr_alt') }}">
                            <div class="nom">{{ $c['name'] }}</div>
                            <div class="code">{{ $c['code'] }}</div>
                        </td>
                    @endforeach
                    @for ($k = count($row); $k < 3; $k++)<td></td>@endfor
                </tr>
            @endforeach
        </table>
        <p class="ref">{{ __('fileattente::ui.pdf.ref', ['brand' => $faBrandName, 'url' => $faBrandUrl]) }}</p>
    </div>
@endforeach
</body>
</html>
