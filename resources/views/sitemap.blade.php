<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') }}</loc>
        @if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        @if (! empty($url['changefreq']))
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        @endif
        @if (! empty($url['priority']))
        <priority>{{ $url['priority'] }}</priority>
        @endif
    </url>
@endforeach
</urlset>
