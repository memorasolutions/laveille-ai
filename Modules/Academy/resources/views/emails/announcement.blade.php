{{-- V5-c - Notification : nouvelle annonce de cours. --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Une nouvelle annonce a été publiée dans le cours <strong>{{ $course->title }}</strong> :</p>
<h2 style="margin:0 0 12px;font-size:19px;color:#0f172a;">{{ $announcement->title }}</h2>
<div style="color:#475569;font-size:15px;line-height:1.7;">{!! $bodyHtml !!}</div>
@endsection
