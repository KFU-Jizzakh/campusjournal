<journal_article publication_type="full_text">
    <titles>
        <title>{{ $article->title }}</title>
    </titles>
    @if ($authors->isNotEmpty())
        <contributors>
            @foreach ($authors as $index => $author)
                @php($parts = $authorNameParts($author))
                <person_name sequence="{{ $index === 0 ? 'first' : 'additional' }}" contributor_role="author">
                    @if ($parts['given_name'])
                        <given_name>{{ $parts['given_name'] }}</given_name>
                    @endif
                    <surname>{{ $parts['surname'] }}</surname>
                    @if ($author->organization)
                        <affiliations>
                            <institution>
                                <institution_name>{{ $author->organization }}</institution_name>
                            </institution>
                        </affiliations>
                    @endif
                    @if ($author->orcid)
                        <ORCID>{{ \App\Support\Orcid::url($author->orcid) }}</ORCID>
                    @endif
                </person_name>
            @endforeach
        </contributors>
    @endif
    @if ($article->abstract_en || $article->abstract_ru)
        <jats:abstract xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1">
            <jats:p>{{ $article->abstract_en ?: $article->abstract_ru }}</jats:p>
        </jats:abstract>
    @endif
    @if ($article->published_at)
        <publication_date media_type="online">
            <month>{{ $article->published_at->format('m') }}</month>
            <day>{{ $article->published_at->format('d') }}</day>
            <year>{{ $article->published_at->format('Y') }}</year>
        </publication_date>
    @endif
    @if ($article->first_page)
        <pages>
            <first_page>{{ $article->first_page }}</first_page>
            @if ($article->last_page)
                <last_page>{{ $article->last_page }}</last_page>
            @endif
        </pages>
    @elseif ($article->pages)
        <pages>
            <first_page>{{ $article->pages }}</first_page>
        </pages>
    @endif
    @if ($article->doi)
        <doi_data>
            <doi>{{ $article->doi }}</doi>
            <resource>{{ $resourceUrl }}</resource>
        </doi_data>
    @endif
    @if (! empty($funding))
        @foreach ($funding as $funder)
            <fr:program name="fundref" xmlns:fr="http://www.crossref.org/fundref.xsd">
                <fr:assertion name="funder_name">{{ $funder['funder_name'] }}</fr:assertion>
                @if (! empty($funder['funder_identifier']))
                    <fr:assertion name="funder_identifier">{{ $funder['funder_identifier'] }}</fr:assertion>
                @endif
                @if (! empty($funder['award_number']))
                    <fr:assertion name="award_number">{{ $funder['award_number'] }}</fr:assertion>
                @endif
            </fr:program>
        @endforeach
    @endif
</journal_article>
