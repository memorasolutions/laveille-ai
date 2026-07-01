{{-- Tuteur IA — rappel calme avant expiration de la fenêtre d'accès (J-7/J-1). --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">
@if($daysLeft <= 1)
Votre accès au tuteur IA du cours « {{ $course->title }} » se termine demain.
@else
Votre accès au tuteur IA du cours « {{ $course->title }} » se termine dans {{ $daysLeft }} jours.
@endif
</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;border:1px solid #e2e8f0;border-radius:8px;">
<tr><td style="padding:14px 16px;">
<p style="margin:0;color:#475569;font-size:14px;">
Bonne nouvelle : <strong>seul le tuteur IA</strong> sera désactivé. Vous gardez un accès complet à tout le reste du cours (leçons, exercices, certificat).
</p>
</td></tr>
</table>
<p style="margin:0;color:#475569;">Profitez-en pour poser vos dernières questions au tuteur avant l'échéance.</p>
@endsection
