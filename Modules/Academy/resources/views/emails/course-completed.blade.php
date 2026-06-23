{{-- V5-c - Notification : cours complété, certificat émis. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Félicitations ! Vous avez complété le cours <strong>{{ $course->title }}</strong>.</p>
<p style="margin:0 0 12px;color:#475569;">Votre certificat de réussite est prêt. Vous pouvez le consulter et le partager à tout moment.</p>
@if(! empty($certificate->serial))
<p style="margin:0;font-size:13px;color:#64748b;">Numéro de certificat : {{ $certificate->serial }}</p>
@endif
@endsection
