@extends('oai.envelope')
@section('content')
    <GetRecord>
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
    </GetRecord>
@endsection
