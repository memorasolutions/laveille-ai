<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="widget widget-newsletter mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    @if($widget->content)
        <p>{{ $widget->content }}</p>
    @endif
    @if(Route::has('newsletter.subscribe'))
        <form action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <div class="input-group mb-2">
                <input type="email" name="email" class="form-control" placeholder="{{ __('Votre courriel') }}" required autocomplete="email">
                <button type="submit" class="btn btn-primary">{{ __('S\'inscrire') }}</button>
            </div>
            <label style="display: flex; align-items: flex-start; gap: 6px; font-size: 11px; color: #6b7280; cursor: pointer; line-height: 1.3;">
                <input type="checkbox" name="consent" required style="margin-top: 2px; flex-shrink: 0;">
                {!! __('J\'accepte la <a href=":url" style="color: var(--c-primary);">politique de confidentialite</a>.', ['url' => Route::has('legal.privacy') ? route('legal.privacy') : '/privacy-policy']) !!}
            </label>
        </form>
    @endif
</div>
