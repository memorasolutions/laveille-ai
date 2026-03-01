<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::themes.gosass.layouts.app')

@section('title')
    @yield('title', 'Blog') - {{ config('app.name') }}
@endsection

@section('meta')
    @yield('meta')
@endsection

@section('content')
    <div class="cs_height_58 cs_height_lg_40"></div>
    <div class="container">
        @yield('page_header')
    </div>
    @yield('blog_content')
@endsection
