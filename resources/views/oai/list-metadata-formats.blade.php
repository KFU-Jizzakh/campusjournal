@extends('oai.envelope')
@section('content')
    <ListMetadataFormats>
        @foreach ($formats as $format)
            <metadataFormat>
                <metadataPrefix>{{ $format['prefix'] }}</metadataPrefix>
                <schema>{{ $format['schema'] }}</schema>
                <metadataNamespace>{{ $format['namespace'] }}</metadataNamespace>
            </metadataFormat>
        @endforeach
    </ListMetadataFormats>
@endsection
