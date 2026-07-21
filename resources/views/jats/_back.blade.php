@php
    $refs = $article->references;
@endphp
@if ($refs->isNotEmpty())
<back>
    <ref-list>
@foreach ($refs as $ref)
        <ref id="ref{{ $ref->order }}"><mixed-citation>{{ $ref->raw }}@if ($ref->doi) <pub-id pub-id-type="doi">{{ $ref->doi }}</pub-id>@endif</mixed-citation></ref>
@endforeach
    </ref-list>
</back>
@else
<back/>
@endif
