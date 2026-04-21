<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f4f5; padding: 40px 20px; margin: 0;">
    <div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #0B7285, #1a365d); padding: 28px 32px; text-align: center;">
            <h1 style="color: #fff; margin: 0; font-size: 20px;">{{ config('app.name') }}</h1>
        </div>

        {{-- Content --}}
        <div style="padding: 32px;">
            @if($status === 'approved')
                <div style="text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 48px;">🎉</span>
                </div>
                <h2 style="color: #1a1d23; margin: 0 0 16px; font-size: 22px; text-align: center;">{{ __('Votre article a été publié !') }}</h2>
                <p style="color: #4B5563; line-height: 1.6; font-size: 15px;">
                    {{ __('Bonne nouvelle ! Votre article') }} <strong>« {{ $article->title }} »</strong> {{ __('a été approuvé et publié sur') }} {{ config('app.name') }}.
                </p>
                <p style="color: #4B5563; line-height: 1.6; font-size: 15px;">
                    {{ __('Vous avez gagné') }} <strong style="color: #0B7285;">+25 {{ __('points de réputation') }}</strong> {{ __('pour cette contribution !') }}
                </p>
                @if($article->published_at)
                <div style="text-align: center; margin: 24px 0;">
                    <a href="{{ route('blog.show', $article->slug) }}" style="display: inline-block; background: #0B7285; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px;">{{ __('Voir mon article') }}</a>
                </div>
                @endif
            @else
                <div style="text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 48px;">📝</span>
                </div>
                <h2 style="color: #1a1d23; margin: 0 0 16px; font-size: 22px; text-align: center;">{{ __('Mise à jour sur votre soumission') }}</h2>
                <p style="color: #4B5563; line-height: 1.6; font-size: 15px;">
                    {{ __('Votre article') }} <strong>« {{ $article->title }} »</strong> {{ __('n\'a pas été retenu pour publication cette fois-ci.') }}
                </p>
                <p style="color: #4B5563; line-height: 1.6; font-size: 15px;">
                    {{ __('N\'hésitez pas à soumettre un nouveau sujet ! Chaque contribution enrichit notre communauté.') }}
                </p>
                <div style="text-align: center; margin: 24px 0;">
                    <a href="{{ route('blog.submissions.create') }}" style="display: inline-block; background: #0B7285; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px;">{{ __('Proposer un autre article') }}</a>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div style="background: #F9FAFB; padding: 20px 32px; text-align: center; border-top: 1px solid #E5E7EB;">
            <p style="color: #6B7280; font-size: 12px; margin: 0;">
                {{ __('Cet email a été envoyé automatiquement par') }} {{ config('app.name') }} (laveille.ai)
            </p>
        </div>
    </div>
</body>
</html>
