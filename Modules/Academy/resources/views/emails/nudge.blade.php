{{-- Nudge comportemental : relance bienveillante (jalon, révision ou reprise). --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;color:#0f172a;font-size:16px;font-weight:bold;">{{ $heading }}</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;border:1px solid #e2e8f0;border-radius:8px;">
<tr><td style="padding:14px 16px;">
<p style="margin:0;color:#475569;font-size:14px;">{{ $message }}</p>
</td></tr>
</table>
<p style="margin:0;color:#94a3b8;font-size:13px;">Vous recevez ce message d'encouragement parce que vous suivez « {{ $course->title }} ». Vous pouvez ajuster vos préférences à tout moment.</p>
@endsection
