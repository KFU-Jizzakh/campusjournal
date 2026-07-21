<front>
    <journal-meta>
        <journal-id journal-id-type="publisher">{{ config('jats.journal_abbrev') }}</journal-id>
        <journal-title-group>
            <journal-title>{{ config('app.name') }}</journal-title>
        </journal-title-group>
        @if ($printIssn)
            <issn pub-type="ppub">{{ $printIssn }}</issn>
        @endif
        @if ($electronicIssn)
            <issn pub-type="epub">{{ $electronicIssn }}</issn>
        @endif
        <publisher>
            <publisher-name>{{ config('jats.publisher_name') }}</publisher-name>
            @if (config('jats.publisher_loc'))
                <publisher-loc>{{ config('jats.publisher_loc') }}</publisher-loc>
            @endif
        </publisher>
    </journal-meta>
    <article-meta>
        @if ($article->doi)
            <article-id pub-id-type="doi">{{ $article->doi }}</article-id>
        @endif
        <article-id pub-id-type="publisher-id">{{ $article->id }}</article-id>
        @if ($article->category)
            <article-categories>
                <subj-group>
                    <subject>{{ $article->category->name }}</subject>
                </subj-group>
            </article-categories>
        @endif
        <title-group>
            <article-title>{{ $article->title }}</article-title>
        </title-group>
        @include('jats._contrib_group', [
            'authors' => $authors,
            'affiliations' => $affiliations,
            'authorNameParts' => $authorNameParts,
        ])
        @if ($article->published_at)
            <pub-date pub-type="epub">
                <day>{{ $article->published_at->format('d') }}</day>
                <month>{{ $article->published_at->format('m') }}</month>
                <year>{{ $article->published_at->format('Y') }}</year>
            </pub-date>
        @endif
        @if ($issue)
            <volume>{{ $issue->volume }}</volume>
            <issue>{{ $issue->number }}</issue>
        @endif
        @if ($article->first_page)
            <fpage>{{ $article->first_page }}</fpage>
            @if ($article->last_page)
                <lpage>{{ $article->last_page }}</lpage>
            @endif
        @endif
        <self-uri xlink:href="{{ $resourceUrl }}"/>
        @if ($article->abstract_ru)
            <abstract xml:lang="ru">
                <p>{{ $article->abstract_ru }}</p>
            </abstract>
        @endif
        @if ($article->abstract_en)
            <trans-abstract xml:lang="en">
                <p>{{ $article->abstract_en }}</p>
            </trans-abstract>
        @endif
        @if (! empty($article->keywords))
            <kwd-group xml:lang="ru">
                @foreach ($article->keywords as $keyword)
                    <kwd>{{ $keyword }}</kwd>
                @endforeach
            </kwd-group>
        @endif
    </article-meta>
</front>
