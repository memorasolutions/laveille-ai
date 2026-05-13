<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('errors.layout')
@section('mascot')
    @include('errors.octopus._render', ['emotion' => 'sleeping'])
@endsection
@section('emoji', '⏳')
@section('code', '429')
@section('title', __('Trop de requêtes'))
@section('message', __('Doucement! Vous avancez plus vite que notre serveur. Patientez quelques secondes avant de réessayer.'))
