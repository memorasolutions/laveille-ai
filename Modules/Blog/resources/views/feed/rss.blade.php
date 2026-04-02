<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ config('app.name') }}</title>
    <link>{{ url('/blog') }}</link>
    <description>Les derniers articles</description>
    <language>fr-CA</language>
    <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
    @foreach($articles as $article)
    <item>
      <title><![CDATA[{{ $article->title }}]]></title>
      <link>{{ route('blog.show', $article->slug) }}</link>
      <description><![CDATA[{{ $article->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($article->content), 200) }}]]></description>
      <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
      <guid>{{ route('blog.show', $article->slug) }}</guid>
    </item>
    @endforeach
  </channel>
</rss>
