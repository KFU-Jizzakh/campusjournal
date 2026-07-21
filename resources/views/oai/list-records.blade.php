@extends('oai.envelope')
@section('content')
    <ListRecords>
        @foreach ($records as $record)
            <record>
                @include('oai._header', [
                    'article' => $record['article'],
                    'identifier' => $record['identifier'],
                    'datestamp' => $record['datestamp'],
                    'setSpecs' => $record['setSpecs'],
                ])
                @if (! $record['article']->trashed())
                    <metadata>
                        @include('oai.formats.'.$metadataPrefix, ['article' => $record['article'], 'electronicIssn' => $electronicIssn])
                    </metadata>
                @endif
            </record>
        @endforeach
        @include('oai._resumption-token', [
            'resumptionToken' => $resumptionToken,
            'emitEmptyToken' => $emitEmptyToken ?? false,
        ])
    </ListRecords>
@endsection
