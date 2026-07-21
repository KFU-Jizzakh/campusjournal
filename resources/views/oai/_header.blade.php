<header @if ($article->trashed()) status="deleted" @endif>
    <identifier>{{ $identifier }}</identifier>
    <datestamp>{{ $datestamp }}</datestamp>
    @foreach ($setSpecs as $spec)
        <setSpec>{{ $spec }}</setSpec>
    @endforeach
</header>
