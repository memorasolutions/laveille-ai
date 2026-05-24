<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes nouveaux articles</title>
</head>
<body style="background-color:#F8FAFB; margin:0; padding:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <div style="max-width:600px; margin:40px auto; background:#ffffff; padding:40px; box-shadow:0 4px 12px rgba(6,78,90,0.08); border-radius:12px;">
        <h1 style="color:#064E5A; font-size:24px; margin:0 0 8px;">📚 Tes nouveaux articles cette semaine</h1>
        <p style="color:#3F4554; font-size:15px; margin:0 0 24px;">
            {{ $author->display_name ?? $author->slug }} a publié {{ $posts->count() }} nouvel(s) article(s) depuis ta dernière visite.
        </p>

        @foreach($posts->take(5) as $post)
            @php
                $postUrl = url('/@'.$author->slug.'/'.$post->slug);
            @endphp
            <div style="margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid #eef2f4;">
                <h2 style="font-size:18px; margin:0 0 8px; line-height:1.3;">
                    <a href="{{ $postUrl }}" style="color:#064E5A; text-decoration:none;">{{ $post->title }}</a>
                </h2>
                @if($post->excerpt)
                    <p style="margin:0 0 8px; color:#3F4554; font-size:14px; line-height:1.5;">
                        {{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}
                    </p>
                @endif
                <p style="margin:0; font-size:13px; color:#5A6270;">
                    {{ $post->reading_time ?? 1 }} min à lire ·
                    <a href="{{ $postUrl }}" style="color:#9A2A06; font-weight:600; text-decoration:none;">Lire l'article →</a>
                </p>
            </div>
        @endforeach

        <div style="text-align:center; margin:24px 0;">
            <a href="{{ url('/@'.$author->slug) }}" style="display:inline-block; min-height:44px; padding:12px 24px; background:#064E5A; color:white; border-radius:999px; text-decoration:none; font-weight:600; font-size:14px;">
                Voir tous les articles
            </a>
        </div>

        <footer style="margin-top:32px; padding-top:24px; border-top:1px solid #eef2f4; font-size:12px; color:#5A6270; line-height:1.5;">
            <p style="margin:0 0 8px;">
                Tu reçois ce courriel car tu t'es abonné à la newsletter de {{ $author->display_name ?? $author->slug }} sur laveille.ai.
            </p>
            <p style="margin:0;">
                <a href="{{ $unsubscribeUrl }}" style="color:#9A2A06; text-decoration:underline;">Se désabonner</a>
                · MEMORA solutions, Québec, Canada (Loi 25)
            </p>
        </footer>
    </div>
</body>
</html>
