{{-- V5-c - Notification : rappel d'échéance à venir. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Petit rappel : une échéance approche dans le cours <strong>{{ $event['course_title'] ?? '' }}</strong>.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;border:1px solid #e2e8f0;border-radius:8px;">
<tr><td style="padding:14px 16px;">
<p style="margin:0 0 6px;font-size:16px;color:#0f172a;font-weight:bold;">{{ $event['title'] ?? 'À venir' }}</p>
@if(! empty($event['starts_at']) && is_object($event['starts_at']))
<p style="margin:0;color:#475569;font-size:14px;">Échéance : {{ $event['starts_at']->translatedFormat('l d F Y à H\hi') }}</p>
@endif
</td></tr>
</table>
<p style="margin:0;color:#475569;">Pensez à compléter votre travail à temps.</p>
@endsection
