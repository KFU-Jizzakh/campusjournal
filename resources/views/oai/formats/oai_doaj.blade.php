<record xmlns="http://www.doaj.org/schemas/"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.doaj.org/schemas/ https://doaj.org/static/doaj/doajArticles.xsd">
    <language>ru</language>
    <publisher>{{ config('app.name') }}</publisher>
    <journalTitle>{{ config('app.name') }}</journalTitle>
    @if ($electronicIssn)
        <issn>{{ $electronicIssn }}</issn>
    @endif
    @if ($article->published_at)
        <publicationDate>{{ $article->published_at->format('Y-m-d') }}</publicationDate>
    @endif
    @if ($article->issue)
        <volume>{{ $article->issue->volume }}</volume>
        <issue>{{ $article->issue->number }}</issue>
    @endif
    @if ($article->first_page)
        <startPage>{{ $article->first_page }}</startPage>
        @if ($article->last_page)
            <endPage>{{ $article->last_page }}</endPage>
        @endif
    @endif
    @if ($article->doi)
        <doi>{{ $article->doi }}</doi>
    @endif
    <title>{{ $article->title }}</title>
    @if ($article->authors->isNotEmpty())
        <authors>
            @foreach ($article->authors as $author)
                <author>
                    <name>{{ $author->full_name }}</name>
                    @if ($author->organization)
                        <affiliationId>1</affiliationId>
                    @endif
                </author>
            @endforeach
        </authors>
        <affiliationsList>
            @foreach ($article->authors as $author)
                @if ($author->organization)
                    <affiliationName affiliationId="1">{{ $author->organization }}</affiliationName>
                @break
                @endif
            @endforeach
        </affiliationsList>
    @endif
    @php($abstract = $article->abstract_en ?: $article->abstract_ru)
    @if ($abstract)
        <abstract>{{ $abstract }}</abstract>
    @endif
    <fullTextUrl format="html">{{ route('articles.show', $article) }}</fullTextUrl>
    @if (! empty($article->keywords))
        <keywords>
            @foreach ($article->keywords as $keyword)
                <keyword>{{ $keyword }}</keyword>
            @endforeach
        </keywords>
    @endif
    @if (! empty($article->funding))
        @foreach ($article->funding as $funder)
            <funding>
                <funderName>{{ $funder['funder_name'] }}</funderName>
                @if (! empty($funder['funder_identifier']))
                    <funderIdentifier>{{ $funder['funder_identifier'] }}</funderIdentifier>
                @endif
                @if (! empty($funder['award_number']))
                    <awardNumber>{{ $funder['award_number'] }}</awardNumber>
                @endif
            </funding>
        @endforeach
    @endif
</record>
