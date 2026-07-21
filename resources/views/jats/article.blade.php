@if (! $inline){!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
@endif
<article xmlns="https://jats.nlm.nih.gov/publishing/1.3/" xmlns:xlink="http://www.w3.org/1999/xlink" dtd-version="1.3" article-type="research-article" xml:lang="ru">
    @include('jats._front', [
        'article' => $article,
        'issue' => $issue,
        'authors' => $authors,
        'affiliations' => $affiliations,
        'resourceUrl' => $resourceUrl,
        'authorNameParts' => $authorNameParts,
        'printIssn' => $printIssn,
        'electronicIssn' => $electronicIssn,
    ])
    {!! $bodyXml !!}
@include('jats._back', ['article' => $article])
</article>
