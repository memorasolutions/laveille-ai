{{-- MEMORA solutions <info@memora.ca> (https://memora.solutions) - feuille de cartes QR, document PRIVÉ. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 1.2cm 1cm; }
    body { font-family: DejaVu Sans, sans-serif; color: #1A1D23; margin: 0; }
    h1 { font-size: 16pt; margin: 0 0 2mm 0; }
    p.note { font-size: 8pt; color: #4A4F57; margin: 0 0 4mm 0; }
    table.grille { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.grille td { width: 33.33%; height: 5.5cm; border: 1px dashed #8A8F98; text-align: center; vertical-align: middle; padding: 1mm; }
    table.grille td img { width: 3.4cm; height: 3.4cm; }
    .nom { font-size: 11pt; font-weight: bold; margin-top: 2mm; }
    .code { font-size: 8pt; color: #4A4F57; margin-top: 1mm; }
    .saut { page-break-after: always; }
</style>
</head>
<body>
@foreach ($pages as $i => $cards)
    <div @class(['saut' => ! $loop->last])>
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
    </div>
@endforeach
</body>
</html>
