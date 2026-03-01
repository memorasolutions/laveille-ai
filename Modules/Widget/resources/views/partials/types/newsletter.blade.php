<div class="widget widget-newsletter mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    @if($widget->content)
        <p>{{ $widget->content }}</p>
    @endif
    @if(Route::has('newsletter.subscribe'))
        <form action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <div class="input-group">
                <input type="email" name="email" class="form-control" placeholder="Votre email" required>
                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </div>
        </form>
    @endif
</div>
