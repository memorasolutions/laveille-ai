{{-- V5-c - Notification : travail corrigé (devoir ou essai). --}}
@extends('academy::emails.layout')

@section('content')
<p style="margin:0 0 12px;">Votre travail <strong>{{ $itemTitle }}</strong> du cours <strong>{{ $course->title }}</strong> vient d'être corrigé.</p>
@if(! is_null($scorePercent))
<p style="margin:0 0 12px;font-size:18px;color:#0f172a;">Note obtenue : <strong>{{ $scorePercent }} %</strong></p>
@endif
<p style="margin:0;color:#475569;">Consultez votre cours pour voir le détail de la correction et la rétroaction.</p>
@endsection
