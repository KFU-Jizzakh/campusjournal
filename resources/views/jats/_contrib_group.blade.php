<contrib-group>
    @foreach ($authors as $author)
        @php
            $parts = $authorNameParts($author);
            $affIds = [];
            foreach ($affiliations as $aff) {
                if (in_array($author->id, $aff['author_ids'], true)) {
                    $affIds[] = $aff['id'];
                }
            }
        @endphp
        <contrib contrib-type="author">
            @if ($author->orcid)
                <contrib-id contrib-id-type="orcid">{{ \App\Support\Orcid::url($author->orcid) }}</contrib-id>
            @endif
            <name>
                <surname>{{ $parts['surname'] }}</surname>
                @if ($parts['given_name'])
                    <given-names>{{ $parts['given_name'] }}</given-names>
                @endif
            </name>
            @foreach ($affIds as $id)
                <xref ref-type="aff" rid="{{ $id }}"/>
            @endforeach
            @if ($author->email)
                <email>{{ $author->email }}</email>
            @endif
        </contrib>
    @endforeach
</contrib-group>
@foreach ($affiliations as $aff)
    <aff id="{{ $aff['id'] }}"><institution>{{ $aff['name'] }}</institution></aff>
@endforeach
