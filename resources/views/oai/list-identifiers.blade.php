@extends('oai.envelope')
@section('content')
    <ListIdentifiers>
        @foreach ($records as $record)
            @include('oai._header', [
                'article' => $record['article'],
                'identifier' => $record['identifier'],
                'datestamp' => $record['datestamp'],
                'setSpecs' => $record['setSpecs'],
            ])
        @endforeach
        @include('oai._resumption-token', [
            'resumptionToken' => $resumptionToken,
            'emitEmptyToken' => $emitEmptyToken ?? false,
        ])
    </ListIdentifiers>
@endsection
