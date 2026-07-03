{{-- Messagerie directe (DM) - Notification : nouveau message reçu. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Vous avez reçu un nouveau message de <strong>{{ $sender->name }}</strong> :</p>
<div style="border-left:3px solid #064E5A;padding:8px 14px;margin:12px 0;background-color:#f0fdfa;color:#475569;font-size:15px;line-height:1.7;">{{ $excerpt }}</div>
@endsection
