{{-- V5-c - Notification : nouvelle réponse à un sujet de forum. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Une nouvelle réponse a été publiée dans votre sujet <strong>{{ $topic->title }}</strong> (cours {{ $course->title }}) :</p>
<div style="border-left:3px solid #0d9488;padding:8px 14px;margin:12px 0;background-color:#f0fdfa;color:#475569;font-size:15px;line-height:1.7;">{!! $bodyHtml !!}</div>
@endsection
