<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
@page { margin: 1.5cm 1.2cm; }
* { box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; color: #1A1D23; font-size: 11pt; line-height: 1.4; }
.cw-header { display: table; width: 100%; border-bottom: 2px solid #053d4a; padding-bottom: 8px; margin-bottom: 16px; }
.cw-header .brand { display: table-cell; width: 60%; vertical-align: middle; }
.cw-header .brand-name { font-size: 16pt; font-weight: bold; color: #053d4a; letter-spacing: -0.5px; }
.cw-header .brand-tag { font-size: 8pt; color: #475569; }
.cw-header .meta { display: table-cell; text-align: right; vertical-align: middle; font-size: 9pt; color: #475569; }
.solution-banner { background: #fee2e2; border: 1px solid #b91c1c; color: #7f1d1d; padding: 6px 10px; border-radius: 4px; font-size: 9pt; font-weight: bold; text-align: center; margin-bottom: 12px; }
h1 { font-size: 14pt; color: #053d4a; margin: 0 0 14px 0; text-align: center; font-weight: bold; }
/* S80 #55 — Hybride : bordure externe grille + cases actives bordurées + cases inactives sans bordure (sauf BLACK statu quo). POTENTIAL-EXTRACT S81 */
.cw-grid-wrap { text-align: center; margin: 0 auto 16px auto; }
table.cw-grid { table-layout: fixed; border-collapse: collapse; margin: 0 auto; }
table.cw-grid td { width: {{ $cellSize ?? 28 }}px; height: {{ $cellSize ?? 28 }}px; padding: 0; text-align: center; vertical-align: middle; position: relative; }
table.cw-grid td.cell-active { background: #ffffff; font-size: {{ max(7, intval(($cellSize ?? 28) * 0.42)) }}pt; font-weight: bold; color: #b91c1c; border: 0.75pt solid #1A1D23; }
@php
    $bg = ['black' => '#1A1D23', 'gray' => '#9ca3af', 'border' => '#ffffff'][$inactiveStyle ?? 'black'] ?? '#1A1D23';
    $inactiveBorder = ($inactiveStyle ?? 'black') === 'black' ? '0.75pt solid #1A1D23' : '0';
@endphp
table.cw-grid td.cell-inactive { background: {{ $bg }}; border: {{ $inactiveBorder }}; }
table.cw-grid td .num { position: absolute; top: 1px; left: 2px; font-size: 6pt; font-weight: bold; color: #053d4a; line-height: 1; }
table.cw-grid td .letter { display: inline-block; padding-top: 3px; }
.cw-clues { width: 100%; margin-top: 14px; }
.cw-clues-col { display: inline-block; width: 49%; vertical-align: top; padding: 0 4px; }
.cw-clues h2 { font-size: 11pt; color: #053d4a; margin: 0 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #053d4a; }
.cw-clues ul { list-style: none; padding: 0; margin: 0; }
.cw-clues li { font-size: 9.5pt; margin-bottom: 5px; line-height: 1.35; }
.cw-clues li b { color: #053d4a; min-width: 16px; display: inline-block; }
.cw-clues li i { color: #b91c1c; font-style: normal; font-weight: bold; margin-left: 4px; }
.cw-footer { position: fixed; bottom: 0; left: 0; right: 0; padding-top: 6px; border-top: 1px solid #cbd5e1; font-size: 8pt; color: #64748b; display: table; width: 100%; }
.cw-footer .left { display: table-cell; width: 70%; }
.cw-footer .right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="cw-header">
<div class="brand">
<div class="brand-name">laveille.ai</div>
<div class="brand-tag">Générateur de mots croisés — IA & techno Québec</div>
</div>
<div class="meta">{{ $generatedAt }}</div>
</div>

<div class="solution-banner">Version corrigée — réponses révélées</div>

<h1>{{ $title }}</h1>

@if($grid && !empty($grid['cells']))
<div class="cw-grid-wrap">
<table class="cw-grid">
<tbody>
@foreach($grid['cells'] as $row)
<tr>
@foreach($row as $cell)
@if($cell !== null)
<td class="cell-active">@if(!empty($cell['number']))<span class="num">{{ $cell['number'] }}</span>@endif<span class="letter">{{ $cell['letter'] ?? '' }}</span></td>
@else
<td class="cell-inactive"></td>
@endif
@endforeach
</tr>
@endforeach
</table>
</div>
@endif

{{-- S80 #62 : table layout au lieu de display:inline-block (mal supporté DomPDF, empile les colonnes) --}}
<table class="cw-clues" style="width:100%;margin-top:14px;border-collapse:collapse;">
<tr>
<td style="width:50%;vertical-align:top;padding:0 8px 0 0;">
<h2 style="font-size:11pt;color:#053d4a;margin:0 0 6px 0;padding-bottom:3px;border-bottom:1px solid #053d4a;">Horizontaux &rarr;</h2>
<ul style="list-style:none;padding:0;margin:0;">
@foreach(collect($words)->where('orientation','horizontal')->sortBy('number') as $w)
<li style="font-size:9.5pt;margin-bottom:5px;line-height:1.35;"><b style="color:#053d4a;">{{ $w['number'] }}.</b> {{ $w['clue'] }} <i style="color:#b91c1c;font-style:normal;font-weight:bold;margin-left:4px;">({{ $w['answer'] }})</i></li>
@endforeach
</ul>
</td>
<td style="width:50%;vertical-align:top;padding:0 0 0 8px;">
<h2 style="font-size:11pt;color:#053d4a;margin:0 0 6px 0;padding-bottom:3px;border-bottom:1px solid #053d4a;">Verticaux &darr;</h2>
<ul style="list-style:none;padding:0;margin:0;">
@foreach(collect($words)->where('orientation','vertical')->sortBy('number') as $w)
<li style="font-size:9.5pt;margin-bottom:5px;line-height:1.35;"><b style="color:#053d4a;">{{ $w['number'] }}.</b> {{ $w['clue'] }} <i style="color:#b91c1c;font-style:normal;font-weight:bold;margin-left:4px;">({{ $w['answer'] }})</i></li>
@endforeach
</ul>
</td>
</tr>
</table>

<div class="cw-footer">
<div class="left">
@if($playUrl)Jouer en ligne : {{ $playUrl }} &middot; @endif
Généré par laveille.ai/outils/mots-croises
</div>
<div class="right">{{ $generatedAt }}</div>
</div>

</body>
</html>
