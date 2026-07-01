{{-- Séance en direct - rappel d'une séance imminente rattachée à un cours. --}}
@extends('academy::emails.layout')

@section('content')
@php
    $qc = $session->starts_at->copy()->setTimezone('America/Toronto');
    $utc = $session->starts_at->copy()->utc();
@endphp
<p style="margin:0 0 12px;">Petit rappel : une séance en direct approche dans le cours <strong>{{ $course?->title ?? '' }}</strong>.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;border:1px solid #e2e8f0;border-radius:8px;">
<tr><td style="padding:14px 16px;">
<p style="margin:0 0 6px;font-size:16px;color:#0f172a;font-weight:bold;">{{ $session->title }}</p>
<p style="margin:0 0 4px;color:#475569;font-size:14px;">
{{-- Heure du Québec d'abord, UTC entre parenthèses (norme Memora). --}}
{{ $qc->translatedFormat('l d F Y \à H\hi') }} (heure du Québec) · {{ $utc->format('H:i') }} UTC
</p>
<p style="margin:0;color:#475569;font-size:14px;">Plateforme : {{ $session->providerLabel() }}</p>
</td></tr>
</table>
<p style="margin:0;color:#475569;">Cliquez le bouton ci-dessous à l'heure prévue pour rejoindre la séance.</p>
@endsection
