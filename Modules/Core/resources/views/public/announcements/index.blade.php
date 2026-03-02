@extends('front-theme::layouts.gosass')

@section('title', 'Nouveautes')

@section('content')
<section class="cs_gray_bg_4 cs_py_8">
    <div class="container">
        <div class="cs_section_heading cs_style_1 text-center mb-5">
            <h2 class="cs_section_title">Nouveautes et changelog</h2>
            <p class="cs_section_subtitle">Suivez les dernieres evolutions de la plateforme.</p>
        </div>

        @forelse($announcements as $announcement)
            <div class="card mb-3 border-start border-4 @if($announcement->type === 'feature') border-success @elseif($announcement->type === 'fix') border-warning @elseif($announcement->type === 'improvement') border-primary @else border-info @endif">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge {{ $announcement->typeBadgeClass() }} me-2">{{ $announcement->typeLabel() }}</span>
                            @if($announcement->version)
                                <span class="badge bg-light text-dark">v{{ $announcement->version }}</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $announcement->published_at?->format('d/m/Y') ?? $announcement->created_at->format('d/m/Y') }}</small>
                    </div>
                    <h5 class="card-title">{{ $announcement->title }}</h5>
                    <div class="card-text">{!! $announcement->safeBody() !!}</div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <p class="text-muted">Aucune nouveaute pour le moment.</p>
            </div>
        @endforelse

        <div class="mt-4">{{ $announcements->links() }}</div>
    </div>
</section>
@endsection
