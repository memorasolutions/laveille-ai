<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('errors.layout')
@section('mascot')
    @include('errors.octopus._render', ['emotion' => 'confused'])
@endsection
@section('emoji', '🛑')
@section('code', '405')
@section('title', __('Méthode non autorisée'))
@section('message', __('Cette action n\'est pas autorisée ici. Le bouton ou le lien que vous avez emprunté ne fait pas ce qu\'il devrait.'))
