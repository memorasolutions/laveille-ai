{{-- SRS - Relance de révision espacée : cartes dues aujourd'hui. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Petit rappel de révision : c'est le moment idéal pour ancrer ce que vous avez appris.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;border:1px solid #e2e8f0;border-radius:8px;">
<tr><td style="padding:14px 16px;">
<p style="margin:0 0 6px;font-size:16px;color:#0f172a;font-weight:bold;">
@if($dueCount > 1)
{{ $dueCount }} cartes vous attendent
@else
Une carte vous attend
@endif
</p>
<p style="margin:0;color:#475569;font-size:14px;">Une courte session de révision (moins de deux minutes) suffit à renforcer votre mémoire durablement.</p>
</td></tr>
</table>
<p style="margin:0;color:#475569;">La répétition espacée programme chaque carte au meilleur moment pour éviter l'oubli.</p>
@endsection
