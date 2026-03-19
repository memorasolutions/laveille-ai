<!-- start of breadcumb-section -->
<div class="wpo-breadcumb-area">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="wpo-breadcumb-wrap">
                    <h2>{{ $breadcrumbTitle ?? '' }}</h2>
                    <ul>
                        <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                        @isset($breadcrumbItems)
                            @foreach($breadcrumbItems as $item)
                                <li><span>{{ $item }}</span></li>
                            @endforeach
                        @else
                            <li><span>{{ $breadcrumbTitle ?? '' }}</span></li>
                        @endisset
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end of wpo-breadcumb-section-->
