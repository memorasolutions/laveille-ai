{{-- MEMORA solutions - fil d'Ariane du site (absent hors thème : le repli autonome n'en affiche pas). --}}
@if (view()->exists('fronttheme::partials.breadcrumb'))
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $crumb, 'breadcrumbItems' => [__('fileattente::ui.breadcrumb_tools'), $crumb]])
@endif
