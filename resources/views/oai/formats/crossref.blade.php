@php
    $article->loadMissing('authors');
    $authors = $article->authors;
    $resourceUrl = route('articles.show', $article);
@endphp
<crossref xmlns="http://www.crossref.org/schema/5.3.1"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1"
          xsi:schemaLocation="http://www.crossref.org/schema/5.3.1 https://data.crossref.org/schemas/crossref5.3.1.xsd">
    @include('crossref._journal_article', [
        'article' => $article,
        'authors' => $authors,
        'authorNameParts' => fn ($author) => $author->name_parts,
        'resourceUrl' => $resourceUrl,
    ])
</crossref>
