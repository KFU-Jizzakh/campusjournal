<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
           xmlns:dc="http://purl.org/dc/elements/1.1/"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
    <dc:title>{{ $article->title }}</dc:title>
    @foreach ($article->authors as $author)
        <dc:creator>{{ $author->full_name }}</dc:creator>
    @endforeach
    @foreach (($article->keywords ?? []) as $keyword)
        <dc:subject>{{ $keyword }}</dc:subject>
    @endforeach
    @php($abstract = $article->abstract_en ?: $article->abstract_ru)
    @if ($abstract)
        <dc:description>{{ $abstract }}</dc:description>
    @endif
    <dc:publisher>{{ config('app.name') }}</dc:publisher>
    @if ($article->published_at)
        <dc:date>{{ $article->published_at->format('Y-m-d') }}</dc:date>
    @endif
    <dc:type>Text</dc:type>
    @if ($article->doi)
        <dc:identifier>https://doi.org/{{ $article->doi }}</dc:identifier>
    @endif
    <dc:identifier>{{ route('articles.show', $article) }}</dc:identifier>
    <dc:language>ru</dc:language>
    @if ($article->issue_id)
        <dc:relation>{{ route('issues.show', $article->issue_id) }}</dc:relation>
    @endif
    <dc:rights>{{ config('app.name') }} — all rights reserved</dc:rights>
</oai_dc:dc>
