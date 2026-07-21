@extends('oai.envelope')
@section('content')
    <ListSets>
        @foreach ($sets as $set)
            <set>
                <setSpec>{{ $set['spec'] }}</setSpec>
                <setName>{{ $set['name'] }}</setName>
            </set>
        @endforeach
    </ListSets>
@endsection
